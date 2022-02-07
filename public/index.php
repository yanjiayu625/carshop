<?php
ini_set('display_errors',1); 
error_reporting(-1); 
ini_set('error_log', dirname(__FILE__) . '/error_log.txt');

mb_internal_encoding("UTF-8");
define("APPLICATION_PATH", realpath(dirname(__FILE__) . '/../'));
$app = new \Yaf\Application(APPLICATION_PATH . "/conf/application.ini", ini_get('yaf.environ'));
$app->bootstrap()->run();
