<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Get all folders from Test source');
$I->sendGet('?action=folders&source=test&path=folder1');
$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
	],
]);

$I->seeResponseJsonMatchesJsonPath('$.data.sources[0].folders');
$I->dontSeeResponseJsonMatchesJsonPath('$.data.sources[1].folders');

$I->seeResponseContainsJson([
	'success' => true,
	'data' => [
		'code' => 220,
		'sources' => [
			json_decode(
				<<<JSON

            {
							"name": "test",
              "title": "Some files",
              "baseurl": "http://localhost:8081/files/",
              "path": "folder1/",
              "folders": [
				  			"..",
								"subfolder",
								"subfolder with \u043f\u0440\u043e\u0431\u0435\u043b"
							]
            }
JSON
				,
				true
			),
		],
	],
]);
