<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check uploading files with same name should rename file');

$I->sendPost(
	'?',
	[
		'action' => 'fileUpload',
		'source' => 'test',
	],
	[
		'files' => [
			realpath(__DIR__ . '/../files/regina.png'),
			realpath(__DIR__ . '/../test.png'),
			realpath(__DIR__ . '/../files/test.csv'),
		],
	]
);

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
		'files' => ['regina(1).png', 'test.png', 'test(1).csv'],
		'isImages' => [true, true, false],
	],
]);

$I->sendPost(
	'?',
	[
		'action' => 'fileUpload',
		'source' => 'test',
	],
	[
		'files' => [
			realpath(__DIR__ . '/../files/regina.png'),
			realpath(__DIR__ . '/../test.png'),
			realpath(__DIR__ . '/../files/test.csv'),
		],
	]
);

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
		'files' => ['regina(2).png', 'test(1).png', 'test(2).csv'],
		'isImages' => [true, true, false],
	],
]);

foreach (
	[
		'regina(1).png',
		'test.png',
		'test(1).csv',
		'regina(2).png',
		'test(1).png',
		'test(2).csv',
	]
	as $file
) {
	$I->sendGet('?action=fileRemove&source=test&name=' . $file);
	$I->seeResponseContainsJson([
		'success' => true,
	]);
}

$I->sendPost(
	'?custom_config=' .
		rawurlencode(
			json_encode([
				'saveSameFileNameStrategy' => 'error',
			])
		),
	[
		'action' => 'fileUpload',
		'source' => 'test',
	],
	[
		'files' => [realpath(__DIR__ . '/../files/regina.png')],
	]
);

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => false,
	'data' => [
		'code' => 400,
		'messages' => ['File already exists'],
	],
]);
