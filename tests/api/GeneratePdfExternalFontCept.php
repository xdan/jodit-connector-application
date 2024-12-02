<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check generate pdf from HTML with some Korean font');

$body = [
	'action' => 'generatePdf',
	'html' => '
		<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
		<style>

		body {
          font-family: "Noto Sans KR", sans-serif;
          font-optical-sizing: auto;
          font-weight: 400;
          font-style: normal;
        }
        </style>
	<p class="noto-sans-kr">모든 인류 구성원의 천부</p>
	 <p class="noto-sans-kr">das</p>
	 <p class="noto-sans-kr">d</p>
	 <p class="noto-sans-kr">sada<img src="https://xdsoft.net/jodit/finder/files/pexels-keli-santos-1853268.jpeg" width="300px"></p>',
];

$I->sendPost('', $body);

$I->seeHttpHeader('Content-Type', 'application/pdf');
$I->seeHttpHeader('Content-Length', '228394');

$files_root = realpath(__DIR__ . '/../files') . '/';
$file_path = $files_root . 'doc.pdf';
file_put_contents($file_path, $I->grabResponse());
try {
	$I->assertEquals(filesize($file_path), 228394);
} finally {
	unlink($file_path);
}
