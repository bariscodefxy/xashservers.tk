<?php

require __DIR__ . "/config.php";

error_reporting(0);

const VIEW_DIR = "views/";

require_once "vendor/autoload.php";

$game = @$_GET['game'];

switch($game)
{
	default:
		include __DIR__ . "/" . VIEW_DIR . "server-list.php";
		break;
}