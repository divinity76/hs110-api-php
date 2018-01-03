<?php
declare(strict_types = 1);
class tpapi {
	protected static function encrypt(string $string): string {
		$key = 171;
		$ret = "\0\0\0\0";
		// TODO: should it be str_split or mb_strsplit ?
		// Warning: Might have a bug with unicode encodings (æøå)
		foreach ( str_split ( $string, 1 ) as $chr ) {
			$a = $key ^ ord ( $chr );
			$key = $a;
			$ret .= chr ( $a );
		}
		return $ret;
	}
	protected static function decrypt(string $encrypted): string {
		$key = 171;
		$ret = "";
		foreach ( str_split ( $encrypted, 1 ) as $byte ) {
			$a = $key ^ ord ( $byte );
			$key = ord ( $byte );
			$ret .= chr ( $a );
		}
		return $ret;
	}
	public function execRaw(string $command, bool $prettifyReturn = true, bool $encryptInput = true, bool $decryptOutput = true): string {
		try {
			$sock = socket_create ( AF_INET, SOCK_STREAM, SOL_TCP );
			if (! $sock) {
				throw new \RuntimeException ( 'failed to create socket!' );
			}
			$port = 9999;
			if (! socket_connect ( $sock, $this->ip, $port )) {
				throw new \RuntimeException ( 'failed to connect!' );
			}
			$commandEnc = ($encryptInput ? self::encrypt ( $command ) : $command);
			// TODO: socket_write_all
			$written = socket_write ( $sock, $commandEnc );
			if (strlen ( $commandEnc ) !== $written) {
				throw new RuntimeException ( 'tried to write ' . strlen ( $written ) . ' bytes, but could only write ' . var_export ( $written, true ) . ' bytes!' );
			}
			$recievedRaw = '';
			while ( false != ($last = socket_read ( $sock, 4096 )) ) {
				// waiting for remote host to close connection
				$recievedRaw .= $last;
			}
			if (empty ( $recievedRaw )) {
				// mhm
				return $recievedRaw;
			}
			// var_dump ( $recievedRaw );
			$recieved = ($decryptOutput ? self::decrypt ( $recievedRaw ) : $recievedRaw);
			// var_dump ( substr ( $recieved, 5 ) ); //
			// var_dump ( $recieved );
			if ($prettifyReturn) {
				// why skip the first 5 bytes? idk, some protocol weirdness (perhaps a checksum?)
				// why add the weird { ? idk, some corruption somewhere...
				$json = json_decode ( '{' . substr ( $recieved, 5 ), true, 512, JSON_BIGINT_AS_STRING );
				if (json_last_error ()) {
					throw new \RuntimeException ( '1failed to prettify return! (only json can be prettified, turn off $prettifyReturn if its not json) json_last_error: ' . json_last_error () . '. json_last_error_msg: ' . json_last_error_msg () );
				}
				$json = json_encode ( $json, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION );
				if (json_last_error ()) {
					throw new \RuntimeException ( '2failed to prettify return! (only json can be prettified, turn off $prettifyReturn if its not json) json_last_error: ' . json_last_error () . '. json_last_error_msg: ' . json_last_error_msg () );
				}
				$recieved = $json;
			}
			return $recieved;
		} finally{
			socket_close ( $sock );
		}
	}
	public function execCommand(string $command): string {
		// https://github.com/softScheck/tplink-smartplug/blob/master/tplink-smarthome-commands.txt
		$commands = array ();
		$commands ['info'] = '{"system":{"get_sysinfo":null}}';
		$commands ['reboot'] = '{"system":{"reboot":{"delay":1}}}';
		$commands ['reset'] = '{"system":{"reset":{"delay":1}}}';
		$commands ['on'] = '{"system":{"set_relay_state":{"state":1}}}';
		$commands ['off'] = '{"system":{"set_relay_state":{"state":0}}}';
		$commands ['realtime'] = '{"emeter":{"get_realtime":{}}}';
		$commands ['now'] = $commands ['realtime'];
		$commands ['month'] = '{"emeter":{"get_daystat":{"month":' . (date ( "m" )) . ',"year":' . (date ( "Y" )) . '}}}';
		$commands ['lastmonth'] = (date ( "m" ) === '1' ? '{"emeter":{"get_daystat":{"month":12,"year":' . (date ( "Y" ) - 1) . '}}}' : '{"emeter":{"get_daystat":{"month":' . (date ( "m" ) - 1) . ',"year":' . (date ( "Y" )) . '}}}');
		$commands ['year'] = '{"emeter":{""get_monthstat":{"year":' . (date ( "Y" )) . '}}}';
		$command = strtolower ( $command );
		if (! in_array ( $command, array_keys ( $commands ), true )) {
			throw new \InvalidArgumentException ( 'unknown command! supported commands: ' . implode ( ' - ', array_keys ( $commands ) ) );
		}
		return $this->execRaw ( $commands [$command], true );
	}
	public $ip;
	function __construct(string $ip) {
		// TODO: FILTER_VALIDATE__IP
		$this->ip = $ip;
	}
}