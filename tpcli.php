#!/usr/bin/env php
<?php
declare(strict_types = 1);
const IP='10.0.0.7';
require_once ('tpapi.class.php');
$tp = new tpapi ( IP );
readline_add_history ( '{"system":{"get_sysinfo":null}}' );
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
