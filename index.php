<?php

error_reporting(E_ALL);

const VIEW_DIR = "views/";

require_once "vendor/autoload.php";

$game = @$_GET['game'];

switch($game)
{
	default:
		include __DIR__ . "/" . VIEW_DIR . "server-list.php";
		break;
}