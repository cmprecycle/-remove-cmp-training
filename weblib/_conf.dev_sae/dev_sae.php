<?php
$_conf_all_common_['flag_rb_freeze']=FALSE;
$_conf_all_[$_switch_conf]=$_conf_all_common_;

$_conf_all_[$_switch_conf]["CONFIG_TYPE"]="DEVSAE";//Server 1 ReadWrite
$_conf_all_[$_switch_conf]["debug_a"]=array(
	"*"=>1,//1 to open debug all for quicklog(); note:: quicklog_must() bypass this check
);
//go saas core to get the saas tenant config infor

//$SCRIPT_NAME=$_SERVER['SCRIPT_NAME'];
//$PATH=dirname($SCRIPT_NAME);
//$SERVER_PORT=$_SERVER['SERVER_PORT'];
//$SERVER_PORT=$SERVER_PORT?:"80";


