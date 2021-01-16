<?php
namespace Jodit\actions;

use Jodit\Config;
use Exception;


/**
 * Trait Files
 */
trait Files {
	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Load all files from folder ore source or sources
	 * @throws Exception
	 */
	public function actionFiles() {
		$sources = [];

		foreach ($this->config->getSources() as $source) {
			$sourceData = $this->read($source);
			$sourceData->name = $source->sourceName;
			$sources[] = $sourceData;
		}

		return ['sources' => $sources];
	}

	/**
	 * @param $source
	 * @return mixed
	 */
	abstract function read($source);
}
