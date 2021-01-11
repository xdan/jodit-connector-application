<?php
require __DIR__ . "/../../src/Helper.php";

$I = new ApiTester($scenario);
$files_root = realpath(__DIR__ . '/../files') . '/';

$I->wantTo('Check remove folder');

$I->assertFileNotExists($files_root . 'folder2');
Jodit\Helper::copy($files_root . 'ceicom', $files_root . 'folder2');
$I->assertFileExists($files_root . 'folder2');

$I->sendGET('?action=folderRemove&source=test&name=folder2&path=');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);

$I->assertFileNotExists($files_root . 'folder2');

$I->sendGET('?action=folderRemove&source=test&name=folder2&path=&newname=folder2');

$I->seeResponseContainsJson([
	"success" => false,
	"data" => [
		"code" => 404,
	]
]);

$I->assertFileNotExists($files_root . 'folder2');

