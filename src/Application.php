<?php
namespace Jodit;

use abeautifulsite\SimpleImage;

abstract class Application {
	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Check whether the user has the ability to view files
	 * You can define JoditCheckPermissions function in config.php and use it
	 */
	abstract public function checkPermissions ();

	function corsHeaders() {
		if (isset($_SERVER['HTTP_ORIGIN'])) {
			header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
		} else {
			header("Access-Control-Allow-Origin: *");
		}

		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Headers: Origin,X-Requested-With,Content-Type,Accept');
		header('Access-Control-Max-Age: 86400');    // cache for 1 day

		if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
			if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
				header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
			}

			exit(0);
		}

	}

	function display () {
		if (!$this->config->debug) {
			ob_end_clean();
			header('Content-Type: application/json');
		}

		// replace full path from message
		foreach ($this->config->sources as $source) {
			if (isset($this->response->data->messages)) {
				foreach ($this->response->data->messages as &$message) {
					$message = str_replace($source->root, '/', $message);
				}
			}
		}

		exit(json_encode($this->response, $this->config->debug ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES: 0));
	}

	/**
	 * Get user role
	 *
	 * @return string
	 */
	function getUserRole() {
		return isset($_SESSION[$this->config->roleSessionVar]) ? $_SESSION[$this->config->roleSessionVar] : $this->config->defaultRole;
	}

	function execute () {
		$methods =  get_class_methods($this);

		if (in_array('action' . ucfirst($this->action), $methods)) {
			$this->accessControl->checkPermission($this->getUserRole(), $this->action);
			$this->response->data =  (object)call_user_func_array([$this, 'action' . $this->action], []);
		} else {
			throw new \Exception('Action "' . $this->action . '" not found', 404);
		}

		$this->response->success = true;
		$this->response->data->code = 220;
		$this->display();
	}

	/**
	 * Constructor FileBrowser
	 *
	 * @param {array} $config
	 * @throws \Exception
	 */

	/**
	 * @var \Jodit\AccessControl
	 */
	public $accessControl;
	function __construct ($config) {
		ob_start();

//		set_error_handler([$this, 'errorHandler'], E_ALL);
		set_exception_handler([$this, 'exceptionHandler']);

		$this->config  = new Config($config);

		if ($this->config->allowCrossOrigin) {
			$this->corsHeaders();
		}

		$this->response  = new Response();
		$this->request  = new Request();

		$this->action  = $this->request->action;

		if ($this->config->debug) {
			error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
			ini_set('display_errors', 'on');
		} else {
			error_reporting(0);
			ini_set('display_errors', 'off');
		}

		if ($this->request->source && $this->request->source !== 'default' && empty($this->config->sources[$this->request->source])) {
			throw new \Exception('Need valid parameter source key', 400);
		}

		$this->accessControl = new AccessControl();
	}

	/**
	 * Get default(first) source or by $_REQUEST['source']
	 *
	 * @return Source
	 */
	public function getSource() {
		if (!$this->request->source || empty($this->config->sources[$this->request->source])) {
			return $this->config->sources[0];
		}
		return $this->config->sources[$this->request->source];
	}

	/**
	 * @property Response $response
	 */
	public $response;

	/**
	 * @property Request $request
	 */
	public $request;


	/**
	 * @property string $action
	 */
	public $action;

	/**
	 * Check file extension
	 *
	 * @param {string} $file
	 * @param {Source} $source
	 * @return bool
	 */
	private function isGoodFile($file, Source $source) {
		$info = pathinfo($file);

		if (!isset($info['extension']) or (!in_array(strtolower($info['extension']), $source->extensions))) {
			return false;
		}

		if (in_array(strtolower($info['extension']), $source->imageExtensions) and !Helper::isImage($file)) {
			return false;
		}

		return true;
	}

	protected function getImageEditorInfo() {
		$source = $this->getSource();
		$path = $source->getPath();

		$file = $this->request->name;

		$box = (object)[
			'w' => 0,
			'h' => 0,
			'x' => 0,
			'y' => 0,
		];

		if ($this->request->box && is_array($this->request->box)) {
			foreach ($box as $key=>&$value) {
				$value = isset($this->request->box[$key]) ? $this->request->box[$key] : 0;
			}
		}

		$newName = $this->request->newname ?  Helper::makeSafe($this->request->newname) : '';

		if (!$path || !$file || !file_exists($path . $file) || !is_file($path . $file)) {
			throw new \Exception('Source file not set or not exists', 404);
		}

		$img = new SimpleImage();


		$img->load($path . $file);


		if ($newName) {
			$info = pathinfo($path . $file);

			// if has not same extension
			if (!preg_match('#\.(' . $info['extension'] . ')$#i', $newName)) {
				$newName = $newName . '.' . $info['extension'];
			}

			if (!$this->config->allowReplaceSourceFile and file_exists($path . $newName)) {
				throw new \Exception('File ' . $newName . ' already exists', 400);
			}
		} else {
			$newName = $file;
		}

		if (file_exists($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $newName)) {
			unlink($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $newName);
		}

		$info = $img->get_original_info();

		return (object)[
			'path' => $path,
			'file' => $file,
			'box' => $box,
			'newname' => $newName,
			'img' => $img,
			'width' => $info['width'],
			'height' => $info['height'],
		];
	}

	/**
	 * Error handler
	 *
	 * @param {int} errno contains the level of the error raised, as an integer.
	 * @param {string} errstr contains the error message, as a string.
	 * @param {string} errfile which contains the filename that the error was raised in, as a string.
	 * @param {string} errline which contains the line number the error was raised at, as an integer.
	 */
	public function errorHandler ($errorNumber, $errorMessage, $file, $line) {
		$this->response->success = false;
		$this->response->data->code = $errorNumber;
		$this->response->data->messages[] = $errorMessage . ($this->config->debug ? ' - file:' . $file . ' line:' . $line : '');

		$this->display();
	}

	/**
	 * @param \Exception $exception
	 */
	public function exceptionHandler ($exception) {
		$this->errorHandler($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
	}


	/**
	 * Load all files from folder ore source or sources
	 */
	protected function actionFiles() {
		$sources = [];
		foreach ($this->config->sources as $key => $source) {
			if ($this->request->source && $this->request->source !== 'default' && $key !== $this->request->source && $this->request->path !== './') {
				continue;
			}

			$path = $source->getPath();

			try {
				$this->accessControl->checkPermission($this->getUserRole(), $this->action, $path);
			} catch (\Exception $e) {
				continue;
			}

			$sourceData = (object)[
				'baseurl' => $source->baseurl,
				'path' =>  str_replace(realpath($source->getRoot()) . DIRECTORY_SEPARATOR, '', $path),
				'files' => [],
			];

			$dir = opendir($path);

			while ($file = readdir($dir)) {
				if ($file != '.' && $file != '..' && is_file($path . $file)) {
					if ($this->isGoodFile($path . $file, $source)) {
						$item = [
							'file' => $file,
						];

						if ($this->config->createThumb) {
							if (!is_dir($path . $this->config->thumbFolderName)) {
								mkdir($path . $this->config->thumbFolderName, 0777);
							}
							if (!file_exists($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file)) {
								try {
									$img = new SimpleImage($path . $file);
									$img
										->best_fit(150, 150)
										->save($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file, $this->config->quality);
								} catch (\Exception $e) {
									continue;
								}
							}
							$item['thumb'] = $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file;
						}

						$item['changed'] = date($this->config->datetimeFormat, filemtime($path.$file));
						$item['size'] = Helper::humanFileSize(filesize($path.$file));
						$sourceData->files[] = $item;
					}
				}
			}

			$sources[$key] = $sourceData;
		}

		return [
			'sources' => $sources
		];
	}

	/**
	 * Load all folders from folder ore source or sources
	 */
	protected function actionFolders() {
		$sources = [];
		foreach ($this->config->sources as $key => $source) {
			if ($this->request->source && $this->request->source !== 'default' && $key !== $this->request->source && $this->request->path !== './') {
				continue;
			}

			$path = $source->getPath();

			try {
				$this->accessControl->checkPermission($this->getUserRole(), $this->action, $path);
			} catch (\Exception $e) {
				continue;
			}

			$sourceData = (object)[
				'baseurl' => $source->baseurl,
				'path' =>  str_replace(realpath($source->getRoot()) . DIRECTORY_SEPARATOR, '', $path),
				'folders' => [],
			];

			$sourceData->folders[] = $path == $source->getRoot() ? '.' : '..';

			$dir = opendir($path);
			while ($file = readdir($dir)) {
				if ($file != '.' && $file != '..' && is_dir($path . $file) and (!$this->config->createThumb || $file !== $this->config->thumbFolderName) and !in_array($file, $this->config->excludeDirectoryNames)) {
					$sourceData->folders[] = $file;
				}
			}

			$sources[$key] = $sourceData;
		}

		return [
			'sources' => $sources
		];
	}

	/**
	 * Load remote image by URL to self host
	 * @throws \Exception
	 */
	protected function actionFileUploadRemote() {
		$url = $this->request->url;

		if (!$url) {
			throw new \Exception('Need url parameter', 400);
		}

		$result = parse_url($url);

		if (!isset($result['host']) || !isset($result['path'])) {
			throw new \Exception('Not valid URL', 400);
		}

		$filename = Helper::makeSafe(basename($result['path']));

		if (!$filename) {
			throw new \Exception('Not valid URL', 400);
		}

		Helper::downloadRemoteFile($url, $this->getSource()->getRoot() . $filename);
		$extension = pathinfo($this->getSource()->getRoot() . $filename, PATHINFO_EXTENSION);

		try {
			$this->accessControl->checkPermission($this->getUserRole(), $this->action, $this->getSource()->getRoot(), $extension);
		} catch (\Exception $e) {
			unlink($this->getSource()->getRoot() . $filename);
			throw $e;
		}

		return [
			'newfilename' => $filename,
			'baseurl' => $this->getSource()->baseurl,
		];
	}

	/**
	 * Upload images
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function actionFileUpload() {

		$source = $this->getSource();

		$root = $source->getRoot();
		$path = $source->getPath();

		$messages = [];
		$files = [];

		if (isset($_FILES['files']) and is_array($_FILES['files']) and isset($_FILES['files']['name']) and is_array($_FILES['files']['name']) and count($_FILES['files']['name'])) {
			foreach ($_FILES['files']['name'] as $i => $file) {
				if ($_FILES['files']['error'][$i]) {
					throw new \Exception(isset(Helper::$upload_errors[$_FILES['files']['error'][$i]]) ? Helper::$upload_errors[$_FILES['files']['error'][$i]] : 'Error', $_FILES['files']['error'][$i]);
				}

				$tmp_name = $_FILES['files']['tmp_name'][$i];

				if ($source->maxFileSize and filesize($tmp_name) > Helper::convertToBytes($source->maxFileSize)) {
					unlink($tmp_name);
					throw new \Exception('File size exceeds the allowable', 403);
				}

				if (move_uploaded_file($tmp_name, $file = $path . Helper::makeSafe($_FILES['files']['name'][$i]))) {
					try {
						$this->accessControl->checkPermission($this->getUserRole(), $this->action, $source->getRoot(), pathinfo($file, PATHINFO_EXTENSION));
					} catch (\Exception $e) {
						unlink($file);
						throw $e;
					}

					if (!$this->isGoodFile($file, $source)) {
						unlink($file);
						throw new \Exception('File type is not in white list', 403);
					}

					$messages[] = 'File ' . $_FILES['files']['name'][$i] . ' was upload';
					$files[] = str_replace($root, '', $file);
				} else {
					if (!is_writable($path)) {
						throw new \Exception('Destination directory is not writeble', 424);
					}

					throw new \Exception('No files have been uploaded', 422);
				}
			}
		}

		if (!count($files)) {
			throw new \Exception('No files have been uploaded', 422);
		}

		return [
			'baseurl' => $source->baseurl,
			'messages' => $messages,
			'files' => $files
		];
	}

	/**
	 * Remove file
	 *
	 * @throws \Exception
	 */
	protected function actionFileRemove() {
		$source = $this->getSource();

		$file_path = false;

		$path = $source->getPath();

		$this->accessControl->checkPermission($this->getUserRole(), $this->action, $path);

		$target = $this->request->name;

		if (realpath($path . $target) && strpos(realpath($path . $target), $source->getRoot()) !== false) {
			$file_path = realpath($path . $target);
		}

		if ($file_path && file_exists($file_path)) {
			if (is_file($file_path)) {
				$result = unlink($file_path);
				if ($result) {
					$file = basename($file_path);
					$thumb = dirname($file_path) . DIRECTORY_SEPARATOR . $source->thumbFolderName . DIRECTORY_SEPARATOR . $file;
					if (file_exists($thumb)) {
						unlink($thumb);
						if (!count(glob(dirname($thumb) . DIRECTORY_SEPARATOR . "*"))) {
							rmdir(dirname($thumb));
						}
					}
				} else {
					$error = (object)error_get_last();
					throw new \Exception('Delete failed! ' . $error->message, 424);
				}
			} else {
				throw new \Exception('It is not a file!', 424);
			}

		} else {
			throw new \Exception('File or directory not exists' . $path . $target, 400);
		}
	}

	/**
	 * Remove folder
	 *
	 * @throws \Exception
	 */
	protected function actionFolderRemove() {
		$source = $this->getSource();

		$file_path = false;

		$path = $source->getPath();

		$this->accessControl->checkPermission($this->getUserRole(), $this->action, $path);

		$target = $this->request->name;

		if (realpath($path . $target) && strpos(realpath($path . $target), $source->getRoot()) !== false) {
			$file_path = realpath($path . $target);
		}

		if ($file_path && file_exists($file_path)) {
			if (is_dir($file_path)) {
				$thumb = $file_path . DIRECTORY_SEPARATOR . $source->thumbFolderName . DIRECTORY_SEPARATOR;
				if (is_dir($thumb)) {
					Helper::deleteDir($thumb);
				}
				Helper::deleteDir($file_path);
			} else {
				throw new \Exception('It is not a directory!', 424);
			}
		} else {
			throw new \Exception('Directory not exists', 400);
		}
	}

	/**
	 * Create directory
	 * @throws \Exception
	 */
	protected function actionFolderCreate() {
		$source = $this->getSource();
		$destinationPath = $source->getPath();

		$this->accessControl->checkPermission($this->getUserRole(), $this->action, $destinationPath);

		$folderName = Helper::makeSafe($this->request->name);

		if ($destinationPath) {
			if ($folderName) {
				if (!realpath($destinationPath . $folderName)) {
					mkdir($destinationPath . $folderName, $source->defaultPermission);
					if (is_dir($destinationPath . $folderName)) {
						return ['messages' => ['Directory successfully created']];
					}
					throw new \Exception('Directory was not created', 404);
				}
				throw new \Exception('Directory already exists', 406);
			}
			throw new \Exception('The name for new directory has not been set', 406);
		}
		throw new \Exception('The destination directory has not been set', 406);
	}

	/**
	 * Move file or directory to another folder
	 *
	 * @throws \Exception
	 */
	protected function actionMove() {
		$source = $this->getSource();
		$destinationPath = $source->getPath();
		$sourcePath = $source->getPath($this->request->from);

		$this->accessControl->checkPermission($this->getUserRole(), $this->action, $destinationPath);
		$this->accessControl->checkPermission($this->getUserRole(), $this->action, $sourcePath);

		if ($sourcePath) {
			if ($destinationPath) {
				if (is_file($sourcePath) or is_dir($sourcePath)) {
					rename($sourcePath, $destinationPath . basename($sourcePath));
				} else {
					throw new \Exception('Not file', 404);
				}
			} else {
				throw new \Exception('Need destination path', 400);
			}
		} else {
			throw new \Exception('Need source path', 400);
		}
	}

	/**
	 * Resize image
	 *
	 * @throws \Exception
	 */
	protected function actionImageResize() {
		$source = $this->getSource();

		$this->accessControl->checkPermission($this->getUserRole(), $this->action, $source->getPath());

		$info = $this->getImageEditorInfo();

		if (!$info->box || (int)$info->box->w <= 0) {
			throw new \Exception('Width not specified', 400);
		}

		if (!$info->box || (int)$info->box->h <= 0) {
			throw new \Exception('Height not specified', 400);
		}


		$info->img
			->resize((int)$info->box->w, (int)$info->box->h)
			->save($info->path . $info->newname, $source->quality);
	}

	protected function actionImageCrop() {
		$source = $this->getSource();

		$this->accessControl->checkPermission($this->getUserRole(), $this->action, $source->getPath());

		$info = $this->getImageEditorInfo();

		if ((int)$info->box->x < 0 || (int)$info->box->x > (int)$info->width) {
			throw new \Exception('Start X not specified', 400);
		}

		if ((int)$info->box->y < 0 || (int)$info->box->y > (int)$info->height) {
			throw new \Exception('Start Y not specified', 400);
		}

		if ((int)$info->box->w <= 0) {
			throw new \Exception('Width not specified', 400);
		}

		if ((int)$info->box->h <= 0) {
			throw new \Exception('Height not specified', 400);
		}

		$info->img
			->crop((int)$info->box->x, (int)$info->box->y, (int)$info->box->x + (int)$info->box->w, (int)$info->box->y + (int)$info->box->h)
			->save($info->path . $info->newname, $source->quality);

	}

	/**
	 * Get filepath by URL for local files
	 *
	 * @metod actionGetFileByURL
	 */
	function actionGetLocalFileByUrl() {
		$url = $this->request->url;
		if (!$url) {
			throw new \Exception('Need full url', 400);
		}

		$parts = parse_url($url);

		if (empty($parts['path'])) {
			throw new \Exception('Empty url', 400);
		}

		$found = false;
		$path = '';
		$root = '';

		$key = 0;

		foreach ($this->config->sources as $key => $source) {
			if ($this->request->source && $this->request->source !== 'default' && $key !== $this->request->source && $this->request->path !== './') {
				continue;
			}

			$base = parse_url($source->baseurl);

			$path = preg_replace('#^(/)?' . $base['path'] . '#', '', $parts['path']);


			$root = $source->getPath();

			if (file_exists($root . $path) && is_file($root . $path) && $this->isGoodFile($root . $path, $source)) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			throw new \Exception('File does not exist or is above the root of the connector', 424);
		}

		return [
			'path' => str_replace($root, '', dirname($root . $path) . DIRECTORY_SEPARATOR),
			'name' => basename($path),
			'source' => $key
		];
	}
}