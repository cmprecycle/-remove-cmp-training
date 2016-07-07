<?php
#throw new Exception("Must manually edit config.switch.php");
$SAE=defined('SAE_TMP_PATH') && !$argv[0];//dirty tricks
if($SAE){
	$_switch_conf="dev_sae";//Using SAE config on SAE Env
}else{
	//$SERVER_NAME=$_SERVER['SERVER_NAME'];
	//if(in_array($SERVER_NAME,array("localhost","LOCALHOST","127.0.0.1","Localhost","LocalHost"))){
	//	if(!$_switch_conf)$_switch_conf="dev_local";
	//}else{
	//	require("config.switch.override.php");
	//	if(!$_switch_conf)$_switch_conf="dev_local";
	//}
	/*
		require_once _LIB_CORE_.DIRECTORY_SEPARATOR."inc.v5.secure.php";
	$_get_ip_=_get_ip_();
	if($_get_ip_=="127.0.0.1"){
		//if(!$_switch_conf)
		$_switch_conf="dev_local";
	}else{
		if(file_exists(__DIR__."/config.switch.override.php"))
			require(__DIR__."/config.switch.override.php");//如果有错就会知道应该把example复制修改好!!
		else{
			print "404 config.switch.override";die;
		}
	}
	 */

	//NOTES: 不要提交任何的 .tmp文件到 代码库.
	if(file_exists(__DIR__."/config.switch.override.tmp"))
		require(__DIR__."/config.switch.override.tmp");
	else{
		//print "404 config.switch.override.tmp";die;//正常的项目是用这句来提醒部署者要用这个做“开关”文件.
		$_switch_conf="cmp_demo";//demo先跳过.tmp
	}
}

