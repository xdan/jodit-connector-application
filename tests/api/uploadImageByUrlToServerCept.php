<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check uploading remote image from another site');

$I->sendGet('?action=fileUploadRemote&source=test&url=' . urlencode('https://xdsoft.net/jodit/stuf/icon-joomla.png1'));
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 400
    ]
]);

$I->sendGet('?action=fileUploadRemote&source=test&url=' . urlencode('icon-joomla.png'));
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 400
    ]
]);


$I->sendGet('?action=fileUploadRemote&source=test&url=' . urlencode('https://xdsoft.net/jodit/files/artio.jpg'));
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);

$I->seeResponseJsonMatchesXpath('//data/newfilename');


$I->sendGet('?action=fileRemove&source=test&name=artio.jpg');
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => true,
    "data" => [
        "code" => 220
    ]
]);





