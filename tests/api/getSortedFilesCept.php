<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$files_root = realpath(__DIR__ . '/../files') . '/';
file_put_contents($files_root . 'summer.txt', 'summer');

$I->wantTo('Get all items with some sort');
$I->sendGet('?action=files&mods[sortBy]=changed-asc');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

$changedAsc = [
	'Hello-world.docx',
	'droid-sans-mono.zip',
	'test.txt',
	'test.xlsx',
	'psalm.jpg',
	'test15510.jpg',
	'check.svg',
	'pexels-yuri-manei-2337448.jpg',
	'regina.png',
	'test.csv',
	'summer.txt',
];

foreach ($changedAsc as $index => $title) {
	list($file) = $I->grabDataFromResponseByJsonPath(
		'$.data.sources[?(@.name=="test")].files[' . $index . '].file'
	);
	$I->assertEquals($file, $title);
}

$I->sendGet('?action=files&mods[sortBy]=changed-desc');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$changedDesc = array_reverse($changedAsc);

foreach ($changedDesc as $index => $title) {
	list($file) = $I->grabDataFromResponseByJsonPath(
		'$.data.sources[?(@.name=="test")].files[' . $index . '].file'
	);
	$I->assertEquals($file, $title);
}

$I->sendGet('?action=files&mods[sortBy]=name-asc');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$nameAsc = array_reverse($changedAsc);
sort($nameAsc);

foreach ($nameAsc as $index => $title) {
	list($file) = $I->grabDataFromResponseByJsonPath(
		'$.data.sources[?(@.name=="test")].files[' . $index . '].file'
	);
	$I->assertEquals($file, $title);
}

$I->sendGet('?action=files&mods[sortBy]=name-desc');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$nameAsc = array_reverse($changedAsc);
rsort($nameAsc);

foreach ($nameAsc as $index => $title) {
	list($file) = $I->grabDataFromResponseByJsonPath(
		'$.data.sources[?(@.name=="test")].files[' . $index . '].file'
	);
	$I->assertEquals($file, $title);
}

unlink($files_root . 'summer.txt');
