<?php
use Jodit\Application;
use Jodit\Consts;

/**
 * Class JoditRestTestApplication
 */
class JoditRestTestApplication extends Application {
	function checkAuthentication() {
		if (
			isset($_GET['auth'])
		) {
			throw new ErrorException(
				'Need authorization',
				Consts::ERROR_CODE_FORBIDDEN
			);
		}
	}
}
