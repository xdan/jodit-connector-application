<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check moving file to another directory');

$I->sendGet('?action=fileMove&source=test&from=artio.jpg&path=folder1');

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220,
    ]
]);


$I->sendGet('?action=fileMove&source=test&path=&from=folder1/artio.jpg');



