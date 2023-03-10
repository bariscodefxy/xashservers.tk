<?php
$sub = substr($_SERVER['PHP_SELF'], 0, -strlen(basename($_SERVER['PHP_SELF'])."a")); // returns subfolder
try {
    $db = new \PDO("mysql:host=localhost;dbname=xashservers;charset=utf8", "root", "");
} catch (\PDOException $e){
    die("Couldnt connect to database: " . $e->getMessage());
}