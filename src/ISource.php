<?php

namespace Jodit;


use Jodit\components\Config;

/**
 * Interface ISource
 * @package Jodit\sources
 */
abstract class ISource extends Config {
	/**
	 * @return mixed
	 */
	abstract public function items();

	/**
	 * @return mixed
	 */
	abstract public function folders();

	/**
	 * @param string $file
	 * @return bool
	 */
	abstract public function isExcluded($file);


	/**
	 * @param string $path
	 * @return mixed
	 */
	abstract public function tree($path);


	/**
	 * @param $fromName
	 * @return mixed
	 */
	abstract protected function movePath($fromName);

	/**
	 * @param string $fromName
	 * @param string $newName
	 * @return mixed
	 */
	abstract public function renamePath(
		$fromName,
		$newName
	);

	/**
	 * @param string $path
	 * @return mixed
	 */
	abstract public function fileRemove($path);

	/**
	 * @param $name
	 * @return mixed
	 */
	abstract public function folderRemove($name);
}
