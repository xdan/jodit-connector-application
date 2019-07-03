<?php
$I = new ApiTester($scenario);
$files_root = realpath(__DIR__ . '/../files') . '/';

$I->wantTo('Check rename file');

$I->sendGET('?action=fileRename&source=test&name=artio.jpg&path=&newname=started.jpg');

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);

$I->assertFileExists($files_root . 'started.jpg');

$I->sendGET('?action=fileRename&source=test&name=started.jpg&path=&newname=artio.jpg.php');
$I->assertFileExists($files_root . 'artio.jpg.php.jpg');

$I->sendGET('?action=fileRename&source=test&name=artio.jpg.php.jpg&path=&newname=artio.jpg');

$I->assertFileNotExists($files_root . 'artio.jpg.php');
$I->assertFileNotExists($files_root . 'artio.jpg.php.jpg');
$I->assertFileNotExists($files_root . 'started.jpg');
$I->assertFileExists($files_root . 'artio.jpg');



