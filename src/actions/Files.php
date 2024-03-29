<?php
declare(strict_types=1);

namespace Jodit\actions;

use Jodit\components\Config;
use Exception;


/**
 * Trait Files
 */
trait Files {
	public Config $config;

	/**
	 * Load all files from folder ore source or sources
	 * @throws Exception
	 */
	public function actionFiles(): array {
		$sources = [];

		foreach ($this->config->getSources() as $source) {
			$sourceData = $source->items();
			$sourceData->name = $source->sourceName;
			$sources[] = $sourceData;
		}

		return ['sources' => $sources];
	}
}
