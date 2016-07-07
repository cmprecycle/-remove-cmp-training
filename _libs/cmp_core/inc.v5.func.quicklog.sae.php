<?php
//TODO 要写一个logger viewer
function logger($log_filename,$log_content,$prefix="",$gzf){
	$rt="";
	if(!defined('_LOG_')){ throw new Exception("//_LOG_ not defined to call logger"); }
	if($prefix=="DEFAULT") $prefix="--".date('ymd_His')."";

	//$suffix="\r\n";//for windows
	$suffix="\n";//for all

	$rt=_LOG_ .'/'.$log_filename;
	//file_put_contents($rt, $prefix.$log_content.$suffix, FILE_APPEND);
	//http://sae4java.sinaapp.com/doc/com/sina/sae/storage/SaeStorage.html
	/*
	 * Q: 什么不用 Sae Storage?
	 * A: Storage服务适合用来存储用户上传的文件，比如头像、附件等。不适合存储代码类文件，比如页面内调用的JS、 CSS等，尤其不适合存储追加写的日志。使用Storage服务来保存JS、CSS或者日志，会严重影响页面响应速度。建议JS、 CSS直接保存到代码目录，日志使用sae_debug()方法记录。
	 */
	$mysql = new SaeMysql();
	//注：小心sae大小写敏感..
	//$sql = "INSERT INTO tbl_log_sys (name,value,time) values(".qstr($log_filename).",".qstr($prefix.$log_content.$suffix).",NOW())";
	$sql = "INSERT INTO tbl_log_sys (name,value,time) values(".qstr($log_filename).",'".$mysql->escape(substr($prefix.$log_content.$suffix,0,1024))."',NOW())";
	$mysql->runSql( $sql );
	if( $mysql->errno() != 0 ) {
		$errmsg="failed ($sql) ".$mysql->errmsg();
			/*
CREATE TABLE `tbl_log_sys` (
	`name` varchar(255) DEFAULT NULL,
	`value` varchar(10240) DEFAULT NULL,
	`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	KEY `idx_name` (`name`),
	KEY `idx_time` (`time`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
			 */
		//在系统log那里再写一下....以确保写log失败还是有东西能保存下来
		$prefix2=date('ymd_His');
		try{
			$sql =<<<EOSQL
CREATE TABLE `tbl_log_sys` (
`name` varchar(255) DEFAULT NULL,
`value` varchar(10240) DEFAULT NULL,
`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
KEY `idx_name` (`name`),
KEY `idx_time` (`time`)
)
EOSQL;
			$mysql->runSql( $sql );//写LOG
		}catch(Exception $ex){
			throw new Exception("ADMIN: Log table not found, you need to init a 'share database' in your app for the first time, if you don't know please check the document in the taskmgr system.");
		}
		//file_put_contents(_LOG_ .'/'."SYS-FAILED-$prefix2.log", $prefix.$log_content.$suffix."###".$errmsg); //DIRECTORY_SEPARATOR
	}else{
		$mysql->closeDb();
	}
	return $rt;
}

//强制写log...
function quicklog_must($log_type=false,$log_content,$gz=false){
	if(is_object($log_content) || is_array($log_content)){
		$log_content=my_json_encode($log_content);
	}
	return logger($log_type."-".date('Ymd').".log",$log_content,"DEFAULT",$gz);
}

//NOTES: quicklog 现在其实很少用，一般用来审查系统性能才需要...很多时都还是用quicklog_must了....
function quicklog($log_type=false,$log_content,$gz=false,$write_stack=false){
	$trace = debug_backtrace(false);
	$caller = $trace[1];
	$caller_class=$caller['class'];
	$caller_function=$caller['function'];
	$debug_a=getConf("debug_a");
	$debug2=getConf("$caller_class.$caller_function",array("debug_a"),false);
	if($debug2){
		$debug=$debug2;
	}else{
		$debug1=getConf($caller_class,array("debug_a"),false);
		//println("[DEBUG]/debug_a/$caller_class.$function=$debug1");
		if($debug1){
			$debug=$debug1;
		}else{
			$debug0=getConf("*",array("debug_a"),false);
			//println("[DEBUG]/debug_a/*=$debug0");
			$debug=$debug0;
		}
	}
	if($log_type===false) return $debug;//if call for get debug level only
	if($write_stack){
		unset($trace[0]);
		$_s="";
		foreach($trace as $t)
		{
			$_s.= "\t" . '@ ';
			if(isset($t['file'])) $_s.= basename($t['file']) . ':' . $t['line'];
			else
			{
				$_s.= '<PHP inner-code>';
			}

			$_s.= ' -- ';

			if(isset($t['class'])) $_s.= $t['class'] . $t['type'];

			$_s.= $t['function'];

			if(isset($t['args']) && sizeof($t['args']) > 0) $_s.= '(...)';
			else $_s.= '()';

			$_s.= "\n";
		}
		$log_content.="\n"."[STACK]".$_s;
	}
	if($debug>0){
		if(is_string($log_type)){
			if(is_object($log_content) || is_array($log_content)){
				$log_content=my_json_encode($log_content);
			}
			return logger($log_type."-".date('Ymd').".log",$log_content,"DEFAULT",$gz);
		}
	}
	if(is_object($log_type)){
		throw new Exception("TODO.quicklog().if log_type is object, check it's ['debug'], and then do logging");
	}
}


