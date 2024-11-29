<?php
declare(strict_types=1);
/**
 * @package    jodit
 *
 * @author     Valeriy Chupurnov <chupurnov@gmail.com>
 * @license    GNU General Public License version 2 or later; see LICENSE
 * @link       https://xdsoft.net/jodit/
 */
namespace Jodit;

/**
 * Class Consts
 * @package Jodit
 */
class Consts {
	const ERROR_CODE_NOT_IMPLEMENTED = 501;
	const ERROR_CODE_IS_NOT_WRITEBLE = 424;
	const ERROR_CODE_NO_FILES_UPLOADED = 422;
	const ERROR_CODE_FORBIDDEN = 403;
	const ERROR_CODE_BAD_REQUEST = 400;
	const ERROR_CODE_NOT_EXISTS = 404;
	const ERROR_CODE_NOT_ACCEPTABLE = 406;
	const ERROR_CODE_FAILED = 424;
	const DS = DIRECTORY_SEPARATOR;

	/**
   * @var array The list of the core fonts
   */
  static $coreFonts = [
  	'courier',
  	'courier-bold',
  	'courier-oblique',
  	'courier-boldoblique',
  	'helvetica',
  	'helvetica-bold',
  	'helvetica-oblique',
  	'helvetica-boldoblique',
  	'times-roman',
  	'times-bold',
  	'times-italic',
  	'times-bolditalic',
  	'symbol',
  	'zapfdingbats'
  ];
}
