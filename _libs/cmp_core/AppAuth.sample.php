<?php

//Dummy can be overrided

class AppAuth
	 extends AppCommon
{
	public static function checkApiAccess($request_class,$request_method,$request_method_param){
		quicklog_must("TODO","checkApiAccess $request_class");
		//要做 DDOS 工作，先记录起来。另外写一个分析器，分析和通知前端防火墙做准备...
		return true;
	}
}


