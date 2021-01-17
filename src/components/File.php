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

/**
 * Class Files
 */
class File {
	private $path = '';

	/**
	 * @param string $path
	 * @throws Exception
	 */
	public function __construct($path) {
		$path = realpath($path);

		if (!$path) {
			throw new Exception(
				'File not exists',
				Consts::ERROR_CODE_NOT_EXISTS
			);
		}

		$this->path = $path;
	}

	/**
	 * Check file extension
	 *
	 * @param Config $source
	 * @return bool
	 */
	public function isGoodFile($source) {
		$info = pathinfo($this->path);

		if (
			!isset($info['extension']) or
			!in_array(strtolower($info['extension']), $source->extensions)
		) {
			return false;
		}

		if (
			in_array(
				strtolower($info['extension']),
				$source->imageExtensions
			) and !$this->isImage()
		) {
			return false;
		}

		return true;
	}

	/**
	 * Remove file
	 * @throws Exception
	 */
	public function remove() {
		$file = basename($this->path);
		$thumb =
			dirname($this->path) .
			Consts::DS .
			Jodit::$app->config->getSource(Jodit::$app->request->source)
				->thumbFolderName .
			Consts::DS .
			$file;

		if (file_exists($thumb)) {
			unlink($thumb);

			if (!count(glob(dirname($thumb) . Consts::DS . '*'))) {
				rmdir(dirname($thumb));
			}
		}

		return unlink($this->path);
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return str_replace('\\', Consts::DS, $this->path);
	}

	/**
	 * @return string
	 */
	public function getFolder() {
		return dirname($this->getPath()) . Consts::DS;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return basename($this->path);
	}

	/**
	 * @return int
	 */
	public function getSize() {
		return filesize($this->getPath());
	}

	/**
	 * @return false|int
	 */
	public function getTime() {
		return filemtime($this->getPath());
	}

	/**
	 * Get file extension
	 *
	 * @return string
	 */
	public function getExtension() {
		return pathinfo($this->getPath(), PATHINFO_EXTENSION);
	}

	/**
	 * @param Config $source
	 * @return string|string[]
	 * @throws Exception
	 */
	public function getPathByRoot($source) {
		$path = preg_replace('#[\\\\/]#', '/', $this->getPath());
		$root = preg_replace('#[\\\\/]#', '/', $source->getPath());

		return str_replace($root, '', $path);
	}

	/**
	 * Check by mimetype what file is image
	 * @return bool
	 */
	public function isImage() {
		try {
			if (
				!function_exists('exif_imagetype') &&
				!function_exists('Jodit\exif_imagetype')
			) {
				/**
				 * @param $filename
				 * @return false|mixed
				 */
				function exif_imagetype($filename) {
					if ((list(, , $type) = getimagesize($filename)) !== false) {
						return $type;
					}

					return false;
				}
			}

			return in_array(exif_imagetype($this->getPath()), [
				IMAGETYPE_GIF,
				IMAGETYPE_JPEG,
				IMAGETYPE_PNG,
				IMAGETYPE_BMP,
			]);
		} catch (Exception $e) {
			return false;
		}
	}
}
