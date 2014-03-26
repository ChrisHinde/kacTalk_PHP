<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - Mr_CHISOL - Kachtus 2008
 * @license CC-GNU GPL v2
 */
defined( '_KACTALK_VALID' ) or die( 'Restricted Access!' );

class ktExport
{
	const KT_XML		= 31420;			// -   x  (??)
	const JSON			= 31421;			// -   x  (?)
	const JSONP			= 31422;			// -   x  (?)
	const XML	     	= 31423;			// -   x
	const XML_RPC		= 31424;			// -   -
	const APACHE_CONF	= 31425;			// -   -
	const INI			    = 31426;			// -   -
	const OBJECT_KTS	= 31427;			// -   -
	const OBJECT_PHP	= 31428;			// -   -
	const OBJECT_JS		= ktExport::JSON;	// -   x (*)
	const _VAR_DUMP    = 31429;				// 0   x
	//const NONE			= 31426;
	const _DEFAULT		= ktExport::KT_XML; //ktExport::KT_XML;

	const EXPORT_WRAP			= 16180;
	const RESPONSE_WRAP			= 16181;
	const METH_RESPONSE_WRAP	= 16182;
	const PROP_RESPONSE_WRAP	= 16183;
	const CALL_WRAP				= 16184;
	const PROPERTY_WRAP			= 16185;
	const _DEFAULT_WRAP			= ktExport::EXPORT_WRAP;

	function __construct( &$object = null )
	{
		$this->_obj = &$object;
	}

	static function ExportStatic( $object, $format = ktExport::_DEFAULT,
						$return_export = false, $no_outer = false,
							$_name = '', $_type = '' )
	{
		$exp = new ktExport( $object );
		return $exp->Export( $format, $return_export, $no_outer,
								$_name, $_type );
	}

	function Export( $format = ktExport::_DEFAULT, $return_export = false,
						$no_outer = false, $_name = '', $_type = '' )
	{
		$ret = '';

		if (_DEBUG) { var_dump( $format ); }

		switch ($format) {
			case ktEXPORT::KT_XML: {
				$ret = $this->ExportKT_XML( $return_export, $no_outer,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::XML: {
				$ret = $this->ExportXML( $return_export, $no_outer,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::XML_RPC: {
				$ret = $this->ExportXML_RPC( $return_export, $no_outer,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::JSON: {
				$ret = $this->ExportJSON( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::JSONP: {
				$ret = $this->ExportJSONP( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::INI: {
				$ret = $this->ExportINI( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::OBJECT_PHP: {
				$ret = $this->ExportPHP( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::_VAR_DUMP: {
				$ret = $this->ExportVarDump( $return_export );
				break;
			 }
			default: {
				throw new ktError( "Doesn't support the format#: {$format}",
									"::Export",
									$this );
			}
		}

		return $ret;
	}

	function ExportJSONP( $return_export = false, $_name = '', $_type = '' )
	{
    return $this->ExportJSON( $return_export, $_name, $_type );
  }
  
	function ExportJSON( $return_export = false, $_name = '', $_type = '' )
	{
		$return = "{\n";

		if (isset( $this->_obj )) {
			if (!(is_a( $this->_obj, 'ktObject' ) && is_subclass_of( $this->_obj, 'ktObject' ))) {
				return $this->ExportValueJSON( $return_export,
													             $_name, $_type );
			}

			$n = 0;
			foreach ( $this->_obj->getProperties() as $propName => $value ) {
				if ($propName[0] == '_')	continue;

				if ($n > 0) $return .= ",\n";

				//$value = &$this->_obj->{$propName};

				if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "\t'{$propName}': ";
					if ($value == $this->_obj) {
						$return .= 'this';
					} else {
						$return .= str_replace( "\n", "\n\t",
							$value->Export( ktExport::JSON, true ) );
				}
				} else if (is_array( $value )) {
					$return .= "\t'{$propName}': ";
					$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::JSON, true ) );
				} else if (is_string( $value )) {
					$return .= "\t'{$propName}': ";
					$return .= "'" . addslashes( $value ) . "'";
				} else if (is_bool( $value )) {
					$return .= "\t'{$propName}': ";
					$return .= ktLib::bool2str( $value );
				} else {
					$return .= "\t'{$propName}': '{$value}'";
				}

				$n++;
			}
			$c = get_class( $this->_obj );

			foreach ( $this->_obj->getMethods() as $meth ) {
				if ($n > 0) $return .= ",\n";

				$return .= "\t'{$meth}': function() {\n";
				$return .= "\t\t/** <kto:functionCallDef function=\"{$c}::{$meth}\" /> **/\n";
				$return .= "\t\tkacTalk.functionCall( '";
				$return .= $c . "::{$meth}', ";
				$return .= "arguments, this );\n";
				$return .= "\t}";
			}
		}

		$return .= "\n}\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}
  
  function ExportValueJSON( $return_export = false,
								$name = '', $type = '' )
	{
		$c = ($name != '' ? $name : ( is_array( $this->_obj ) ) ? 'ARRAY' : get_class( $this->_obj ) );
		if (empty($c)) $c = gettype( $this->_obj );
		$value = $this->_obj;

		$return = '';
		$name_a = empty( $name ) ? '' : "'{$name}': ";

		if (!empty( $type )) {  
			$return .= "\t";
			if ($value === $this->_obj) {
				$return .= 'this';
			} else {
				$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
						$this->TreatType( $value,
											$type,
											ktExport::JSON,
											$propName ) );
			}
			//$return .= "";
		} else if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) { 
			$return .= "\t{$name_a}{\n";
      $return .= "\t\t_objectInfo: { 'type': 'ktObject', 'name': '{$_name}' },\n";
			if ($value == $this->_obj) {
				$return .= 'this';
			} else {
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::JSON, true, true ) ) );
				$return .= "\n\t";
			}
			$return .= "</kto:value>";
		} else if (is_array( $value )) {
			$return .= "\t{$name_a}";
			$return .= str_replace( "\n", "\n\t",
					ktExport::ExportArray( $value, ktExport::JSON, true ) );
		} else if (is_string( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"string\">";
			$return .= "<![CDATA[" . $value . "]]></kto:value>";
		} else if (is_bool( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"bool\">";
			$return .= ktLib::bool2str( $value ) . "</kto:value>";
		} else if (is_float( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"float\">{$value}</kto:value>";
		} else if (is_numeric( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"integer\">{$value}</kto:value>";
		} else if ($value == null) {
			$return .= "\t<kto:null {$name_a}/>";
		} else {
			$cl = get_class( $value );
			if (empty($cl)) $cl = gettype( $this->_obj );
			$t = 'kto_custom:' . $cl;
			$return .= "\t<kto:value {$name_a}type=\"{$t}\"><![CDATA[{$value}]]></kto:value>";
		}

		//$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );
		if ($no_outer) {
			$return = substr( $return, 1 );
		}

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
  }

	function ExportXML( $return_export = false, $no_outer = false,
							$_name = '', $_type = '' )
	{
		$return = str_replace( '<kto:', '<', str_replace( '</kto:', '</',
						str_replace( '</kto:array', '</list',
						str_replace( '<kto:array', '<list',
							$this->ExportKT_XML( true, $no_outer,
													$_name, $_type )
									) ) ) );

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportKT_XML( $return_export = false, $no_outer = false,
							$_name = '', $_type = '' )
	{
		$c = ($_name != '' ? $_name : ( is_array( $this->_obj ) ) ? 'ARRAY' : get_class( $this->_obj ) );
		$return = ($no_outer ? '' : "<kto:object name=\"{$c}\">\n" );

		if (isset( $this->_obj )) {
			if (!(is_a( $this->_obj, 'ktObject' ) && is_subclass_of( $this->_obj, 'ktObject' ))) {    
				$return = $this->ExportValueKT_XML( $return_export,
													true,
													$_name, $_type );

				if ($return_export) {
					return $return;
				} else {
					echo $return;
					return true;
				}
			}

			$n = 0;
			foreach ( $this->_obj->getProperties() as $propName => $value ) {
				if ($propName[0] == '_')	continue;

				if ($n > 0) $return .= "\n";
				$propName_a = empty( $propName ) ? '' : "name=\"{$propName}\" ";

				//$value = &$this->_obj->{$propName};

				$type = $this->_obj->getTypeOfProperty($propName);
				if (!empty( $type )) {
					$return .= "\t<kto:value {$propName_a}type=\"{$type}\">";
					if ($value === $this->_obj) {
						$return .= 'this';
					} else {
						$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
								$this->TreatType( $value,
													$type,
													ktExport::KT_XML,
													$propName ) );
						/*$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::KT_XML, true, true ) ) );*/
						//$return .= "\n\t";
					}
					$return .= "</kto:value>";
				} else if (is_array( $value )) {
					$return .= "\t<kto:value {$propName_a}type=\"array\">";
					$return .= "\n\t\t" . str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::KT_XML, true ) ) . "</kto:value>";
				} else if (is_string( $value )) {
					$return .= "\t<kto:value {$propName_a}type=\"string\">";
					$return .= "<![CDATA[" . $value . "]]></kto:value>";
				} else if (is_bool( $value )) {
					$return .= "\t<kto:value {$propName_a}type=\"bool\">";
					$return .= ktLib::bool2str( $value ) . "</kto:value>";
				} else if (is_float( $value )) {
					$return .= "\t<kto:value {$propName_a}type=\"float\">{$value}</kto:value>";
				} else if (is_numeric( $value )) {
					$return .= "\t<kto:value {$propName_a}type=\"integer\">{$value}</kto:value>";
				} else if ($value == null) {
					$return .= "\t<kto:value {$propName_a}type=\"null\">\n\t\t<kto:null {$name_a}/>\n\t</kto:value>";
				} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "\t<kto:value {$propName_a}type=\"ktObject\">";
					if ($value == $this->_obj) {
						$return .= 'this';
					} else {
						$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::KT_XML, true, false ) ) );
						$return .= "\n\t";
					}
					$return .= "</kto:value>";
				} else {
					$t = 'kto_custom:' . get_class( $value );
					$return .= "\t<kto:value {$propName_a}type=\"{$t}\"><![CDATA[{$value}]]></kto:value>";
				}

				$n++;
			}

			foreach ( $this->_obj->getMethods() as $meth ) {
				if ($n > 0) $return .= "\n";

				$return .= "\t<kto:method name=\"{$meth}\">\n";
				$return .= "\t\t<kto:functionCallDef function=\"{$c}::{$meth}\" />\n";
				$return .= "\t</kto:method>";
			}
		} else {
			$return .= "\t<kto:null {$name_a}/>";
		}

		$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportValueKT_XML( $return_export = false,
								$no_outer = true,
								$name = '', $type = '' )
	{
		$c = ($name != '' ? $name : ( is_array( $this->_obj ) ) ? 'ARRAY' : get_class( $this->_obj ) );
		if (empty($c)) $c = gettype( $this->_obj );
		$value = $this->_obj;

		$return = ($no_outer ? '' : "<kto:object name=\"{$c}\">\n" );
		$name_a = empty( $name ) ? '' : "name=\"{$name}\" ";

		if (!empty( $type )) {
			$return .= "\t<kto:value {$name_a}type=\"{$type}\">";
			if ($value === $this->_obj) {
				$return .= 'this';
			} else {
				$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
						$this->TreatType( $value,
											$type,
											ktExport::KT_XML,
											$propName ) );
				/*$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::KT_XML, true, true ) ) );*/
				//$return .= "\n\t";
			}
			$return .= "</kto:value>";
		} else if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
			$return .= "\t<kto:value {$name_a}type=\"ktObject\">";
			if ($value == $this->_obj) {
				$return .= 'this';
			} else {
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::KT_XML, true, true ) ) );
				$return .= "\n\t";
			}
			$return .= "</kto:value>";
		} else if (is_array( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"array\">";
			$return .= "\n\t\t" . str_replace( "\n", "\n\t",
					ktExport::ExportArray( $value, ktExport::KT_XML, true ) ) . "</kto:value>";
		} else if (is_string( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"string\">";
			$return .= "<![CDATA[" . $value . "]]></kto:value>";
		} else if (is_bool( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"bool\">";
			$return .= ktLib::bool2str( $value ) . "</kto:value>";
		} else if (is_float( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"float\">{$value}</kto:value>";
		} else if (is_numeric( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"integer\">{$value}</kto:value>";
		} else if ($value == null) {
			$return .= "\t<kto:null {$name_a}/>";
		} else {
			$cl = get_class( $value );
			if (empty($cl)) $cl = gettype( $this->_obj );
			$t = 'kto_custom:' . $cl;
			$return .= "\t<kto:value {$name_a}type=\"{$t}\"><![CDATA[{$value}]]></kto:value>";
		}

		$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );
		if ($no_outer) {
			$return = substr( $return, 1 );
		}

		return $return;
	}

	function ExportXML_RPC( $return_export = false, $no_outer = false,
							$_name = '', $_type = '' )
	{
		$c = get_class( $this->_obj );
		$return = ($no_outer ? '' : "<struct>\n" );

		if (isset( $this->_obj )) {
			$n = 0;
			foreach ( $this->_obj->getProperties() as $propName => $value ) {
				if ($propName[0] == '_')	continue;

				if ($n > 0) $return .= "\n";

				//$value = &$this->_obj->{$propName};

				$return .= "\t<member>\n";
				$return .= "\t\t<name>{$propName}</name>\n";

				$type = $this->_obj->getTypeOfProperty($propName);
				if (!empty( $type )) {
					$return .= "\t\t<value>";
					if ($value === $this->_obj) {
						$return .= 'this';
					} else {
						$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t\t",
								$this->TreatType( $value,
													$type,
													ktExport::XML_RPC,
													$propName ) );
						/*$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::KT_XML, true, true ) ) );*/
						//$return .= "\n\t";
					}
					$return .= "</value>";
				} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "\t\t<value><ktObject>";
					if ($value == $this->_obj) {
						$return .= 'this';
					} else {
						$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::XML_RPC, true, true ) ) );
						$return .= "\n\t";
					}
					$return .= "</ktObject></value>";
				} else if (is_array( $value )) {
					$return .= "\t\t<value>";
					$return .= "\n\t\t" . str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::XML_RPC, true ) ) . "</value>";
				} else if (is_string( $value )) {
					$return .= "\t<value><string>";
					$return .= "<![CDATA[" . $value . "]]></string></value>";
				} else if (is_bool( $value )) {
					$return .= "\t\t<value><boolen>";
					$return .= ktLib::bool2str( $value ) . "</boolen></value>";
				} else if (is_float( $value )) {
					$return .= "\t\t<value><float>{$value}</float></value>";
				} else if (is_numeric( $value )) {
					$return .= "\t\t<value><int>{$value}</int></value>";
				} else {
					$t = 'kto_custom:' . get_class( $value );	
					$return .= "\t\t<value><{$t}><![CDATA[{$value}]]></{$t}></value>";
				}

				$return .= "\n\t</member>";

				$n++;
			}

			foreach ( $this->_obj->getMethods() as $meth ) {
				if ($n > 0) $return .= "\n";

				$return .= "\t<method>\n";
				$return .= "\t\t<name>{$c}::{$meth}</name>\n";
				$return .= "\t</method>";
			}
		}

		$return .= ($no_outer ? "\n" : "\n</struct>\n" );

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}


	function ExportINI( $return_export = false, $_name = '',
							$_type = '' )
	{
		$name	= ( empty($this->_obj->_name) ? $this->_obj->_object_name : $this->_obj->_name );
		$return = "[{$name}]\n";

		if (isset( $this->_obj )) {
			$n = 0;
			foreach ( $this->_obj->getProperties() as $propName => $value ) {
				if ($propName[0] == '_')	continue;

				if ($n > 0) $return .= "\n";

				//$value = &$this->_obj->{$propName};

				if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "{$propName} = ";
					if ($value == $this->_obj) {
						$return .= 'this';
					} else {
						$return .= str_replace( "\n", "\n\t",
							$value->Export( ktExport::JSON, true ) );
					}
				} else if (is_array( $value )) {
					$return .= "{$propName} = ";
					$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::INI, true ) );
				} else if (is_string( $value )) {
					$return .= "{$propName} = ";
					$return .= "'" . addslashes( $value ) . "'";
				} else if (is_bool( $value )) {
					$return .= "{$propName} = ";
					$return .= ktLib::bool2str( $value );
				} else {
					$return .= "{$propName} = {$value}";
				}

				$n++;
			}
			/*$c = get_class( $this->_obj );

			foreach ( $this->_obj->getMethods() as $meth ) {
				if ($n > 0) $return .= "\n";

				$return .= "\t'{$meth}': function() {\n";
				$return .= "\t\t/** <kto:functionCallDef function=\"{$c}::{$meth}\" /> ** /\n";
				$return .= "\t\tkacTalk.functionCall( '";
				$return .= $c . "::{$meth}', ";
				$return .= "arguments, this );\n";
				$return .= "\t}";
			}*/
		}

		$return .= "\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportPHP( $return_export = false, $_name = '',
						$_type = '' )
	{
		$c = get_class( $this->_obj );
		$return = "class {$c} extends ktObject\n{\n";

		if (isset( $this->_obj )) {
			$n = 0;
			foreach ( $this->_obj->getProperties() as $propName => $value ) {
				if ($propName[0] == '_')	continue;

				if ($n > 0) $return .= "\n";

				//$value = &$this->_obj->{$propName};

				$type = $this->_obj->getTypeOfProperty($propName);
				if (!empty( $type )) {
					$return .= "\tpublic {$propName} = ";
					if ($value === $this->_obj) {
						$return .= 'this';
					} else {
						$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
								$this->TreatType( $value,
													$type,
													ktExport::OBJECT_PHP,
													$propName ) );
						/*$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::KT_XML, true, true ) ) );*/
						//$return .= "\n\t";
					}
					$return .= "; // {$type}";
				} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "\tpublic {$propName} = ";
					if ($value == $this->_obj) {
						$return .= 'null /* $this */';
					} else {
						$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::OBJECT_PHP, true, true ) ) );
						$return .= "\n\t";
					}
					$return .= "; // ktObject";
				} else if (is_array( $value )) {
					$return .= "\t{$propName} = ";
					$return .= str_replace( "\n", "\n\t",
							trim( ktExport::ExportArray( $value, ktExport::OBJECT_PHP, true ) ) ) . "; // array";
				} else if (is_string( $value )) {
					$return .= "\t{$propName} = ";
					$return .= '"' . addslashes( $value ). '"; // string';
				} else if (is_bool( $value )) {
					$return .= "\t{$propName} = ";
					$return .= ktLib::bool2str( $value ) . "; // bool";
				} else if (is_float( $value )) {
					$return .= "\t{$propName} = {$value}; // float";
				} else if (is_numeric( $value )) {
					$return .= "\t{$propName} = {$value}; // integer";
				} else {
					$t = gettype( $value ); //'kto_custom:' . get_class( $value );
					$return .= "\t{$propName} = \"" . addslashes( $value ) . "; // {$t}";
				}

				$n++;
			}

			foreach ( $this->_obj->getMethods() as $meth ) {
				if ($n > 0) $return .= "\n";

				$return .= "\tfunction {$meth}()\n\t{\n";
				$return .= "\t\tkacTalk::functionCall( \"{$c}::{$meth}\" );\n";
				$return .= "\t}";
			}
		}

		$return .= "\n}\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportVarDump( $return = false )
	{
		ob_start();

		$ret = var_dump( $this->_obj );

		if ($return) {
			$ret = ob_get_contents();
			ob_end_clean();
		} else {
			ob_flush();
		}

		return $ret;
	}
	function ExportVarExport( $return = false )
	{
		return var_export( $this->_obj, $return );
	}

	function ExportArray( $array, $format = ktExport::_DEFAULT, $return_export = false, $json_map = false )
	{
		$ret = '';

		if (!is_array( $array )) return $ret;

		switch ($format) {
			case ktEXPORT::XML: {
				$ret = $this->ExportArrayXML( $array, $return_export );
				break;
			 }
			case ktEXPORT::KT_XML: {
				$ret = $this->ExportArrayKT_XML( $array, $return_export );
				break;
			 }
			case ktEXPORT::INI: {
				$ret = $this->ExportArrayINI( $array, $return_export );
				break;
			 }
			case ktEXPORT::JSON:
			case ktEXPORT::JSONP: {
        if ( $json_map ) {
				  $ret = $this->ExportArrayJSON_Mapped( $array, $return_export );
        } else {
				  $ret = $this->ExportArrayJSON( $array, $return_export );
        }
				break;
			 }
			case ktEXPORT::XML_RPC: {
				$ret = $this->ExportArrayXML_RPC( $array, $return_export );
				break;
			 }
			case ktEXPORT::OBJECT_PHP: {
				$ret = $this->ExportArrayPHP( $array, $return_export );
				break;
			 }
			case ktEXPORT::_VAR_DUMP: {
				$ret = $this->ExportArrayVarDump( $array, $return_export );
				break;
			 }
			default: {
				throw new ktError( "Doesn't support the format#: {$format}",
									"::ExportArray",
									$this );
			}
		}

		return $ret;
	}

	function ExportArrayJSON_Mapped( $array, $return_export = false )
	{
		$return = "{\n";

		if ((!is_array( $array )) || empty( $array )) {
			$return = trim( $return ) . "}\n";           
  		if ($return_export) {
  			return $return;
  		} else {
  			echo $return;
  			return true;
  		}
		}

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			//$value = &$this->_obj->{$propName};

			if (is_array( $value )) {
				$return .= "\t'{$key}': ";
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::JSON, true ) );
			} else if (is_string( $value )) {
				$return .= "\t'{$key}': ";
				$return .= "'" . addslashes( $value ) . "'";
			} else if (is_bool( $value )) {
				$return .= "\t'{$key}': ";
				$return .= ktLib::bool2str( $value );
			} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t'{$key}': ";
				if ($value == $this->_obj) {
					$return .= 'this';
				} else {
					$return .= str_replace( "\n", "\n\t",
						$value->Export( ktExport::JSON, true ) );
				}
			} else {
				$return .= "\t'{$key}': {$value}";
			}

			$n++;
		}

		$return .= "\n}\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}
	function ExportArrayJSON( $array, $return_export = false )
	{
		$return = "[\n";

		if ((!is_array( $array )) || empty( $array )) {
			$return = trim( $return ) . "]\n";           
  		if ($return_export) {
  			return $return;
  		} else {
  			echo $return;
  			return true;
  		}
		}

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			//$value = &$this->_obj->{$propName};
      $return .= "\t";
      
			if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
				if ($value == $this->_obj) {
					$return .= 'this';
				} else {
					$return .= str_replace( "\n", "\n\t",
						$value->Export( ktExport::JSON, true ) );
				}
			} else if (is_array( $value )) {
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::JSON, true ) );
			} else if (is_string( $value )) {
				$return .= "'" . addslashes( $value ) . "'";
			} else if (is_bool( $value )) {
				$return .= ktLib::bool2str( $value );
			} else {
				$return .= "{$value}";
			}

			$n++;
		}

		$return .= "\n]\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportArrayKT_XML( $array, $return_export = false )
	{
		$return = "<kto:array length=\"" . count( $array ) ."\">\n";

		if ((!is_array( $array )) || empty( $array )) {
			return trim( $ret ) . "</kto:array>\n";
		}

		$n = 0;
		foreach ( $array as $key => $value ) {
			$noRet = false;
			if ($n > 0) $return .= "\n";

			//$value = &$this->_obj->{$propName};
//var_dump( $value );
			if (@is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"ktObject\">";
				if ($value == $this->_obj) {
					$return .= 'this';
				} else {
					$return .= "\n\t\t\t" . trim( str_replace( "\n", "\n\t\t\t",
						$value->Export( ktExport::KT_XML, true ) ) ) . "\n\t\t";
				}
			} else if (is_array( $value )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"array\">";
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::KT_XML, true ) );
			} else if (is_string( $value )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"string\">";
					$return .= "<![CDATA[" . $value . "]]>";
			} else if (is_bool( $value )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"bool\">";
				$return .= ktLib::bool2str( $value );
			} else if (is_float( $value )) {
				$return .= "\t<kto:value name=\"{$key}\" type=\"float\">";
				$return .= floatval( $value );
			} else if (is_numeric( $value )) {
				$return .= "\t<kto:value name=\"{$key}\" type=\"integer\">";
				$return .= intval( $value );
			} else if ($value == null) {
				$noRet = true;
				$return .= "\t<kto:null name=\"{$key}\" />";
			} else {
				$cl = get_class( $value );
				if (empty($cl)) $cl = gettype( $this->_obj );
				$t = 'kto_custom:' . $cl;
				$return .= "\t<kto:value name=\"{$key}\" type=\"{$t}\"><![CDATA[{$value}]]></kto:value>";
			} /*else {
				$return .= "\t\t<kto:value name=\"{$key}\">{$value}";
			}*/

			if (!$noRet) {
				$return .= "</kto:value>";
			}

			$n++;
		}

		$return .= "\n\t</kto:array>\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportArrayXML_RPC( $array, $return_export = false )
	{
		$return = "<array>\n\t\t<data>\n";

		if ((!is_array( $array )) || empty( $array )) {
			return trim( $ret ) . "\t\t</data>\n</array>\n";
		}

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			//$value = &$this->_obj->{$propName};

			if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t\t\t<value><ktObject>";
				if ($value == $this->_obj) {
					$return .= 'this';
				} else {
					$return .= "\n\t\t\t" . trim( str_replace( "\n", "\n\t\t\t",
						$value->Export( ktExport::XML_RPC, true ) ) ) . "\n\t\t";
				}
				$return .= "</ktObject>";
			} else if (is_array( $value )) {
				$return .= "\t\t\t<value>";
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::XML_RPC, true ) );
			} else if (is_string( $value )) {
				$return .= "\t\t\t<value><string>";
					$return .= "<![CDATA[" . $value . "]]></string>";
			} else if (is_bool( $value )) {
				$return .= "\t\t<value><boolean>";
				$return .= ktLib::bool2str( $value ) . "</boolean>";
			} else {
				$return .= "\t\t<value>{$value}";
			}

			$return .= "</value>";

			$n++;
		}

		$return .= "\n\t\t</data>\n\t</array>\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportArrayXML( $return_export = false, $no_outer = false )
	{
		$return = str_replace( '<kto:', '<', str_replace( '</kto:', '</',
						str_replace( '<kto:array', '<list',
						str_replace( '</kto:array', '</list',
							$this->ExportKT_XML( true, $no_outer ) ) ) ) );

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportArrayINI( $array, $return_export = false )
	{
		$return = "<kto:array length=\"" . count( $array ) ."\">\n";

		if ((!is_array( $array )) || empty( $array )) {
			return trim( $ret ) . "</kto:array>\n";
		}

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			//$value = &$this->_obj->{$propName};

			if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"ktObject\">";
				if ($value == $this->_obj) {
					$return .= 'this';
				} else {
					$return .= "\n\t\t\t" . trim( str_replace( "\n", "\n\t\t\t",
						$value->Export( ktExport::KT_XML, true ) ) ) . "\n\t\t";
				}
			} else if (is_array( $value )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"array\">";
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::KT_XML, true ) );
			} else if (is_string( $value )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"string\">";
					$return .= "<![CDATA[" . $value . "]]>";
			} else if (is_bool( $value )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"bool\">";
				$return .= ktLib::bool2str( $value );
			} else {
				$return .= "\t\t<kto:value name=\"{$key}\">{$value}";
			}

			$return .= "</kto:value>";

			$n++;
		}

		$return .= "\n\t</kto:array>\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	function ExportArrayPHP( $array, $return_export = false )
	{
		$return = "array( \n";

		if ((!is_array( $array )) || empty( $array )) {
			return trim( $ret ) . ")\n";
		}

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			//$value = &$this->_obj->{$propName};
  
			if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t'{$key}' => ";
				if ($value == $this->_obj) {
					$return .= 'this';
				} else {
					$return .= str_replace( "\n", "\n\t",
						trim( $value->Export( ktExport::OBJECT_PHP, true ) ) );
				}
				$return .= " /* object */";
			} else if (is_array( $value )) {
				$return .= "\t'{$key}' => ";
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::OBJECT_PHP, true ) ) . " /* array */";
			} else if (is_string( $value )) {
				$return .= "\t'{$key}' => ";
				$return .= "'" . addslashes( $value ) . "' /* string */";
			} else if (is_bool( $value )) {
				$return .= "\t'{$key}' => ";
				$return .= ktLib::bool2str( $value ) . "' /* bool */";
			} else {
				$return .= "\t'{$key}' => {$value} /* " . gettype( $value ) . " */";
			}

			$n++;
		}

		$return .= "\n}\n";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	public static function ExportWrap( $value, $format = ktExport::_DEFAULT,
										$type = ktExport::_DEFAULT_WRAP, $return_export = false )
	{
		$ret = '';

		switch ( $format ) {
			case ktEXPORT::KT_XML: {
				$ret = ktExport::ExportWrapKT_XML( $value, $type, $return_export );
				break;
			 }
			case ktEXPORT::XML: {
				$ret = ktExport::ExportWrapXML( $value, $type, $return_export );
				break;
			 }
			case ktEXPORT::XML_RPC: {
				$ret = ktExport::ExportWrapXML_RPC( $value, $type, $return_export );
				break;
			 }
			case ktEXPORT::JSON: {
				$ret = ktExport::ExportWrapJSON( $value, $type, $return_export );
				break;
			 }
			case ktEXPORT::JSONP: {
				$ret = ktExport::ExportWrapJSONP( $value, $type, $return_export );
				break;
			 }
			case ktEXPORT::INI: {
				$ret = ktExport::ExportWrapINI( $value, $type, $return_export );
				break;
			 }
			case ktEXPORT::OBJECT_PHP: {
				$ret = ktExport::ExportWrapPHP( $value, $type, $return_export );
				break;
			 }
			default: {
				throw new ktError( "Doesn't support the format#: {$format}",
									"::ExportWrap",
									$this );
			}
		}

		return $ret;
	}                         
   
	public static function ExportWrapJSON( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false )
	{
		$l = ''; $r = '';                     
		if (is_array( $value )) {
			$val = $value['value'];
		} else {
			$val = $value;
		}

		switch( $type ) {
			case ktExport::EXPORT_WRAP:
				$l = "{\n";
				$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				$r = "}";
		  	break;
			case ktExport::RESPONSE_WRAP:
			case ktExport::METH_RESPONSE_WRAP:
			case ktExport::PROP_RESPONSE_WRAP:                               
        $val = trim( $val ) . "\n";
        break;
			default: {
				throw new ktError( "Doesn't support the type#: {$type}",
									"::ExportWrapKT_XML",
									$this );
			}
		};

		header( 'Content-Type: application/json' );
		//$ret  = '<?   ?' . '>' . "\n";
		$ret = $l . $val . $r . "\n";

		if ($type == ktExport::RESPONSE_WRAP) {
			$ret = str_replace( "\t\t", "\t", $ret );
		}

		if ($return_export) {
			return $ret;
		} else {
			echo $ret;
			return true;
		}
	}
	public static function ExportWrapJSONP( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false, $padding = '' )
	{
		$l = ''; $r = '';
    
    if ( empty( $padding ) ) {
      if ( isset($_REQUEST['padding']) ) {
        $padding = $_REQUEST['padding']; 
      } else if ( ! empty( $_name ) ) {
        $padding = $_name; 
      } else {
        $padding = 'handleExportResponse()';
      }
    }
    $padding = trim($padding);

		switch( $type ) {
			case ktExport::EXPORT_WRAP:         
				if (is_array( $value )) {
					$val = $value['value'];
				} else {
					$val = $value;
				}
				$l = "{\n";
				$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				$r = "}";
		  	break;
			case ktExport::RESPONSE_WRAP:
			case ktExport::METH_RESPONSE_WRAP:
			case ktExport::PROP_RESPONSE_WRAP: 
			   break;
			default: {
				throw new ktError( "Doesn't support the type#: {$type}",
									"::ExportWrapKT_XML",
									$this );
			}
		};

		header( 'Content-Type: application/javascript' );
		$ret = $l . $val . $r . "\n";
    
    if ( substr( $padding, -2 ) == '()' ) {
      $ret = substr( $padding, 0, -1 ) . trim( $ret ) . ');'; 
    } else {
      $ret = $padding . ' = ' . trim( $ret ) . ';';
    }

		if ($type == ktExport::RESPONSE_WRAP) {
			$ret = str_replace( "\t\t", "\t", $ret );
		}

		if ($return_export) {
			return $ret;
		} else {
			echo $ret;
			return true;
		}
	}

	public static function ExportWrapKT_XML( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false )
	{
		$l = ''; $r = '';

		switch( $type ) {
			case ktExport::EXPORT_WRAP:
				if (is_array( $value )) {
					$val = $value['value'];
				} else {
					$val = $value;
				}
				$l = "<kto:export xmlns:kto=\"http://www.kachtus.net/xml/defs/kt/kto\">\n";
				$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				$r = "</kto:export>";
			break;
			case ktExport::RESPONSE_WRAP:
			case ktExport::METH_RESPONSE_WRAP:
			case ktExport::PROP_RESPONSE_WRAP:
				$l = "<kto:response xmlns:kto=\"http://www.kachtus.net/xml/defs/kt/kto\">\n";
				if (is_array( $value ) &&
						(
							(isset( $value['kt::IS_RETURN'] ) && $value['kt::IS_RETURN'] )
							||
							(isset( $value['kt::IS_PROPERTY'] ) && $value['kt::IS_PROPERTY'] ) ) ) {
					$n = (!empty( $value['kt::Method'] ) ? ' name="' . $value['kt::Method'] . '"' : 
							(!empty( $value['kt::Property'] ) ? ' name="' . $value['kt::Property'] . '"' : ''));

					if ($type == ktExport::METH_RESPONSE_WRAP) {
						$l .= "\t<kto:methodResponse{$n}>\n";
						$r  = "\t</kto:methodResponse>\n";
					} else if ($type == ktExport::PROP_RESPONSE_WRAP) {
						$l .= "\t<kto:propertyValue{$n}>\n";
						$r  = "\t</kto:propertyValue>\n";
					}
					if (isset( $value['return'] ) && !empty( $value['return'] )) {
						$ret = trim( str_replace( "\n", "\n\t\t\t", $value['return'] ) );
						if ((strpos( $ret, "\n" ) > 0) || (strpos( $r, "<" ) >= 0)) {
							$ret = "\n\t\t\t" . $ret . "\n\t\t";
						}
						$val = "\t\t<kto:return>{$ret}</kto:return>\n";
					} else if (isset( $value['value'] ) && !empty( $value['value'] )) {
						$val = trim( str_replace( "\n", "\n\t\t", $value['value'] ) );
						$val = "\t\t{$val}\n";
					}

					if (isset( $value['output'] ) && !empty( $value['output'] )) {
						$o = trim( str_replace( "\n", "\n\t\t\t", $value['output'] ) );
						if ((strpos( $o, "\n" ) >= 0) || (strpos( $o, "<" ) >= 0)) {
							$o = "\n\t\t\t" . $o . "\n\t\t";
						}
						$val .= "\t\t<kto:output>{$o}</kto:output>\n";
					}
				} else {
					if ($type == ktExport::METH_RESPONSE_WRAP) {
						$l .= "\t<kto:methodResponse>\n";
						$r  = "\t</kto:methodResponse>\n";
					} else if ($type == ktExport::PROP_RESPONSE_WRAP) {
						$l .= "\t<kto:propertyValue>\n";
						$r  = "\t</kto:propertyValue>\n";
					}
					$val = "\t" . trim( str_replace( "\n", "\n\t", $value ) ) . "\n";
				}
				$r .= "</kto:response>";
			break;
			default: {
				throw new ktError( "Doesn't support the type#: {$type}",
									"::ExportWrapKT_XML",
									$this );
			}
		};

		header( 'Content-Type: text/xml' );
		$ret  = '<?xml version="1.0"?' . '>' . "\n";
		$ret .= $l . $val . $r . "\n";

		if ($type == ktExport::RESPONSE_WRAP) {
			$ret = str_replace( "\t\t", "\t", $ret );
		}

		if ($return_export) {
			return $ret;
		} else {
			echo $ret;
			return true;
		}
	}

	public static function ExportWrapXML( $value, $type = ktExport::_DEFAULT_WRAP, $return_export = false )
	{
		$return = str_replace( '<kto:', '<', str_replace( '</kto:', '</',
						str_replace( '</kto:array', '</list',
						str_replace( '<kto:array', '<list',
						str_replace( 'xmlns:kto', 'xmlns',
							ktExport::ExportWrapKT_XML( $value, $type,
													true )
									) ) ) ) );

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}

	public function TreatType( $value, $type, $format = ktExport::_DEFAULT,
						$propName = null )
	{
		switch ($type) {
			case 'string': case 'ktString': {
				if (($format == ktExport::KT_XML) ||
					($format == ktExport::XML)) {
					if (!preg_match( '/^[a-zA-Z0-9.\s]+$/', $value )) {
						return "\n\t<![CDATA[" . $value . "]]>\n";
					} else {
						return $value;
					}
				} else if ($format == ktExport::XML_RPC) {
					if (!preg_match( '/^[a-zA-Z0-9.\s]+$/', $value )) {
						return "\n\t<string><![CDATA[" . $value . "]]></string>\n";
					} else {
						return '<string>' . $value. '</string>';
					}
				}
				return '"' . addslashes( $value ) . '"';
			 }
			case 'array': case 'ktArray': {
				return ktExport::ExportArray( $format, $value, true );
			 }
			case 'object': case 'ktObject': {
				if (is_subclass_of( $value, 'ktObject' ) || is_a( $value, 'ktObject' )) {
					return $value->Export( $format, true );
				} else {
					if ($format == ktExport::XML_RPC) {
						return '<nil/>';
					}
					return 'null';
				}
			 }
			case 'bool': case 'booleskt': case 'boolean': {
				if ($format == ktExport::XML_RPC) {
					return '<boolean>' . ktLib::bool2str( $value ). '</boolean>';
				}
				return ktLib::bool2str( $value );
			 }
			case 'int': case 'integer': {
				if ($format == ktExport::XML_RPC) {
					return '<int>' . intval( $value ). '</int>';
				}
				return intval( $value );
			 }
			case 'float': case 'double': {
				if ($format == ktExport::XML_RPC) {
					return '<double>' . floatval( $value ). '</double>';
				}
				return floatval( $value );
			 }
			case 'datetime': case 'date': {
				if (is_numeric( $value )) {
					$value = date( "Y-M-d H:i:s", $value );
				}

				if ($format == ktExport::XML_RPC) {
					return '<dateTime>' . $value . '</datetime>';
				}
				return $value;
			 }
			default: {
				return $value;
			 }
		};
	}

	public static function TranslateFormat( $format )
	{
		switch( strtolower( $format ) ) {
			case 'kt': case 'kt_xml': case 'ktxml': {
				return ktExport::KT_XML;
			}
			case 'xml': {
				return ktExport::XML;
			}
			case 'xml_rpc': case 'rpc': {
				return ktExport::XML_RPC;
			}
			case 'json': {
				return ktExport::JSON;
			}
			case 'jsonp': {
				return ktExport::JSONP;
			}
			case 'ini': {
				return ktExport::INI;
			}
			case 'conf': case 'aconf': case 'apache_conf': {
				return ktExport::APACHE_CONF;
			}
			case 'js': case 'object_js': {
				return ktExport::OBJECT_JS;
			}
			case 'php': case 'object_php': {
				return ktExport::OBJECT_PHP;
			}
			case 'kto': case 'kts': case 'object_kt': {
				return ktExport::OBJECT_KTS;
			}
			default: {
				return ktExport::_DEFAULT;
			}
		};
	}

	private $_obj = null;
};

?>
