<?php
declare(strict_types=1);

namespace Jodit\interfaces;

use claviska\SimpleImage;

/**
 * @property SimpleImage $img
 * @property string $path
 * @property string $file
 * @property object $box
 * @property string $newname
 * @property int $width
 * @property int $height
 */
class ImageInfo {
	public string $path;
	public string $file;
	public object $box;
	public string $newname;
	public SimpleImage $img;
	public int $width;
	public int $height;

	public function __construct (array $params) {
		$this->path = $params['path'];
		$this->file = $params['file'];
		$this->box = $params['box'];
		$this->img = $params['img'];
		$this->newname = $params['newname'];
		$this->width = $params['width'];
		$this->height = $params['height'];
	}
}
