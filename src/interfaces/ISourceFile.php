<?php
declare(strict_types=1);

namespace Jodit\interfaces;

class ISourceFile {
	public string $file;
	public string $name;
	public string $type;
	public ?string $thumb;

	/**
	 * @var false|string
	 */
	public $changed;

	/**
	 * @var ?string
	 */
	public $size;

	public bool $isImage = false;

	function __construct(string $file, string $name, string $type) {
		$this->file = $file;
		$this->name = $name;
		$this->type = $type;
	}
}
