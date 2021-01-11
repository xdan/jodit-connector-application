<?php
/** @var object $scenario */
$I = new ApiTester($scenario);

$I->wantTo('Get all folders from all sources');
$I->sendGET('?action=folders');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesXpath('//data/sources/0/folders');
$I->seeResponseJsonMatchesXpath('//data/sources/1/folders');
