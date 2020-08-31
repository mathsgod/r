<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set("display_errors", "On");

$loader = require_once("../vendor/autoload.php");
session_start();
$app = new R\App(__DIR__, $loader);
$app->run();
