<?php
/** @var \Codeception\Scenario $scenario */

use Codeception\Util\HttpCode;

$I = new ApiTester($scenario);

$I->wantTo('Check generate docx from HTML');

$I->sendGet('?action=generateDocx&html=' . urlencode('
<h1>test</h1>
<p>asdsadsadas</p>
<p>das</p>
<div style="page-break-after:always"></div>
<p>d</p>
<p>sada<img style="width:100px;height:100px" src="https://xdsoft.net/jodit/finder/files/pexels-bia-sousa-2603201.jpg" width="300px" height="500px"/></p>'));

$I->seeResponseCodeIs(HttpCode::OK); // 200
//$I->seeHttpHeader('Content-Type', 'application/octet-stream');
//$I->seeHttpHeader('Content-Length', '1721004');
//$I->seeHttpHeader('Content-Disposition', 'attachment;filename="document.docx"');
//file_put_contents('ddd.doc', $I->grabResponse());
