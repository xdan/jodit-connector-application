<?php
namespace jodit;

/**
 * Class Config
 * @property \jodit\Source[] $sources
 * @property string $thumbFolderName
 * @property bool $allowCrossOrigin
 * @property bool $createThumb
 * @property string[] $excludeDirectoryNames
 * @property number $quality
 * @property string $datetimeFormat
 * @package jodit
 */
class Config {
	private $data = [];
	private $defaultOptuions = [];

	/**
	 * @var bool
	 */
	public $debug = true; // must be true

	/**
	 * @var \jodit\Source[]
	 */
	public $sources = [];

	/**
	 * @var string
	 */
	public $datetimeFormat = 'm/d/Y g:i A';

	/**
	 * @var int
	 */
	public $quality = 90;

	/**
	 * @var int
	 */
	public $defaultPermission = 0775;

	/**
	 * @var bool
	 */
	public $createThumb = true;

	/**
	 * @var string
	 */
	public $thumbFolderName = '_thumbs';

	/**
	 * @var string[]
	 */
	public $excludeDirectoryNames = ['.tmb', '.quarantine'];

	/**
	 * @var string
	 */
	public $maxFileSize = '8mb';

	/**
	 * @var bool
	 */
	public $allowCrossOrigin = false;

	/**
	 * @var string
	 */
	public $baseurl = '';

	/**
	 * @var string
	 */
	public $root = '';

	/**
	 * @var string
	 */
	public $extensions = ['jpg', 'png', 'gif', 'jpeg'];



	function __get($key) {
		if (!empty($this->data->{$key})) {
			return $this->data->{$key};
		}
		if ($this->defaultOptuions->{$key}) {
			return $this->defaultOptuions->{$key};
		}

		throw new \ErrorException('Option ' . $key . ' not set', 501);
	}
	function __construct($data, $defaultOptuions = null) {
		$this->data = (object)$data;

		foreach ($this->data as $key => $value) {
			switch ($key) {
				case 'sources':
					foreach ($value as $source) {
						$this->sources[] = new Source($source, $this->data);
					}
					break;
				default:
					if (property_exists($this, $key)) {
						$this->{$key} = $value;
					}
			}
		}

		$this->defaultOptuions = (object)$defaultOptuions;
	}
}