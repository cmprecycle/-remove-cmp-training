<?php
//handle global error
error_reporting(0);

$_register_shutdown_function="_shutdown_function";//JSON output mode...
#$_register_shutdown_function="_shutdown_function_nojson";

if(!function_exists($_register_shutdown_function)){
	throw new Exception("$_register_shutdown_function not exists");
}

if($_register_shutdown_function) register_shutdown_function($_register_shutdown_function);
set_exception_handler('exception_handler');
