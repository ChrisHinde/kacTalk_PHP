<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - Mr_CHISOL - Kachtus 2008
 * @license CC-GNU GPL v2
 */
defined( '_KACTALK_VALID' ) or die( 'Restricted Access!' );

class ktXML_Element extends ktObject implements ArrayAccess,Iterator
{
	protected $_parent;
	public $_name = '';
	public $_attr = array();
	public $_value = '';
	public $_childs = array();
	public $_path = '';

	public function ktXML_Element( $parent, $name = null, array $attr = array() )
	{
		//$this->_parent = $parent;
		$this->_name = $name;
		$this->_attr = $attr;
	}

	public function addChild( $elm )
	{
		if (!is_a( $elm, 'ktXML_Element' )) {
			return false;
		}
		if (!is_array( $this->_childs )) {
			$this->_childs = array();
		}

		$name = (!empty( $elm->_name )? $elm->_name : 'element' );
		if (isset( $this->{$name} )) {
			if (!is_array( $this->{$name} )) {
				$this->{$name} = array( $this->{$name}, $elm );
			} else {
				$this->{$name}[] = $elm;
			}
		} else {
			$this->{$name} = $elm;
			$this->_childs[] = $name;
		}

		return true;
	}

	public function hasChild()
	{
		return !empty( $this->_childs );
	}
	public function hasAttributes()
	{
		return !empty( $this->_attr );
	}

	public function getElement( $path )
	{
		while ( $path[0] == '/' ) {
			$path = substr( $path, 1 );
		}
		if (empty( $path )) {
			return $this;
		}

		$pos = strpos( $path, '/' );
		if ($pos <= 0) $pos = strlen( $path );
		$key = substr( $path, 0, $pos );
		$path = substr( $path, $pos );

		if (in_array( $key, $this->_childs )) {
			if (is_a( $this->{$key}, 'ktXML_Element' )) {
				return $this->{$key}->getElement( $path );
			} else if (empty( $path )) {
				return $this->{$key};
			}
		} else if (($key == '_value') && empty( $path )) {
			return $this->_value;
		} else if (($key == '_attr')) {
			if (empty( $path )) {
				return $this->_attr;
			} else {
				while ( $path[0] == '/' ) {
					$path = substr( $path, 1 );
				}
				while ( $path[strlen($path)-1] == '/' ) {
					$path = substr( $path, 0, -1 );
				}
				return $this->_attr[$path];
			}
		}

		return null;
		//return $this->data->getElement( $path );
	}

	function offsetExists( $name )
	{
		return isset( $this->_attr[$name] );
	}
	function offsetGet( $name )
	{
		if (empty( $name )) { return null; }
//kLib::var_dump( $this->_attr );
		if (isset( $this->_attr[$name] )) {
			return $this->_attr[$name];
		} else {
			return null;
		}
	}
	function a( $name )
	{
		return $this->_attr[$name];
	}

	function offsetSet( $name, $value )
	{
		$this->_attr[$name] = $value;
	}
	function offsetUnset( $name )
	{
		unset( $this->_attr[$name] );
	}

	public function rewind()	{ reset( $this->_childs );		}
	public function key()		{ return current( $this->_childs );	}
	public function	current()	{ return $this->{$this->key()};		}
	public function	next()
	{
		$next = next($this->_childs);
		if(empty( $next )) { return false; }
		return $this->{$next};
	}
	public function valid()
	{
		$key	= $this->key();
		return ($key !== false) && ($key[0] != '_');
	}

	public function __toString()	{ return $this->_value;			}
/*
	public function __call($name, $args) {
        	echo "Calling object method '$name' " . implode(', ', $args). "\n";
	}*/
}

class ktXML
{
	// XML parser variables
	protected $parser;
	protected $getType;
	public $data = null;
	protected $stack = array();
	protected $nameStack = array();
	public $keys;
	public $path;

	const GET_FILE		= 42001;
	const GET_CONTENTS	= 42002;
	const GET_AUTO		= 42003;
	const _DEFAULT = ktXML::GET_AUTO;

	// function with the default parameter value
	function ktXML( $url = null, $type = ktXML::_DEFAULT ) {
		if (is_string( $type )) $type = ktXML::TranslateType( $type );

		if (_DEBUG) { echo "ktXML( url, {$type} );\n"; }
		$this->getType	= $type;
		$this->url		= $url;
		$this->parse();
	}

	// parse XML data
	function parse()
	{
		if (_DEBUG) { echo "ktXML::Parse( );\n"; }

		$data = '';
		$this->parser = xml_parser_create( 'UTF-8' );
		xml_set_object( $this->parser, $this );
		xml_set_element_handler( $this->parser, 'startXML', 'endXML' );
		xml_set_character_data_handler( $this->parser, 'charXML' );

		xml_parser_set_option( $this->parser, XML_OPTION_CASE_FOLDING, false );

		if ($this->getType == ktXML::GET_AUTO) {
			if (_DEBUG) { echo "ktXML::Parse: AUTO\n"; }
			if ( (strpos( $this->url, "\n" ) === false) &&
					(!preg_match( '/<[^>]>/', $this->url )) ) {
				$type = ktXML::GET_FILE;
			if (_DEBUG) { echo "ktXML::Parse: AUTO : URL\n"; }
			} else {
				$type = ktXML::GET_CONTENTS;
			if (_DEBUG) { echo "ktXML::Parse: AUTO : CONTENTS\n"; }
			}
		} else {
			$type = $this->getType;
		}

		if ($type == ktXML::GET_FILE) {
			if (_DEBUG) { echo "ktXML::Parse: OPEN FILE {$this->url}\n"; }
			if (!($fp = @fopen($this->url, 'rb'))) {
				throw new ktError( "Cannot open {$this->url}",
									"::Parse",
									$this );
			}

			while (($data = fread($fp, 8192))) {
				if (!xml_parse($this->parser, $data, feof($fp))) {
					throw new ktError( sprintf('XML error at line %d column %d',
							xml_get_current_line_number($this->parser),
							xml_get_current_column_number($this->parser)),
						"::Parse", $this );
				}
			}
		} else if ($type == ktXML::GET_CONTENTS) {
			$lines = explode("\n",$this->url);
			foreach ($lines as $val) {
				if (_DEBUG) { echo "ktXML::Parse: CONTENTS: Line: {$val}\n"; }
				if (trim($val) == '')
					continue;
				$data = $val . "\n";
				if (!xml_parse($this->parser, $data)) {
					if (_DEBUG) echo 'ktXML::Parser : DATA: ' . $data.'<br />';
					throw new ktError( sprintf('XML error at line %d column %d',
							xml_get_current_line_number($this->parser),
							xml_get_current_column_number($this->parser)),
						"::Parse", $this );
				}
			}
		}
	}

	function startXML( $parser, $name, $attr ) {
		if (_DEBUG) { echo "ktXML::startXML: {$name} ({$attr})\n"; }
		if (!is_array( $this->stack )) {
			$this->stack = array();
		}
		if (!is_array( $this->nameStack )) {
			$this->nameStack = array();
		}
		if (!isset( $this->data )) {
			$this->data = new ktXML_Element( $this, '#ROOT' );
			$this->stack[] = $this->data;
			$this->nameStack[] = '#ROOT';
		}

		$this->nameStack[] = $name;
		/*$keys = '';
		$total = count($this->stack)-1;
		$i = 0;
		foreach ($this->stack as $key => $val) {
			if (count($this->stack) > 1) {
				if ($total == $i)
					$keys .= $key;
				else
					$keys .= $key . '|'; // The saparator
			} else
				$keys .= $key;
			$i++;
		}*/
		$elm = new ktXML_Element( $this, $name, $attr );
		$elm->path = join( '/', $this->nameStack );
		$this->currentElm()->addChild( $elm );
		$this->stack[] = $elm;
	}

	function endXML($parser, $name) {
		if (_DEBUG) { echo "ktXML::endXML: {$name}\n"; }
		if ($this->currentName() == $name) {
			array_pop($this->nameStack);
			array_pop($this->stack);
		}
	}

	function charXML($parser, $data) {
		if (_DEBUG) { echo "ktXML::charXML: {$data}\n"; }
		if (trim($data) != '') {
			$this->currentElm()->_value = $data;
			/*$startFrom = count($this->data[$this->keys])-1; // fixes weird splitting (bug?)
			$startFrom = $startFrom == -1 ? $startFrom = 0 : $startFrom;
			$this->data[$this->keys]['data'][$startFrom] .= trim(str_replace("\n", '', $data));*/
		}
	}

	protected function currentName()
	{
		if (is_array( $this->nameStack ) && (!empty( $this->nameStack ))) {
			return $this->nameStack[count( $this->nameStack ) - 1];
		}
	}
	protected function &currentElm()
	{
		if (is_array( $this->stack ) && (!empty( $this->stack ))) {
			return $this->stack[count( $this->stack ) - 1];
		}
	}

	static function TranslateType( string $type ) {
		switch (strtolower( $type )) {
			case 'url':  case 'uri':
			case 'file':
				return ktXML::GET_FILE;
			break;
			case 'content': case 'contents':
			case 'xml':
				return ktXML::GET_CONTENTS;
			break;
			case 'auto':	case 'auto_type':
			case 'automatic':
				return ktXML::GET_AUTO;
			break;
		};
		return ktXML::_DEFAULT;
	}

	public function getElement( $path )
	{
		if (!is_a( $this->data, 'ktXML_Element' )) {
			return null;
		}
		if ( substr( $path, 0, 5 ) == '#ROOT' ) {
			$path = substr( $path, 5 );
		}
		while ( $path[0] == '/' ) {
			$path = substr( $path, 1 );
		}

		return $this->data->getElement( $path );
	}

	public function asXML()
	{
	}
};

?>
