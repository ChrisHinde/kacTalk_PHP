<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - 2014
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
	const OBJECT_KTO	= 31427;			// -   -
	const OBJECT_PHP	= 31428;			// -   -
	const OBJECT_JS		= ktExport::JSON;	// -   x (*)
	const _VAR_DUMP   = 31429;				// 0   x
	const NONE		   	= 31430;
	const _DEFAULT		= ktExport::KT_XML; //ktExport::KT_XML;

	const EXPORT_WRAP			= 16180;
	const RESPONSE_WRAP			= 16181;
	const METH_RESPONSE_WRAP	= 16182;
	const PROP_RESPONSE_WRAP	= 16183;
	const CALL_WRAP				= 16184;
	const PROPERTY_WRAP			= 16185;
	const _DEFAULT_WRAP			= ktExport::EXPORT_WRAP;

	/// <summary>
	/// Constructor for ktExport
	/// </summary>
	/// <param name="object">The object to export</param>
	function __construct( &$object = null )
	{
		// Store the object
		$this->_obj = &$object;
	} // __construct

	/// <summary>
	/// Static method to simplify export
	/// </summary>
	/// <param name="object">The object to export</param>
	/// <param name="format">The format we should use for the export</param>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="no_outer">Should we skip the "wrap"/outer part?</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	/// <param name="stack">Should we add the object to the stack? (Use with causion, only ment for the "initial call")</param>
	static function ExportStatic( &$object, $format = ktExport::_DEFAULT,
																	$return_export = false, $no_outer = false,
																	$_name = '', $_type = '', $stack = false )
	{
  	$ret = '';

		// If we should stack this object before we export it!
		if ( $stack )
			// Stack the object
			ktExport::StackObject( $object );

		// Create a new ktExport object for the object/value we want to export
		$exp = new ktExport( $object );

		// If the thing to export is an object
		if ( is_object( $object ) )
			// Export the object
			$ret = $exp->Export( $format, $return_export, $no_outer,
														$_name, $_type );
		// Not an object...
		else
			// Export the value
			$ret = $exp->ExportValue( $format, $return_export, $no_outer,
													 			$_name, $_type );

		// If we stacked this object before we exported it!
		if ( $stack )
			// Pop the object off the stack
			ktExport::PopObject();

		// Return the result of the export
		return $ret;
	} // /ExportStatic

	/// <summary>
	/// Generic method to export the current object
	/// </summary>
	/// <param name="format">The format we should use for the export</param>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="no_outer">Should we skip the "wrap"/outer part?</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function Export( $format = ktExport::_DEFAULT, $return_export = false,
						$no_outer = false, $_name = '', $_type = '' )
	{
		$ret = '';

		if (_DEBUG) { var_dump( $format ); }

		// Check the format to use and call the appropiate method
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
			case ktEXPORT::OBJECT_KTO: {
				$ret = $this->ExportKTO( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::_VAR_DUMP: {
				$ret = $this->ExportVarDump( $return_export );
				break;
			 }
			// Ooops! Unsupported format!
			default: {
				// Throw a nice exception!
				throw ktError::E( 'Export->FormatNotSupported(' . ktError::$_formatString . ",#{$format})",
									"Doesn't support the format: {ktExport::$_formatString}",
									"::Export",
									$this,
									ktError::NOTIMP );
			}
		}

		return $ret;
	} // /Export

	/// <summary>
	/// Generic method to export the current object as a value
	/// </summary>
	/// <param name="format">The format we should use for the export</param>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="no_outer">Should we skip the "wrap"/outer part?</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportValue( $format = ktExport::_DEFAULT, $return_export = false,
												$no_outer = false, $_name = '', $_type = '' )
	{
		$ret = '';

		if (_DEBUG) { var_dump( $format ); }

		// Check the format and call the apropriate method
		switch ($format) {
			case ktEXPORT::KT_XML: {
				$ret = $this->ExportValueKT_XML( $return_export, $no_outer,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::XML: {
				$ret = $this->ExportValueXML( $return_export, $no_outer,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::XML_RPC: {
				$ret = $this->ExportValueXML_RPC( $return_export, $no_outer,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::JSON: {
				$ret = $this->ExportValueJSON( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::JSONP: {
				$ret = $this->ExportValueJSON( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::INI: {
				$ret = $this->ExportValueINI( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::OBJECT_PHP: {
				$ret = $this->ExportValuePHP( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::OBJECT_KTO: {
				$ret = $this->ExportValueKTO( $return_export,
											$_name, $_type );
				break;
			 }
			case ktEXPORT::_VAR_DUMP: {
				$ret = $this->ExportVarDump( $return_export );
				break;
			 }
			// Oops! An unsupported format!
			default: {
				// Throw a nice exception!
				throw ktError::E( 'Export->FormatNotSupported(' . ktExport::$_formatString . ",#{$format})",
									"Doesn't support the format: {ktExport::$_formatString}",
									"::Export",
									$this );
			}
		}

		return $ret;
	} // /ExportValue

	/// <summary>
	/// Method for exporting as JSONP (just an alias for ::ExportJSON)
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportJSONP( $return_export = false, $_name = '', $_type = '' )
	{
		// Let ::ExportJSON handle the export
		return $this->ExportJSON( $return_export, $_name, $_type );
	} // /ExportJSONP

	/// <summary>
	/// Method for exporting as JSON
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportJSON( $return_export = false, $_name = '', $_type = '' )
	{
		// Get the class name
		$c = ($_name != '' ? $_name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );

		$return = "{\n";

		// We can't do anything without an object
		if (!isset( $this->_obj )) {
			$return .= "\tnull\n}\n";
			// Should we return the result?
			if ($return_export) {
				return $return;
			// Otherwise echo it
			} else {
				echo $return;
				return true;
			}
		}

		// Isn't it an object?
		if ( ! is_object( $this->_obj ) ) {
			// Export it as a (simple) value
			return $this->ExportValueJSON( $return_export,
																		 $_name, $_type );
		}

		// Handle the properties and methods in separate methods
		$return .= ktExport::_ExportProperties( $c, 'JSON' ) . "\n";
		$return .= ktExport::_ExportMethods( $c, 'JSON' );

		if ( substr($return, -1 ) == ',' )
			$return = substr( $return, 0, -1 );

		// Add the end bracket
		$return .= "\n}\n";

		// Should we return the result?
		if ($return_export) {
			return $return;
		// Otherwise echo it
		} else {
			echo $return;
			return true;
		}
	} // /ExportJSON

	/// <summary>
	/// Export a property in JSON format
	/// </summary>
	/// <param name="prop_n">The name of the property</param>
	/// <param name="value">The vale of the property</param>
	/// <param name="n">The number of the the member</param>
	/// <param name="c">The class name</param>
	function _ExportJSON_Property( $prop_n, &$value, $n = 0, $c = '' )
	{
		$return = '';

		// Add property name
		$return .= "\t'{$prop_n}':\t";

		// Is it an object
		if ( is_object( $value ) ) {
			// Is it a reference to the current object?
			if ($value == $this->_obj) {
				$return .= "this /*<kto:this />*/";
      // Are we inside (a descendent of) this object already??)
			} else if ( ktExport::CheckRecursive($value) ) {
				$return .= "null /*<kto:recursive name=\"{$prop_n}\" type=\"ktObject\" class=\"{$c}\" />*/";
			// It's a "normal object"
			} else {
				$o = '';
				// Is it a ktObject??
				if ( is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' ) )
					// Use the object's own export method
					$o = $value->Export( ktExport::JSON, true );
				else
					// Create a new ktExport object to handle the object
					$o = ktExport::ExportStatic( $value, ktExport::JSON, true, true );
				// Handle indentation
				$return .= str_replace( "\n", "\n\t", trim( $o ) );
			}
			$return .= ",\t/* ktObject({$c}) */"; // Signify the type in a comment
		// Is it an array?
		} else if (is_array( $value )) {
			// Export the array
			$arr = ktExport::ExportArray( $value, ktExport::JSON, true );
			$return .= str_replace( "\n", "\n\t",
															trim( $arr ) ); // Handle indentation
			$return .= ",\t/* array */";						// Signify the type in a comment
		// Is it a string?
		} else if (is_string( $value )) {
			$return .= "'" . str_replace('\\"', '"',
													addslashes( $value ) ) . "'";	// Give it proper treatment
			$return .= ",\t/* string */";						// Signify the type in a comment
		// Is it a boolena?
		} else if (is_bool( $value )) {
			$return .= ktLib::bool2str( $value );		// Convert the bool to a string
			$return .= ",\t/* bool */";							// Signify the type in a comment
		// Is it a float number?
		} else if (is_float( $value )) {
			$return .= "\t{$value}";								// Simply output the value
			$return .= ",\t/* float */";						// Signify the type in a comment
		// Is it a integer?
		} else if (is_numeric( $value )) {
			$return .= "\t{$value}";								// Simply output the value
			$return .= ",\t/* integer */";					// Signify the type in a comment
		// Is it a null?
		} else if (is_null( $value )) {
			$return .= "\tnull";										// Output the null keyword
			$return .= ",\t/* NULL */"; 						// Signify the type in a comment
		// Default value...
		} else {
			$return .= "\t'{$value}'";
		}

		return $return . "\n";
	}

	/// <summary>
	/// Export a method in JSON format
	/// </summary>
	/// <param name="meth_n">The name of the method</param>
	/// <param name="n">The number of the the member</param>
	/// <param name="c">The class name</param>
	function _ExportJSON_Method( $meth_n, $n, $c )
	{
		$return = ($n > 0) ? "\n" : '';

		// Create the structure for a method in JSON
		$return .= "\t'{$meth_n}': function() {\n";
		$return .= "\t\t/** <kto:functionCallDef function=\"{$c}::{$meth_n}\" /> ** /\n";
		$return .= "\t\tkacTalk.functionCall( '";
		$return .= $c . "::{$meth_n}', ";
		$return .= "arguments, this );\n";
		$return .= "\t},";

		return $return;
	} // /_ExportJSON_Method

	/// <summary>
	/// Export a value in JSON format
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportValueJSON( $return_export = false,
														$name = '', $type = '' )
	{
		// Get the class name
		$c = ($name != '' ? $name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
		if (empty($c)) $c = gettype( $this->_obj );

		$value = $this->_obj;
		$return = '';

		$name_a = ( empty( $name ) || kacTalk::$_kt->GetReturnPureValue() ) ? '' : "{$name} = ";

		if (!empty( $type )) {
			$return .= "\t";
			if ($value === $this->_obj) {
				$return .= 'this';
			} else if ( ktExport::CheckRecursive($value) ) {
				$c = $this->GetClass( $value );
				$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
			} else {
				$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
						$this->TreatType( $value,
											$type,
											ktExport::JSON,
											$prop_n ) );
			}
		} else if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
			$return .= "\t{$name_a}{\n";
			$return .= "\t\t_objectInfo: { 'type': 'ktObject', 'name': '{$_name}' },\n";
			if ($value == $this->_obj) {
				$return .= 'this /*<kto:this />*/';
			} else if ( ktExport::CheckRecursive($value) ) {
				$c = $this->GetClass( $value );
				$return .= "null /*<kto:recursive name=\"{$_name}\" type=\"ktObject\" class=\"{$c}\" />*/";
			} else {
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::JSON, true, true ) ) );
				$return .= "\n\t";
			}
			$return .= "}";
		} else if (is_array( $value )) {
			$return .= "\t{$name_a}";
			$return .= str_replace( "\n", "\n\t",
					ktExport::ExportArray( $value, ktExport::JSON, true ) );
		} else if (is_string( $value )) {
			$return .= "\t{$name_a}'" . $value . "'";
		} else if (is_bool( $value )) {
			$return .= "\t{$name_a}";
			$return .= ktLib::bool2str( $value );
		} else if (is_float( $value ) || is_numeric( $value )) {
			$return .= "\t{$name_a}{$value}";
		} else if ($value == null) {
			$return .= "\t{$name_a}null";
		} else {
			$cl = ktExport::GetClass( $value );
			$t = 'kto_custom:' . $cl;
			$return .= "\t<kto:value {$name_a}type=\"{$t}\"><![CDATA[{$value}]]></kto:value>";
		}

		if ( kacTalk::$_kt->GetReturnPureValue() )
			$return .= ';';
		else
			$return = trim( $return );

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}
	function ExportValueKTO( $return_export = false,
								$name = '', $type = '' )
	{
		$c = ($name != '' ? $name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
		if (empty($c)) $c = gettype( $this->_obj );
		$value = $this->_obj;

		$return = '';
		$name_a = ( empty( $name ) || kacTalk::$_kt->GetReturnPureValue() ) ? '' : "{$name} = ";

		if (!empty( $type )) {
			$return .= "\t";
			if ($value === $this->_obj) {
				$return .= 'this';
			} else if ( ktExport::CheckRecursive($value) ) {
				$c = $this->GetClass( $value );
				$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
			} else {
				$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
						$this->TreatType( $value,
											$type,
											ktExport::JSON,
											$prop_n ) );
			}
			//$return .= "";
		} else if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
			$return .= "\t{$name_a}{\n";
			$return .= "\t\t_objectInfo: { 'type': 'ktObject', 'name': '{$_name}' },\n";
			if ($value == $this->_obj) {
				$return .= 'this';
			} else if ( ktExport::CheckRecursive($value) ) {
				$c = $this->GetClass( $value );
				$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
			} else {
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::JSON, true, true ) ) );
				$return .= "\n\t";
			}
			$return .= "}";
		} else if (is_array( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\tktList ";
			$return .= $name_a;
			$return .= str_replace( "\n", "\n\t",
															ktExport::ExportArray( $value, ktExport::OBJECT_KTO, true ) );
		} else if (is_string( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\tktString {$name_a}\"{$value}\"";
			else
				$return .= $value;
		} else if (is_bool( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\tktBool ";
			$return .= $name_a;
			$return .= ktLib::bool2str( $value );
		} else if (is_float( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\tktFloat ";
			$return .= "{$name_a}{$value}";
		} else if (is_numeric( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\tktInt ";
			$return .= "{$name_a}{$value}";
		} else if ($value == null) {
			$return .= "\t{$name_a}:null";
		} else {
			$cl = ktExport::LookUpClass( $value );
			if (empty($cl)) $cl = gettype( $this->_obj );
			$t = 'kto_custom:' . $cl;
			$return .= "\t{$name_a}{$value}";
		}
		if ( kacTalk::$_kt->GetReturnPureValue() )
			$return .= ';';

		//$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );
/*		if ($no_outer) {
			$return = substr( $return, 1 );
		}*/

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}
	function ExportValuePHP( $return_export = false,
								$name = '', $type = '' )
	{
		$c = ($name != '' ? $name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
		if (empty($c)) $c = gettype( $this->_obj );
		$value = $this->_obj;

		$return = '';
		$name_a = empty( $name ) ? '' : "{$name} = ";

		if (!empty( $type )) {
			$return .= "\t";
			if ($value === $this->_obj) {
				$return .= '$this';
			} else if ( ktExport::CheckRecursive($value) ) {
				$c = $this->GetClass( $value );
				$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
			} else {
				$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
						$this->TreatType( $value,
											$type,
											ktExport::JSON,
											$prop_n ) );
			}
			//$return .= "";
		} else if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
			$return .= "\t{$name_a}{\n";
			$return .= "\t\t_objectInfo: { 'type': 'ktObject', 'name': '{$_name}' },\n";
			if ($value == $this->_obj) {
				$return .= '$this';
			} else if ( ktExport::CheckRecursive($value) ) {
				$c = $this->GetClass( $value );
				$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
			} else {
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::JSON, true, true ) ) );
				$return .= "\n\t";
			}
			$return .= "}";
		} else if (is_array( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\t";
			$return .= $name_a;
			$return .= str_replace( "\n", "\n\t",
					ktExport::ExportArray( $value, ktExport::OBJECT_KTO, true ) );
		} else if (is_string( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\tktString {$name_a}\"{$value}\"";
			else
				$return .= $value;
		} else if (is_bool( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\t";
			$return .= $name_a;
			$return .= ktLib::bool2str( $value );
		} else if (is_float( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\t";
			$return .= "{$name_a}{$value}";
		} else if (is_numeric( $value )) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\t";
			$return .= "{$name_a}{$value}";
		} else if ($value == null) {
			if (kacTalk::$_kt->GetReturnPureValue() )
				$return .= "\t";
			$return .= "{$name_a}NULL";
		} else {
			$cl = ktExport::LookUpClass( $value );
			if (empty($cl)) $cl = gettype( $this->_obj );
			$t = 'kto_custom:' . $cl;
			$return .= "\t{$name_a}{$value}";
		}
		$return .= ';';

		//$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );
/*		if ($no_outer) {
			$return = substr( $return, 1 );
		}*/

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

	/// <summary>
	/// Export with KT_XML as format
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="no_outer">Should we skip the "wrap"/outer part?</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportKT_XML( $return_export = false, $no_outer = false,
														$_name = '', $_type = '' )
	{
		// Get the class name
		$c = ($_name != '' ? $_name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
		$return = ($no_outer ? '' : "<kto:object name=\"{$c}\">\n" );

		// We can't do anything without an object
		if (!isset( $this->_obj )) {
			$return .= "\t<kto:null {$_name}/>";
			// Add the end tag (if we should output the "outer tags")
			$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );
			// Should we return the result?
			if ($return_export) {
				return $return;
			// Otherwise echo it
			} else {
				echo $return;
				return true;
			}
		}

		// Handle the properties and methods in separate methods
		$return .= ktExport::_ExportProperties( $c, 'KT_XML' ) ."\n";
		$return .= ktExport::_ExportMethods( $c, 'KT_XML' );

		// Add the end tag (if we should output the "outer tags")
		$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );

		// Should we return the result?
		if ($return_export) {
			return $return;
		// otherwise echo it
		} else {
			echo $return;
			return true;
		}
	} // ExportKT_XML
// reorganise
	/// <summary>
	/// Finds and goes through the properties of the object and calls the appropriate Export method
	/// </summary>
	/// <param name="c">The class name</param>
	/// <param name="mf">The format part of the method name (Ex. KT_XML)</param>
	function _ExportProperties( $c, $mf )
	{
		$return = '';
		// Is it not a ktObject?
		if (!(is_a( $this->_obj, 'ktObject' ) ||
								is_subclass_of( $this->_obj, 'ktObject' ))) {
			// Use reflection to get the properties from normal objects
			$refl = new ReflectionClass( $this->_obj );
			$props = $refl->getProperties( ReflectionProperty::IS_PUBLIC );

			$n = 0;
			// Loop through the properties of this object
			foreach ( $props as $prop ) {
				$prop_n = $prop->getName();
				$value = $this->_obj->{$prop_n};

				// We skip properties that starts with a underscore [_]
				if ($prop_n[0] == '_')	continue;

				// Let the appropriate ::_Export???_Property handle the tag structure
				$return .= $this->{'_Export' . $mf . '_Property'}( $prop_n, $value, $n, $c );

				$n++;
			}

		// This is a ktObject
		} else {
			$n = 0;
			// Loop through the properties of this object
			foreach ( $this->_obj->getProperties() as $prop_n => $value ) {
				// Let the appropriate ::_Export???_Property handle the tag structure
				$return .= $this->{'_Export' . $mf . '_Property'}( $prop_n, $value, $n, $c );

				$n++;
			}
		}

		// Done!
		return $return;
	} // /_ExportProperties

	/// <summary>
	/// Finds and goes through the methods of the object and calls the appropriate Export method
	/// </summary>
	/// <param name="c">The class name</param>
	/// <param name="mf">The format part of the method name (Ex. KT_XML)</param>
	function _ExportMethods( $c, $mf )
	{
		$return = '';
		if (!( is_a( $this->_obj, 'ktObject' ) ||
					 is_subclass_of( $this->_obj, 'ktObject' ))) {
			// Use reflection to get the methods from normal objects
			$refl = new ReflectionClass( $this->_obj );
			$methods = $refl->getMethods( ReflectionMethod::IS_PUBLIC );

			$n = 0;
			// Loop through the methods
			foreach ( $methods as $meth ) {
				$meth_n = $meth->name;
				// We skip methods that start with a underscore [_]
				if ($meth_n[0] == '_')	continue;

				// Let handle ::_ExportKT_XML_Property the tag structure
				$return .= $this->{'_Export' . $mf . '_Method'}( $meth_n, $n, $c );

				$n++;
			}
		// This is a ktObject
		} else {
			$n = 0;
			// Loop through the methods of this object
			foreach ( $this->_obj->getMethods() as $meth_n ) { ;
				// Let handle ::_ExportKT_XML_Property the tag structure
				$return .= $this->{'_Export' . $mf . '_Method'}( $meth_n, $n, $c );

				$n++;
			}
		}

		return $return;
	} // /_Export_Methods

	/// <summary>
	/// Export a method in KT_XML format
	/// </summary>
	/// <param name="meth_n">The name of the method</param>
	/// <param name="n">The number of the the member</param>
	/// <param name="c">The class name</param>
	function _ExportKT_XML_Method( $meth_n, $n, $c )
	{
		$return = ($n > 0) ? "\n" : '';

		// Create the tag structure for the method
		$return .= "\t<kto:method name=\"{$meth_n}\">\n";
		$return .= "\t\t<kto:functionCallDef function=\"{$c}::{$meth_n}\" />\n";
		$return .= "\t</kto:method>";

		return $return;
	} // /_ExportKT_XML_Method

	/// <summary>
	/// Export a property in KT_XML format
	/// </summary>
	/// <param name="prop_n">The name of the property</param>
	/// <param name="value">The vale of the property</param>
	/// <param name="n">The number of the the member</param>
	/// <param name="c">The class name</param>
	function _ExportKT_XML_Property( $prop_n, &$value, $n = 0, $c = '' )
	{
		$return = '';

		// Add a newline if this isn't the first property we've exported
		if ($n > 0) $return .= "\n";
		// Add the property name to the output
		$prop_n_a = empty( $prop_n ) ? '' : "name=\"{$prop_n}\" ";

		// Get the type of the the property
		$type = is_object( $value ) ? 'object' : '';
		// If it has a type, we should be able to handle it!
		if (!empty( $type )) {
			// Start the tag-structure for a value
			$return .= "\t<kto:value {$prop_n_a}type=\"{$type}\">";
			// If this propery is reference to the object
			if ($value === $this->_obj) {
				$return .= "\n\t\t<kto:this />";
			// Are we inside (a descendent of) this object already??)
			} else if ( ktExport::CheckRecursive($value) ) {
				// Get The name of the type/class ...
				$c = $this->GetClass( $value );
				// ... and output a tag that indicates that this is a recursive value
				$return .= "\n\t\t<kto:recursive name=\"{$prop_n}\" type=\"ktObject\" class=\"{$c}\" />";
			// We are ok!
			} else {
				// Stack this object
				ktExport::StackObject( $value );
				// Let ::TreatType take care of the rest
				$return .= "\n\t\t" . trim( str_replace( "\n", "\n\t\t",
																						$this->TreatType( $value,
																															$type,
																															ktExport::KT_XML,
																															$prop_n ) ) );
				/// Pop the object of again
				ktExport::PopObject();
			}
			// End the tag structue
			$return .= "\n\t</kto:value>";
		// Is the property an array?
		} else if (is_array( $value )) {
			// Create a tag structure for a value ...
			$return .= "\t<kto:value {$prop_n_a}type=\"array\">";
			// .. and let ::ExportArray handle the array
			$return .= "\n\t\t" . str_replace( "\n", "\n\t",
																				 ktExport::ExportArray( $value,
																				 ktExport::KT_XML, true ) );
			$return .= "</kto:value>";
		// Is it a string?
		} else if (is_string( $value )) {
			// Create a tag structure for a string
			$return .= "\t<kto:value {$prop_n_a}type=\"string\">";
			$return .= "<![CDATA[" . $value . "]]></kto:value>";
		// Is it a boolean value?
		} else if (is_bool( $value )) {
			// Create a tag for the bool
			$return .= "\t<kto:value {$prop_n_a}type=\"bool\">";
			$return .= ktLib::bool2str( $value ) . "</kto:value>";
		// Is it a boolean value?
		} else if (is_float( $value )) {
			// Create a tag for the float
			$return .= "\t<kto:value {$prop_n_a}type=\"float\">{$value}</kto:value>";
		// Is it a integer/numeric value?
		} else if (is_numeric( $value )) {
			// Create a tag for the integer
			$return .= "\t<kto:value {$prop_n_a}type=\"integer\">{$value}</kto:value>";
		// Is it a null value?
		} else if ($value == null) {
			// Create a tag structure for a null value
			$return .= "\t<kto:value {$prop_n_a}type=\"null\">\n\t\t<kto:null {$name_a}/>\n\t</kto:value>";
		// Is the object derived from ktObject??
		} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
			// Get the class of the current value
			$c = $this->GetClass( $value );
			// Create the proper tag structure
			$return .= "\t<kto:value {$prop_n_a}type=\"ktObject\">";
			// Is this value a refernce to the current object?
			if ($value == $this->_obj) {
				$return .= '<kto:this/>';
			// Are we already in this (or a child of) object
			} else if ( ktExport::CheckRecursive($value) ) {
				$return .= "\t<kto:recursive name=\"{$_name}\" type=\"ktObject\" class=\"{$c}\" />";
			// OK then
			} else {
				// Stack this object
				ktExport::StackObject( $value );
				// Use the export method of the object
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
																		trim( $value->Export( ktExport::KT_XML, true, false ) ) );/**/
				$return .= "\n\t";
				// Pop the object off again
				ktExport::PopObject();
			}
			// End the tag.
			$return .= "</kto:value>";
		// Is it a normal object?
		} else if (is_object( $value )) {
			// Get the class of the current value
			$c = $this->GetClass( $value );
			// Create the proper tag structure
			$return .= "\t<kto:value {$prop_n_a}type=\"object\">\n\t\t";
			// Is this value a refernce to the current object?
			if ($value == $this->_obj) {
				$return .= '<kto:this />';
			// Are we already in this (or a child of) object
			} else if ( ktExport::CheckRecursive($value) ) {
				$return .= "<kto:recursive name=\"{$_name}\" type=\"object\" class=\"{$c}\" />";
			// OK then
			} else {
				// Stack this object
				ktExport::StackObject( $value );
				// Export the object using ::ExportStatic
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( ktExport::ExportStatic( $value, ktExport::KT_XML,
																				$return_export, true,
																				$c, 'object' ) ) );/**/
				$return .= "\n\t";
				// Pop the object off again
				ktExport::PopObject();
			}
			// End the tag.
			$return .= "\n\t</kto:value>";
		// Custom!?
		} else {
			$t = 'kto_custom:' . ktExport::LookUpClass( $value );
			$return .= "\t<kto:value {$prop_n_a}type=\"{$t}\"><![CDATA[{$value}]]></kto:value>";
		}

		// Done!
		return $return;
	} // /_ExportKT_XML_Property

	function ExportValueKT_XML( $return_export = false,
															$no_outer = true,
															$name = '', $type = '' )
	{
		$c = ($name != '' ? $name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
//		if (empty($c)) $c = gettype( $this->_obj );
		$value = $this->_obj;

		$return = ($no_outer ? '' : "<kto:object name=\"{$c}\">\n" );
		$name_a = empty( $name ) ? '' : "name=\"{$name}\" ";

		if (!empty( $type )) {
			$return .= "\t<kto:value {$name_a}type=\"{$type}\">\n\t\t";
			if ($value === $this->_obj) {
				$return .= '<kto:this />';
			} else if ( ktExport::CheckRecursive($value) ) {
				$c = $this->GetClass( $value );
				$return .= "<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />";
			} else {
				$return .= str_replace( "\n", "\n\t",
						$this->TreatType( $value,
											$type,
											ktExport::KT_XML,
											$prop_n ) );
				/*$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::KT_XML, true, true ) ) );*/
				//$return .= "\n\t";
			}
			$return .= "\n\t</kto:value>";
		} else if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
			$return .= "\t<kto:value {$name_a}type=\"ktObject\">";
			/*if ($value == $this->_obj) {
				$return .= '<kto:this />';
			} else */{
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( $value->Export( ktExport::KT_XML, true, true ) ) );
				$return .= "\n\t";
			}
			$return .= "</kto:value>";
		} else if (is_object( $value )) {
			$return .= "\t<kto:value {$name_a}type=\"object\">";
			/*if ($value == $this->_obj) {
				$return .= '<kto:this />';
			} else */{
				$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
					trim( ktExport::ExportStatic( $value, ktExport::KT_XML, true, true ) ) );
				$return .= "\n";
			}
			$return .= "\t</kto:value>";
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
			$cl = ktExport::LookUpClass( $value );
			if (empty($cl)) $cl = gettype( $this->_obj );
			$t = 'kto_custom:' . $cl;
			$return .= "\t<kto:value {$name_a}type=\"{$t}\"><![CDATA[{$value}]]></kto:value>";
		}

		$return .= ($no_outer ? "\n" : "\n</kto:object>\n" );
		if ($no_outer) {
			$return = substr( $return, 1 );
		}

		return $return;
	} // /ExportValueKT_XML

	/// <summary>
	/// Export a value with XML as format (alias for ::ExportValueKT_XML)
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="no_outer">Should we skip the "wrap"/outer part?</param>
	/// <param name="name">The name of the object we are exporting</param>
	/// <param name="type">The type of the object we are exporting</param>
	function ExportValueXML( $return_export = false,
														$no_outer = true,
														$name = '', $type = '' )
	{
		// Use ::ExportValueKT_XML
		return $this->ExportValueKT_XML( $return_export, $no_outer, $name, $type );
	} // /ExportValueXML

	/// <summary>
	/// Export with XML_RPC as format
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="no_outer">Should we skip the "wrap"/outer part?</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportXML_RPC( $return_export = false, $no_outer = false,
							$_name = '', $_type = '' )
	{
		$c = ($_name != '' ? $_name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
		$return = ($no_outer ? '' : "<struct>\n" );

		if (isset( $this->_obj )) {
			$n = 0;
			foreach ( $this->_obj->getProperties() as $prop_n => $value ) {
				if ($prop_n[0] == '_')	continue;

				if ($n > 0) $return .= "\n";

				//$value = &$this->_obj->{$prop_n};

				$return .= "\t<member>\n";
				$return .= "\t\t<name>{$prop_n}</name>\n";

				$type = $this->_obj->getTypeOfProperty($prop_n);
				if (!empty( $type )) {
					$return .= "\t\t<value>";
					if ($value === $this->_obj) {
						$return .= '<this />';
					} else if ( ktExport::CheckRecursive($value) ) {
						$c = $this->GetClass( $value );
						$return .= "<recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />";
					} else {
						$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t\t",
								$this->TreatType( $value,
													$type,
													ktExport::XML_RPC,
													$prop_n ) );
						/*$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::KT_XML, true, true ) ) );*/
						//$return .= "\n\t";
					}
					$return .= "</value>";
				} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "\t\t<value><ktObject>";
					if ($value == $this->_obj) {
						$return .= '<this />';
					} else if ( ktExport::CheckRecursive($value) ) {
						$c = $this->GetClass( $value );
						$return .= "<recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />";
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
					$t = 'kto_custom:' . ktExport::LookUpClass( $value );
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
	} // ExportXML_RPC


	/// <summary>
	/// Export with INI as format
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportINI( $return_export = false, $_name = '',
												$_type = '' )
	{
	//	$name	= ( empty($this->_obj->_name) ? $this->_obj->_object_name : $this->_obj->_name );
		$name = ($_name != '' ? $_name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
		$return = "[{$name}]\n";

		if (isset( $this->_obj )) {
			$n = 0;
			foreach ( $this->_obj->getProperties() as $prop_n => $value ) {
				if ($prop_n[0] == '_')	continue;

				if ($n > 0) $return .= "\n";

				//$value = &$this->_obj->{$prop_n};

				if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "{$prop_n} = ";
					if ($value == $this->_obj) {
						$return .= 'this';
					} else if ( ktExport::CheckRecursive($value) ) {
						$c = $this->GetClass( $value );
						$return .= "0 /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
					} else {
						$return .= str_replace( "\n", "\n\t",
							$value->Export( ktExport::JSON, true ) );
					}
				} else if (is_array( $value )) {
					$return .= "{$prop_n} = ";
					$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::INI, true ) );
				} else if (is_string( $value )) {
					$return .= "{$prop_n} = ";
					$return .= "'" . addslashes( $value ) . "'";
				} else if (is_bool( $value )) {
					$return .= "{$prop_n} = ";
					$return .= ktLib::bool2str( $value );
				} else {
					$return .= "{$prop_n} = {$value}";
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
	} // /ExportINI

	/// <summary>
	/// Export with PHP as format
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="no_outer">Should we skip the "wrap"/outer part?</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportPHP( $return_export = false, $_name = '',
						$_type = '' )
	{
		// Get the class name
		$c = ($_name != '' ? $_name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );

		// Start the class declaration
		$return = "class {$c}";
		// If this is a (derivative of) ktObject, add the proper code to the output
		if ( (is_a( $this->_obj, 'ktObject' ) || is_subclass_of( $this->_obj, 'ktObject' )) )
			$return .= ' extends ktObject';
		$return .= "\n{\n";

		// We can't do anything without an object
		if (!isset( $this->_obj )) {
			$return .= "}\n";
			// Should we return the result?
			if ($return_export) {
				return $return;
			// Otherwise echo it
			} else {
				echo $return;
				return true;
			}
		}

		// Isn't it an object?
		if ( ! is_object( $this->_obj ) ) {
			// Export it as a (simple) value
			return $this->ExportValuePHP( $return_export,
																		 $_name, $_type );
		}

		// Handle the properties and methods in separate methods
		$return .= ktExport::_ExportProperties( $c, 'PHP' ) . "\n";
		$return .= ktExport::_ExportMethods( $c, 'PHP' );

		if ( substr($return, -1 ) == ',' )
			$return = substr( $return, 0, -1 );

		// Add the end bracket
		$return .= "\n}\n";

		// Should we return the result?
		if ($return_export) {
			return $return;
		// Otherwise echo it
		} else {
			echo $return;
			return true;
		}
	} // ExportPHP

	/// <summary>
	/// Export a property in PHP format
	/// </summary>
	/// <param name="prop_n">The name of the property</param>
	/// <param name="value">The vale of the property</param>
	/// <param name="n">The number of the the member</param>
	/// <param name="c">The class name</param>
	function _ExportPHP_Property( $prop_n, &$value, $n = 0, $c = '' )
	{
		$return = '';

		// Get the type/class of the value
		$type = ktExport::GetClass($value);

		// Add property name
		$return .= "\tpublic \${$prop_n} =\t";

		// Is it an object
		if ( is_object( $value ) ) {
			// Is it a reference to the current object?
			if ($value == $this->_obj) {
				$return .= '$this /*<kto:this />*/';
      // Are we inside (a descendent of) this object already??)
			} else if ( ktExport::CheckRecursive($value) ) {
				$return .= "null /*<kto:recursive name=\"{$prop_n}\" type=\"ktObject\" class=\"{$type}\" />*/";
			// It's a "normal object"
			} else {
						$return .= 'new ' . $type . '()';
						/*
				$o = '';
				// Is it a ktObject??
				if ( is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' ) )
					// Use the object's own export method
					$o = $value->Export( ktExport::OBJECT_PHP, true );
				else
					// Create a new ktExport object to handle the object
					$o = ktExport::ExportStatic( $value, ktExport::OBJECT_PHP, true, true );
				// Handle indentation
				$return .= str_replace( "\n", "\n\t", trim( $o ) ); */
			}
			$return .= ",\t/* ktObject({$type}) */"; // Signify the type in a comment
		// Is it an array?
		} else if (is_array( $value )) {
			// Export the array
			$arr = ktExport::ExportArray( $value, ktExport::OBJECT_PHP, true );
			$return .= str_replace( "\n", "\n\t",
															trim( $arr ) ); // Handle indentation
			$return .= ",\t/* array */";						// Signify the type in a comment
		// Is it a string?
		} else if (is_string( $value )) {
			$return .= "'" . str_replace('\\"', '"',
													addslashes( $value ) ) . "'";	// Give it proper treatment
			$return .= ",\t/* string */";						// Signify the type in a comment
		// Is it a boolena?
		} else if (is_bool( $value )) {
			$return .= ktLib::bool2str( $value );		// Convert the bool to a string
			$return .= ",\t/* bool */";							// Signify the type in a comment
		// Is it a float number?
		} else if (is_float( $value )) {
			$return .= "\t{$value}";								// Simply output the value
			$return .= ",\t/* float */";						// Signify the type in a comment
		// Is it a integer?
		} else if (is_numeric( $value )) {
			$return .= "\t{$value}";								// Simply output the value
			$return .= ",\t/* integer */";					// Signify the type in a comment
		// Is it a null?
		} else if (is_null( $value )) {
			$return .= "\tnull";										// Output the null keyword
			$return .= ",\t/* NULL */"; 						// Signify the type in a comment
		// Default value...
		} else {
			$return .= "\t'{$value}'";
		}

		return $return . "\n";
	}

	/// <summary>
	/// Export a method in PHP format
	/// </summary>
	/// <param name="meth_n">The name of the method</param>
	/// <param name="n">The number of the the member</param>
	/// <param name="c">The class name</param>
	function _ExportPHP_Method( $meth_n, $n, $c )
	{
		$return = ($n > 0) ? "\n" : '';

		// Create the structure for a method in JSON
		$return .= "\t'{$meth_n}': function() {\n";
		$return .= "\t\t/** <kto:functionCallDef function=\"{$c}::{$meth_n}\" /> ** /\n";
		$return .= "\t\tkacTalk.functionCall( '";
		$return .= $c . "::{$meth_n}', ";
		$return .= "arguments, this );\n";
		$return .= "\t},";

		return $return;
	} // /_ExportPHP_Method

	/// <summary>
	/// Export with KTO as format
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	/// <param name="_name">The name of the object we are exporting</param>
	/// <param name="_type">The type of the object we are exporting</param>
	function ExportKTO( $return_export = false, $_name = '',
											$_type = '' )
	{
		$c = ($_name != '' ? $_name : ( is_array( $this->_obj ) ? 'ARRAY' : ktExport::GetClass( $this->_obj ) ) );
		$return = "class {$c}\n{\n";

		if (isset( $this->_obj )) {
			$n = 0;
			foreach ( $this->_obj->getProperties() as $prop_n => $value ) {
				if ($prop_n[0] == '_')	continue;

				if ($n > 0) $return .= "\n";

				//$value = &$this->_obj->{$prop_n};

				$type = $this->_obj->getTypeOfProperty($prop_n);
				if (!empty( $type )) {
					$return .= "\tpublic {$prop_n} = ";
					if ($value === $this->_obj) {
						$return .= 'this';
					} else if ( ktExport::CheckRecursive($value) ) {
						$c = $this->GetClass( $value );
						$return .= ":recursive";
					} else {
						$return .= /*"\n\t\t" .*/ str_replace( "\n", "\n\t",
								$this->TreatType( $value,
													$type,
													ktExport::OBJECT_PHP,
													$prop_n ) );
						/*$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::KT_XML, true, true ) ) );*/
						//$return .= "\n\t";
					}
					$return .= "; // {$type}";
				} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
					$return .= "\tpublic ktObject {$prop_n} = ";
					if ($value == $this->_obj) {
						$return .= 'null /* $this */';
					} else if ( ktExport::CheckRecursive($value) ) {
						$c = $this->GetClass( $value );
						$return .= ":recursive";
					} else {
						$return .= "\n\t\t" . str_replace( "\n", "\n\t\t",
							trim( $value->Export( ktExport::OBJECT_KTO, true, true ) ) );
						$return .= "\n\t";
					}
					$return .= ";";
				} else if (is_array( $value )) {
					$return .= "\tpublic ktList {$prop_n} = ";
					$return .= str_replace( "\n", "\n\t",
							trim( ktExport::ExportArray( $value, ktExport::OBJECT_KTO, true ) ) ) . ";";
				} else if (is_string( $value )) {
					$return .= "\tpublic ktString {$prop_n} = ";
					$return .= '"' . addslashes( $value ). '";';
				} else if (is_bool( $value )) {
					$return .= "\tpublic ktBool {$prop_n} = ";
					$return .= ktLib::bool2str( $value ) . ";";
				} else if (is_float( $value )) {
					$return .= "\tpublic ktFloat {$prop_n} = {$value};";
				} else if (is_numeric( $value )) {
					$return .= "\tpublic ktInt {$prop_n} = {$value};";
				} else if (is_null( $value )) {
					$return .= "\tpublic {$prop_n} = :null;";
				} else {
					$t = gettype( $value ); //'kto_custom:' . ktExport::GetClass( $value );
					$return .= "\tpublic {$t} {$prop_n} = \"" . addslashes( $value ) . "\";";
				}

				$n++;
			}
			$return .= "\n";

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
	} // ExportKTO

	/// <summary>
	/// Export as a "normal" var_dump
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	function ExportVarDump( $return_export = false )
	{
		// Capture it all
		ob_start();

		// Dump the object
		$ret = var_dump( $this->_obj );

		// Should we return the result?
		if ($return_export) {
			$ret = ob_get_contents();   // Get the result of the dump
			ob_end_clean();             // End and empty the output buffer
		// Just echo it
		} else {
			ob_flush();                 // End and flush (echo) the contents of the output buffer
		}

		// Done
		return $ret;
	} // /ExportVarDump

	/// <summary>
	/// Export as a "normal" var_export
	/// </summary>
	/// <param name="return_export">Should we return the result of the export? (otherwise echo it)</param>
	function ExportVarExport( $return_export = false )
	{
		return var_export( $this->_obj, $return_export );
	} // /ExportVarExport

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
				if ( $json_map || ktLib::isAssoc($array) ) {
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
			case ktEXPORT::OBJECT_KTO: {
				$ret = $this->ExportArrayKTO( $array, $return_export );
				break;
			 }
			case ktEXPORT::_VAR_DUMP: {
				$ret = $this->ExportArrayVarDump( $array, $return_export );
				break;
			 }
			default: {
				throw ktError::E( 'Export->FormatNotSupported(' . ktExport::$_formatString . ",#{$format})",
									"Doesn't support the format: " . ktExport::$_formatString,
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
			if ($n > 0) $return = str_replace(",",',', $return) . "\n";

			//$value = &$this->_obj->{$prop_n};

			if (is_array( $value )) {
				$return .= "\t'{$key}':\t";
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::JSON, true ) );
				$return .= ",\t/* array */";
			} else if (is_string( $value )) {
				$return .= "\t'{$key}': ";
				$return .= "'" . str_replace('\\"', '"', addslashes( $value ) ) . "'";
				$return .= ",\t/* string */";
			} else if (is_bool( $value )) {
				$return .= "\t'{$key}': ";
				$return .= ktLib::bool2str( $value );
				$return .= ",\t/* bool */";
			} else if (is_float( $value )) {
				$return .= "\t'{$key}': ";
				$return .= $value;
				$return .= ",\t/* float */";
			} else if (is_integer( $value )) {
				$return .= "\t'{$key}': ";
				$return .= $value;
				$return .= ",\t/* integer */";
			} else if (is_null( $value )) {
				$return .= "\t'{$key}': null";
				$return .= ",\t/* NULL */";
			} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t'{$key}': ";
				if ($value == $this->_obj) {
					$return .= 'this';
				} else if ( ktExport::CheckRecursive($value) ) {
					$c = $this->GetClass( $value );
					$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
				} else {
					$return .= str_replace( "\n", "\n\t",
																		$value->Export( ktExport::JSON, true ) );
				}
				$return .= ",\t/* ktObject */";
			} else {
				$return .= "\t'{$key}': {$value}";
			}

			$n++;
		}
		$return = str_replace(",",'', $return);

		$return .= "\n}";

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
			$return = trim( $return ) . "]";
			if ($return_export) {
				return $return;
			} else {
				echo $return;
				return true;
			}
		}

		$n = 0;
		foreach ( $array as $key => $value ) {
			if ($n > 0) $return .= "\n";

			//$value = &$this->_obj->{$prop_n};
			$return .= "\t";

			if (is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
				if ($value == $this->_obj) {
					$return .= 'this';
				} else if ( ktExport::CheckRecursive($value) ) {
					$c = $this->GetClass( $value );
					$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
				} else {
					$return .= str_replace( "\n", "\n\t",
						$value->Export( ktExport::JSON, true ) );
				}
				$return .= ",\t/* ktObject */";
			} else if (is_array( $value )) {
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::JSON, true ) );
				$return .= ",\t/* array */";
			} else if (is_string( $value )) {
				$return .= "'" . addslashes( $value ) . "'";
				$return .= ",\t/* string */";
			} else if (is_bool( $value )) {
				$return .= ktLib::bool2str( $value );
				$return .= ",\t/* bool */";
			} else if (is_numeric( $value )) {
				$return .= $value;
				$return .= ",\t/* integer */";
			} else if (is_null( $value )) {
				$return .= "\tnull";
				$return .= ",\t/* NULL */";
			} else {
				$return .= "{$value}";
				$cl = ktExport::LookUpClass( $value );
				if (empty($cl)) $cl = gettype( $value );
				$return .= ",\t/* {$cl} */";
			}

			$n++;
		}
		if ( substr( $return, -1 ) == ',' )
			$return = substr( $return, 0, -1 );

		$return .= "\n]";

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	}
	function ExportArrayKTO( $array, $return_export = false )
	{
		$return = "(\n";

		if ((!is_array( $array )) || empty( $array )) {
			$return = trim( $return ) . ")\n";
			if ($return_export) {
				return $return;
			} else {
				echo $return;
				return true;
			}
		}

		$is_assoc = ktLib::IsAssoc($array);

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			if ( $is_assoc )
				$return .= "\t{$key}: ";
			else
				$return .= "\t";

			//$value = &$this->_obj->{$prop_n};

			if (is_array( $value )) {
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::JSON, true ) );
			} else if (is_string( $value )) {
				$return .= "'" . str_replace('\\"', '"', addslashes( $value ) ) . "'";
			} else if (is_bool( $value )) {
				$return .= ktLib::bool2str( $value );
			} else if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				if ($value == $this->_obj) {
					$return .= 'this';
				} else if ( ktExport::CheckRecursive($value) ) {
					$c = $this->GetClass( $value );
					$return .= "null /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
				}	else {
					$return .= str_replace( "\n", "\n\t",
						$value->Export( ktExport::OBJECT_KTO, true ) );
				}
			} else {
				$return .= " {$value}";
			}

			$n++;
		}

		$return .= "\n)\n";

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

			//$value = &$this->_obj->{$prop_n};
//var_dump( $value );
			if (@is_a( $value, 'ktObject' ) || @is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"ktObject\">";
				if ($value == $this->_obj) {
					$return .= '<kto:this />';
				} else if ( ktExport::CheckRecursive($value) ) {
					$c = $this->GetClass( $value );
					$return .= "\n\t\t\t<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />";
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
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"integer\">";
				$return .= intval( $value );
			} else if ($value == null) {
				$noRet = true;
				$return .= "\t<kto:null name=\"{$key}\" />";
			} else {
				$cl = ktExport::LookUpClass( $value );
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	function ExportArrayXML_RPC( $array, $return_export = false )
	{
		$return = "<array>\n\t\t<data>\n";

		if ((!is_array( $array )) || empty( $array )) {
			return trim( $ret ) . "\t\t</data>\n</array>\n";
		}

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			//$value = &$this->_obj->{$prop_n};

			if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t\t\t<value><ktObject>";
				if ($value == $this->_obj) {
					$return .= '<this />';
				} else if ( ktExport::CheckRecursive($value) ) {
					$c = $this->GetClass( $value );
					$return .= "<recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />";
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	function ExportArrayINI( $array, $return_export = false )
	{
		$return = "<kto:array length=\"" . count( $array ) ."\">\n";

		if ((!is_array( $array )) || empty( $array )) {
			return trim( $ret ) . "</kto:array>\n";
		}

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";

			//$value = &$this->_obj->{$prop_n};

			if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				$return .= "\t\t<kto:value name=\"{$key}\" type=\"ktObject\">";
				if ($value == $this->_obj) {
					$return .= '<kto:this />';
				} else if ( ktExport::CheckRecursive($value) ) {
					$c = $this->GetClass( $value );
					$return .= "<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />";
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	function ExportArrayPHP( $array, $return_export = false )
	{
		$return = "array( \n";

		if ((!is_array( $array )) || empty( $array )) {
			return trim( $ret ) . ")\n";
		}

		$is_assoc = ktLib::IsAssoc($array);

		$n = 0;
		foreach ( $array as $key => $value ) {

			if ($n > 0) $return .= ",\n";


			if ( $is_assoc )
				$return .= "\t'{$key}' => ";
			else
				$return .= "\t";
			//$value = &$this->_obj->{$prop_n};

			if (is_a( $value, 'ktObject' ) || is_subclass_of( $value, 'ktObject' )) {
				if ($value == $this->_obj) {
					$return .= '$this';
				} else if ( ktExport::CheckRecursive($value) ) {
					$c = $this->GetClass( $value );
					$return .= "null; /*<kto:recursive name=\"{$key}\" type=\"ktObject\" class=\"{$c}\" />*/";
				} else {
					$return .= str_replace( "\n", "\n\t",
						trim( $value->Export( ktExport::OBJECT_PHP, true ) ) );
				}
				$return .= "\t/* ktObject */";
			} else if (is_array( $value )) {
				$return .= str_replace( "\n", "\n\t",
							ktExport::ExportArray( $value, ktExport::OBJECT_PHP, true ) ) . " /* array */";
			} else if (is_string( $value )) {
				$return .= "'" . addslashes( $value ) . "'\t/* string */";
			} else if (is_bool( $value )) {
				$return .= ktLib::bool2str( $value ) . "'\t/* bool */";
			} else {
				$return .= "{$value}\t/* " . gettype( $value ) . " */";
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	public static function ExportWrap( $value, $format = ktExport::_DEFAULT,
										$type = ktExport::_DEFAULT_WRAP, $return_export = false )
	{
		$ret = '';
		switch( $type ) {
			case ktExport::EXPORT_WRAP:
				break;
			case ktExport::METH_RESPONSE_WRAP:
			case ktExport::RESPONSE_WRAP:
				/*$val = ktExport::ExportArrayKTO($value,true);
				$val = trim( $val ) . "\n";*/
				if (is_array( $value ) && ( kacTalk::$_kt->GetReturnPureValue() ) ) {
					$val = trim($value['return']);
				}
				break;
			case ktExport::PROP_RESPONSE_WRAP:
				if (is_array( $value ) && ( kacTalk::$_kt->GetReturnPureValue() ) ) {
					$val = trim($value['value']);
				}
				break;
		};

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
			case ktEXPORT::OBJECT_KTO: {
				$ret = ktExport::ExportWrapKTO( $value, $type, $return_export );
				break;
			 }
			default: {
				throw ktError::E( 'Export->FormatNotSupported(' . ktExport::$_formatString . ",#{$format})",
									"Doesn't support the format: {ktExport::$_formatString}",
									"::ExportWrap",
									$this );
			}
		}

		return $ret;
	}

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	public static function ExportWrapJSON( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false )
	{
		$l = ''; $val = $value; $r = '';

		switch( $type ) {
			case ktExport::EXPORT_WRAP:
				//$l = "{\n";
				//$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				//$r = "}";
				break;
			case ktExport::METH_RESPONSE_WRAP:
			case ktExport::RESPONSE_WRAP:
				$val = ktExport::ExportArrayJSON_Mapped($value,true);
				$val = trim( $val ) . "\n";
				break;
			case ktExport::PROP_RESPONSE_WRAP:
				/*if (is_array( $value )) {
					$val = $value['value'];
					$v = trim($val);
					if ( isset($value['kt::Property']) && ( substr($v,-1) != ';' ) ) {
						$l = $value['kt::Property'] . ' = ';
						$r = ';';
					}
				} else {
					$val = $value;
				}											 */
				$val = trim( $val );
				break;
			default: {
				throw ktError::E( 'Export->TypeNotSupported(' . "#{$type})",
									"Doesn't support the type#: {$type}",
									"::ExportWrapJSON",
									$this );
			}
		};

		header( 'Content-Type: application/json' );
		//$ret	= '<?	 ?' . '>' . "\n";
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	public static function ExportWrapJSONP( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false, $padding = '' )
	{
		$l = ''; $val = ''; $r = '';
		if (is_array( $value )) {
			$val = isset($value['value']) ? $value['value'] : $value; // ( isset($value['return']) ? $value['return'] : $value );
		} else {
			$val = $value;
		}

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
				//$l = "{\n";
				//$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				//$r = "}";
				break;
			case ktExport::METH_RESPONSE_WRAP:
			case ktExport::RESPONSE_WRAP:
				$val = ktExport::ExportArrayJSON_Mapped($value,true);
				$val = trim( $val ) . "\n";
				break;
			case ktExport::PROP_RESPONSE_WRAP:
				/*if (is_array( $value )) {
					$val = $value['value'];
					$v = trim($val);
					if ( isset($value['kt::Property']) && ( substr($v,-1) != ';' ) ) {
						$l = $value['kt::Property'] . ' = ';
						$r = ';';
					}
				} else {
					$val = $value;
				}											 */
				$val = trim( $val );
				break;
			default: {
				throw ktError::E( 'Export->TypeNotSupported(' . "#{$type})",
									"Doesn't support the type#: {$type}",
									"::ExportWrapJSON",
									$this );
			}
		};
		/*switch( $type ) {
			case ktExport::EXPORT_WRAP:
				$l = "{\n";
				$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				$r = "}";
				break;
			case ktExport::RESPONSE_WRAP:
			case ktExport::METH_RESPONSE_WRAP:

				break;
			case ktExport::PROP_RESPONSE_WRAP:
				$val = trim( $val );
				if ( ($p = strpos($val, ' = ')) > 0 ) {
					$val = substr( $val, $p + 2, -1 );
					$val = trim( $val );
				}
				 break;
			default: {
				throw new ktError( "Doesn't support the type#: {$type}",
									"::ExportWrapJSONP",
									$this );
			}
		};*/

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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	public static function ExportWrapKTO( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false )
	{
		$l = ''; $val = ''; $r = '';

		switch( $type ) {
			case ktExport::EXPORT_WRAP:
				if (is_array( $value ) && isset($value['value']) ) {
					$val = $value['value'];
				} else {
					$val = $value;
				}
				//$l = "{\n";
				//$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				//$r = "}";
				break;
			case ktExport::RESPONSE_WRAP:
			case ktExport::METH_RESPONSE_WRAP:
				$val = ktExport::ExportArrayKTO($value,true);
				$val = trim( $val ) . "\n";
				break;
			case ktExport::PROP_RESPONSE_WRAP:
				if (is_array( $value ) && isset($value['value']) ) {
					$val = $value['value'];
				} else {
					$val = $value;
				}
				$val = trim( $val ) . "\n";
				break;
			default: {
				throw ktError::E( 'ExportKTO->TypeNotSupported(' . ",#{$type})",
									"Doesn't support the type#: {$type}",
									"::ExportWrapJSON",
									$this );
			}
		};

		header( 'Content-Type: application/json' );
		//$ret	= '<?	 ?' . '>' . "\n";
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	public static function ExportWrapPHP( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false )
	{
		$l = ''; $r = '';
		if (is_array( $value )) {
			$val = isset($value['value']) ? $value['value'] : $value;
		} else {
			$val = $value;
		}

		switch( $type ) {
			case ktExport::EXPORT_WRAP:
				$l = "<?php\n";
				$val = "\t" . trim( str_replace( "\n", "\n\t", $val ) ) . "\n";
				$r = '?' . '>';
				break;
			case ktExport::RESPONSE_WRAP:
			case ktExport::METH_RESPONSE_WRAP:
				$val = ktExport::ExportArrayPHP($value,true);
				$val = trim( $val ) . "\n";
				break;
			case ktExport::PROP_RESPONSE_WRAP:
				$val = trim( $val ) . "\n";
				break;
			default: {
				throw ktError::E( 'Export->TypeNotSupported(' . ",#{$type})",
									"Doesn't support the type#: {$type}",
									"::ExportWrapPHP",
									$this );
			}
		};

		//header( 'Content-Type: application/x-php' );
		header( 'Content-Type: text/plain' );
		//$ret	= '<?	 ?' . '>' . "\n";
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	public static function ExportWrapKT_XML( $value, $type = ktExport::_DEFAULT_WRAP,
												$return_export = false )
	{
		$l = ''; $val = ''; $r = '';

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
						$r	= "\t</kto:methodResponse>\n";
					} else if ($type == ktExport::PROP_RESPONSE_WRAP) {
						$l .= "\t<kto:propertyValue{$n}>\n";
						$r	= "\t</kto:propertyValue>\n";
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
						$r	= "\t</kto:methodResponse>\n";
					} else if ($type == ktExport::PROP_RESPONSE_WRAP) {
						$l .= "\t<kto:propertyValue>\n";
						$r	= "\t</kto:propertyValue>\n";
					}
					$val = "\t" . trim( str_replace( "\n", "\n\t", $value ) ) . "\n";
				}
				$r .= "</kto:response>";
			break;
			default: {
				throw ktError::E( 'Export->TypeNotSupported(' . ",#{$type})",
													"Doesn't support the type#: {$type}",
													"::ExportWrapKT_XML",
													$this );
			}
		};

		header( 'Content-Type: text/xml' );
		$ret	= '<?xml version="1.0"?' . '>' . "\n";
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
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

	/// <summary>
	///
	/// </summary>
	/// <param name=""></param>
	public static function ExportWrapXML_RPC( $value, $type = ktExport::_DEFAULT_WRAP, $return_export = false )
	{
		$return = str_replace( '<kto:', '<', str_replace( '</kto:', '</',
						str_replace( '</kto:array', '</list',
						str_replace( '<kto:array', '<list',
						str_replace( '<kto_custom:', '<',
						str_replace( '</kto_custom:', '</',
						str_replace( 'xmlns:kto', 'xmlns',
							ktExport::ExportWrapKT_XML( $value, $type,
													true )
									) ) ) ) ) ) );

		if ($return_export) {
			return $return;
		} else {
			echo $return;
			return true;
		}
	} // /ExportWrapXML_RPC

	/// <summary>
	/// Handle the value depending on the type
	/// </summary>
	/// <param name="value">The value to treat</param>
	/// <param name="type">The type to treat the value as</param>
	/// <param name="format">The format of the export</param>
	public function TreatType( $value, $type, $format = ktExport::_DEFAULT )
	{
		// Check which type
		switch ($type) {
			// Is it a string?
			case 'string': case 'ktString': {
				// Are we exporting as (KT)XML?
				if (($format == ktExport::KT_XML) ||
						($format == ktExport::XML)) {
					// If the value contains strange/"dangerous" characters ...
					if (!preg_match( '/^[a-zA-Z0-9.\s]+$/', $value )) {
						// ... Wrap in a CDATA-tag
						return "\n\t<![CDATA[" . $value . "]]>\n";
					} else {
						// Return the pure value
						return $value;
					}
				// Are we exporting as XML_RPC?
				} else if ($format == ktExport::XML_RPC) {
					// If the value contains strange/"dangerous" characters ...
					if (!preg_match( '/^[a-zA-Z0-9.\s]+$/', $value )) {
						// ... Wrap in a CDATA-tag
						return "\n\t<string><![CDATA[" . $value . "]]></string>\n";
					} else {
						// Return the pure value in a string-tag
						return '<string>' . $value. '</string>';
					}
				// Are we exporting as JSON(P)?
				} else if ( ($format == ktExport::JSON) || ($format == ktExport::JSONP) ) {
					// Return the string in ' '
					return "'" . $value . "'";
				}
				// Default: Return the string in " "
				return '"' . addslashes( $value ) . '"';
			 }
			// Is it an array?
			case 'array': case 'ktArray': {
				// Let ::ExportArray handle the array
				return ktExport::ExportArray( $format, $value, true );
			 }
			// Is it an object?
			case 'object': case 'ktObject': {
				$ret = '';
				// Is the object an subclass or object of ktObject?
				if (is_subclass_of( $value, 'ktObject' ) || is_a( $value, 'ktObject' )) {
					// Use the method ::Export of the object to export it!
					$ret = $value->Export( $format, true );
				// It's a null?
				} else if ( $value == null ) {
					// If we are exporting as XML-RPC
					if ($format == ktExport::XML_RPC) {
						// Return a nil-tag
						return '<nil/>';
					}
					// Just null...
					return 'null';
				// It's an normal object (TODO: This should probably do more!)
				} else {
					// Export the object
					$ret = ktExport::ExportStatic( $value, $format,	true, false );
				}

				// Return the result
				return $ret;
			 }
			// Is it a boolean?
			case 'bool': case 'booleskt': case 'boolean': {
				// Are we exporting as XML_RPC?
				if ($format == ktExport::XML_RPC) {
					// Wrap the value in a boolean tag
					return '<boolean>' . ktLib::bool2str( $value ). '</boolean>';
				}
				// Return the boolean value as a string
				return ktLib::bool2str( $value );
			 }
			// Is it an integer?
			case 'int': case 'integer': {
				// Are we exporting as XML_RPC?
				if ($format == ktExport::XML_RPC) {
					// Wrap the value in a int tag
					return '<int>' . intval( $value ). '</int>';
				}
				// Turn the string into a integer value and return it
				return intval( $value );
			 }
			// Is it a float or double?
			case 'float': case 'double': {
				// Are we exporting as XML_RPC?
				if ($format == ktExport::XML_RPC) {
					// Wrap the value in a double tag
					return '<double>' . floatval( $value ). '</double>';
				}
				// Turn the string into a float value and return it
				return floatval( $value );
			 }
			// Is it a date?
			case 'datetime': case 'date': {
				// If the value is a number ...
				if (is_numeric( $value )) {
					// ... convert it to a ISO-date string
					$value = date( "c", $value );
				}

				// Are we exporting as XML_RPC?
				if ($format == ktExport::XML_RPC) {
					// Wrap the value in a datetime tag
					return '<dateTime>' . $value . '</datetime>';
				}
				// Default: return the pure value
				return $value;
			 }
			// Default...
			default: {
				// Return the pure value
				return $value;
			 }
		};
	} // /TreatType

	/// <summary>
	/// Look up the class name (or type) of an object or PHP class
	/// </summary>
	/// <param name="class">The class name or object to look up</param>
	public static function GetClass( $class )
	{
		if ( is_object( $class ) ) {
			$cn = ktExport::LookUpClass( $c = get_class( $class ) );
			if ( empty($cn) )
				$cn = $c;
			return $cn;
		} else
			return gettype( $class );
	} // GetClass

	/// <summary>
	/// Look up the class name (given to kacTalk) of an object or PHP class
	/// </summary>
	/// <param name="class">The class name or object to look up</param>
	public static function LookUpClass( $class )
	{
			// Find the class name via the kactalk object/instance
			return kacTalk::$_kt->GetClassName( $class );
	} // /LookUpClass

	/// <summary>
	/// Add an object to the object stack
	/// </summary>
	/// <param name="object">The object to put on the stack</param>
	protected static function StackObject( &$object )
	{
		// Init the arrays if it isn't done already!
		if (!is_array( ktExport::$_object_stack )) { ktExport::$_object_stack = array(); }
		if (!is_array( ktExport::$_object_stack_n )) { ktExport::$_object_stack_n = array(); }

		// Add the object to the object stack ...
		ktExport::$_object_stack[] = $object;
		// ... and the name to the name stack
		ktExport::$_object_stack_n[] = $c = strtolower( ktExport::LookUpClass( $object ) )	;
	} // /StackObject

	/// <summary>
	/// Pop the last object of the object stack
	/// </summary>
	protected static function PopObject( )
	{
		//We can't do anything with an empty stack!
		if (empty( ktExport::$_object_stack )) { return null; }

		// Pop the name
		array_pop( ktExport::$_object_stack_n );
		// Pop the object and return it
		return array_pop( ktExport::$_object_stack );
	} // /PopObject

	/// <summary>
	/// Check if $object appears in the object stack
	/// </summary>
	/// <param name="object">The object to look for</param>
	protected static function CheckRecursive( &$object )
	{
		// Look for the object in the stacks
		return ktLib::IsInArray( ktExport::$_object_stack_n,
													 strtolower( ktExport::GetClass( $object ) ) ) ||
						 ktLib::IsInArray( ktExport::$_object_stack, $object );
	} // /CheckRecursive

	/// <summary>
	/// Translate a string format (given by the client) to a ENUM
	/// </summary>
	/// <param name="format">The format string to translate</param>
	public static function TranslateFormat( $format )
	{
		// Ignorethe case of the letters
		$format = strtolower($format);
		// Check the format
		switch( $format ) {
			case 'kt': case 'kt_xml': case 'ktxml': {
				ktExport::$_formatString = 'KT_XML';
				return ktExport::KT_XML;
			}
			case 'xml': {
				ktExport::$_formatString = 'XML';
				return ktExport::XML;
			}
			case 'xml_rpc': case 'rpc': {
				ktExport::$_formatString = 'RPC';
				return ktExport::XML_RPC;
			}
			case 'json': {
				ktExport::$_formatString = 'JSON';
				return ktExport::JSON;
			}
			case 'jsonp': {
				ktExport::$_formatString = 'JSONP';
				return ktExport::JSONP;
			}
			case 'ini': {
				ktExport::$_formatString = 'INI';
				return ktExport::INI;
			}
			case 'conf': case 'aconf': case 'apache_conf': {
				ktExport::$_formatString = 'ACONF';
				return ktExport::APACHE_CONF;
			}
			case 'js': case 'object_js': {
				ktExport::$_formatString = 'OBJECT_JS';
				return ktExport::OBJECT_JS;
			}
			case 'php': case 'object_php': {
				ktExport::$_formatString = 'PHP';
				return ktExport::OBJECT_PHP;
			}
			case 'kto': case 'kts': case 'object_kt': {
				ktExport::$_formatString = 'KTO';
				return ktExport::OBJECT_KTO;
			}
			case '': case 'def': case 'default': case '_def': case '_default': case '_': {
				ktExport::$_formatString = $format;
				return ktExport::_DEFAULT;
			}
			default: {
				ktExport::$_formatString = $format;
				return ktExport::NONE;
			}
		};
	} // /TranslateFormat


	public static $_formatString = 'KT';
	protected $_obj = null;

	protected $_parent = null;

	protected static $_object_stack = array();
	protected static $_object_stack_n = array();
};

?>
