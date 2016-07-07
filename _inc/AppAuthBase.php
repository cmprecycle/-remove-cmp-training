<?php
//如果不够用，在应用的目录覆盖一个就可以了.
class AppAuthBase
{
	public static function checkApiAccess($request_class,$request_method,$request_method_param){

		$request_class=strtolower(trim($request_class));
		if(preg_match('/^(api|web|wap|ussd|sms|3g|mobile)/',$request_class)){
			return true;
		}
		if(preg_match('/^(a)$/',$request_class)){
			return true;
		}

		//TODO 要做app级别的 防DDOS工作，所以要先记录起来。另外写一个分析器，分析和通知前端防火墙做准备...
		quicklog_must("403","$request_class");//TODO 记录IP、时间、
		throw new Exception("403 $request_class");
	}
}

