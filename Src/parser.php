<?php
require_once(dirname(__FILE__) . "/../vendor/autoload.php");
$parser = new Parser\Parser(realpath(dirname(__FILE__) . '/../'));
$parser->run();
