<?
ini_set("display_errors", "On");

$loader = require_once("../vendor/autoload.php");

$app = new R\App(__DIR__, $loader);
$app->run();
