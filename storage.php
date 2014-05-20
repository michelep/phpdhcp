<?php
/*
  Copyright 2009 Angelo R. DiNardi (angelo@dinardi.name)
 
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

class dhcpLease {
    public $ID;
    public $PoolId;
    public $IP;
    public $Router;
    public $SubnetMask;
    public $DNSServers = array();
    public $leaseTime = 84600;
    public $Domain;
    
    function __construct($poolId=1) {
	$result = doQuery("SELECT ID,Router,SubnetMask,DNSServers,Domain FROM DHCPPools WHERE ID='$poolId';");
	if(mysql_num_rows($result) > 0) {
	    $row = mysql_fetch_array($result, MYSQL_ASSOC);
	    $this->PoolId = $row['ID'];
	    $this->Router = explode('.',$row['Router']);
	    $this->SubnetMask = explode('.',$row['SubnetMask']);
	    $this->Domain = $row['Domain'];
	    foreach(explode(',',$row['DNSServers']) as $DNSServer) {
		$this->DNSServers[] = explode('.',$DNSServer);
	    }
        }
    }

    function isValidIP($ip) {
    	$result = doQuery("SELECT NOW() BETWEEN ChgDate AND DATE_ADD(ChgDate,INTERVAL 1 HOUR) AS isValid FROM DHCPLeases WHERE IP='$ip';");
	if(mysql_num_rows($result) > 0) {
	    $row = mysql_fetch_array($result,MYSQL_ASSOC);
	    if($row['isValid'] == 1) {
		return true;
	    }
	}
	return false;
    }
    
    function leaseRenew() {
	doQuery("UPDATE DHCPLeases SET IP='".implode('.',$this->IP)."';");
    }
    
    function getFreeIP() {
	$result = doQuery("SELECT IPStart,IPEnd FROM DHCPPools WHERE ID='$this->PoolId';");
	if(mysql_num_rows($result) > 0) {
	    $row = mysql_fetch_array($result, MYSQL_ASSOC);
	    $startIp = explode('.',$row['IPStart']);
	    $endIp = explode('.',$row['IPEnd']);

	    System_Daemon::log(System_Daemon::LOG_DEBUG, "PoolID: $this->PoolId Start: ".$row['IPStart']." End:".$row['IPEnd']);
	    
	    for($a=$startIp[0];$a<=$endIp[0];$a++) {
		for($b=$startIp[1];$b<=$endIp[1];$b++) {
		    for($c=$startIp[2];$c<=$endIp[2];$c++) {
			for($d=$startIp[3];$d<=$endIp[3];$d++) {
			    $tmpIp = array($a,$b,$c,$d);
			    $result = doQuery("SELECT ID FROM DHCPLeases WHERE IP='".implode('.',$tmpIp)."';");
			    if(!mysql_num_rows($result)) {
				System_Daemon::log(System_Daemon::LOG_DEBUG, "Got free lease IP: ".implode('.',$tmpIp));
				$this->IP = $tmpIp;
				return true;
			    }
	    		}
		    }
		}
	    }
	}
    }
}

class dhcpStorage {
    function __construct($server) {
    	$this->dhcpServer = $server;
    }

    function getAttributesForClient(dhcpPacket $packet) {
	// Set default values
	$response = array(
            'yiaddr' => array(172, 20, 1, 250),
            'subnet_mask' => array(255, 255, 255, 0),
            'router' => array(172, 20, 1, 1),
            'dns_server' => array(172, 20, 1, 2),
            'lease_time' => 86400,
            'domain_name' => 'voip.unisi.it'
        );
        
        $mac = $packet->getMACAddress();
        
        // Query database for MAC validity (only listed MAC addresses can get an IP !)
	$result = doQuery("SELECT IP,PoolID,Brand,Model,isEnable FROM Devices WHERE MAC='".trim($mac)."' LIMIT 1;");
	if(mysql_num_rows($result) > 0) {
	    $row = mysql_fetch_array($result,MYSQL_ASSOC);
	    $poolId = $row['PoolID'];
	    
	    $deviceLease = new dhcpLease($poolId);
	    
	    $deviceIp = $row['IP'];
	
	    if($deviceIp) {
		// Seems that this device already have an IP. Is still valid ?
		if($deviceLease->isValidIP($deviceIp)) {
		    $deviceLease->IP = explode('.',$deviceIp);
		    $deviceLease->leaseRenew();
		} else {
		    $deviceLease->getFreeIP();
		}
	    } else {
		$deviceLease->getFreeIP();
	    }
	    
	    $response['yiaddr'] = $deviceLease->IP;
	    $response['subnet_mask'] = $deviceLease->SubnetMask;
	    $response['router'] = $deviceLease->Router;
	    $response['dns_server'] = $deviceLease->DNSServers;
	    $response['lease_time'] = $deviceLease->leaseTime;
	    $response['domain_name'] = $deviceLease->Domain;
	    
	    print_r($response);
	    
	    System_Daemon::log(System_Daemon::LOG_INFO, "MAC $mac can get IP ".implode('.',$response['yiaddr']));
	    return $response;
	} else {
	    System_Daemon::log(System_Daemon::LOG_WARNING, "MAC $mac unknown");
	    return false;
	}
    }
}