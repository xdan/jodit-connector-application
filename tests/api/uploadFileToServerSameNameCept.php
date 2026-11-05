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
		'files' => ['regina1.png', 'test.png', 'test1.csv'],
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
		'files' => ['regina2.png', 'test1.png', 'test2.csv'],
		'isImages' => [true, true, false],
	],
]);

foreach (
	[
		'regina1.png',
		'test.png',
		'test1.csv',
		'regina2.png',
		'test1.png',
		'test2.csv',
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
		'code' => 409,
		'messages' => ['File already exists'],
	],
]);

// The client may pick the strategy for one upload with `onExists`
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
		'onExists' => 'replace',
	],
	[
		'files' => [realpath(__DIR__ . '/../files/regina.png')],
	]
);

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
		'files' => ['regina.png'],
	],
]);

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
		'onExists' => 'addNumber',
	],
	[
		'files' => [realpath(__DIR__ . '/../files/regina.png')],
	]
);

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
		'files' => ['regina1.png'],
	],
]);

$I->sendGet('?action=fileRemove&source=test&name=regina1.png');
$I->seeResponseContainsJson([
	'success' => true,
]);

// Unknown value is rejected before anything is written
$I->sendPost(
	'?',
	[
		'action' => 'fileUpload',
		'source' => 'test',
		'onExists' => 'overwrite',
	],
	[
		'files' => [realpath(__DIR__ . '/../files/regina.png')],
	]
);

$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => false,
	'data' => [
		'code' => 400,
		'messages' => [
			'Unknown onExists value, expected one of addNumber, replace, error',
		],
	],
]);

// Replacing on request needs the remove permission on that path
$I->sendPost(
	'?custom_config=' .
		rawurlencode(
			json_encode([
				'accessControl' => [
					[
						'role' => '*',
						'extensions' => '*',
						'path' => '/',
						'FILE_UPLOAD' => true,
						'FILE_REMOVE' => false,
					],
				],
			])
		),
	[
		'action' => 'fileUpload',
		'source' => 'test',
		'onExists' => 'replace',
	],
	[
		'files' => [realpath(__DIR__ . '/../files/regina.png')],
	]
);

$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => false,
	'data' => [
		'code' => 403,
		'messages' => ['Access denied'],
	],
]);
