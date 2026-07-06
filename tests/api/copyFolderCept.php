<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

require_once __DIR__ . '/../../src/Helper.php';

$I = new ApiTester($scenario);
$files_root = realpath(__DIR__ . '/../files') . '/';

$I->wantTo('Check copying folder recursively');

// A previous aborted run may have left the working folders behind —
// start from a clean state instead of failing on the leftovers
foreach ([$files_root . 'folder3', $files_root . 'folder1/folder3'] as $dir) {
	if (is_dir($dir)) {
		Jodit\Helper::removeDirectory($dir);
	}
}

$I->assertFileNotExists($files_root . 'folder3');
Jodit\Helper::copy($files_root . 'images', $files_root . 'folder3');
$I->assertFileExists($files_root . 'folder3');

$I->sendGet('?action=folderCopy&source=test&from=folder3&path=folder1');

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

// The original stays, the copy contains the whole tree
$I->assertFileExists($files_root . 'folder3');
$I->assertFileExists($files_root . 'folder1/folder3');
$I->assertFileExists(
	$files_root . 'folder1/folder3/david-suarez-d81WD5Q87E0-unsplash.jpg'
);

// A folder can not be copied into itself
$I->sendGet('?action=folderCopy&source=test&from=folder3&path=folder3');

$I->seeResponseContainsJson([
	'success' => false,
	'data' => [
		'code' => 400,
	],
]);

// Cleanup
$I->sendGet('?action=folderRemove&source=test&name=folder3&path=folder1');

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

$I->sendGet('?action=folderRemove&source=test&name=folder3&path=');

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

$I->assertFileNotExists($files_root . 'folder3');
$I->assertFileNotExists($files_root . 'folder1/folder3');
