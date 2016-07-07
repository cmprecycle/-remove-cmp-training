<?php
//-----------------
//NTOES:
//$HTTP_X_REAL_IP= getenv("HTTP_X_REAL_IP") || $_SERVER["HTTP_X_REAL_IP"]; //php这样的话法有问题，会得到1，跟js有些不同..
function _get_env($k){
	$rt=getenv($k);
	if($rt && $rt!="") return $rt;
	$rt=$_SERVER[$k];
	if($rt && $rt!="") return $rt;
	return null;
}
function _get_ip_(){
	static $_ip="";
	do{
		if($_ip!="") return $_ip;

		$LOCAL127="127.0.0.1";

		$HTTP_X_REAL_IP=_get_env("HTTP_X_REAL_IP");
		if($HTTP_X_REAL_IP && $HTTP_X_REAL_IP!=$LOCAL127){
			$_ip=$HTTP_X_REAL_IP;break;
		}

		$HTTP_CLIENT_IP=_get_env("HTTP_CLIENT_IP");
		if($HTTP_CLIENT_IP && $HTTP_CLIENT_IP!=$LOCAL127){
			$_ip=$HTTP_CLIENT_IP;break;
		}

		$HTTP_X_FORWARDED_FOR=_get_env("HTTP_X_FORWARDED_FOR");
		if($HTTP_X_FORWARDED_FOR)
			list($HTTP_X_FORWARDED_FOR)= explode(",",$HTTP_X_FORWARDED_FOR);
		if($HTTP_X_FORWARDED_FOR && $HTTP_X_FORWARDED_FOR!=$LOCAL127){
			$_ip=$HTTP_X_FORWARDED_FOR;break;
		}

		$REMOTE_ADDR=_get_env("REMOTE_ADDR");
		//		if($REMOTE_ADDR && $REMOTE_ADDR!=$LOCAL127){
		//			$_ip=$REMOTE_ADDR;break;
		//		}
		$_ip=$REMOTE_ADDR;
	}while(false);
	return($_ip);
}
function check_ip(){
	if(@$_SESSION['_ip']!=_get_ip_()){
		throw new Exception("IP Changed, Please login again.",4444);
	}
}

