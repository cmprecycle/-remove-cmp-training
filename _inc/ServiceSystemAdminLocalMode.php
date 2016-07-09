<?php
//This is for Quick Mode to log the error to call SA by writing log simply locally.
//Usage A: in system tail -f  + tee to notify sms/email to admin with every XX min. 
//Usage A+: auto sync to (tail + tee + append?) log file in central and let house keeper to notify real system admin.
class ServiceSystemAdminLocalMode
{
	//@ref AppBpmeDefaultMode.onEvent()
	public static function notify($event, $info){
		logger('SA-CHECK.log',date('ymd_His:').$event);
		logger('SA-CHECK.log',$info);
		logger('SA-CHECK.log',"_SERVER-------".var_export($_SERVER,true));

		logger('SA-CHECK.log',"REQUEST_URI---------".$_SERVER['REQUEST_URI']);
		//quicklog_must("IT-CHECK","php_input---------".$php_input);
		logger('SA-CHECK.log',"HTTP_RAW_POST_DATA---------".$GLOBALS['HTTP_RAW_POST_DATA']);
		logger('SA-CHECK.log',"_REQUEST---------".var_export($_REQUEST,true));
		if($_POST) logger('SA-CHECK.log',"_POST---------".var_export($_POST,true));
	}
}

