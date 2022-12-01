<?php
declare(strict_types=1);

namespace Jodit\interfaces;

class ISourceFolders {
	public string $baseurl;
	public string $path = '';
	public string $name = '';
	public string $title = '';

	/**
	 * @var string[]
	 */
	public $folders = [];

	/**
	 * @param string[] $folders
	 */
	function __construct(
		string $name,
		string $title,
		string $baseurl,
		string $path,
		$folders = []
	) {
		$this->name = $name;
		$this->title = $title;
		$this->baseurl = $baseurl;
		$this->path = $path;
		$this->folders = $folders;
	}
}
