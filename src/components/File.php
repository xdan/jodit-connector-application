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
use Jodit\interfaces\IFile;
use Jodit\interfaces\ISource;

/**
 * Class Files
 */
class File extends IFile {
	/**
	 * @return File
	 * @throws Exception
	 */
	public static function create(string $path) {
		return new File($path);
	}

	private string $path = '';

	/**
	 * @throws Exception
	 */
	protected function __construct(string $path) {
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
	 */
	public function isGoodFile(ISource $source): bool {
		$ext = $this->getExtension();

		if (!$ext or !in_array($ext, $source->extensions)) {
			return false;
		}

		return true;
	}

	/**
	 * File is safe
	 */
	public function isSafeFile(ISource $source): bool {
		$ext = $this->getExtension();

		if (!$this->isGoodFile($source)) {
			return false;
		}

		if (in_array($ext, $source->imageExtensions) && !$this->isImage()) {
			return false;
		}

		return true;
	}

	public function isDirectory(): bool {
		return is_dir($this->path);
	}

	/**
	 * Remove file
	 * @throws Exception
	 */
	public function remove(): bool {
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

	public function getPath(): string {
		return str_replace('\\', Consts::DS, $this->path);
	}

	public function getFolder(): string {
		return dirname($this->getPath()) . Consts::DS;
	}

	public function getName(): string {
		$parts = explode(Consts::DS, $this->getPath());
		return array_pop($parts) ?: '';
	}

	/**
	 * Get file extension
	 */
	public function getExtension(): string {
		$parts = explode('.', $this->getName());
		return mb_strtolower(array_pop($parts) ?: '');
	}

	/**
	 * Get file basename(urf8 basename analogue)
	 */
	public function getBasename(): string {
		$parts = explode('.', $this->getName());
		array_pop($parts);
		return implode('.', $parts);
	}

	private ?int $cacheSize = null;
	public function getSize(): int {
		if ($this->cacheSize!== null) {
			return $this->cacheSize;
		}

		$size = filesize($this->getPath());

		if ($size === false) {
			throw new Exception(
				'Can not read filesize',
				Consts::ERROR_CODE_NOT_IMPLEMENTED
			);
		}

		$this->cacheSize = $size;

		return $size;
	}

	private ?int $cacheTime = null;
	public function getTime(): int {
		if ($this->cacheTime!== null) {
			return $this->cacheTime;
		}

		$time = filemtime($this->getPath());

		if ($time === false) {
			throw new Exception(
				'Can not read filemtime',
				Consts::ERROR_CODE_NOT_IMPLEMENTED
			);
		}

		$this->cacheTime = $time;

		return $time;
	}

	/**
	 * @throws Exception
	 */
	public function getPathByRoot(ISource $source): string {
		$path = preg_replace('#[\\\\/]#', '/', $this->getPath());
		$root = preg_replace('#[\\\\/]#', '/', $source->getPath());

		return str_replace($root, '', $path);
	}

	private static array $isFile = [];

	/**
	 * Check by mimetype what file is image
	 */
	public function isImage(): bool {
		$path = $this->getPath();

		if (isset(self::$isFile[$path])) {
			return self::$isFile[$path];
		}

		try {
			if ($this->isSVGImage()) {
				self::$isFile[$path] = true;
				return true;
			}

			if (
				!function_exists('exif_imagetype') &&
				!function_exists('Jodit\exif_imagetype') &&
				!function_exists('Jodit\components\exif_imagetype')
			) {
				/**
				 * @return false|mixed
				 */
				function exif_imagetype(string $filename) {
					if ((list(, , $type) = getimagesize($filename)) !== false) {
						return $type;
					}

					return false;
				}
			}

			self::$isFile[$path] = in_array(exif_imagetype($path), [
				IMAGETYPE_GIF,
				IMAGETYPE_JPEG,
				IMAGETYPE_PNG,
				IMAGETYPE_BMP,
				IMAGETYPE_WEBP,
			]);
		} catch (Exception $e) {
			self::$isFile[$path] = false;
		}

		return self::$isFile[$path];
	}

	/**
	 * Check file is SVG image
	 */
	public function isSVGImage(): bool {
		return $this->getExtension() === 'svg';
	}

	/**
	 * Send file for download
	 */
	public function send(): void {
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=' . $this->getName());
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . $this->getSize());
		ob_clean();
		flush();
		readfile($this->getPath());
		exit();
	}
}
