<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);
$files_root = realpath(__DIR__ . '/../files') . '/';

$I->wantTo('Check copying file to another directory');

$I->assertFileNotExists($files_root . 'folder1/test.txt');

$I->sendGet('?action=fileCopy&source=test&from=test.txt&path=folder1');

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

// The original stays, the copy appears in the destination
$I->assertFileExists($files_root . 'test.txt');
$I->assertFileExists($files_root . 'folder1/test.txt');

// A second copy into the same destination gets a " (N)" suffix
$I->sendGet('?action=fileCopy&source=test&from=test.txt&path=folder1');

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

$I->assertFileExists($files_root . 'folder1/test (1).txt');

// Cleanup
unlink($files_root . 'folder1/test.txt');
unlink($files_root . 'folder1/test (1).txt');
$I->assertFileNotExists($files_root . 'folder1/test.txt');
$I->assertFileNotExists($files_root . 'folder1/test (1).txt');

// Copying a missing file is an error
$I->sendGet('?action=fileCopy&source=test&from=does-not-exist.txt&path=folder1');

$I->seeResponseContainsJson([
	'success' => false,
	'data' => [
		'code' => 404,
	],
]);
