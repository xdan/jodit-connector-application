<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Load an image as a base64 data URL (imageLoad)');

$I->sendPost('', [
	'action' => 'imageLoad',
	'source' => 'test',
	'name' => 'pexels-yuri-manei-2337448.jpg',
]);

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
		'name' => 'pexels-yuri-manei-2337448.jpg',
	],
]);

$I->seeResponseMatchesJsonType([
	'data' => [
		'content' => 'string:regex(~^data:image/jpeg;base64,~)',
	],
]);

// Missing file -> not found.
$I->sendPost('', [
	'action' => 'imageLoad',
	'source' => 'test',
	'name' => 'does-not-exist.png',
]);

$I->seeResponseIsJson();
$I->seeResponseContainsJson([
	'success' => false,
]);
