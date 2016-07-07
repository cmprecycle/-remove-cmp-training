<?php
function mg_autoload_function($class_name)
{
	require_once _LIB_CORE_ ."/MyClassLoader.php";
	$rt=MyClassLoader::old_load($class_name);
	return $rt;
}
