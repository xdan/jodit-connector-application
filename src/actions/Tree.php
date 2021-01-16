<?php
namespace Jodit\actions;

use Jodit\Config;

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

		$this->getTree($path, $source);
	}

	/**
	 * @param string $path
	 * @param Config $source
	 * @return array
	 */
	private function getTree($path, $source) {
		$dir = opendir($path);
		$tree = [];

		while ($file = readdir($dir)) {
			if (is_dir($path . $file) and !$this->isExcluded($file)) {
				$tree[] = [
					'name' => $file,
					'path' => $path . $file,
					'sourceName' => $source->sourceName,
					'children' => $this->getTree($path . $file, $source)
				];
			}
		}

		return $tree;
	}

	/**
	 * @param string $file
	 * @return bool
	 */
	private function isExcluded($file) {
		return $file === '.' ||
			$file === '..' ||
			($this->config->createThumb &&
				$file === $this->config->thumbFolderName) ||
			in_array($file, $this->config->excludeDirectoryNames);
	}
}
