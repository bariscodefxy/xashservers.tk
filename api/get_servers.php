<?php

require_once "../vendor/autoload.php";

$_GAME = @$_GET['game'];
if(!$_GAME) $_GAME = "cstrike";
$masterapi = new \ServerList\MasterApi();
$serverapi = new \ServerList\ServerQuery();
$masterapi->connect();
$servers = $masterapi->getServers($_GAME);
$masterapi->close();
$info = [];

foreach($servers as $s)
{
	$ip = explode(':', $s)[0];
	$port = explode(':', $s)[1];
	$s_info = $serverapi->getServerInfo($ip, $port);
	if(!$s_info) continue;
	$s_info["name"] = preg_replace("/\^([0-9])/", "", $s_info["name"]);
	$info[] = $s_info;
}

echo json_encode($info);