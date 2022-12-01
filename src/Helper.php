<?php
declare(strict_types=1);
/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */

namespace Jodit;

use Cocur\Slugify\Slugify;
use Exception;
use InvalidArgumentException;

/**
 * Class Helper
 * @package Jodit
 */
abstract class Helper {
	public static array $uploadErrors = [
		0 => 'There is no error, the file uploaded with success',
		1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
		2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
		3 => 'The uploaded file was only partially uploaded',
		4 => 'No file was uploaded',
		6 => 'Missing a temporary folder',
		7 => 'Failed to write file to disk.',
		8 => 'A PHP extension stopped the file upload.',
	];

	/**
	 * Convert number bytes to human format
	 *
	 */
	public static function humanFileSize(
		int $bytes,
		int $decimals = 2
	): string {
		$size = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
		$factor = (int) floor((strlen((string) $bytes) - 1) / 3);

		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) .
			$size[$factor];
	}

	/**
	 * Converts from human readable file size (kb,mb,gb,tb) to bytes
	 *
	 * @param string|int $from human readable file size. Example 1gb or 11.2mb
	 * @return int
	 */
	public static function convertToBytes($from) {
		if (is_numeric($from)) {
			return (int) $from;
		}

		$number = substr($from, 0, -2);
		$formats = ['KB', 'MB', 'GB', 'TB'];
		$format = strtoupper(substr($from, -2));

		return in_array($format, $formats)
			? (int) ((int) $number *
				pow(1024, array_search($format, $formats) + 1))
			: (int) $from;
	}

	public static function translate(string $str): string {
		$replace = [
			'а' => 'a',
			'б' => 'b',
			'в' => 'v',
			'г' => 'g',
			'д' => 'd',
			'е' => 'e',
			'ё' => 'yo',
			'ж' => 'zh',
			'з' => 'z',
			'и' => 'i',
			'й' => 'y',
			'к' => 'k',
			'л' => 'l',
			'м' => 'm',
			'н' => 'n',
			'о' => 'o',
			'п' => 'p',
			'р' => 'r',
			'с' => 's',
			'т' => 't',
			'у' => 'u',
			'ф' => 'f',
			'х' => 'h',
			'ц' => 'ts',
			'ч' => 'ch',
			'ш' => 'sh',
			'щ' => 'shch',
			'ъ' => '',
			'ы' => 'i',
			'ь' => '',
			'э' => 'e',
			'ю' => 'yu',
			'я' => 'ya',
			' ' => '-',
			'А' => 'A',
			'Б' => 'B',
			'В' => 'V',
			'Г' => 'G',
			'Д' => 'D',
			'Е' => 'E',
			'Ё' => 'Yo',
			'Ж' => 'Zh',
			'З' => 'Z',
			'И' => 'I',
			'Й' => 'Y',
			'К' => 'K',
			'Л' => 'L',
			'М' => 'M',
			'Н' => 'N',
			'О' => 'O',
			'П' => 'P',
			'Р' => 'R',
			'С' => 'S',
			'Т' => 'T',
			'У' => 'U',
			'Ф' => 'F',
			'Х' => 'H',
			'Ц' => 'Ts',
			'Ч' => 'CH',
			'Ш' => 'Sh',
			'Щ' => 'Shch',
			'Ъ' => '',
			'Ы' => 'I',
			'Ь' => '',
			'Э' => 'E',
			'Ю' => 'Yu',
			'Я' => 'Ya',
		];

		$str = strtr($str, $replace);

		return $str;
	}

	public static function makeSafe(string $file): string {
		$file = rtrim(self::translate($file), '.');
		$regex = ['#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#'];
		return trim(preg_replace($regex, '', $file));
	}

	private static ?Slugify $slugify = null;
	public static function slugify(string $name): string {
		if (!self::$slugify) {
			self::$slugify = new Slugify();
		}
		return self::$slugify->slugify($name);
	}

	/**
	 * Download remote file on server
	 * @throws Exception
	 */
	public static function downloadRemoteFile(
		string $url,
		string $destinationFilename
	): void {
		if (!ini_get('allow_url_fopen')) {
			throw new Exception('allow_url_fopen is disabled', 501);
		}

		$response = parse_url($url);
		if (
			!$response or
			empty($response['host']) or
			empty($response['scheme'])
		) {
			throw new Exception('Invalid URL', 501);
		}

		$message = 'File was not loaded';

		if (function_exists('curl_init')) {
			try {
				$raw = file_get_contents($url);
			} catch (Exception $e) {
				throw new Exception($message, Consts::ERROR_CODE_BAD_REQUEST);
			}
		} else {
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30); // таймаут4

			$response = parse_url($url);
			curl_setopt(
				$ch,
				CURLOPT_REFERER,
				$response['scheme'] . '://' . $response['host']
			);
			curl_setopt(
				$ch,
				CURLOPT_USERAGENT,
				'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_3) ' .
					'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/71.0.3578.99 YaBrowser/19.1.1.907 Yowser/2.5 Safari/537.36'
			);

			$raw = curl_exec($ch);

			if (!$raw) {
				throw new Exception($message, Consts::ERROR_CODE_BAD_REQUEST);
			}

			curl_close($ch);
		}

		if ($raw) {
			file_put_contents($destinationFilename, $raw);
		} else {
			throw new Exception($message, Consts::ERROR_CODE_BAD_REQUEST);
		}
	}

	public static function upperize(string $string): string {
		$string = preg_replace('#([a-z])([A-Z])#', '\1_\2', $string);
		return strtoupper($string);
	}

	public static function camelCase(string $string): string {
		$string = preg_replace_callback(
			'#([_])(\w)#',
			function ($m) {
				return strtoupper($m[2]);
			},
			strtolower($string)
		);

		return ucfirst($string);
	}

	public static function removeDirectory(string $dirPath): bool {
		if (!is_dir($dirPath)) {
			throw new InvalidArgumentException("$dirPath must be a directory");
		}

		if (substr($dirPath, strlen($dirPath) - 1, 1) != Consts::DS) {
			$dirPath .= Consts::DS;
		}

		$files = glob($dirPath . '*', GLOB_MARK);

		foreach ($files as $file) {
			if (is_dir($file)) {
				self::removeDirectory($file);
			} else {
				unlink($file);
			}
		}

		return rmdir($dirPath);
	}

	public static function copy(string $source, string $dest): bool {
		if (!file_exists($source)) {
			throw new InvalidArgumentException(
				"$source must be file or directory"
			);
		}

		if (is_file($source)) {
			return copy($source, $dest);
		}

		if (!is_dir($dest)) {
			mkdir($dest, fileperms($source));
		}

		$dir_handle = opendir($source);
		$ds = Consts::DS;

		while ($file = readdir($dir_handle)) {
			if ($file != '.' && $file != '..') {
				if (is_dir($source . $ds . $file)) {
					if (!is_dir($dest . $ds . $file)) {
						mkdir(
							$dest . $ds . $file,
							fileperms($source . $ds . $file)
						);
					}
				}

				self::copy($source . $ds . $file, $dest . $ds . $file);
			}
		}

		closedir($dir_handle);

		return true;
	}

	public static function normalizePath(string $path): string {
		return preg_replace('#[\\\\/]+#', '/', $path);
	}

	/**
	 * Return first of keys
	 * @return int|string|null
	 */
	public static function arrayKeyFirst(array $array) {
		if (count($array)) {
			reset($array);
			return key($array);
		}

		return null;
	}

	public static function sameFileStrategy(
		components\File $file,
		string $saveSameFileNameStrategy
	): string {
		switch ($saveSameFileNameStrategy) {
			case 'error':
				throw new Exception(
					'File already exists',
					Consts::ERROR_CODE_BAD_REQUEST
				);

			case 'replace':
				return $file->getName();

			case 'addNumber':
			default:
				$i = 1;
				do {
					$newFileName =
						$file->getBasename() . "($i)." . $file->getExtension();
					$i += 1;
				} while (file_exists($file->getFolder() . $newFileName));

				return $newFileName;
		}
	}
}
