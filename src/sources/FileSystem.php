<?php

namespace Jodit\sources;

use abeautifulsite\SimpleImage;

use Exception;
use Jodit\components\Config;
use Jodit\components\File;
use Jodit\components\Image;
use Jodit\Consts;
use Jodit\Helper;
use Jodit\interfaces\IFile;
use Jodit\interfaces\ISource;

/**
 * Class FileSystem
 * @package Jodit\sources
 */
class FileSystem extends ISource {
	/**
	 * @param string $path
	 * @param string $content
	 * @return IFile
	 * @throws Exception
	 */
	public function makeFile($path, $content = null) {
		if ($content !== null) {
			file_put_contents($path, $content);
		}

		return File::create($path);
	}

	/**
	 * @param string $path
	 */
	public function makeFolder($path) {
		mkdir($path, $this->defaultPermission);
	}

	/**
	 * @param IFile $file
	 * @return mixed
	 */
	public function makeThumb(IFile $file) {
		$path = $file->getFolder();

		if (!is_dir($path . $this->thumbFolderName)) {
			$this->makeFolder($path . $this->thumbFolderName);
		}

		$thumbName =
			$path . $this->thumbFolderName . Consts::DS . $file->getName();

		if (!$file->isImage()) {
			$thumbName =
				$path .
				$this->thumbFolderName .
				Consts::DS .
				$file->getName() .
				'.svg';
		}

		if (!file_exists($thumbName)) {
			if ($file->isImage()) {
				try {
					$img = new SimpleImage($file->getPath());
					$img->best_fit(150, 150)->save($thumbName, $this->quality);
				} catch (Exception $e) {
					return $file;
				}
			} else {
				Image::generateIcon($file, $thumbName, $this);
			}
		}

		return $this->makeFile($thumbName);
	}

	/**
	 * @return mixed
	 */
	public function items() {
		/**
		 * Read folder and retrun filelist
		 *
		 * @param Config $source
		 *
		 * @return object
		 * @throws Exception
		 */
		$path = $this->getPath();

		$sourceData = (object) [
			'baseurl' => $this->baseurl,
			'path' => str_replace(
				realpath($this->getRoot()) . Consts::DS,
				'',
				$path
			),
			'files' => [],
		];

		try {
			$this->access->checkPermission(
				$this->getUserRole(),
				$this->action,
				$path
			);
		} catch (Exception $e) {
			return $sourceData;
		}

		$dir = opendir($path);

		$config = $this;

		while ($file = readdir($dir)) {
			if ($file != '.' && $file != '..' && is_file($path . $file)) {
				$file = $this->makeFile($path . $file);

				if ($file->isGoodFile($this)) {
					$item = ['file' => $file->getPathByRoot($this)];

					if ($config->createThumb || !$file->isImage()) {
						$item['thumb'] = $this->makeThumb($file)->getPathByRoot(
							$this
						);
					}

					$item['changed'] = date(
						$config->datetimeFormat,
						$file->getTime()
					);
					$item['size'] = Helper::humanFileSize($file->getSize());
					$item['isImage'] = $file->isImage();

					$sourceData->files[] = $item;
				}
			}
		}

		return $sourceData;
	}

	/**
	 * @return mixed
	 */
	public function folders() {
		$path = $this->getPath();

		$sourceData = (object) [
			'name' => $this->sourceName,
			'baseurl' => $this->baseurl,
			'path' => str_replace(
				realpath($this->getRoot()) . Consts::DS,
				'',
				$path
			),
			'folders' => [],
		];

		$sourceData->folders[] = $path === $this->getRoot() ? '.' : '..';

		$dir = opendir($path);
		while ($file = readdir($dir)) {
			if (is_dir($path . $file) and !$this->isExcluded($file)) {
				$sourceData->folders[] = $file;
			}
		}

		return $sourceData;
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	public function isExcluded($file) {
		return $file === '.' ||
			$file === '..' ||
			($this->createThumb && $file === $this->thumbFolderName) ||
			in_array($file, $this->excludeDirectoryNames);
	}

	/**
	 * @param string $path
	 * @return mixed
	 */
	public function tree($path) {
		return $this->getTree($path);
	}

	/**
	 * @param string $path
	 * @return array
	 */
	private function getTree($path) {
		$dir = opendir($path);
		$tree = [];

		while ($file = readdir($dir)) {
			if (is_dir($path . $file) and !$this->isExcluded($file)) {
				$tree[] = [
					'name' => $file,
					'path' => $path . $file,
					'sourceName' => $this->sourceName,
					'children' => $this->getTree($path . $file),
				];
			}
		}

		return $tree;
	}

	/**
	 * @param string $fromName
	 * @param string $newName
	 */
	public function renamePath($fromName, $newName) {
		$fromName = Helper::makeSafe($fromName);
		$fromPath = $this->getPath() . $fromName;

		$action = is_file($fromPath) ? 'FILE_RENAME' : 'FOLDER_RENAME';

		$this->access->checkPermission(
			$this->getUserRole(),
			$action,
			$fromPath
		);

		$newName = Helper::makeSafe($newName);
		$destinationPath = $this->getPath() . $newName;

		$this->access->checkPermission(
			$this->getUserRole(),
			$action,
			$destinationPath
		);

		if (!$fromPath) {
			throw new Exception(
				'Need source path',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if (!$destinationPath) {
			throw new Exception(
				'Need destination path',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if (!is_file($fromPath) and !is_dir($fromPath)) {
			throw new Exception(
				'Path not exists',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		if (is_file($fromPath)) {
			$ext = strtolower(pathinfo($fromPath, PATHINFO_EXTENSION));
			$newExt = strtolower(
				pathinfo($destinationPath, PATHINFO_EXTENSION)
			);
			if ($newExt !== $ext) {
				$destinationPath .= '.' . $ext;
			}
		}

		if (is_file($destinationPath) or is_dir($destinationPath)) {
			throw new Exception(
				'New ' . basename($destinationPath) . ' already exists',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		rename($fromPath, $destinationPath);
	}

	/**
	 * Move file or directory to another folder
	 * @throws Exception
	 */
	public function movePath($from) {
		$destinationPath = $this->getPath();
		$sourcePath = $this->getPath($from);

		$action = is_file($sourcePath) ? 'FILE_MOVE' : 'FOLDER_MOVE';

		$this->access->checkPermission(
			$this->getUserRole(),
			$action,
			$destinationPath
		);

		$this->access->checkPermission(
			$this->getUserRole(),
			$action,
			$sourcePath
		);

		if (!$sourcePath) {
			throw new Exception(
				'Need source path',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if (!$destinationPath) {
			throw new Exception(
				'Need destination path',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		if (is_file($sourcePath) or is_dir($sourcePath)) {
			rename($sourcePath, $destinationPath . basename($sourcePath));
		} else {
			throw new Exception('Not file', Consts::ERROR_CODE_NOT_EXISTS);
		}
	}

	/**
	 * Remove file
	 * @param string $target
	 * @throws Exception
	 */
	public function fileRemove($target) {
		$file_path = false;

		$path = $this->getPath();

		$this->access->checkPermission(
			$this->getUserRole(),
			'FILE_REMOVE',
			$path
		);

		if (
			realpath($path . $target) &&
			strpos(realpath($path . $target), $this->getRoot()) !== false
		) {
			$file_path = realpath($path . $target);
		}

		if (!$file_path || !file_exists($file_path)) {
			throw new Exception(
				'File or directory not exists ' . $path . $target,
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		if (is_file($file_path)) {
			$file = $this->makeFile($file_path);

			if (!$file->remove()) {
				$error = (object) error_get_last();

				throw new Exception(
					'Delete failed! ' . $error->message,
					Consts::ERROR_CODE_IS_NOT_WRITEBLE
				);
			}
		} else {
			throw new Exception(
				'It is not a file!',
				Consts::ERROR_CODE_IS_NOT_WRITEBLE
			);
		}
	}

	/**
	 * Remove folder
	 *
	 * @throws Exception
	 */
	public function folderRemove($name) {
		$file_path = false;

		$path = $this->getPath();

		$this->access->checkPermission(
			$this->getUserRole(),
			'FOLDER_REMOVE',
			$path
		);

		if (
			realpath($path . $name) &&
			strpos(realpath($path . $name), $this->getRoot()) !== false
		) {
			$file_path = realpath($path . $name);
		}

		if ($file_path && file_exists($file_path)) {
			if (is_dir($file_path)) {
				$thumb =
					$file_path .
					Consts::DS .
					$this->thumbFolderName .
					Consts::DS;

				if (is_dir($thumb)) {
					Helper::removeDirectory($thumb);
				}

				Helper::removeDirectory($file_path);
			} else {
				throw new Exception(
					'It is not a directory!',
					Consts::ERROR_CODE_IS_NOT_WRITEBLE
				);
			}
		} else {
			throw new Exception(
				'Directory not exists',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}
	}

	/**
	 * @param string $url
	 * @return mixed
	 */
	public function resolveFileByUrl($url) {
		$base = parse_url($this->baseurl);
		$parts = parse_url($url);

		$path = preg_replace('#^(/)?' . $base['path'] . '#', '', $parts['path']);

		$root = $this->getPath();

		if (file_exists($root . $path) && is_file($root . $path)) {
			$file = $this->makeFile($root . $path);

			if ($file->isGoodFile($this)) {
				return [
					'path' => str_replace(
						$root,
						'',
						dirname($root . $path) . Consts::DS
					),
					'name' => basename($path),
					'source' => $this->sourceName,
				];
			}
		}

		return null;
	}
}
