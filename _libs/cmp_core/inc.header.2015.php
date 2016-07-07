<?php
/* vim: set tabstop=2 shiftwidth=2 softtabstop=2: */
/*
 *   注意：这是个example，可用，如果有特别修改，请不要直接修改，请复制到自己的目录然后再做修改
 */
error_reporting(E_ERROR|E_COMPILE_ERROR|E_PARSE|E_CORE_ERROR|E_USER_ERROR);

if(!defined("_APP_DIR_")) die('"404 _APP_DIR_"');

if(!defined("_LIB_CORE_")) define("_LIB_CORE_",realpath(dirname(__FILE__)));

require _APP_DIR_."/config.switch.php";//switch of runtime env conf

ini_set("session.use_cookies",0);//Default not using Cookie
ini_set("session.name","_s");
if($SAE){
	//SAE mode
	if(!defined("_LOG_"))
		define("_LOG_",  'saestor://logs/');//提醒：新建SAE应用要打开 storage应用并新建 domain=logs
	if(!defined("_TMP_"))
		define("_TMP_", "saemc://");//提醒：新建 SAE应用要打开 memcache 服务...

	//SAE Special...but seems not working at all
	if(ini_get("session.name")!='_s'){
		setcookie('PHPSESSID',"",-1,'/');//http://stackoverflow.com/questions/686155/remove-a-cookie
		unset($_COOKIE['PHPSESSID']);
	}
}else{
	//local mode
	//LOG
	if(!defined("_LOG_"))
		define(_LOG_, _APP_DIR_ .'/_logs/');
	if(!is_dir(_LOG_)){
		mkdir(_LOG_,0777,true);
		if(!is_dir(_LOG_)){
			throw new Exception("_LOG_ FAIL");
		}	
	}
	//TMP && SESSION DEFAULT
	if(!defined("_TMP_")){
		define(_TMP_, _APP_DIR_ .'/_tmp/');
	}
	if(!is_dir(_TMP_)){
		mkdir(_TMP_,0777,true);
		if(!is_dir(_TMP_)){
			throw new Exception("_TMP_ FAIL");
		}
	}
	if(!is_dir(_TMP_.'/session/')){
		mkdir(_TMP_.'/session/',0777,true);
		if(!is_dir(_TMP_.'/session/')){
			throw new Exception("_TMP_/session/ FAIL");
		}
	}
}
//SAFE CHECK
//if(!defined("_LOG_"))
//	throw new Exception("_LOG_ is not config");
//if(!defined("_TMP_"))
//	throw new Exception("_TMP_ is not config");
//LIB
if(!defined("_LIB_")){
	define(_LIB_, realpath(_APP_DIR_ .'/_libs/'));
}
if(!is_dir(_LIB_)){
	throw new Exception("404 _LIB_");
}

require_once "inc.v5.mymagicload.php";//class loader adjustment ** important class

require_once "inc.v5.globalerror.example.php";//虽然是example但好好用...

require_once "inc.header.myglobalerror.php";//global error handling

require_once "inc.commonfunc.v5.php";//very common funcs for quick and simple coding

require_once 'inc.v5.func.config.php';//quick func of config...

if(!$SAE){//non SAE mode
	require_once 'inc.local.func.quicklog.php';//quick log...
}else{//SAE mode
	require_once 'inc.v5.func.quicklog.sae.php';//quick log...
}

require_once "inc.v5.lang.php";//quick func for lang handling

require_once "inc.v5.session.php";//quick func for session handling

require_once "inc.v5.quick.php";//more quick func (arr2var,arr2var_all,var2arr,arr2arr)

require_once "inc.v5.sqlsafe.php";//qstr() and qstr2();

