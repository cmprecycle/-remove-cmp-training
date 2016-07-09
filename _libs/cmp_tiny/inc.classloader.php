<?php
if(!function_exists('mg_autoload_function')){
	function mg_autoload_function($class_name)
	{
		if( file_exists( __DIR__ . '/MyClassLoader.php') ){
			require_once __DIR__ ."/MyClassLoader.php";
			$rt=MyClassLoader::load($class_name);
			//return $rt;
		}else{
			include_once "$class_name.php";//Try Blind
		}
	}
}
if( function_exists('__autoload') ){
	//__autoload since php5, but no longer suggested...

	if( function_exists('spl_autoload_register') ){
		//spl_autoload_register since php 5.1.2
		spl_autoload_register('__autoload');//(for php5.0~5.1.2)
		spl_autoload_register('mg_autoload_function');
	} else {
		throw new Exception("spl_autoload_register() required");
	}
} else {
	if(function_exists('spl_autoload_register')) {
		spl_autoload_register('mg_autoload_function');
	} else {
		//maybe works (php5.0, php5.1.2), but no effects to <php5.0:
		function __autoload($class_name) {
			mg_autoload_function($class_name);
		}
	}
}
