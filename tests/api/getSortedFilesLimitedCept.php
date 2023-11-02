<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$files_root = realpath(__DIR__ . '/../files') . '/';
file_put_contents(
	$files_root . 'regina-copy.png',
	file_get_contents($files_root . 'regina.png')
);

$I->wantTo('Get limited items with some sort');
$I->sendGet(
	'?action=files&mods[sortBy]=changed-asc&mods[foldersPosition]=top&mods[onlyImages]=true&mods[offset]=0&mods[limit]=10'
);
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

$changedAsc = [
	'psalm.jpg',
	'test15510.jpg',
	'check.svg',
	'pexels-yuri-manei-2337448.jpg',
	'regina.png',
	'regina-copy.png',
];

foreach ($changedAsc as $index => $title) {
	list($file) = $I->grabDataFromResponseByJsonPath(
		'$.data.sources[?(@.name=="test")].files[' . $index . '].file'
	);
	$I->assertEquals($file, $title);
}

$I->sendPost(
	'',
	[
		'action' => 'files',
		'mods' => [
			'sortBy' => 'changed-desc',
			'foldersPosition' => 'top',
			'onlyImages' => 'true',
			'offset' => 0,
			'limit' => 10,
		],
	]
);
$I->seeResponseCodeIs(HttpCode::OK); // 200

$changedDesc = array_reverse($changedAsc);

foreach ($changedDesc as $index => $title) {
	list($file) = $I->grabDataFromResponseByJsonPath(
		'$.data.sources[?(@.name=="test")].files[' . $index . '].file'
	);
	$I->assertEquals($file, $title);
}

unlink($files_root . 'regina-copy.png');
