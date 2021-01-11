<?php
$I = new ApiTester($scenario);

$I->wantTo('Check moving folder to another directory');

$files_root = realpath(__DIR__ . '/../files') . '/';

if (!file_exists($files_root . 'testMove')) {
    mkdir($files_root . 'testMove', 0777);
}

file_put_contents($files_root . 'testMove/test.txt', 'test');

$I->sendGET('?action=folderMove&source=test&from=testMove&path=folder1');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200

$I->seeResponseIsJson();


$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);

$I->assertFileExists($files_root . 'folder1/testMove/test.txt');
unlink($files_root . 'folder1/testMove/test.txt');
rmdir($files_root . 'folder1/testMove');


