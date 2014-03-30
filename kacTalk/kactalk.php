<?php
/**
 * @package Kachtus Talk Objects (Kachtus Object Format/XML)
 * @version v0.1b
 * @copyright Christopher Hindefjord - Mr_CHISOL - Kachtus 2008
 * @license CC-GNU GPL v2
 */

define( '_KACTALK_VALID', 1 );

define( '_KACTALK_NAME', 'kacTalk' );
define( '_KACTALK_VERSION', 'v0.1b' );
define( '_KACTALK_VERSION_F', 0.101 );
define( '_KACTALK_AUTHOR', "Christopher Hindefjord (2014)" );
define( '_KACTALK_LONG_NAME', 'Kachtus KacTalk (PHP)' );
define( '_KACTALK_LONG_NAME_VERSION', _KACTALK_LONG_NAME . ' ' . _KACTALK_VERSION );

require_once( 'ktlib.php'		);
require_once( 'kterror.php'		);
require_once( 'ktobject.php'	);
require_once( 'ktxml.php'		);
require_once( 'ktnet.php'		);

class kacTalk
{
	const PROTOCOL_HTTP = 30001;
	const _DEFAULT_PROT = kacTalk::PROTOCOL_HTTP;

	public function __construct( $object = null, $getRoute = null )
	{
		if (empty( $getRoute )) {
			$this->_routeClass = 'ktLib';
		} else {
			$this->_routeClass = $getRoute;
		}
    kacTalk::$_kt = $this; 
    
    $this->AddExtraParameters();
    
    $this->_returnPureValue = isset( $this->_extraParameters['pureValue'] )
                                && ( $this->_extraParameters['pureValue'] == '1' );
	}
          
  public function GetReturnPureValue()
  {
    return $this->_returnPureValue;
  }
  
  public function AddExtraParameters()
  {
    $this->_extraParameters = array();
    
    foreach ( $_REQUEST as $k => $v ) {
      $this->_extraParameters[$k] = $v;    
    }
  }
  public function GetExtraParameters()
  {
    return $this->_extraParameters;
  }
  public function GetExtraParameter( $key )
  {
    return $this->_extraParameters[$key];
  }
  
  public function SetAPIKey( $apiKey )
  {
    $this->_apiKey = $apiKey;
  }
  public function CheckAPIKey( $apiKey = '' )
  {
    if ( empty($this->_apiKey) )
      return true;

    if ( empty($apiKey) ) {
      if ( isset( $_REQUEST['api_key'] ) )
        $apiKey = $_REQUEST['api_key'];
      elseif ( isset( $_REQUEST['apikey'] ) )
        $apiKey = $_REQUEST['apikey'];
      else
        return false;
    }                 
    
    return $this->_apiKey == $apiKey;    
  }          
  public function CheckAPIKeyOrDie( $apiKey = '', $msg = '' )
  {
    if ( empty($msg) )
      $msg = 'The given API-key wasn\'t correct!';
    
    if ( $this->CheckAPIKey( $apiKey ) )
      return true;
    else
      die($msg);
  }

	public function Register( &$object, $name = '' )
	{
		if (empty( $object )) { return false; }

		if (!isset( $this->_objects )) {
			$this->_objects = array();
		}

		if (empty( $name )) {
      if ( is_object( $object ) )
        $name = $object->_object_name;
      else
        $name = $object;
		}

		$this->_objects[$name] = $object;

		return $name;
	}
	public function RegisterClass( $object, $name = '' )
	{                                 
		if (empty( $object )) { return false; }

		if (!isset( $this->_objects )) {
			$this->_objects = array();
		}

		if (empty( $name )) {
      if ( is_object( $object ) )
        $name = $object->_object_name;
      else
        $name = $object;
		}

		$this->_objects[$name] = $object;

		return $name;
	}
	protected function GetObject( $name )
	{
		if (empty( $name )) {
			return null;
		}

		if (array_key_exists( $name, $this->_objects )) {
			$obj = & $this->_objects[$name];
      if ( is_string($obj) ) {
        $obj = new $obj();
      }
      return $obj;
		} else if (isset( $this->object ) && isset( $this->object->_object_name ) &&
					( $this->object->_object_name == $name ) ) {
			return $this->object;
		} else {
			return null;
		}
	}
  public function GetClassName( $class )
  {
  if ( is_numeric($class) ) throw new Exception('NO');
    if (array_key_exists( $class, $this->_objects ) )
      return $class;
    else if ( ($c = array_search( $class, $this->_objects )) !== false ) {
      return $c;
    }  
  }

	public function Call( $host, $uri, $index = '', $protocol = kacTalk::_DEFAULT_PROT )
	{
		switch ($protocol) {
			case kacTalk::PROTOCOL_HTTP:
				return $this->CallHTTP( $host, $uri, $index );
			break;
			default: {
				throw new ktError( "Doesn't support the protocol#: {$protocol}",
									"::Call",
									$this,
                  ktError::NOTIMP );
			}
		}
	}
	public function CallHTTP( $host, $uri, $index = '', $meth = 'GET' )
	{
		if (empty( $index )) { $index = 'index.php'; }
		$base_url = $url = 'http://' . $host . '/kactalk/' . $index;
//var_dump( 'IMPORT_URI', $uri );
		if (is_array( $uri )) {
			$continue = false;
			if (!empty( $uri['object'] )) {
				$url .= '/' . $uri['object'];
				$continue = true;
			}
			if ($continue && !empty( $uri['member'] )) {
				$url .= '/' . $uri['member'];
			} else $continue = false;
			if ($continue && !empty( $uri['extra'] )) {
				foreach( $uri['extra'] as $extr ) {
					if (empty( $extr )) continue;

					$url .= '/' . $extr;
				}
			} else if ($continue && isset( $uri['extra0'] )) {
				$n = 0;
				while( isset( $uri['extra' . $n] ) ) {
					$extr = $uri['extra' . $n];
					if (empty( $extr )) continue;

					$url .= '/' . $extr;
					$n++;
				}
			}
			if (!empty( $uri['format'] )) {
				$url .= '.' . $uri['format'];
				$continue = false;
			}
		} else if (is_string( $uri )) {
			$url .= $uri;
		}
		$format = ktImport::TranslateFormat( $this->path_arr['format'] );

//		echo 'IMPORT_URL:'. $url. "\n";
		//$res = @file_get_contents( $url );

		if (!($stream = @fopen( $url, 'r' ))) {
			throw new ktError( "Couldn't open the url {$url}",
								"::CallHTTP",
								$this );
		}
		$res = stream_get_contents( $stream );
		fclose( $stream );

		$import = new ktImport( $res, ktImport::KT_XML, true, $this, $host, $uri, $index );
//		var_dump( 'IMPORT_OBJECT', $import->object );

		return $import->object;
	}

	public function Run( $path = '', $listObjects = true )
	{
		$ret = null;
		if (empty( $path )) {
		  if (isset( $_SERVER['PATH_INFO'] )) {
			 $path = $_SERVER['PATH_INFO'];
      } else if (isset( $_REQUEST['kuery'] )) {
        $path = $_REQUEST['kuery'];
      }
		}

		if (_DEBUG) { echo 'Path:' . $path . ";\n"; }

		$this->path_arr = $this->GetRoute( $path );
		$format = ktExport::TranslateFormat( $this->path_arr['format'] );
		$wrap_type = ktExport::_DEFAULT_WRAP;

		if (_DEBUG) { var_dump( $this->path_arr ); }

		if (empty( $this->path_arr ) || empty( $this->path_arr['object'] )) {
			if (_DEBUG) { var_dump( $this->_objects ); }
			if ($listObjects) {
				$this->path_arr = array( 'object' => '_kactalk', 'member' => 'objects' );
			} else {
				return null;
			}
		}

		if ($this->path_arr['object'] == '_kactalk') {
			if ($this->path_arr['member'] == '_author') {
				echo _KACTALK_AUTHOR . "\n";
			} else if ($this->path_arr['member'] == 'objects') {
        $this->CheckAPIKeyOrDie();

				$objs = $this->GetAvaliableObjects();
				/*$ret = ktExport::ExportStatic( $objs, $format,
												true, true, 'objects' );*/
        $exp = new ktExport( $objs );
        $ret = $exp->ExportArray( $objs, $format, true );

				$ret = ktExport::ExportWrap( array( 'value' => $ret,
											'kt::IS_PROPERTY' => true,
											'kt::Property' => 'objects' ), $format, ktExport::PROP_RESPONSE_WRAP /*$wrap_type */ );
				return $objs;
			} else if ($this->path_arr['member'] == '_info') {      
//				$objs = $this->GetAvaliableObjects();
                                                                                                                              
        $info = array( // 'objects' => $objs,
                        'version' => _KACTALK_VERSION,
                        'full_version' => _KACTALK_LONG_NAME_VERSION,
                        'version_f' => _KACTALK_VERSION_F,
                        'timestamp' => time(),
                        'datetime' => date('c'),
                        'is_proxy' => $this->_isProxy );

        if ( $this->CheckAPIKey() ) {
          $objs = $this->GetAvaliableObjects();
          $info['objects'] = $objs;
        }
        
        $exp = new ktExport( $info );
        $ret = $exp->ExportArray( $info, $format, true, true );

				$ret = ktExport::ExportWrap( array( 'value' => $ret,
											'kt::IS_PROPERTY' => true,
											'kt::Property' => 'objects' ), $format, ktExport::PROP_RESPONSE_WRAP /*$wrap_type */ );
				return $ret;
			} else if ($this->path_arr['member'] == 'version') {
				echo _KACTALK_VERSION . "\n";
			} else if ($this->path_arr['member'] == 'full_version') {
				echo _KACTALK_LONG_NAME_VERSION . "\n";
			} else if ($this->path_arr['member'] == 'egg') {
				$this->OutTheEgg();
			}
			return true;
		}
    $this->CheckAPIKeyOrDie();

		return $this->Parse( $this->path_arr, $format );
	}

	protected function Parse( $path_arr, $format )
	{
		if (!is_array( $path_arr )) { return null; }
		$wrap_type = ktExport::_DEFAULT_WRAP;
		$ref = $ret = null;
    $obj_n = $mem_n = $path_arr['object']; 

		$obj = $this->GetObject( $obj_n );
    if ( is_null( $obj ) ) {
      throw new ktError("UnavailableObject({$obj_n}) [The object '{$obj_n}' doesn't exist!]",'::Parse',$this,ktError::_404);
    }

		if (!empty( $path_arr['member'] )) {
			$mem_n = $path_arr['member'];
      
			if (method_exists( $obj, $mem_n )) {
				$ret = $this->RunMethod( $obj, $mem_n, $format, false, $path_arr );
				$wrap_type = ktExport::METH_RESPONSE_WRAP;
			} else if (property_exists( $obj, $mem_n )) {
        try {
          $ref = new ReflectionProperty(get_class($obj),$mem_n);
        } catch ( ReflectionException $e ) {}        
        if ( ( $ref != null ) && ! $ref->isPublic() )
          throw new ktError("UnavailableProperty({$obj_n}::{$mem_n}) [The property '{$mem_n}' of '{$obj_n}' isn't publicily available!]",'::Parse',$this,ktError::NOT_AVAILABLE);
        
				$mem = $obj->{$mem_n};
				$this->SetCurrentObject( $mem );
				if (($mem instanceof ktObject) && isset( $path_arr['extra0'] )) {
					$path_a = array( 'object' => get_class( $mem ),
										'member' => $path_arr['extra0'] );
					for ($i = 0; $i < count( $path_arr ) - 4; $i++) {
						$path_a['extra' . $i] = $path_arr['extra' . ($i+1)];
					}
					$ret = $this->Parse( $path_a, $format );
				} else {
					$wrap_type = ktExport::PROP_RESPONSE_WRAP;
					$ret = ktExport::ExportStatic( $mem, $format,
													true, true, $mem_n );

					$ret = ktExport::ExportWrap( array( 'value' => $ret,
												'kt::IS_PROPERTY' => true,
												'kt::Property' => $mem_n ), $format, $wrap_type );
				}
				$this->PopCurrentObject();
			} else {
				$wrap_type = ktExport::PROP_RESPONSE_WRAP;
				$ret = ktExport::ExportStatic( null, $format,
												true, false, $mem_n );
				$ret = ktExport::ExportWrap( array( 'value' => $ret,
											'kt::IS_PROPERTY' => true,
											'kt::Property' => $mem_n ), $format, $wrap_type );
			}
		} else {
			$reto = $obj;
		}
    
    //if ( $reto == null )

		if (empty( $ret )) {
			$ret = ktExport::ExportStatic( $reto, $format, true, false, $mem_n );
			$ret = ktExport::ExportWrap( $ret, $format, $wrap_type );
		}

		if (_DEBUG) { echo '<!--Path:' . $path . "-->\n"; }

		return $ret;
	}

	protected function RunMethod( $obj, $meth, $format = ktExport::_DEFAULT,
									$return_export = false, $path_arr = null )
	{
		if (!isset( $path_arr )) { $path_arr = $this->path_arr; }

		ob_start();

		$params = array();

		if (isset( $path_arr['extra0'] )) {
			$n = 0;
			while( isset( $path_arr['extra' . $n ] ) ) {
				$params[] = $path_arr['extra' . $n ];

				$n++;
			}
		}

		$r = $ret = call_user_func_array( array( $obj, $meth ), $params );
                                  
		$obr = ob_get_contents();
		ob_end_clean();
     
		if (!empty( $obr )) {
			$obr = ktExport::ExportStatic( $obr, $format, true, true );
		}
		if ($ret !== null) {
			$ret = ktExport::ExportStatic( $ret, $format, true, true );
		}  
    if (is_numeric($ret) || is_float($ret) )
      $ret = $r;  

		$ret = ktExport::ExportWrap( array( 'return' => $ret, 'output' => $obr, 'kt::IS_RETURN' => true,
											'kt::Method' => $meth, 'kt::Type' => gettype($r) ),
											$format, ktExport::METH_RESPONSE_WRAP, $return_export );

		return $ret;
	}

	public function GetAvaliableObjects()
	{
		$ret = array();

		if (isset( $this->_objects )) {
			$ret = array_keys( $this->_objects );
		}

		return $ret;
	}

	public function GetRoute( $path = null )
  {	                                     
		if (version_compare( PHP_VERSION, '5.3.3', '>=' )) {
			$c = $this->_routeClass;                
			return $c::GetRoute( $path );
		} else {
			$o = new $this->_routeClass();
			return $o->GetRoute( $path );
		}
	}

	protected function SetCurrentObject( $object )
	{
		if (!is_array( $this->_object_stack )) { $this->_object_stack = array(); }

		$this->_object_stack[] = $this->object;
		$this->object = $object;
	}
	protected function PopCurrentObject( )
	{
		if (empty( $this->_object_stack )) {
			$this->object = null;
			return null;
		}

		$this->object = array_pop( $this->_object_stack );
		return $this->object;
	}

	public function __toString()
	{
		return _KACTALK_LONG_NAME_VERSION;
	}

	public function OutTheEgg()
	{
		echo "                /\\  /\\\n";
		echo "               /--\\/--\\\n";
		//echo "               /     \\\n";
		echo "O'Really?     /  \\\\\\   \\\n";
		echo "             /          \\\n";
		echo "        \\   /  ^      ^  \\\n";
		echo "           /.  O  ||  O  .\\\n";
		echo "          /                \\\n";
		echo "         |     /  \\/  \\     |\n";
		echo "         |     \\      /     |\n";
		echo "         |     /\\ .. /\\     |\n";
		echo "          \\     \\\\  //     /\n";
		echo "           \\     \\\\//     /\n";
		echo "            \\     \\/     /    \\\n";
		echo "             \\          /\n";
		echo "              \\   ||   /        Lets talk KacTalk!\n";
		//echo "               \\     /\n";
		//echo "                 +   +\n";
		echo "               \\------/\n";
	}

	protected $_objects = null;
	protected $_routeClass = 'ktLib';
  protected $_apiKey = '';
  protected $_isProxy = false;
  protected $_returnPureValue = false;
  
  protected $_extraParameters = array();

	//protected $_current_object = null;
  protected $object = null;
	protected $_object_stack = array();
  
  public static $_kt = null;
};
