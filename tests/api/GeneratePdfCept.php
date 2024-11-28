<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check generate pdf from HTML');

$I->sendGet('?action=generatePdf&html=' . urlencode('<p>asdsadsadas</p>
<p>das</p>
<p>d</p>
<p>sada<img src="https://xdsoft.net/jodit/finder/files/pexels-keli-santos-1853268.jpeg" width="300px"></p>'));

$I->seeHttpHeader('Content-Type', 'application/pdf');
$I->seeHttpHeader('Content-Length', '224798');
$I->seeHttpHeader('Content-Disposition', 'attachment; filename="document.pdf"');

$files_root = realpath(__DIR__ . '/../files') . '/';
$file_path = $files_root . 'doc.pdf';
file_put_contents($file_path, $I->grabResponse());
try {
	$I->assertEquals(filesize($file_path), 224798);
} finally {
	unlink($file_path);
}
