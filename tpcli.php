#!/usr/bin/env php
<?php
declare(strict_types = 1);
require_once ('tpapi.class.php');
$ip = readline ( "host[:port=9999]: " );
$ip = explode ( ":", $ip, 2 );
if (count ( $ip ) === 2) {
	$port = $ip [1];
	if (false === ($port = filter_var ( $port, FILTER_VALIDATE_INT, array (
			"options" => array (
					"min_range" => 0,
					"max_range" => 0xFFFF 
			) 
	) ))) {
		fprintf ( STDERR, "invalid port! (must be in range 0-65535)\n" );
		die ( 1 );
	}
} else {
	$port = 9999;
}
$ip = gethostbynamel ( $ip [0] );
if (false === $ip) {
	fprintf ( STDERR, "could not resolve hostname!\n" );
	die ( 1 );
}
$ip = $ip [0];
$tp = new tpapi ( $ip, $port );
array_map ( 'readline_add_history', array_keys ( $tp->commands ) );
echo "for a list of built-in command aliases, press the up key repeatedly. for a comprehensive list, see https://github.com/softScheck/tplink-smartplug/blob/master/tplink-smarthome-commands.txt \n";
while ( is_string ( ($cmd = readline ( "cmd: " )) ) ) {
	if (empty ( ($cmd = trim ( $cmd )) )) {
		// echo "\n";
		continue;
	}
	readline_add_history ( $cmd );
	try {
		$ret = $tp->execCommand ( $cmd );
		echo "(command mode)\n", $ret, "\n";
	} catch ( \InvalidArgumentException $ex ) {
		$ret = $tp->execRaw ( $cmd );
		echo "(raw mode)\n", $ret, "\n";
	}
}
