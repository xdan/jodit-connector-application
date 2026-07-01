<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Block SSRF on fileUploadRemote (private hosts / non-http schemes)');

// Loopback IP.
$I->sendGet(
	'?action=fileUploadRemote&source=test&url=' .
		urlencode('http://127.0.0.1/x.png')
);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
	'success' => false,
	'data' => ['code' => 403],
]);

// localhost hostname.
$I->sendGet(
	'?action=fileUploadRemote&source=test&url=' .
		urlencode('http://localhost/x.png')
);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
	'success' => false,
	'data' => ['code' => 403],
]);

// Private range.
$I->sendGet(
	'?action=fileUploadRemote&source=test&url=' .
		urlencode('http://169.254.169.254/latest/meta-data/')
);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
	'success' => false,
	'data' => ['code' => 403],
]);

// Non-http(s) scheme -> rejected as a bad request.
$I->sendGet(
	'?action=fileUploadRemote&source=test&url=' .
		urlencode('file:///etc/passwd')
);
$I->seeResponseIsJson();
$I->seeResponseContainsJson([
	'success' => false,
	'data' => ['code' => 400],
]);
