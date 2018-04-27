<?php
$I = new ApiTester($scenario);

$I->wantTo('Get all files root and config without source field');
$I->sendGET('?action=files&custom_config=' . rawurlencode(json_encode([
	'sources' => null
])));

$I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
$I->seeResponseIsJson();

$I->seeResponseContainsJson([
	"success" => true,
	"data" => [
		"code" => 220
	]
]);

$I->seeResponseJsonMatchesXpath('//data/sources/default/files/file');
