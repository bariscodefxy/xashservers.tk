<?php 

declare(strict_types = 1);

namespace ServerList;

use ServerList\ByteBuffer;
use ServerList\Parts\SocketBase;

class MasterApi extends SocketBase {

	public function connect($ip = "ms.xash.su", $port = 27010)
	{
		parent::connect($ip, $port);
	}

	public function getServers( $game = "cstrike" ) {
		$msg = "1\xff0.0.0.0:0\x00\\nat\\0\\gamedir\\$game\\clver\\0.19.3\x00";
		socket_send($this->socket, $msg, strlen($msg), 0);
		$read = socket_read($this->socket, 1024);
		$servers = [];
		$byteBuffer = ByteBuffer::wrap($read);
		$this->contentData = $byteBuffer;
		do {
            $firstOctet = $this->contentData->getByte();
            $secondOctet = $this->contentData->getByte();
            $thirdOctet = $this->contentData->getByte();
            $fourthOctet = $this->contentData->getByte();
            $portNumber = $this->contentData->getShort();
            $portNumber = (($portNumber & 0xFF) << 8) + ($portNumber >> 8);

            $servers[] = "$firstOctet.$secondOctet.$thirdOctet.$fourthOctet:$portNumber";
        } while($this->contentData->remaining() > 0);
        array_shift($servers);
        array_pop($servers);
		return $servers;
	}

}