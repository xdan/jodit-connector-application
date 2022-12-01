<?php
declare(strict_types=1);
/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */

namespace Jodit\components;

/**
 * Class Response
 * @package jodit
 */
class Response {
	public bool $success = true;
	public string $time;

	public object $data;

	public float $elapsedTime = 0;

	public function __construct () {
		$this->time = date('Y-m-d H:i:s');
		$this->data = (object)[
			'messages' => [],
			'code' => 220,
		];
	}
}
