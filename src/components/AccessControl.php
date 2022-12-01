<?php
/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */

namespace Jodit\components;

use Exception;
use Jodit\Consts;
use Jodit\Helper;

/**
 * Class AccessControl
 * @package Jodit
 */
class AccessControl {
	private array $accessList = [];

	public static array $defaultRule;

	public function setAccessList(array $list): void {
		$this->accessList = $list;
	}

	/**
	 * @throws Exception
	 */
	public function checkPermission(
		string $role,
		string $action,
		string $path = '/',
		string $fileExtension = '*'
	): bool {
		if (!$this->isAllow($role, $action, $path, $fileExtension)) {
			throw new Exception('Access denied', Consts::ERROR_CODE_FORBIDDEN);
		}

		return true;
	}

	public function isAllow(
		string $role,
		string $action,
		string $path = '/',
		string $fileExtension = '*'
	): bool {
		$action = Helper::upperize($action);

		$allow = null;

		foreach ($this->accessList as $rule) {
			if (
				!isset($rule['role']) or
				$rule['role'] === '*' or
				$rule['role'] === $role
			) {
				if (isset($rule['path'])) {
					if (
						strpos(
							Helper::normalizePath($path),
							Helper::normalizePath($rule['path'])
						) !== 0
					) {
						continue;
					}
				}

				if (isset($rule['extensions'])) {
					$allowExtensions = ['*'];

					if (is_string($rule['extensions'])) {
						$rule['extensions'] = preg_split(
							'#[,\s]+#',
							$rule['extensions']
						);
					}

					if (is_array($rule['extensions'])) {
						$allowExtensions = array_map(
							['\Jodit\Helper', 'upperize'],
							$rule['extensions']
						);
					}

					if (is_callable($rule['extensions'])) {
						$allowExtensions = call_user_func_array(
							$rule['extensions'],
							[$action, $rule, $path, $fileExtension]
						);
					}

					if (
						!(
							in_array('*', $allowExtensions) or
							in_array(
								strtoupper($fileExtension),
								$allowExtensions
							)
						)
					) {
						continue;
					}
				}

				if (isset($rule[$action])) {
					if (is_callable($rule[$action])) {
						$allow = call_user_func_array($rule[$action], [
							$action,
							$rule,
							$path,
							$fileExtension,
						]);
					} else {
						$allow = is_bool($rule[$action])
							? $rule[$action]
							: true;
					}
				}
			}
		}

		if ($allow === null) {
			$allow = isset(static::$defaultRule[$action])
				? static::$defaultRule[$action]
				: true;
		}

		if ($allow === false) {
			return false;
		}

		return true;
	}
}

AccessControl::$defaultRule = include __DIR__ . '/../configs/defaultRules.php';
