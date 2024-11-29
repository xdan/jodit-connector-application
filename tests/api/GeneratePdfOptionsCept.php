<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check generate pdf from HTML with some font');

$body = [
	'action' => 'generatePdf',
	'options' => [
		'defaultFont' => 'Arial',
	],
	'html' => '<p>asdsadsadas</p>
	 <p>das</p>
	 <p>d</p>
	 <p>sada<img src="https://xdsoft.net/jodit/finder/files/pexels-keli-santos-1853268.jpeg" width="300px"></p>',
];

$I->sendPost('', $body);

// Incorrect font
$I->seeResponseContainsJson([
    "success" => false,
    "data" => [
        "code" => 400
    ]
]);

$body['options']['defaultFont'] = 'helvetica';
$I->sendPost('', $body);

$I->seeHttpHeader('Content-Type', 'application/pdf');
$I->seeHttpHeader('Content-Length', '224795');

$body['options']['format'] = 'A3';
$I->sendPost('', $body);

$I->seeHttpHeader('Content-Type', 'application/pdf');
$I->seeHttpHeader('Content-Length', '224799');

$body['options']['page_orientation'] = 'landscape';
$I->sendPost('', $body);

$I->seeHttpHeader('Content-Type', 'application/pdf');
$I->seeHttpHeader('Content-Length', '224797');
