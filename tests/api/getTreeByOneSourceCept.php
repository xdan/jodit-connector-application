<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Get tree from Test source');
$I->sendGet('?action=folderTree&source=test&path=folder1');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
		'tree' => [
			[
				'name' => 'dddd',
				'children' => [
					[
						'name' => 'eeee'
					]
				],
			],
		],
	],
]);


$I->seeResponseJsonMatchesJsonPath('$.data.tree[?(@.sourceName=="test")]');
$I->seeResponseJsonMatchesJsonPath('$.data.tree[?(@.name=="dddd")]');
$I->seeResponseJsonMatchesJsonPath(
	'$.data.tree[?(@.name=="dddd")].children[?(@.name=="eeee")]'
);
$I->seeResponseJsonMatchesJsonPath(
	'$.data.tree[?(@.name=="dddd")].children[?(@.name=="eeee")].children'
);
