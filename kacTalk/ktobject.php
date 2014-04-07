<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - 2014
 * @license CC-GNU GPL v2
 */

defined( '_KACTALK_VALID' ) or die( 'Restricted Access!' );

include_once( 'ktexport.php' );
include_once( 'ktimport.php' );

class ktObject /*extends ArrayObject*/
{
	function __construct( $name = '' )
	{
		if (empty( $name )) {
			$this->_object_name = get_class( $this );
		} else {
			$this->_object_name = $name;
		}
	}

	function getProperties()
	{
		$props_	= get_object_vars( $this );
		$props	= array();

		foreach ($props_ as $propName => $value ) {
			if ($propName[0] == '_') continue;

			$props[$propName] = $value;
		}

		return $props;
	}
	function setTypeOfProperty( $property, $type = '' )
	{
		if (!is_array( $this->_property_types )) {
			$this->_property_types = array();
		}

		$this->_property_types[$property] = $type;
	}
	function getTypeOfProperty( $property )
	{
		if (!is_array( $this->_property_types )) {
			return '';
		}
    if ( array_key_exists( $property, $this->_property_types ) ) 
		  return $this->_property_types[$property];
    else
      return '';
	}

	function getMethods()
	{
		$base_meths	= get_class_methods( 'ktObject' );
		$meths_ = get_class_methods( $this );
		$meths	= array();

		foreach ($meths_ as $methName ) {
			if (($methName[0] == '_') ||
				in_array( $methName, $base_meths )) continue;

			$meths[] = $methName;
		}

		return $meths;
	}

	function Export( $format = ktExport::_DEFAULT, $return = true,
						$no_outer = false )
	{
		return ktExport::ExportStatic( $this, $format, $return, $no_outer );
	}

	public $_object_name = 'ktObject';
	protected $_property_types = array();
};

?>
