<?php

include "config.inc.php";

date_default_timezone_set('Europe/Rome');

function connectDb() {
    global $CFG;
    $db = mysql_connect($CFG['dbHost'],$CFG['dbUser'],$CFG['dbPassword']);
    if($db == false) {
	System_Daemon::log(System_Daemon::LOG_ERROR, "Error connecting to MySQL DB @ ".$CFG['dbHost'].": ".mysql_error());
	exit();
    }
    System_Daemon::log(System_Daemon::LOG_INFO, "Successfully connected to MySQL DB @ ".$CFG['dbHost']);
    mysql_select_db($CFG['dbName'],$db);
}

function doQuery($query) {
    $result = mysql_query($query);
    if($result === false) {
        System_Daemon::log(System_Daemon::LOG_ERROR, "MySQL error ".mysql_error());
        exit();
    }
    return $result;
}



?>