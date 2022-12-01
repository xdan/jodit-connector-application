<?php
declare(strict_types=1);

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
use Jodit\interfaces\ISource;

$defaultConfig = include __DIR__ . '/../configs/defaultConfig.php';

/**
 * Class Config
 * @property string $defaultRole
 * @property string $title
 * @property string $root
 * @property string $thumbFolderName
 * @property number $countInChunk
 * @property string $defaultSortBy
 * @property string $maxFileSize
 * @property string $memoryLimit
 * @property string $sourceClassName
 * @property number $thumbSize
 * @property number $timeoutLimit
 * @property bool $allowCrossOrigin
 * @property AccessControl $access
 * @property bool $createThumb
 * @property bool $allowReplaceSourceFile
 * @property bool $debug
 * @property string[] $excludeDirectoryNames
 * @property number $quality
 * @property string $datetimeFormat
 * @property string $baseurl
 * @property int $defaultPermission
 * @property number $safeThumbsCountInOneTime
 * @property string[] $imageExtensions
 * @property string[] $extensions
 * @property array $accessControl
 * @property string $saveSameFileNameStrategy
 * @package jodit
 */
class Config {
	private ?Config $parent;

	static array $defaultOptions;

	private object $data;

	/**
	 * @var ISource[]
	 */
	public array $sources = [];

	public string $sourceName = 'default';

	public AccessControl $access;

	/**
	 * @throws Exception
	 */
	private static function makeSource(array $sourceData, Config $param, string $key): ISource {
		$className = !empty($sourceData ['sourceClassName'])
			? $sourceData ['sourceClassName']
			: $param->sourceClassName;

		if (!$className) {
			$className = self::$defaultOptions['sourceClassName'];
		}

		$instance = new $className($sourceData , $param, $key);

		if ($instance instanceof ISource) {
			return $instance;
		}

		throw new Exception("Class '{$className}' does not implement ISource");
	}

	/**
	 * @return ISource[]
	 * @throws Exception
	 */
	public function getSources(): array {
		$sources = [];
		$request = Jodit::$app->request;
		$action = Jodit::$app->action;

		foreach ($this->sources as $key => $source) {
			if (
				$request->source &&
				$request->source !== 'default' &&
				$key !== $request->source &&
				$request->path !== './'
			) {
				continue;
			}

			$path = $source->getPath();

			try {
				$this->access->checkPermission(
					$this->getUserRole(),
					$action,
					$path
				);
			} catch (Exception $e) {
				continue;
			}

			$sources[] = $source;
		}

		if (count($sources) === 0) {
			throw new Exception(
				'Need valid source',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		return $sources;
	}

	/**
	 * Get user role
	 */
	public function getUserRole(): string {
		return isset($_SESSION[$this->roleSessionVar])
			? $_SESSION[$this->roleSessionVar]
			: $this->defaultRole;
	}

	/**
	 * @param mixed $value
	 */
	public function __set(string $key, $value): void {
		$this->data->{$key} = $value;
	}

	/**
	 * @return mixed | null
	 */
	public function __get(string $key) {
		if (isset($this->data->{$key})) {
			return $this->data->{$key};
		}

		if ($this->parent) {
			return $this->parent->{$key};
		}

		return null;
	}

	/**
	 * Config constructor.
	 * @param object | array  $data
	 * @param Config | null | false  $parent
	 * @throws Exception
	 */
	function __construct($data, $parent, string $sourceName = 'default') {
		$this->parent = $parent ? $parent : null;
		$this->data = is_array($data) ? (object)$data : $data;
		$this->sourceName = $sourceName;
		$this->access = new AccessControl();

		if ($parent === null) {
			if (!$this->baseurl) {
				$this->baseurl =
					((isset($_SERVER['HTTPS']) and $_SERVER['HTTPS'])
						? 'https://'
						: 'http://') .
					(isset($_SERVER['HTTP_HOST'])
						? $_SERVER['HTTP_HOST']
						: '') .
					'/';
			}

			$this->parent = new Config(self::$defaultOptions, false);

			if (
				isset($this->data->sources) and
				is_array($this->data->sources) and
				count($this->data->sources)
			) {
				foreach ($this->data->sources as $key => $source) {
					$this->sources[$key] = self::makeSource(
						$source,
						$this,
						$key
					);
				}
			} else {
				$this->sources['default'] = self::makeSource(
					[],
					$this,
					'default'
				);
			}
		}
	}

	/**
	 * Get full path for $source->root with trailing slash
	 * @throws Exception
	 */
	public function getRoot(): string {
		if ($this->root) {
			if (!is_dir($this->root)) {
				throw new Exception(
					'Root directory not exists ' . $this->root,
					Consts::ERROR_CODE_NOT_EXISTS
				);
			}

			$root = realpath($this->root);

			return  $root !==  Consts::DS ? $root . Consts::DS : Consts::DS;
		}

		throw new Exception(
			'Set root directory for source',
			Consts::ERROR_CODE_NOT_IMPLEMENTED
		);
	}

	/**
	 * Get full path for $_REQUEST[$name] relative path with trailing slash(if directory)
	 * @throws \Exception
	 */
	public function getPath(string $relativePath = null): string {
		$root = $this->getRoot();

		if ($relativePath === null) {
			$relativePath = Jodit::$app->request->path ?: '';
		}

		$path = realpath(Helper::normalizePath($root . $relativePath));

		//always check whether we are below the root category is not reached
		if ($path && strpos($path . Consts::DS, $root) !== false) {
			$root = $path;
			if (is_dir($root)) {
				$root .= Consts::DS;
			}
		} else {
			throw new Exception(
				'Path does not exist',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		return $root;
	}

	/**
	 * Get source by name
	 * @throws Exception
	 */
	public function getSource(?string $sourceName): ISource {
		if (!$sourceName || $sourceName === 'default') {
			$sourceName = Helper::arrayKeyFirst($this->sources);
		}

		$source = isset($this->sources[$sourceName])
			? $this->sources[$sourceName]
			: null;

		if (!$source) {
			throw new Exception(
				'Source not found',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		$source->access->checkPermission(
			$source->getUserRole(),
			Jodit::$app->action,
			$source->getPath()
		);

		return $source;
	}

	/**
	 * @throws Exception
	 */
	public function getCompatibleSource(?string $sourceName = null): ISource {
		if ($sourceName === 'default') {
			$sourceName = null;
		}

		if ($sourceName) {
			$source = $this->getSource($sourceName);

			$this->access->checkPermission(
				$this->getUserRole(),
				Jodit::$app->action,
				$source->getPath()
			);

			return $source;
		}

		if ($sourceName === null || $sourceName === '') {
			foreach ($this->sources as $key => $item) {
				try {
					return $item->getCompatibleSource();
				} catch (Exception $e) {
				}
			}
		}

		$this->access->checkPermission(
			$this->getUserRole(),
			Jodit::$app->action,
			$this->getPath()
		);

		return self::makeSource([], $this, 'default');
	}
}

Config::$defaultOptions = $defaultConfig;
