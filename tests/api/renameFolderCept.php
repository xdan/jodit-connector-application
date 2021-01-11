<?php
/** @var object $scenario */
$I = new ApiTester($scenario);
$files_root = realpath(__DIR__ . '/../files') . '/';

$I->wantTo('Check rename file');

$I->sendGET('?action=folderRename&source=test&name=folder1&path=&newname=folder2');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);

$I->assertFileExists($files_root . 'folder2');

$I->sendGET('?action=folderRename&source=test&name=folder1&path=&newname=folder2');

$I->seeResponseContainsJson([
	"success" => false,
	"data" => [
		"code" => 404,
	]
]);


$I->sendGET('?action=folderRename&source=test&name=folder2&path=&newname=ceicom');

$I->seeResponseContainsJson([
	"success" => false,
	"data" => [
		"code" => 400,
	]
]);

$I->sendGET('?action=folderRename&source=test&name=folder2&path=&newname=folder1');


$I->assertFileExists($files_root . 'folder1');



