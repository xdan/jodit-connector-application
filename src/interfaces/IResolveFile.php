<?php
declare(strict_types=1);

namespace Jodit\interfaces;

class IResolveFile {
	public string $path;
	public string $name;
	public string $source;
	public array $messages;
	public int $code;

	function __construct(string $path, string $name , string $source){
		$this->path = $path;
		$this->name = $name;
		$this->source = $source;
	}
}
