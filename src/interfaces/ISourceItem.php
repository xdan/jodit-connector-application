<?php
declare(strict_types=1);

namespace Jodit\interfaces;

class ISourceItem {
	public string $name;
	public string $baseurl;
	public string $path = '';
	/**
	 * @var ISourceFile[]
	 */
	public array $files = [];

	/**
	 * @param ISourceFile[] $files
	 */
	function __construct(string $baseurl, string $path, array $files = []){
		$this->baseurl = $baseurl;
		$this->path = $path;
		$this->files = $files;
	}
}
