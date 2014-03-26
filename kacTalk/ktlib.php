<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - Mr_CHISOL - Kachtus 2008
 * @license CC-GNU GPL v2
 */
defined( '_KACTALK_VALID' ) or die( 'Restricted Access!' );

class ktLib
{
	static function bool2str( $bool )
	{
		return $bool ? 'true' : 'false';
	}

	static function GetRoute( $path )
	{
		$ret = array();

		if (empty( $path )) {
			$path = $_SERVER['PATH_INFO'];
		}
		while ($path[0] == '/') {
			$path = substr( $path, 1 );
		}

		$s1 = split( "/", $path );

		$n = 0;
		foreach ( $s1 as $part ) {
			switch ($n) {
				case 0: {
					$ret['object'] = $part;
					break;
				 }
				case 1: {
					$ret['member'] = $part;
					break;
				 }
				default: {
					$ret['extra' . ($n - 2)] = $part;
				}
			};
			$lastPart = $part;
			$n++;
		}

		if (($pos = strrpos( $lastPart, '.' )) !== false) {
			$format = substr( $lastPart, $pos + 1 );
			if (is_numeric( $format )) {
				return $ret;
			}

			$part = substr( $lastPart, 0, $pos );
			switch ($n - 1) {
				case 0: {
					$ret['object'] = $part;
					break;
				 }
				case 1: {
					$ret['member'] = $part;
					break;
				 }
				default: {
					$ret['extra' . ($n - 3)] = $part;
				}
			};
			$ret['format'] = $format;
		}

		return $ret;
	}

	public static function ArrayCopy( $in )
	{
		$out = array();

		foreach ( $in as $k => $v ) {
			$val = null;
			if (is_string( $v )) {
				$val = '' . $v . '';
			} else if (is_int( $v )) {
				$val = $v + 0;
			} else if (is_float( $v )) {
				$val = $v + 0.0;
			} else if (is_array( $v )) {
				$val = ktLib::ArrayCopy( $v );
			} else if (is_object( $v )) {
				$val = clone $v;
			}

			$out[$k] = $val;
		}

		return $out;
	}
}

?>
