<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check uploading remote image from another site');

// A URL that resolves but has nothing behind it — the guard is opted out of
// here so the failure comes from the download itself
$I->sendGet('?action=fileUploadRemote&source=test&custom_config=%7B%22allowPrivateNetworkUploads%22%3Atrue%7D&url=' . urlencode('http://127.0.0.1:8082/no-such-file.png'));
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


// Without an explicit opt-in the SSRF guard must refuse a private address
$I->sendGet('?action=fileUploadRemote&source=test&url=' . urlencode('http://127.0.0.1:8082/artio.jpg'));
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 403
    ]
]);

$I->sendGet('?action=fileUploadRemote&source=test&custom_config=%7B%22allowPrivateNetworkUploads%22%3Atrue%7D&url=' . urlencode('http://127.0.0.1:8082/artio.jpg'));
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





