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

// POST only: a GET must be rejected.
$I->sendGet('?action=imageSave&source=test&newname=x.png');
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
	'success' => false,
	'data' => [
		'code' => 406,
	],
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

// Overwriting a file must invalidate its cached thumbnail, so the file browser
// regenerates a fresh one (otherwise an edited image keeps showing the old
// thumbnail — the bug this fixes).
$root = realpath(__DIR__ . '/../files') . '/';
$thumbDir = $root . '_thumbs';
$overwrite = 'thumbtest' . rand(10000, 20000) . '.png';

copy($root . 'regina.png', $root . $overwrite);

if (!is_dir($thumbDir)) {
	mkdir($thumbDir);
}

// Seed a stale cached thumbnail as a previous listing would have generated.
copy($root . 'regina.png', $thumbDir . '/' . $overwrite);
$I->assertFileExists($thumbDir . '/' . $overwrite);

$I->sendPost(
	'',
	[
		'action' => 'imageSave',
		'source' => 'test',
		'name' => $overwrite,
	],
	[
		'files' => [realpath(__DIR__ . '/../files/regina.png')],
	]
);

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseContainsJson(['success' => true]);

// The stale thumbnail must be gone.
$I->assertFileDoesNotExist($thumbDir . '/' . $overwrite);

// Cleanup the file we created for this check.
if (file_exists($root . $overwrite)) {
	unlink($root . $overwrite);
}
