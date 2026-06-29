<?php

use Codeception\Test\Unit;
use Jodit\Helper;

/**
 * Class HelperTest
 */
class HelperTest extends Unit {
	/**
	 * @var UnitTester $tester
	 */
	protected $tester;

	// tests
	public function testUpperaze() {
		$this->assertEquals('FILE_UPLOAD', Helper::upperize('fileUpload'));
		$this->assertEquals(
			'FILE_UPLOAD',
			Helper::upperize('FileUpload')
		);
		$this->assertEquals(
			'FIL_EUPLOAD',
			Helper::upperize('FilEUpload')
		);
		$this->assertEquals('FILE', Helper::upperize('File'));
	}

	public function testCamelCase() {
		$this->assertEquals(
			'FileUpload',
			Helper::camelCase('FILE_UPLOAD')
		);
		$this->assertEquals('File', Helper::camelCase('FILE'));
	}

	public function testSlugify() {
		$this->assertEquals(
			'privet-mir',
			Helper::slugify('привет мир')
		);
	}

	public function testNormalizePath() {
		$this->assertEquals(
			'C:/sdfsdf/',
			Helper::normalizePath('C:\\sdfsdf\\')
		);
		$this->assertEquals(
			'C:/sdfsdf/',
			Helper::normalizePath('C:/sdfsdf/')
		);
		$this->assertEquals(
			'C:/sdfsdf/',
			Helper::normalizePath('C://\\sdfsdf/')
		);
	}

	public function testConvertToBytes() {
		// Single-letter units (PHP ini style) — the regression: "15M" used to
		// be parsed as 15 bytes, which rejected every upload.
		$this->assertEquals(15 * 1024 * 1024, Helper::convertToBytes('15M'));
		$this->assertEquals(2 * 1024, Helper::convertToBytes('2K'));
		$this->assertEquals(1024 * 1024 * 1024, Helper::convertToBytes('1G'));

		// Two-letter units, any case.
		$this->assertEquals(15 * 1024 * 1024, Helper::convertToBytes('15MB'));
		$this->assertEquals(8 * 1024 * 1024, Helper::convertToBytes('8mb'));
		$this->assertEquals(1024 * 1024 * 1024, Helper::convertToBytes('1gb'));

		// Decimals and plain byte counts.
		$this->assertEquals(
			(int) (11.2 * 1024 * 1024),
			Helper::convertToBytes('11.2mb')
		);
		$this->assertEquals(1024, Helper::convertToBytes('1024'));
		$this->assertEquals(2048, Helper::convertToBytes(2048));
	}
}
