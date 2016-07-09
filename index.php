<?php
error_reporting(E_ERROR|E_COMPILE_ERROR|E_PARSE|E_CORE_ERROR|E_USER_ERROR);

$SERVER_NAME=strtolower($_SERVER['SERVER_NAME']);
if(in_array($SERVER_NAME,array('test.cmptech.info','demo.cmptech.info',
))){
	header("Location: cmp_demo/?rnd=".rand());
}else{
	header("Location: cmp_demo/?r=".rand());
	//print "{errmsg:'Wrong Entry'}";
}
