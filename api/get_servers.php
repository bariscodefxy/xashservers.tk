<?php

error_reporting(0);
require_once "../vendor/autoload.php";
require_once "../config.php";

$_GAME = @$_GET['game'];
if(!$_GAME) $_GAME = "cstrike";
$masterapi = new \ServerList\MasterApi();
$serverapi = new \ServerList\ServerQuery();
$masterapi->connect();
$servers = $masterapi->getServers($_GAME);
$blacklist = @$db->prepare("SELECT * FROM ip_blacklist");
$blacklist->execute([]);
$blacklist = $blacklist->fetchAll(\PDO::FETCH_ASSOC);
$masterapi->close();
$info = @$db->query("SELECT * FROM cache_servers")->fetchAll(\PDO::FETCH_ASSOC);

/**
 * Human readable times on $info array
 *
 * @return void
 */
function replaceTimes() {
	global $info;
	foreach($info as $key => $i)
	{
		if ((time() - $info[$key]['lastregistry']) < 60) {
			$info[$key]['lastregistry'] = time() - $info[$key]['lastregistry'] . " seconds ago";
		} elseif (intval(((((time() - $info[$key]['lastregistry']) / 60) / 60) / 24) / 7)) {
			$info[$key]['lastregistry'] = intval((((((time() - $info[$key]['lastregistry']) / 60) / 60) / 24) / 7) / 52) . " years ago";
		} elseif (intval((((time() - $info[$key]['lastregistry']) / 60) / 60) / 24) > 7) { 
			$info[$key]['lastregistry'] = intval(((((time() - $info[$key]['lastregistry']) / 60) / 60) / 24) / 7) . " weeks ago";
		} elseif (intval(((time() - $info[$key]['lastregistry']) / 60) / 60) > 24) {
			$info[$key]['lastregistry'] = intval((((time() - $info[$key]['lastregistry']) / 60) / 60) / 24) . " days ago";
		} elseif (intval((time() - $info[$key]['lastregistry']) / 60) > 60) {
			$info[$key]['lastregistry'] = intval(((time() - $info[$key]['lastregistry']) / 60) / 60) . " hours ago";
		} else {
			$info[$key]['lastregistry'] = intval((time() - $info[$key]['lastregistry']) / 60) . " mins ago";
		}
	}
}

/**
 * Searchs for the specific server for $info variable
 *
 * @param string $ip
 * @param integer $port
 * @return bool
 */
function findServer(string $ip, int $port) {
	global $info;
	foreach($info as $i)
	{
		if($i['ip'] == $ip && $i['port'] == $port) return true;
	}
	return false;
}

/**
 * Server osunu döndürür
 * 
 * @return string
 */
function getOsName(string $char) {
	switch($char) {
		case "w":
			return "Windows";
		break;
		case "l":
			return "Linux";
		break;
		case "m":
			return "MacOS";
		break;
	}
	return "Unknown";
}

$s = $db->query("SELECT * FROM cache_servers")->fetchAll(\PDO::FETCH_ASSOC);
foreach($s as $server) {
	if( // delete the server if...
	array_count_values(array_column($s, 'name'))[$server['name']] > 1 // hostname already exists?
	|| 
	time() - $server["lastregistry"] > ((60 * 60) * 24)               // 1 day or more offline
	) {
		$db->query("DELETE FROM cache_servers WHERE id = '" . $server["id"] . "'"); // delete it
	}
}
unset($s);

foreach($servers as $s)
{
	$ip = trim(explode(':', $s)[0]);
	$port = trim(explode(':', $s)[1]);

	$SERVERSDB = @$db->prepare('SELECT * FROM cache_servers WHERE ip = ? AND port = ?');
	$SERVERSDB->execute([$ip, $port]);
	$exists = $SERVERSDB->rowCount();
	$SERVERSDB = $SERVERSDB->fetch(\PDO::FETCH_ASSOC);
	
	if($exists) {
		if ( time() - $SERVERSDB['lastregistry'] > 60 * 5) {
			goto fetchserver;
		} else {
			if( findServer($ip, $port) ) continue;
			$info[] = [
				"ip" => $SERVERSDB["ip"],
				"port" => $SERVERSDB["port"],
				"name" => $SERVERSDB["name"],
				"map" => $SERVERSDB["map"],
				"activeplayers" => $SERVERSDB["activeplayers"],
				"maxplayers" => $SERVERSDB["maxplayers"],
				"os" => $SERVERSDB["os"],
				"lastregistry" => $SERVERSDB["lastregistry"]
			];
			continue;
		}
	}

fetchserver:
	$ipexists = false;
	foreach($blacklist as $key => $ips)
	{
		if ($ips['ip'] == $ip) {
			$ipexists = true;
			$ipindex = $key;
		}
	}
	if(!@$ipexists || @$blacklist[@$ipindex]['tries'] < 4) $s_info = $serverapi->getServerInfo($ip, $port);
	if(!@$s_info["map"]) {
		if( findServer($ip, $port) ) continue;
		if (@$ipexists && @$blacklist[@$ipindex]['tries'] < 4) {
			$query = $db->prepare("UPDATE ip_blacklist SET tries = ? WHERE ip = ?");
			$query->execute([$blacklist[$ipindex]['tries']+1, $ip]);
		}
		else if(!$ipexists)
		{
			@$db->query("INSERT INTO ip_blacklist SET ip = '" . $ip . "', tries = '1'");
		}
		continue;
	}
	$s_info["ip"] = $ip;
	$s_info["port"] = $port;
	$s_info["name"] = preg_replace("/\^([0-9])/", "", $s_info["name"]);
	$s_info["lastregistry"] = "Now";
	$s_info["os"] = getOsName($s_info["os"]);
	$info[] = [
		"ip" => $s_info["ip"],
		"port" => $s_info["port"],
		"name" => $s_info["name"],
		"map" => $s_info["map"],
		"activeplayers" => $s_info["activeplayers"],
		"maxplayers" => $s_info["maxplayers"],
		"os" => $s_info["os"],
		"lastregistry" => $s_info["lastregistry"]
	];
	if ($exists) {
		$query = $db->prepare("UPDATE cache_servers SET name = ?, map = ?, activeplayers = ?, maxplayers = ?, os = ?, lastregistry = ? WHERE id = ?");
		$query->execute([$s_info["name"], $s_info["map"], $s_info["activeplayers"], $s_info["maxplayers"], $s_info["os"], time(), $SERVERSDB['id']]);
	}
	else
	{
		$query = $db->prepare("INSERT INTO cache_servers SET ip = ?, port = ?, name = ?, map = ?, activeplayers = ?, maxplayers = ?, os = ?, lastregistry = ?");
		$query->execute([$s_info["ip"], $s_info["port"], $s_info["name"], $s_info["map"], $s_info["activeplayers"], $s_info["maxplayers"], $s_info["os"], time()]);
	}
	unset($s_info);
}

function activeplayers($a, $b) {
	return $a['activeplayers'] < $b['activeplayers'];
}
usort($info, "activeplayers");
replaceTimes();
foreach($info as $key => $sw){
	if(isset($sw['id'])) unset($info[$key]['id']);
	if(is_numeric($sw['activeplayers'])) $info[$key]['activeplayers'] = strval($sw['activeplayers']);
	if(is_numeric($sw['maxplayers'])) $info[$key]['maxplayers'] = strval($sw['maxplayers']);
}

echo json_encode($info);	