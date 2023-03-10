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
 * @return void
 */
function findServer(string $ip, int $port) {
	global $info;
	foreach($info as $i)
	{
		if($i['ip'] == $ip && $i['port'] == $port) return true;
	}
	return false;
}

$s = $db->query("SELECT * FROM cache_servers")->fetchAll(\PDO::FETCH_ASSOC);
foreach($s as $server) {
	if(array_count_values(array_column($s, 'name'))[$server['name']] > 1) {
		$db->query("DELETE FROM cache_servers WHERE id = '" . $server["id"] . "'");
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
			unset($SERVERSDB['id']);
			$info[] = $SERVERSDB;
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
	$s_info["name"] = preg_replace("/\^([0-9])/", "", $s_info["name"]);
	$s_info["lastregistry"] = "Now";
	$s_info["activeplayers"] -= $s_info["botplayers"];
	$info[] = $s_info;
	if ($exists) {
		$query = $db->prepare("UPDATE cache_servers SET name = ?, map = ?, activeplayers = ?, maxplayers = ?, lastregistry = ? WHERE id = ?");
		$query->execute([$s_info["name"], $s_info["map"], $s_info["activeplayers"], $s_info["maxplayers"], time(), $SERVERSDB['id']]);
	}
	else
	{
		$query = $db->prepare("INSERT INTO cache_servers SET ip = ?, port = ?, name = ?, map = ?, activeplayers = ?, maxplayers = ?, lastregistry = ?");
		$query->execute([$s_info["ip"], $s_info["port"], $s_info["name"], $s_info["map"], $s_info["activeplayers"], $s_info["maxplayers"], time()]);
	}
	unset($s_info);
}

function activeplayers($a, $b) {
	return $a['activeplayers'] < $b['activeplayers'];
}
usort($info, "activeplayers");
replaceTimes();

echo json_encode($info);