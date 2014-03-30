<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - Mr_CHISOL - Kachtus 2008
 * @license CC-GNU GPL v2
 */
defined( '_KACTALK_VALID' ) or die( 'Restricted Access!' );

class ktError extends Exception
{
	function __construct( $msg = '', $function = '', &$obj = null,
				$code = ktError::DEFAULT_ERROR )
	{
		$this->msg		= $msg;
		$this->function	= $function;
		$this->object	= $obj;
		parent::__construct( $msg, $code );
	}

	public function __toString()
	{
		$f = 'unknown function';

		if (!empty( $this->function )) {
			$f = $this->function . '()';
			if (substr( $f, 0, 2 ) == '::') {
				if (is_a( $this->object, 'ktObject' )) {
					$f = $this->object->_object_name . $f;
				} else {
					$f = get_class( $this->object ) . $f;
				}
			}
		} else if (isset( $this->object )) {
			if (is_a( $this->object, 'ktObject' )) {
				$f = $this->object->_object_name;
			} else {
				$f = get_class( $this->object );
			}
		}
		return 'KacTalk Error: ' . $this->msg . ' in ' . $f . '!';
	}

	protected $msg = '';
	protected $function = '';
	protected $object = null;

	const DEFAULT_ERROR	= 3000;
	const NOTSET		= 3001;
	const NOTIMP		= 3002;
	const UNEXP			= 3003;
	const NOTDEF		= 3004;
	const NOTDEC		= 3005;
	const _404			= 3006;
	const NOTFOUND		= ktError::_404;
	const MISSING		= 3007;
	const NULL			= 3008;
	const OUT_OF_RANGE	= 3009;
	const WRONGTYPE		= 3010;
	const DIV_BY_ZERO	= 3011;
	const IS_EMPTY		= 3012;
	const CONSTANT		= 3013;
	const UNKNOWN		  = 3014;
  const NOT_AVAILABLE = 3015;

	const REGEX_COULDNT_SET_PATTERN = 3030;

	const _MAX_ERROR_NO	= 3030;

	const NOERROR = 0;
};

?>
