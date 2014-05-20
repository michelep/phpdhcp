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
require_once "server.php";
require_once "requestProcessor.php";
require_once "storage.php";
require_once "System/Daemon.php";
include "common.inc.php";

$options = array(
    'appName' => 'phpdhcpd',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'DHCP server written in PHP',
    'authorName' => 'O-Zone',
    'authorEmail' => 'o-zone@zerozone.it',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M',
    'appRunAsGID' => 1,
    'appRunAsUID' => 1,
);

System_Daemon::setOptions($options);

System_Daemon::log(System_Daemon::LOG_INFO, "phpDHCPd starting...");

// System_Daemon::start();

connectDb();

$server = new dhcpServer($CFG);

if($server) {
    $server->verbosity = 1;
    while (!System_Daemon::isDying() && $server) {
	$server->poll();
    }
}

// System_Daemon::stop();

