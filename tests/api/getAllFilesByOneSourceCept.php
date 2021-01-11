<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Get all files from all sources');
$I->sendGET('?action=files&source=test');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesJsonPath('$.data.sources[?(@.name=="test")].files[0].file');
$I->dontSeeResponseJsonMatchesJsonPath('$.data.sources[?(@.name=="folder1")].files[0].file');
