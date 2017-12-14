<?php


class HelperTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    // tests
    public function testUpperaze()
    {
		$this->assertEquals('FILE_UPLOAD', \Jodit\Helper::Upperize('fileUpload'));
		$this->assertEquals('FILE_UPLOAD', \Jodit\Helper::Upperize('FileUpload'));
		$this->assertEquals('FILE', \Jodit\Helper::Upperize('File'));
    }
}