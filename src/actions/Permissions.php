<?php
declare(strict_types=1);

namespace Jodit\actions;

use Exception;
use Jodit\components\AccessControl;
use Jodit\components\Config;
use Jodit\components\Request;
use Jodit\Helper;

/**
 * Trait for checking path accesses
 */
trait Permissions {
	public Config $config;
	public Request $request;

	/**
	 * @throws Exception
	 */
	public function actionPermissions(): array {
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
