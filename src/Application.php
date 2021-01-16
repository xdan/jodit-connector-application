<?php
/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */

namespace Jodit;

use Exception;
use Jodit\actions\Folder;
use Jodit\actions\Tree;
use Jodit\actions\Files;
use Jodit\actions\File;
use Jodit\actions\Image;

/**
 * Class Application
 * @package Jodit
 */
abstract class Application extends BaseApplication {
	use Tree;
	use Files;
	use Folder;
	use File;
	use Image;

	/**
	 * Move file or directory to another folder
	 * @throws Exception
	 */
	private function movePath() {
		$source = $this->config->getSource($this->request->source);
		$destinationPath = $source->getPath();
		$sourcePath = $source->getPath($this->request->from);

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$destinationPath
		);

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action,
			$sourcePath
		);

		if ($sourcePath) {
			if ($destinationPath) {
				if (is_file($sourcePath) or is_dir($sourcePath)) {
					rename(
						$sourcePath,
						$destinationPath . basename($sourcePath)
					);
				} else {
					throw new Exception(
						'Not file',
						Consts::ERROR_CODE_NOT_EXISTS
					);
				}
			} else {
				throw new Exception(
					'Need destination path',
					Consts::ERROR_CODE_BAD_REQUEST
				);
			}
		} else {
			throw new Exception(
				'Need source path',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}
	}

	/**
	 * @return array[]
	 * @throws Exception
	 */
	public function actionPermissions() {
		$result = [];
		$source = $this->config->getSource($this->request->source);

		foreach (AccessControl::$defaultRule as $permission => $tmp) {
			if (preg_match('#^[A-Z_]+$#', $permission)) {
				$allow = false;

				try {
					$this->config->access->checkPermission(
						$this->config->getUserRole(),
						$permission,
						$source->getPath()
					);
					$allow = true;
				} catch (Exception $e) {
				}

				$result['allow' . Helper::camelCase($permission)] = $allow;
			}
		}

		return ['permissions' => $result];
	}
}
