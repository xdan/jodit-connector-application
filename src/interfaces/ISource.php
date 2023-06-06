<?php
declare(strict_types=1);

namespace Jodit\interfaces;

use Jodit\components\Config;
use Jodit\interfaces\IFile;
use Jodit\interfaces\ISourceItem;
use Jodit\interfaces\ISourceFolders;

/**
 * Interface ISource
 * @package Jodit\interfaces
 */
abstract class ISource extends Config {
	public string $name;

	abstract public function items(): ISourceItem;
	abstract public function folders(): ISourceFolders;

	abstract public function makeFolder(string $path): void;

	abstract public function makeThumb(IFile $file, int &$countThumbs): IFile;

	abstract public function isExcluded(string $file): bool;

	abstract protected function movePath(string $fromName): void;

	abstract public function renamePath(
		string $fromName,
		string $newName
	): void;

	abstract public function fileRemove(string $target): void;

	abstract public function fileDownload(string $target): void;

	abstract public function folderRemove(string $name): void;

	abstract public function resolveFileByUrl(string $url): ?IResolveFile;

	abstract public function makeFile(
		string $path,
		string $content = null
	): IFile;
}
