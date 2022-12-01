<?php
declare(strict_types=1);

namespace Jodit\actions;

use Jodit\components\Config;
use Jodit\components\Request;
use Jodit\Consts;
use Jodit\Helper;
use Exception;

/**
 * Trait Folder
 * @package Jodit\actions
 */
trait Folder {
	public Config $config;
	public Request $request;

	/**
	 * Load all folders from folder ore source or sources
	 * @throws Exception
	 */
	public function actionFolders(): array {
		$sources = [];

		foreach ($this->config->getSources() as $source) {
			$sources[] = $source->folders();
		}

		return ['sources' => $sources];
	}

	/**
	 * Remove folder
	 *
	 * @throws Exception
	 */
	public function actionFolderRemove(): void {
		$this->config
			->getSource($this->request->source)
			->folderRemove($this->request->name);
	}

	/**
	 * Create directory
	 * @throws Exception
	 */
	public function actionFolderCreate(): ?array {
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
	public function actionFolderMove(): void {
		$this->config
			->getSource($this->request->source)
			->movePath($this->request->from);
	}

	/**
	 * Rename folder
	 * @throws \Exception
	 */
	public function actionFolderRename(): void {
		$this->config
			->getSource($this->request->source)
			->renamePath($this->request->name, $this->request->newname);
	}
}
