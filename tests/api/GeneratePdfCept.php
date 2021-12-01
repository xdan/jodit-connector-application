<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check generate pdf from HTML');

$I->sendGet('?action=generatePdf&html=' . urlencode('<strong>Hello world</strong>'));

$I->seeResponseCodeIs(HttpCode::OK); // 200
$I->seeHttpHeader('Content-Type', 'application/pdf');
$I->seeHttpHeader('Content-Length', '1137');
$I->seeHttpHeader('Content-Disposition', 'attachment; filename="document.pdf"');
