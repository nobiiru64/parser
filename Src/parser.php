<?php
require_once(dirname(__FILE__) . "/vendor/autoload.php");


$dotenv = Dotenv\Dotenv::create(__DIR__);
$dotenv->load();

$parser = new Parser\Parser(__DIR__);
