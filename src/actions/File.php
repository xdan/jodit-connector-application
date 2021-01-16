<?php

namespace Jodit\actions;

use Jodit\Config;
use Jodit\Consts;
use Jodit\Helper;
use Jodit\Request;
use Exception;

/**
 * Trait File
 * @package Jodit\actions
 */
trait File {
	/**
	 * @var Request
	 */
	public $request;

	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Load remote image by URL to self host
	 * @throws Exception
	 */
	public function actionFileUploadRemote() {
		$url = $this->request->url;

		if (!$url) {
			throw new Exception(
				'Need url parameter',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$result = parse_url($url);

		if (!isset($result['host']) || !isset($result['path'])) {
			throw new Exception(
				'Not valid URL',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$filename = Helper::makeSafe(basename($result['path']));

		if (!$filename) {
			throw new Exception(
				'Not valid URL',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$source = $this->config->getCompatibleSource($this->request->source);

		Helper::downloadRemoteFile($url, $source->getRoot() . $filename);

		$file = new \Jodit\File($source->getRoot() . $filename);

		try {
			if (!$file->isGoodFile($source)) {
				throw new Exception('Bad file', Consts::ERROR_CODE_FORBIDDEN);
			}

			$this->config->access->checkPermission(
				$this->config->getUserRole(),
				$this->action,
				$source->getRoot(),
				$file->getExtension()
			);
		} catch (Exception $e) {
			$file->remove();
			throw $e;
		}

		return [
			'newfilename' => $file->getName(),
			'baseurl' => $source->baseurl,
		];
	}

	/**
	 * Upload images
	 *
	 * @return array
	 * @throws Exception
	 */
	public function actionFileUpload() {
		$source = $this->config->getCompatibleSource($this->request->source);

		$root = $source->getRoot();
		$path = $source->getPath();

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$path
		);

		$messages = [];

		$files = $this->move($source);

		$isImages = [];

		$files = array_map(function ($file) use (
			$source,
			$root,
			&$isImages
		) {
			$messages[] = 'File ' . $file->getName() . ' was uploaded';
			$isImages[] = $file->isImage();
			return str_replace($root, '', $file->getPath());
		},
		$files);

		if (!count($files)) {
			throw new Exception(
				'No files have been uploaded',
				Consts::ERROR_CODE_NO_FILES_UPLOADED
			);
		}

		return [
			'baseurl' => $source->baseurl,
			'messages' => $messages,
			'files' => $files,
			'isImages' => $isImages,
		];
	}

	/**
	 * Remove file
	 *
	 * @throws Exception
	 */
	public function actionFileRemove() {
		$source = $this->config->getSource($this->request->source);

		$file_path = false;

		$path = $source->getPath();

		$target = $this->request->name;

		if (
			realpath($path . $target) &&
			strpos(realpath($path . $target), $source->getRoot()) !== false
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
			$file = new \Jodit\File($file_path);
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
	 * @param Config $source
	 * @return \Jodit\File[]
	 */
	abstract public function move($source);
	abstract public function movePath();
	abstract public function renamePath();

	/**
	 * Move file
	 * @throws Exception
	 */
	public function actionFileMove() {
		$this->movePath();
	}

	/**
	 * Rename file
	 * @throws Exception
	 */
	public function actionFileRename() {
		$this->renamePath();
	}

	/**
	 * Get filepath by URL for local files
	 * @throws Exception
	 */
	public function actionGetLocalFileByUrl() {
		$url = $this->request->url;

		if (!$url) {
			throw new Exception(
				'Need full url',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$parts = parse_url($url);

		if (empty($parts['path'])) {
			throw new Exception('Empty url', Consts::ERROR_CODE_BAD_REQUEST);
		}

		foreach ($this->config->getSources() as $source) {
			$base = parse_url($source->baseurl);

			$path = preg_replace(
				'#^(/)?' . $base['path'] . '#',
				'',
				$parts['path']
			);

			$root = $source->getPath();

			if (file_exists($root . $path) && is_file($root . $path)) {
				$file = new \Jodit\File($root . $path);
				if ($file->isGoodFile($source)) {
					return [
						'path' => str_replace(
							$root,
							'',
							dirname($root . $path) . Consts::DS
						),
						'name' => basename($path),
						'source' => $source->sourceName,
					];
				}
			}
		}

		throw new Exception(
			'File does not exist or is above the root of the connector',
			Consts::ERROR_CODE_FAILED
		);
	}
}
