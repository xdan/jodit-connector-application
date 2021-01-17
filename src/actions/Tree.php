<?php
namespace Jodit\actions;

use Jodit\components\Config;

/**
 * Trait Tree
 */
trait Tree {
	/**
	 * @var Config
	 */
	public $config;

	/**
	 * Load tree of directories
	 */
	public function actionFolderTree() {
		$source = $this->config->getSource($this->request->source);
		$path = $source->getPath();

		return $source->tree($path);
	}
}
