<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - Mr_CHISOL - Kachtus 2008
 * @license CC-GNU GPL v2
 */
defined( '_KACTALK_VALID' ) or die( 'Restricted Access!' );

require_once( 'ktxml.php' );

class ktImportedObject extends ktObject
{
	protected $_kactalk	= null;
	protected $_host	= 'localhost';
	protected $_uri		= null;
	protected $_index	= 'index.php';

	public function __construct( $name )
	{
		parent::__construct( $name );
	}

	protected function _Call( $uri_ = null )
	{
		$uri = $this->_uri;
//var_dump( 'URI1:', $uri );
		if (!is_array( $uri )) {
			$uri = $uri_;
		} else if (is_array( $uri_ )) {
			array_shift( $uri_ );
			$n = 0;
			foreach( $uri_ as $k => $v ) {
				if ((substr( $k, 0, 5 ) == 'extra') && is_array( $v )) {
					foreach( $v as $k2 => $v2 ) {
						$uri['extra' . $n] = $v2;
						$n++;
					}
				} else {
					$uri['extra' . $n] = $v;
					$n++;
				}
			}
		}
//var_dump( 'URI2:', $uri );
//var_dump( 'URI_:', $uri_ );
		return $this->_kactalk->Call( $this->_host, $uri, $this->_index );
	}
}

class ktImport
{
	const KT_XML		= 31420;			// -   x  (??)
	const JSON			= 31421;			// -   x  (?)
	const XML			= 31422;			// -   x
	const XML_RPC		= 31423;			// -   -
	const APACHE_CONF	= 31424;			// -   -
	const INI			= 31425;			// -   -
	const OBJECT_KTS	= 31426;			// -   -
	const OBJECT_PHP	= 31427;			// -   -
	const OBJECT_JS		= ktImport::JSON;	// -   x (*)
	const _AUTO			= 31428;
	const _DEFAULT		= ktImport::KT_XML;

	public $object;
	public $format;
	public $content;

	protected $_kactalk			= null;
	protected $_host			= 'localhost';
	protected $_uri				= null;
	protected $_index			= 'index.php';
	protected $_object_tmpl		= null;
	protected $_object_stack	= array();
	protected $_object_t_stack	= array();

	public function __construct( $content = '', $format = ktImport::_DEFAULT, $autoParse = true,
								$kt = null, $host = 'localhost', $uri = '', $index = 'index.php' )
	{
		$this->_kactalk	= $kt;
		$this->_host	= $host;
		$this->_uri		= $uri;
		$this->_index	= $index;

		$this->content	= $content;
		$this->format	= $format;
		if (empty( $content )) {
			$autoParse = false;
		}
		if ($autoParse) {
			$this->Import( $content, $format );
		}
	}

	public function Import( $content, $format = ktImport::_AUTO )
	{
		if (!empty( $content )) {
			$this->content = $content;
		}
		if (empty( $this->content )) {
			return null;
		}
		if ($format == ktImport::_AUTO) {
			$format = $this->format;
		}
		$this->_format = $format;

		switch ($format) {
			case ktImport::KT_XML:
				$this->object = $this->ImportKT_XML( );
			break;
			default: {
				throw new ktError( "Doesn't support the format#: {$format}",
									"::Import",
									$this );
			}
		}

		return $this->object;
	}

	public function Import_Export( $data )
	{
		if (empty( $data )) {
			return null;
		}

		switch ($format) {
			case ktImport::KT_XML:
				return $this->ImportKT_XML_Export( );
			break;
			default: {
				throw new ktError( "Doesn't support the format#: {$format}",
									"::Import_Export",
									$this );
			}
		}
	}

	public function ImportKT_XML( $prefix = 'kto:' )
	{
		$xml = new ktXML( $this->content );
		$this->_prefix = $prefix;
		//var_dump( $xml->data/*->{'kto:response'}->{'kto:methodResponse'}->{'kto:return'}->{'kto:value'} */);
		//var_dump( $xml->getElement( '#ROOT/kto:response/kto:methodResponse/kto:return/kto:value/_value' ) );

		if ( in_array( $prefix . 'export', $xml->data->_childs ) ) {
			return $this->ImportKT_XML_Export( $xml, $prefix );
		} else if ( in_array( $prefix . 'response', $xml->data->_childs ) ) {
			return $this->ImportKT_XML_Response( $xml, $prefix );
		}
	}
	protected function ImportKT_XML_Export( &$xml, $prefix = 'kto:', $createObject = true )
	{
		$xml_o = null;
//*D*echo "== ImportKT_XML_Export ==\n";
		if (is_a( $xml, 'ktXML' )) {
			$xml_o = $xml->getElement( $prefix . 'export/' . $prefix . 'object' );
		} else if (is_a( $xml, 'ktXML_Element' )) {
			$xml_o = $xml;
		}
//*D*print_r($xml_o );

		if (isset( $this->object )) {
			$this->_object_stack[] = clone $this->object;
		} else {
			$this->_object_stack[] = null;
		}
		if (isset( $this->_object_tmpl )) {
			$this->_object_t_stack[] = new ArrayObject( $this->_object_tmpl );
		} else {
			$this->_object_t_stack[] = array( 'name' => '#ROOT', 
			'values' => array(),
			'methods' => array(),
			'constructor_code' => '' );
		}
		$this->_object_tmpl = array(
			'name' => $xml_o->a('name'),//$xml->getElement( $prefix . 'export/' . $prefix . 'object/_attr/name' ),
			'values' => array(),
			'methods' => array(),
			'constructor_code' => '' );
		$this->object = null;
//*D*var_dump( '_o_tmp:', $this->_object_tmpl, '_o_stack:', $this->_object_t_stack );
		$this->_object_tmpl['tmpl_name'] = $this->getTmplName( $this->_object_tmpl['name'] );

		if ( is_array( $xml_o->_childs ) && !empty( $xml_o->_childs ) ) {
			foreach ( $xml_o->_childs as $child ) {
				if ($child == $prefix . 'value') {
					$this->ImportKT_XML_Value( $xml_o->{$child}, $prefix );
				} else if ($child == $prefix . 'method') {
					$this->ImportKT_XML_Method( $xml_o->{$child}, $prefix );
				}
			}
		}

		if ($createObject) {
			$o = $this->BuildObject( );
			//$o = $this->object;
		} else {
			$class_code = $this->BuildClass( $this->_object_tmpl );
			$o = $this->_object_tmpl['tmpl_name'];
			$this->_object_tmpl['class_code'] = $class_code;

			eval( $class_code );
		}
//*D*echo "+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+--------\n";
//*D*var_dump( '_o_tmp:', $this->_object_tmpl, '_o_stack:', $this->_object_t_stack );
		//array_pop( $this->_object_t_stack );
		$this->_object_tmpl = array_pop( $this->_object_t_stack );//end( $this->_object_t_stack );
		$this->object = &array_pop( $this->_object_stack );
//*D*var_dump( '_o_tmp:', $this->_object_tmpl, '_o_stack:', $this->_object_t_stack );
		return $o;
	}
	protected function ImportKT_XML_Response( &$xml, $prefix = 'kto:' )
	{
		//echo "\nImportKT_XML_Response\n";
		$respElm = $prefix . 'methodResponse';
		$resp = array(
			'name' => $xml->getElement( $prefix . 'response/' . $respElm . '/_attr/name' ),
			'return' => '',
			'output' => '' );

		$ret_xml = $xml->getElement( "{$prefix}response/{$respElm}/{$prefix}return" );
		if (isset( $ret_xml ) && isset( $ret_xml->{$prefix . 'value'} )) {
			$resp['return'] = $this->ImportKT_XML_Value_( $ret_xml, $prefix );
		} else {
			$resp['return'] = null;
		}
		$out_xml = $xml->getElement( "{$prefix}response/{$respElm}/{$prefix}output" );
		if (isset( $out_xml ) && isset( $out_xml->{$prefix . 'value'} )) {
			$resp['output'] = $this->ImportKT_XML_Value_( $out_xml, $prefix );
		} else {
			$resp['output'] = null;
		}

		return $resp;
	}

	public function ImportKT_XML_Value_( $elm, $prefix = 'kto:' )
	{//*D*echo "++ ImportKT_XML_Value__ ++\n";
		if (is_array( $elm )) {
			foreach( $elm as $val ) {
				$this->ImportKT_XML_Value_( $val );
			}

			return true;
		}
		//*D*var_dump( $elm );

		$value = $elm->{$prefix.'value'};
		$val = $this->TreatType( $value->_value, $value->_attr['type'], $value );

		return $val;
	}

	public function ImportKT_XML_Value( $value, $prefix = 'kto:' )
	{//*D*echo "-- ImportKT_XML_Value --\n";
//*D*var_dump( $value );
		if (is_array( $value )) {
			foreach( $value as $val ) {
				$this->ImportKT_XML_Value( $val );
			}

			return true;
		}
		$this->AddProperty( $value->_attr['name'], $value->_value, $value->_attr['type'], $value );
		/*/$this->object->{$value->_attr['name']} = $this->TreatType( $value->_value,
																	$value->_attr['type'] );/**/
	}

	public function ImportKT_XML_Method( $method, $prefix = 'kto:' )
	{
		if (is_array( $method )) {
			foreach( $method as $meth ) {
				$this->ImportKT_XML_Method( $meth, $prefix );
			}

			return true;
		}

		if (in_array( $prefix . 'functionCallDef', $method->_childs )) {
			$type = 'callback';
			$code = '';
		}

		$this->AddMethod( $method->_attr['name'], $code, $type );
	}

	protected function AddProperty( $name, $value, $type = '', $valueObj = null )
	{
//*D*echo "## AddProp ##\n";
//*D*var_dump( $valueObj->_childs[0],isset($valueObj), $type,  substr(strstr($valueObj->_childs[0],':'),1) );
		if (isset( $valueObj ) &&
				((strtolower($type) == 'array') || (strtolower($type) == 'list')) &&
				((strtolower(substr(strstr($valueObj->_childs[0],':'),1)) == 'array') ||
					(strtolower(substr(strstr($valueObj->_childs[0],':'),1)) == 'list'))) {
			$arr = $valueObj->{$valueObj->_childs[0]};
			$value = $this->ValueAsArray( $arr );
		} else if (isset( $valueObj ) &&
				((strtolower($type) == 'ktobject') ||
					(strtolower($type) == 'object')) ) {
//*D*var_dump( 'val:', $value, 'valObj:', $valueObj );
			$cname = $this->ImportKT_XML_Export( $valueObj->{'kto:object'}, $this->_prefix, false );
			$n_uri_arr = ktLib::ArrayCopy( $this->_uri );
			$n_uri_arr['member'] = $name;
			$n_uri = var_export( $n_uri_arr, true );
			$this->_object_tmpl['constructor_code'] .= "\n\t\t\$this->$name = new $cname( \$this->_kactalk, \$this->_host, $n_uri, \$this->_index );";
//*D*var_dump( 'R:', $cname, '_O_t:', $this->_object_tmpl );
		}
//*D*var_dump( $valueObj);
		$this->_object_tmpl['values'][$name] = array( 'v' => $value, 't' => $type );
//*D*var_dump( 'AP_OT:', $this->_object_tmpl );
	}
	protected function AddMethod( $name, $code, $type )
	{
		$this->_object_tmpl['methods'][$name] = array( 'code' => $code, 't' => $type );
	}

	public function TreatType( $value, $type )
	{
//*D*echo 'TT:' . $type .";\n";
		switch (strtolower($type)) {
			case 'float': case 'double':
				return floatval( $value );
			case 'int': case 'integer':
				return intval( $value );
			case 'bool': case 'boolean':
				return (( $value == 'true' ) || ( $value == '1' ));
			case 'null':
				return null;
			case 'array': case 'list':
				return $this->ValueAsArray( $value );
			case 'ktobject': {
//*D*var_dump( 'TTktOBJ', $value );
				$this->_object_tmpl['constructor_code'] .= 'echo 42;';
				return null;
			}
		}
		return $value;
	}
	public function TreatType_str( $value, $type )
	{
//*D*echo 'TT_str:' . $type .";\n";
		switch (strtolower($type)) {
			case 'float': case 'double':
				return floatval( $value );
			case 'int': case 'integer':
				return intval( $value );
			case 'bool': case 'boolean':
				return (( $value == 'true' ) || ( $value == '1' )) ? 'true' : 'false';
			case 'null':
				return 'null';
			case 'string':
				return '"' . addslashes( $value ) . '"';
			case 'array': case 'list':
				return $this->ValueAsArray_str( $value );
			case 'ktobject': {
//*D*var_dump( 'TTktOBJ_str', $value );
				if ($value == null) {
					return 'null';
				}
				/*/$this->Import( $value, $this->_format );
				if ( in_array( $this->_prefix . 'export', $xml->data->_childs ) ) {* /
				$r = $this->ImportKT_XML_Export( &$value, $this->_prefix );
				var_dump( $r, $this->_object_tmpl );*/
				$this->_object_tmpl['constructor_code'] .= 'echo 42;';
				return 'null';
			}
		}
		return 'null';
	}

	public function ValueAsArray( $value )
	{
//*D*echo "__ ValuesAsArray __\n";
		$values = $value->{$value->_childs[0]};
		$ret = array();

		foreach ($values as $val) {
//var_dump( $val );
			$v = $this->TreatType( $val->_value, $val->a('type'), $val );
			$ret[$val->_attr['name']] = $v;
		}

		return $ret;
	}

	public function ValueAsArray_str( $value )
	{
		return var_export( $value, true );
		//return '0';
	}

	protected function BuildObject()
	{
		$class_code = $this->BuildClass( $this->_object_tmpl );
		$cname = $this->_object_tmpl['tmpl_name'];
		$this->_object_tmpl['class_code'] = $class_code;
//echo $class_code;
		eval( $class_code );

		return new $cname( $this->_kactalk, $this->_host, $this->_uri, $this->_index );
	}

	protected function BuildClass( $tmpl )
	{
//*D*echo "=??=?= BuildClass =?=??=\n";
//*D*var_dump( $tmpl );
		$class = 'class ' . $tmpl['tmpl_name'] . " extends ktImportedObject {\n";

		$prop_types = array();

		foreach ($tmpl['values'] as $v_n => $v ) {
			$t = '';
			if (is_array( $v )) {
				$t = $v['t'];
				$val = $this->TreatType_str( $v['v'], $v['t'] );
			} else {
				$val = $v;
				//$t = get
			}

			$class .= "\tpublic \${$v_n} = {$val};\n";
			$prop_types[] = "'{$v_n}' => '{$t}'";
		}
		$class .= "\n";

		$class .= "\tpublic function __construct( \$kt = null, \$host = 'localhost', \$uri = null, \$index = 'index.php' ) {\n";
		$class .= "\t\t\$this->_kactalk	= \$kt;\n";
		$class .= "\t\t\$this->_host	= \$host;\n";
		$class .= "\t\t\$this->_uri		= \$uri;\n";
		$class .= "\t\t\$this->_index	= \$index;\n";
		$class .= "\t\t\$this->_property_types = array( " . join( ', ', $prop_types ) . " );\n";
		$class .= $tmpl['constructor_code'] . "\n";
		$class .= "\t\tparent::__construct( '{$tmpl['name']}' );\n";
		$class .= "\t}\n";

		$class .= "\n";

		foreach ($tmpl['methods'] as $m_n => $m ) {
			if (is_array( $m )) {
				$meth = $this->TreatType_str( $m['v'], $v['t'] );
			} else { $meth = $m; }

			$class .= "\tpublic function {$m_n}()\n\t{\n";
			$class .= "\t\t\$uri = array( 'object' => \$this->_object_name, 'member' => '{$m_n}', 'extra' => func_get_args() );\n";
			/*$class .= "\t\tif (isset( \$this->_uri ) && isset( \$this->_uri['object'] )) {\n";
			$class .= "\t\t\t\$uri['object'] = \$this->_uri['object'];\n";
			$class .= "\t\t}\n\n";*/
			//$class .= "\t\tforeach( ;\n";
			//$class .= "\t\t ;\n";
			$class .= "\t\t\$ret_a = \$this->_Call( \$uri );\n";
			//$class .= "\t\t var_dump( \$ret_a );\n";
			$class .= "\t\t\$ret = \$ret_a['return'];\n";
			$class .= "\t\techo \$ret_a['output'];\n";
			$class .= "\t\treturn \$ret;\n";
			$class .= "\t}\n";
		}

		$class .= '}';

		return $class;
	}

	protected function getTmplName( $name )
	{
		$r = rand( 0, 42424242 );
		return 'ktObjectTmpl' . ucfirst( $name ) . "_{$r}";
	}

	public static function TranslateFormat( $format )
	{
		switch( strtolower( $format ) ) {
			case 'kt': case 'kt_xml': case 'ktxml': {
				return ktImport::KT_XML;
			}
			case 'xml': {
				return ktImport::XML;
			}
			case 'xml_rpc': case 'rpc': {
				return ktImport::XML_RPC;
			}
			case 'json': {
				return ktImport::JSON;
			}
			case 'ini': {
				return ktImport::INI;
			}
			case 'conf': case 'aconf': case 'apache_conf': {
				return ktImport::APACHE_CONF;
			}
			case 'js': case 'object_js': {
				return ktImport::OBJECT_JS;
			}
			case 'php': case 'object_php': {
				return ktImport::OBJECT_PHP;
			}
			case 'kto': case 'kts': case 'object_kt': {
				return ktImport::OBJECT_KTS;
			}
			default: {
				return ktImport::_DEFAULT;
			}
		};
	}
}
