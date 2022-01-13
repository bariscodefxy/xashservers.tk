<?php

namespace ServerList\Parts;

class SocketBase {

	protected $socket;

	public function connect($ip, $port) {
		$this->socket = socket_create(\AF_INET, \SOCK_DGRAM, 0);
		socket_connect($this->socket, $ip, $port);
		return true;
	}

	public function close() {
		socket_close($this->socket);
		unset($this->socket);
		return true;
	}

}