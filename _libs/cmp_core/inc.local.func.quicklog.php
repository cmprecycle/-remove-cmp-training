<?php
#============================================================================================ logger / quicklog
function logger($log_filename,$log_content,$prefix="",$gzf){
	$rt="";
	if(!defined('_LOG_')){ throw new Exception("//_LOG_ not defined to call logger"); }
	if($prefix=="DEFAULT"){
		list($sec, $usec) = explode(".", microtime(true));
		$prefix="--".date('ymd His').".$usec: ";
	}
	//$suffix="\r\n";//for windows
	$suffix="\n";//for all
	if($gzf){
		$rt=_LOG_ .'/'.$log_filename .'.gz';
		//$gz = gzopen($rt,'a');
		//gzwrite($gz, $prefix.$log_content.$suffix);
		//gzclose($gz);
		file_put_contents($rt, gzencode($prefix.$log_content.$suffix), FILE_APPEND);
	}else{
		$rt=_LOG_ .'/'.$log_filename;
		file_put_contents($rt, $prefix.$log_content.$suffix, FILE_APPEND);
	}
	return $rt;
}
function quicklog_must($log_type=false,$log_content,$gz=false){
	//back to the old days that u must log
	if(is_string($log_content)){
	}else{
		$log_content=var_export($log_content,true);
	}
	//if(is_object($log_content) || is_array($log_content)){
	//	$log_content=my_json_encode($log_content);
	//}
	return logger($log_type."-".date('Ymd').".log",$log_content,"DEFAULT",$gz);
}

//Usage:
//quicklog(false);//get current function debug or not
//quicklog($logtype, $logtxt, $gz=true );//write $logtxt to _LOG_/$logtype-Ymd.log
//quicklog($logtype, $logtxt, $gz=false, $write_stack=true);// write the $logtxt and whole process steps
function quicklog($log_type=false,$log_content,$gz=false,$write_stack=false){
	$trace = debug_backtrace(false);
	$caller = $trace[1];
	$caller_class=$caller['class'];
	$caller_function=$caller['function'];
	$debug_a=getConf("debug_a");
	$debug2=getConf("$caller_class.$caller_function",array("debug_a"),false);
	//println("[DEBUG]/debug_a/$caller_class.$caller_function=$debug2");
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

			//$_s.= PHP_EOL;
			$_s.= "\n";
		}
		//$log_content.=PHP_EOL."[STACK]".$_s;
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


