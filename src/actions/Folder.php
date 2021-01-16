<?php

namespace Jodit\actions;

use Jodit\Config;
use Jodit\Consts;
use Jodit\Helper;
use Jodit\Request;
use Exception;

/**
 * Trait Folder
 * @package Jodit\actions
 */
trait Folder {
	/**
	 * @var Config
	 */
	public $config;

	/**
	 * @var Request
	 */
	public $request;

	/**
	 * Load all folders from folder ore source or sources
	 * @throws Exception
	 */
	public function actionFolders() {
		$sources = [];

		foreach ($this->config->getSources() as $source) {
			$path = $source->getPath();

			$sourceData = (object) [
				'name' => $source->sourceName,
				'baseurl' => $source->baseurl,
				'path' => str_replace(
					realpath($source->getRoot()) . Consts::DS,
					'',
					$path
				),
				'folders' => [],
			];

			$sourceData->folders[] = $path === $source->getRoot() ? '.' : '..';

			$dir = opendir($path);
			while ($file = readdir($dir)) {
				if (is_dir($path . $file) and !$this->isExcluded($file)) {
					$sourceData->folders[] = $file;
				}
			}

			$sources[] = $sourceData;
		}

		return ['sources' => $sources];
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	abstract function isExcluded($file);

	/**
	 * Remove folder
	 *
	 * @throws Exception
	 */
	public function actionFolderRemove() {
		$source = $this->config->getSource($this->request->source);

		$file_path = false;

		$path = $source->getPath();

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$path
		);

		$target = $this->request->name;

		if (
			realpath($path . $target) &&
			strpos(realpath($path . $target), $source->getRoot()) !== false
		) {
			$file_path = realpath($path . $target);
		}

		if ($file_path && file_exists($file_path)) {
			if (is_dir($file_path)) {
				$thumb =
					$file_path .
					Consts::DS .
					$source->thumbFolderName .
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
	 * Create directory
	 * @throws Exception
	 */
	public function actionFolderCreate() {
		$source = $this->config->getSource($this->request->source);
		$destinationPath = $source->getPath();

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$destinationPath
		);

		$folderName = Helper::makeSafe($this->request->name);

		if ($destinationPath) {
			if ($folderName) {
				if (!realpath($destinationPath . $folderName)) {
					mkdir(
						$destinationPath . $folderName,
						$source->defaultPermission
					);
					if (is_dir($destinationPath . $folderName)) {
						return [
							'messages' => ['Directory successfully created'],
						];
					}

					throw new Exception(
						'Directory was not created',
						Consts::ERROR_CODE_NOT_EXISTS
					);
				}

				throw new Exception(
					'Directory already exists',
					Consts::ERROR_CODE_NOT_ACCEPTABLE
				);
			}

			throw new Exception(
				'The name for new directory has not been set',
				Consts::ERROR_CODE_NOT_ACCEPTABLE
			);
		}

		throw new Exception(
			'The destination directory has not been set',
			Consts::ERROR_CODE_NOT_ACCEPTABLE
		);
	}

	/**
	 * Move folder
	 * @throws Exception
	 */
	public function actionFolderMove() {
		$this->movePath();
	}

	abstract public function movePath();

	/**
	 * Rename folder
	 * @throws \Exception
	 */
	public function actionFolderRename() {
		$this->renamePath();
	}

	abstract public function renamePath();
}
