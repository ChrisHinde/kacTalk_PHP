<?php

define( '_DEBUG', 0 );

header( 'Content-Type: text/plain' );

include "kacTalk/kactalk.php";

/**
 * A collection of Math functions that we want to expose to the world
 */ 
class MathFunctions extends ktObject
{
  public $pi = 3.1421;
  
  function fractal( $n = 3 )
  {
    if ( $n > 42 ) {
      throw ktError::E('Math->TooHighFractal(' . $n . ')',
                        'Whoa! Let\'s draw the line at (42!)!',
      									"::Fractal",
      									$this,
                        ktError::NOT_ALLOWED );
    } else if ( $n <= 0 )
      return 1;
    
    return $this->fractal( $n - 1 )*$n;
  }
};

class Test extends ktObject
{
	public $list = array( 'foo', 'bar', 1, 2 );
	public $myChild = null;
	public $map = null;
	public $me = null;
	private $secret = 'This property isn\'t accesible!';

	public function __construct()
	{                                 
		$this->map = array( 'first' => 'one', 'second' => 2 );
    $this->myChild = new Child($this);
    $this->me = $this;
	}

	public function Remember( $in )
	{
		session_start();              
    if ( !isset( $_SESSION['remember'] ) )
      $_SESSION['remember'] = $in;
    else
      $_SESSION['remember'] .= ';' . $in;
      
    return $_SESSION['remember'];
	}
  
  public function Arguments()
  {
    $arguments = func_get_args();
    $n = 0;
    
    foreach ( $arguments as $i => $argument ) {
      echo $i . ': ' . $argument . "\n";
      $n++;
    }
    
    return $n;
  }
};

class Child
{
  public function __construct( &$parent )
  {
    $this->parent = $parent;
  }
  public function Path()
  {
    return kacTalk::$_path;
  } 
  
  public $parent = null;
};


try {
  $kt = new kacTalk();
  $kt->SetAPIKey('658B8C89-BA37-42D6-8D02-7119A5FA613A');

  $kt->RegisterClass( 'MathFunctions', "math" );
	$test = new Test();
	$test->secondChild = new Child($test);
	$kt->Register( $test, "test" );
  
  
	if (_DEBUG) {
		var_dump( $kt );
		echo "==============================\n";
		echo "||          R U N           ||\n";
		echo "==============================\n";
	}

	$kt->Run();

	if (_DEBUG) { echo "==============================\n"; }
} catch ( Exception $err ) {
  echo $err . ' (ERR#' . $err->getCode() . ')';
   // echo 'Caught exception: (#' . $err->getCode() . ') ' .  $err . "\n";
	//echo $err->getTraceAsString();
}

//echo "NULL";
?>