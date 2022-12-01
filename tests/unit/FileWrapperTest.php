<?php

use Jodit\components\File;

class FileWrapperTest extends \Codeception\Test\Unit {
	/**
	 * @var UnitTester
	 */
	protected $tester;
	protected File $file;

	protected function _before() {
		$this->file = File::create(
			realpath(__DIR__ . '/../files/folder1/алина test15510.jpg')
		);
	}

	protected function _after() {
	}

	// tests
	public function testGetName() {
		$this->assertEquals('алина test15510.jpg', $this->file->getName());
	}

	// tests
	public function testGetBaseName() {
		$this->assertEquals('алина test15510', $this->file->getBasename());
	}

	public function testGetExtension() {
		$this->assertEquals('jpg', $this->file->getExtension());
	}

	public function testGetPath() {
		$this->assertEquals(
			realpath(__DIR__ . '/../files/folder1/алина test15510.jpg'),
			$this->file->getPath()
		);
	}
}
