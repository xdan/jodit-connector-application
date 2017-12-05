<?php
namespace Jodit;

/**
 * Class Source
 * @package jodit
 * @property string $baseurl
 * @property string $root
 * @property number $maxFileSize
 * @property number $quality
 * @property string $thumbFolderName
 * @property string $defaultPermission
 */
class Source {
	private $data = [];
	/**
	 * @var \Jodit\Config
	 */
	private $defaultOptions;

	function __get($key) {
		if (!empty($this->data->{$key})) {
			return $this->data->{$key};
		}
		if ($this->defaultOptions->{$key}) {
			return $this->defaultOptions->{$key};
		}

		throw new \ErrorException('Option ' . $key . ' not set', 501);
	}

	function __construct($data, $defaultOptuions) {
		$this->data           = (object)$data;
		$this->defaultOptions = $defaultOptuions;
	}
}