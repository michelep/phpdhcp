<?php
/*
  Copyright 2009 Angelo R. DiNardi (angelo@dinardi.name)

  Modified 2014 by Michele "O-Zone" Pinassi (o-zone@zerozone.it)
 
  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at
 
    http://www.apache.org/licenses/LICENSE-2.0
 
  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
*/

require_once "packet.php";
require_once "requestProcessor.php";
require_once "storage.php";

class dhcpServer {
    private $packetProcessor = null;
    private $socket = null;
    private $storage = null;
    public $verbosity=2;			// 0-2 - level of verbosity (0 to sqelch, 1 for a little, 2 for a lot (packet dumps))

    function __construct($CFG) {
	$this->packetProcessor = new defaultPacketProcessor;
	$this->CFG = $CFG;

        $this->storage = new dhcpStorage($this);

        $this->socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if(!socket_bind($this->socket, $this->CFG["ifIP"], $this->CFG["ifPort"])) return false;
        socket_set_option($this->socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        if($this->CFG["ifBind"]) {
	    socket_set_option($this->socket, SOL_SOCKET, 25, $this->CFG["ifBind"]); // SO_BINDTODEVICE = 25
    	}
    }

    function getSocketHost() {
    	return socket_getsockname($this->socket, $addr) ? $addr : NULL;
    }
    
    function poll() {
	$data = socket_read($this->socket, 4608);
        $this->processPacket($data);
        $this->verbosity && print("\n\n");
    }
    
    function processPacket($packetData) {
        $packet = new dhcpPacket();
        $packet->parse($packetData);
        $processor = new dhcpRequestProcessor($this, $this->packetProcessor, $this->storage, $packet);
        
	if ($responsePacket = $processor->getResponse()) {
            $responseData = $responsePacket->build();
            $this->verbosity && print("Sending response" . "\n");
            $ciaddr = $packet->getClientAddress();
            if ($ciaddr == '0.0.0.0') {
                $this->verbosity && print("Switching to broadcast address...\n");
                $ciaddr = '255.255.255.255';
            }
            $this->verbosity && print("Attempting to send response packet to " . $ciaddr . "\n");
            $numBytesSent = socket_sendto($this->socket, $responseData, strlen($responseData), MSG_DONTROUTE, $ciaddr, 68);
            if ($numBytesSent === FALSE) {
                $this->verbosity && print("send failed for specific address, broadcast.\n");
                $numBytesSent = socket_sendto($this->socket, $responseData, strlen($responseData), MSG_DONTROUTE, "255.255.255.255", 68);
//                $numBytesSent = socket_sendto($this->socket, $responseData, strlen($responseData), 0, "255.255.255.255", 68);
    	        $numBytesSent === FALSE && $this->verbosity && printf('socket send error: %s\n',socket_strerror(socket_last_error($this->socket)));
            }
            $numBytesSent && $this->verbosity && print("Response packet sent.\n");
    	} else {
	    $this->verbosity && print("Packet ignored\n");
	}
    }
}