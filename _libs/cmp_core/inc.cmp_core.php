<?php

# Don't Modify Me, You can copy and run at your own DIR, like:
#_APP_DIR_ .'/inc.app.php':
/*
if (!defined("_APP_DIR_")) define("_APP_DIR_",realpath(dirname(__FILE__)));
#if (!defined("_LIB_")) define("_LIB_",realpath(__DIR__ ."/_libs/"));
require '../_libs/cmp_core/inc.cmp_core.php';
 */

error_reporting(E_ERROR|E_COMPILE_ERROR|E_PARSE|E_CORE_ERROR|E_USER_ERROR);

if (!defined("_APP_DIR_")) define("_APP_DIR_",realpath(__DIR__ ."/../../"));

if (!defined("_LIB_")) define("_LIB_",realpath(__DIR__ ."/../"));

if (_LIB_=="" || _LIB_=="_LIB_") throw new Exception("empty _LIB_");

#Again, if not enough, copy it and make your own.
#require_once 'inc.header.sample.2014b.php';
require_once 'inc.header.2015.php';

//adjust_timezone();//adjust php timezone getConf(SERVER_TIMEZONE), ignore it if using db time

