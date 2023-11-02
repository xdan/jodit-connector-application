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

use claviska\SimpleImage;
use Exception;
use Jodit\Consts;
use Jodit\Helper;
use Jodit\interfaces\IFile;
use Jodit\interfaces\ImageInfo;
use Jodit\interfaces\ISource;

/**
 * Class BaseApplication
 * @package Jodit
 */
abstract class BaseApplication {
	public Response $response;

	public Request $request;

	public string $action;

	public Config $config;

	/**
	 * Check whether the user has the ability to view files
	 * You can define JoditCheckPermissions function in config.php and use it
	 * @return mixed
	 */
	abstract public function checkAuthentication();

	protected function corsHeaders(): void {
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		} else {
			header('Access-Control-Allow-Origin: *');
		}

		header('Access-Control-Allow-Credentials: true');
		header(
			'Access-Control-Allow-Headers: Origin,X-Requested-With,Content-Type,Accept'
		);
		header('Access-Control-Max-Age: 86400'); // cache for 1 day

		if ($this->request->getMethod() === 'OPTIONS') {
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
				header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
			}

			exit(0);
		}
	}

	public function display(): void {
		$version = json_decode(
			file_get_contents(__DIR__ . '/../../package.json')
		)->version;
		header('X-App-version: ' . $version);

		if (!$this->config->debug) {
			if (ob_get_length()) {
				ob_end_clean();
			}
			header('Content-Type: application/json');
		} else {
			$this->response->elapsedTime = microtime(true) - $this->startedTime;
		}

		echo json_encode(
			$this->response,
			$this->config->debug
				? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
				: 0
		);
		die();
	}

	/**
	 * @throws Exception
	 */
	public function execute(): void {
		$methods = get_class_methods($this);

		if (!in_array('action' . ucfirst($this->action), $methods)) {
			throw new Exception(
				'Action "' . htmlspecialchars($this->action) . '" not found',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		$this->config->access->checkPermission(
			$this->config->getUserRole(),
			$this->action
		);

		$this->response->data = (object) call_user_func_array(
			[$this, 'action' . $this->action],
			[]
		);

		$this->response->success = true;
		$this->response->data->code = 220;

		$this->display();
	}

	/**
	 * Constructor FileBrowser
	 *
	 * @param {array} $config
	 * @throws Exception
	 */

	private float $startedTime;

	/**
	 * BaseApplication constructor.
	 * @throws Exception
	 */
	function __construct(array $config) {
		$this->config = new Config($config, null);

		$this->startOutputBuffer();

		$this->startedTime = microtime(true);

		$this->response = new Response();

		set_error_handler(function ($ignore, $error, $errorFile, $errorLine) {
			throw new Exception(
				$error .
					($this->config->debug
						? ' - file:' . $errorFile . ' line:' . $errorLine
						: ''),
				Consts::ERROR_CODE_NOT_IMPLEMENTED
			);
		});

		set_exception_handler([$this, 'exceptionHandler']);

		$this->request = new Request();

		if ($this->config->allowCrossOrigin) {
			$this->corsHeaders();
		}

		$this->action = $this->request->action ?: 'default';

		$this->config->access->setAccessList($this->config->accessControl);
		Jodit::$app = $this;
	}

	protected function startOutputBuffer(): void {
		ob_start();
	}

	/**
	 * @throws Exception
	 */
	protected function getImageEditorInfo(): ImageInfo {
		$source = $this->config->getSource($this->request->source);
		$path = $source->getPath();

		$file = $this->request->name;

		$box = (object) ['w' => 0, 'h' => 0, 'x' => 0, 'y' => 0];

		if ($this->request->box && is_array($this->request->box)) {
			foreach ($box as $key => &$value) {
				$value = isset($this->request->box[$key])
					? $this->request->box[$key]
					: 0;
			}
		}

		$newName = $this->request->newname
			? Helper::makeSafe($this->request->newname)
			: '';

		if (
			!$path ||
			!$file ||
			!file_exists($path . $file) ||
			!is_file($path . $file)
		) {
			throw new Exception(
				'File not exists',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		$img = new SimpleImage();

		$img->fromFile($path . $file);

		if ($newName) {
			$info = pathinfo($path . $file);

			// if has not same extension
			if (
				!empty($info['extension']) &&
				!preg_match('#\.(' . $info['extension'] . ')$#i', $newName)
			) {
				$newName = $newName . '.' . $info['extension'];
			}

			if (
				!$this->config->allowReplaceSourceFile and
				file_exists($path . $newName)
			) {
				throw new Exception(
					'File ' . $newName . ' already exists',
					Consts::ERROR_CODE_BAD_REQUEST
				);
			}
		} else {
			$newName = $file;
		}

		if (
			file_exists(
				$path . $this->config->thumbFolderName . Consts::DS . $newName
			)
		) {
			unlink(
				$path . $this->config->thumbFolderName . Consts::DS . $newName
			);
		}

		return new ImageInfo([
			'path' => $path,
			'file' => $file,
			'box' => $box,
			'newname' => $newName,
			'img' => $img,
			'width' => $img->getWidth(),
			'height' => $img->getHeight(),
		]);
	}

	/**
	 * @param mixed $e
	 */
	public function exceptionHandler($e): void {
		$this->response->success = false;
		$this->response->data = (object)[
			"code" =>  $e->getCode(),
			"messages" =>  [$e->getMessage()],
		];

		if ($this->config->debug) {
			do {
				$traces = $e->getTrace();
				$this->response->data->messages[] = implode(' - ', [
					$e->getFile(),
					$e->getLine(),
				]);
				foreach ($traces as $trace) {
					$line = [];
					if (isset($trace['file'])) {
						$line[] = $trace['file'];
					}
					if (isset($trace['function'])) {
						$line[] = $trace['function'];
					}
					if (isset($trace['line'])) {
						$line[] = $trace['line'];
					}
					$this->response->data->messages[] = implode(' - ', $line);
				}
				$e = $e->getPrevious();
			} while ($e);
		}

		$this->display();
	}

	/**
	 * @return IFile[]
	 * @throws Exception
	 */
	public function uploadedFiles(ISource $source): array {
		if (!isset($_FILES[$source->defaultFilesKey])) {
			throw new Exception(
				'Incorrect request',
				Consts::ERROR_CODE_BAD_REQUEST
			);
		}

		$files = $_FILES[$source->defaultFilesKey];
		/**
		 * @var File[] $output
		 */
		$output = [];

		try {
			if (
				isset($files) and
				is_array($files) and
				isset($files['name']) and
				is_array($files['name']) and
				count($files['name'])
			) {
				foreach ($files['name'] as $i => $file) {
					if ($files['error'][$i]) {
						throw new Exception(
							isset(Helper::$uploadErrors[$files['error'][$i]])
								? Helper::$uploadErrors[$files['error'][$i]]
								: 'Error',
							$files['error'][$i]
						);
					}

					$path = $source->getPath();
					$tmp_name = $files['tmp_name'][$i];
					$fileName = Helper::makeSafe($files['name'][$i]);
					$new_path = $path . $fileName;

					if (file_exists($new_path)) {
						$new_path =
							$path .
							Helper::sameFileStrategy(
								File::create($new_path),
								$source->saveSameFileNameStrategy
							);
					}

					if (!move_uploaded_file($tmp_name, $new_path)) {
						if (!is_writable($path)) {
							throw new Exception(
								'Destination directory is not writable',
								Consts::ERROR_CODE_IS_NOT_WRITEBLE
							);
						}

						throw new Exception(
							'No files have been uploaded',
							Consts::ERROR_CODE_NO_FILES_UPLOADED
						);
					}

					$file = $source->makeFile($new_path);

					try {
						$this->config->access->checkPermission(
							$this->config->getUserRole(),
							$this->action,
							$source->getRoot(),
							pathinfo($file->getPath(), PATHINFO_EXTENSION)
						);
					} catch (Exception $e) {
						$file->remove();
						throw $e;
					}

					if (!$file->isSafeFile($source)) {
						$file->remove();
						throw new Exception(
							'File type is not in white list',
							Consts::ERROR_CODE_FORBIDDEN
						);
					}

					if (
						$source->maxFileSize and
						$file->getSize() >
							Helper::convertToBytes($source->maxFileSize)
					) {
						$file->remove();
						throw new Exception(
							'File size exceeds the allowable',
							Consts::ERROR_CODE_FORBIDDEN
						);
					}

					$output[] = $file;
				}
			}
		} catch (Exception $e) {
			foreach ($output as $file) {
				$file->remove();
			}
			throw $e;
		}

		return $output;
	}

	public function getRoot(): string {
		if (!isset($_SERVER['DOCUMENT_ROOT'])) {
			throw new Exception(
				'Empty DOCUMENT_ROOT',
				Consts::ERROR_CODE_NOT_IMPLEMENTED
			);
		}

		return realpath($_SERVER['DOCUMENT_ROOT']) . Consts::DS;
	}
}
