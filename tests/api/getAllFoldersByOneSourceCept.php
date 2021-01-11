<?php
$I = new ApiTester($scenario);

$I->wantTo('Get all folders from Test source');
$I->sendGET('?action=folders&source=test&path=folder1');
$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesJsonPath('$.data.sources[0].folders');
$I->dontSeeResponseJsonMatchesJsonPath('$.data.sources[1].folders');
