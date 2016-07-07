<?php
//NOTES: Can be overrided.
//注释：如果逻辑不够用，可以被过载，参考着重写checkApiAccess即可.
class AppAuth
{
	public static function checkApiAccess($request_class,$request_method,$request_method_param){

		$request_class=strtolower(trim($request_class));
		if(preg_match('/^(api|web|wap|ussd|sms|3g|mobile)/',$request_class)){
			return true;
		}

		quicklog_must("403","$request_class");//TODO 记录IP、时间、

		//重载时：比如说要做app级别的 防DDOS工作，所以要先记录起来。另外写一个分析器，分析和通知前端防火墙做调整防御.

		throw new Exception("403 $request_class");
	}
}

