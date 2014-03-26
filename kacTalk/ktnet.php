<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - Mr_CHISOL - Kachtus 2008
 * @license CC-GNU GPL v2
 */
defined( '_KACTALK_VALID' ) or die( 'Restricted Access!' );

class ktNet extends ktObject
{
	const KACTALK			= 4203142;
	const PROTO_KACTALK		= ktNet::KACTALK;
	const HTTP				= 4200080;
	const PROTO_HTTP		= ktNet::HTTP;
	const HTTPS				= 4200421;
	const PROTO_HTTPS		= ktNet::HTTPS;
	const FTP				= 4200021;
	const PROTO_FTP			= ktNet::FTP;
	const FILE				= 4200000;
	const PROTO_FILE		= ktNet::FILE;
	const _DEFAULT_PROTO	= ktNet::KACTALK;
	const _AUTO_PROTO		= 42424242;

	public function __construct( )
	{
		parent::__construct(  );
		throw new ktError( "You are not to create an object of this class!",
							"::__construct",
							$this );
	}

	public static function TranslateProtocol( $protocol, $include_sep = false )
	{
		if (is_int( $protocol )) {
			$end = $include_sep ? '://' : '';
			switch( $protocol ) {
				case ktNet::PROTO_KACTALK:
					return 'kact' . $sep;
				case ktNet::PROTO_HTTP:
					return 'http' . $sep;
				case ktNet::PROTO_HTTPS:
					return 'https' . $sep;
				case ktNet::PROTO_FTP:
					return 'ftp' . $sep;
				case ktNet::PROTO_FILE:
					return 'file' . $sep;
			}
		} else if (is_string( $protocol )) {
			if (substr( $protocol, -3 ) == '://') {
				$protocol = substr( $protocol, 0, -3 );
			} else if (substr( $protocol, -2 ) == ':/') {
				$protocol = substr( $protocol, 0, -2 );
			}
			switch (strtolower( $protocol )) {
				case 'kt':
				case 'kact':
				case 'kactalk':
					return ktNet::PROTO_KACTALK;
				case 'http':
					return ktNet::PROTO_HTTP;
				case 'https':
					return ktNet::PROTO_HTTPS;
				case 'ftp':
					return ktNet::PROTO_FTP;
				case 'file':
					return ktNet::PROTO_FILE;
			}
		}

		return false;
	}
}

class ktNetTalker extends ktObject
{
	protected $_host;
	protected $_protocol;

	public function __construct( $host = '', $protocol = ktNet::_AUTO_PROTO )
	{
		parent::__construct(  );

		$this->SetHost( $host, $protocol );
	}

	public function SetHost( $host = '', $protocol = ktNet::_AUTO_PROTO )
	{
		$proto_s = '';
		$pos = strpos( $host, '://' );
		if (($pos > 0) && ($pos < 9)) {
			$proto_s	= substr( $host, 0, $pos );
			$host		= substr( $host, $pos );
		}
	}
}

?>
