<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Save an edited image (imageSave)');

$name = 'edited' . rand(10000, 20000);

// Happy path: save the uploaded (edited) image bytes under a new name.
$I->sendPost(
	'',
	[
		'action' => 'imageSave',
		'source' => 'test',
		'newname' => $name,
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
		'newPath' => 'http://localhost:8081/files/' . $name . '.png',
	],
]);

// Error path: no image uploaded.
$I->sendPost('', [
	'action' => 'imageSave',
	'source' => 'test',
	'newname' => 'no-file',
]);

$I->seeResponseIsJson();
$I->seeResponseContainsJson([
	'success' => false,
]);

// Cleanup: remove the saved file.
$I->sendGet('?action=fileRemove&source=test&name=' . $name . '.png');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);
