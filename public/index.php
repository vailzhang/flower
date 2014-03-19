<?php
// define('BASE_URL', 'http://yaf');
define("APP_PATH",  realpath(dirname(__FILE__) . '/../')); /* 指向public的上一级 */
define("VALID_PATH", APP_PATH.'/application/library/validator/');//验证目录
define("VALID_EXCEP_PATH", APP_PATH.'/application/library/exception/');//验证异常目录
define("FILTER__PATH", APP_PATH.'/application/library/filter/');//过滤目录
$app  = new Yaf_Application(APP_PATH . "/conf/application.ini");
$app->bootstrap()->run();
