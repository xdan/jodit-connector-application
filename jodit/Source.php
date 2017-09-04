<?php
namespace jodit;

/**
 * Class Source
 * @package jodit
 * @property string $baseurl
 * @property number $maxFileSize
 * @property number $quality
 * @property string $thumbFolderName
 * @property string $defaultPermission
 */
class Source {
	private $data = [];
	private $defaultOptuions = [];
	function __get($key) {
		if (!empty($this->data->{$key})) {
			return $this->data->{$key};
		}
		if ($this->defaultOptuions->{$key}) {
			return $this->defaultOptuions->{$key};
		}

		throw new ErrorException('Option ' . $key . ' not set', 501);
	}
	function __construct($data, $defaultOptuions) {
		$this->data = (object)$data;
		$this->defaultOptuions = (object)$defaultOptuions;
	}
}