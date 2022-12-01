<?php
declare(strict_types=1);

namespace Jodit\interfaces;


/**
 * Interface IFile
 * @package Jodit\interfaces
 */
abstract class IFile {
	abstract public function isGoodFile(ISource $source): bool;
	abstract public function isSafeFile(ISource $source): bool;

	abstract public function isDirectory(): bool;

	abstract public function remove(): bool;
	abstract public function send(): void;

	abstract public function getPath(): string;

	abstract public function getFolder(): string;

	abstract public function getName(): string;

	abstract public function getExtension(): string;

	abstract public function getBasename(): string;

	abstract public function getSize(): int;

	abstract public function getTime(): int;

	abstract public function getPathByRoot(ISource $source): string;

	abstract public function isImage(): bool;

	abstract public function isSVGImage(): bool;
}
