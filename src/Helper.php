<?php
namespace Jodit;

abstract class Helper {
	/**
	 * Convert number bytes to human format
	 *
	 * @param $bytes
	 * @param int $decimals
	 * @return string
	 */
	static function humanFileSize($bytes, $decimals = 2) {
		$size = ['B','kB','MB','GB','TB','PB','EB','ZB','YB'];
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[(int)$factor];
	}

	/**
	 * Converts from human readable file size (kb,mb,gb,tb) to bytes
	 *
	 * @param {string|int} human readable file size. Example 1gb or 11.2mb
	 * @return int
	 */
	static function convertToBytes($from) {
		if (is_numeric($from)) {
			return (int)$from;
		}

		$number = substr($from, 0, -2);
		$formats = ["KB", "MB", "GB", "TB"];
		$format = strtoupper(substr($from, -2));

		return in_array($format, $formats) ? (int)($number * pow(1024, array_search($format, $formats) + 1)) : (int)$from;
	}

	static function translit ($str) {
		$str = (string)$str;

		$replace = [
			'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
			'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
			'х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'i','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
			' '=>'-',
			'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'Yo','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'Y',
			'К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F',
			'Х'=>'H','Ц'=>'Ts','Ч'=>'CH','Ш'=>'Sh','Щ'=>'Shch','Ъ'=>'','Ы'=>'I','Ь'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya',
		];

		$str = strtr($str, $replace);

		return $str;
	}

	static function makeSafe($file) {
		$file = rtrim(self::translit($file), '.');
		$regex = ['#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#'];
		return trim(preg_replace($regex, '', $file));
	}

	/**
	 * Check by mimetype what file is image
	 *
	 * @param string $path
	 *
	 * @return bool
	 */
	static function isImage($path) {
		try {
			if (!function_exists('exif_imagetype')) {
				function exif_imagetype($filename) {
					if ((list(, , $type) = getimagesize($filename)) !== false) {
						return $type;
					}

					return false;
				}
			}

			return in_array(exif_imagetype($path), [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP]);
		} catch (\Exception $e) {
			return false;
		}
	}
}