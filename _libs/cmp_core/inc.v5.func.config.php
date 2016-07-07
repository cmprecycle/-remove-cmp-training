<?php
#============================================================================================ config
function &getConf($key,$path=array(),$mandate_flag=false,$setConf=0,$setValue=null){
	#throw new Exception("getConf() to be rewritten as V5Conf::get()");
	static $_conf_=null;
	if(!$_conf_){
		$_switch_conf="";
		require(_APP_DIR_ ."/config.switch.php");
		if($_switch_conf=="") throw new Exception("ConfigError: config.switch.php not found?? ($_switch_conf)");

		//require "inc.commonconf.php";//_conf_all_common_
		require _APP_DIR_."/_conf/inc.commonconf.php";//_conf_all_common_
		if(!$_conf_all_common_) throw new Exception("ConfigError: not found _conf_all_common_");

		//$dir_switch_conf=$_conf_all_common_['dir_switch_conf'];
		//if(!$dir_switch_conf) throw new Exception("ConfigError: not found $_switch_conf.dir_switch_conf");

		$conf_file=(realpath(_APP_DIR_."/_conf.$_switch_conf/$_switch_conf.php"));
		if(!$conf_file) throw new Exception("ConfigError: $_switch_conf not found");
		require $conf_file;

		//if($mandate_flag && $_conf_all_[$_switch_conf]){} else {
		//	throw new Exception("ConfigError: getConf failed \$_conf_all_[$_switch_conf]");
		//}

		if(! $_conf_all_[$_switch_conf] )
			throw new Exception("ConfigError: getConf failed \$_conf_all_[$_switch_conf]");
		
		$_conf_=($_conf_all_[$_switch_conf]);
	}
	
	$rt=& $_conf_;
	$errmsg="ConfigError: getConf".join('/',$path)."/$key failed";
	if($key){
		foreach($path as $_k){
			if(!array_key_exists($_k,$rt)){
				if($setConf==1){
					$rt[$_k]=array();
				}elseif($mandate_flag){
					throw new Exception($errmsg);
				}
			}
			$rt=& $rt[$_k];
		}
		if($setConf==1){ //1=save
			$rt[$key]=$setValue;
		}elseif($setConf==2){//2=remove
			unset($rt[$key]);
		}
		if(array_key_exists($key,$rt)){
		}else{
			if($mandate_flag){
				throw new Exception($errmsg);
			}
			if($setConf==1){ //1=save
				$rt[$key]=$setValue;
			}elseif($setConf==2){//2=remove
				unset($rt[$key]);
			}
		}
		//$rt=$val=$rt[$key];
		$rt= & $rt[$key];
	}else{
		throw new Exception("getConf need param key");
	}
	return $rt;
}
function setConf($key,$val,$path=array()){
	#throw new Exception("setConf() to be rewritten as V5Conf::set()");
	return getConf($key,$path,false,1,$val);
}
function removeConf($key,$val,$path=array()){
	#throw new Exception("removeConf() to be rewritten as V5Conf::remove()");
	return getConf($key,$path,false,2);
}
function saveConf($key,$path=array(),$filename=""){//save to file
	#throw new Exception("saveConf() to be rewritten as V5Conf::save()");
}

