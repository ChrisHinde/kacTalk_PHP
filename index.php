<?php

define( '_DEBUG', 0 );

header( 'Content-Type: text/plain' );

include "kacTalk/kactalk.php";


class testKTO extends ktObject
{
	public $slu = 42;
	public $pi = 3.142;
	public $booleskt = true;
	public $TT = null;

	function dummy( $str = 'What?' )
	{
		echo 'The dummy says: ' . $str . "\n";
	}

	function add( $a = 1, $b = 2 )
	{
		return intval( $a ) + intval( $b );
	}
	function mult( $a = 1, $b = 2 )
	{
		return floatval( $a ) * floatval( $b );
	}

	function printa( )
	{
		$args = func_get_args();
		$n = 1;

		foreach ($args as $a) {
			$n_s = (($n > 9) ? '0' : ''). $n;
			echo $n_s . ": {$a}\n";

			$n++;
		}

		return $n - 1;
	}
};

class TestTwo extends ktObject
{
	public $list = array( 42, 'slu' );
	public $emptyL = null;
	public $map = null;
	private $secret = 3.142;

	public function __construct()
	{
		$this->map = array( 'first' => 'a', '2nd' => 2 );
	}

	public function add( $in )
	{
		if (!is_array( $this->emptyL )) {
			$this->emptyL = array();
		}

		$this->emptyL[] = $in;

		return count( $this->emptyL );
	}
};


try {
  $kt = new kacTalk();

  $kto = new testKTO();
	$kt->Register( $kto, "kto" );
	$my2 = new testTwo();
	$my2->kt0 = $kto;
	$kt->Register( $my2, "my2nd" );
//var_dump( $kto );
	if (_DEBUG) {
		var_dump( $kt );
		echo "==============================\n";
		echo "||          R U N           ||\n";
		echo "==============================\n";
	}

	$kt->Run();

	if (_DEBUG) { echo "==============================\n"; }
} catch ( Exception $err ) {
    echo 'Caught exception: (#' . $err->getCode() . ') ' .  $err . "\n";
	echo $err->getTraceAsString();
}

//echo "NULL";
?>