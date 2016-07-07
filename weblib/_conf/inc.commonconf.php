<?php
$_conf_all_common_=array(

	#debug for quicklog() # maybe need to upgrade later.  
	"debug_a"=>array(
		"*"=>0,//quicklog debug level, default 0 means no debugging at all
	),

	#class path
	"class_path_a"=>array(
		_APP_DIR_."/../_inc",
		_LIB_."/cmp_ext",
	),

	#mini tpl
	"dz"=>array(
		'template_dir'=> _APP_DIR_,
		'compile_dir' => _TMP_,
		'force_compile' => true,
	),

	#cache lite
	"cache_lite"=>array(
		'dir_name' => 'lib.Cache_Lite-1.7.2',
		'cache_dir' => _TMP_ ."/",
	),

	#lang
	'lang_pack_conf'=>'_lang/lang_pack.xls',//in XML (excel2003) format
	'lang_list'=>array(
		"en"=>"en",
		"en-us"=>"en",
		"en-uk"=>"en",
		"zh-cn"=>"zh-cn",
		"zh-tw"=>"zh-tw",
		"zh-hk"=>"zh-tw",//normally hk share tw
		"th"=>"th",
		"kh"=>"kh",
		"vn"=>"vn",
	),
	'default_lang'=>'en',

	#timezone
	"SERVER_TIMEZONE"=>"Asia/Phnom_Penh",//for adjust_timezone()
	//"SERVER_TIMEZONE"=>"Etc/GMT-8",//default timezone Bejing Time
	//"SERVER_TIMEZONE"=>"Etc/GMT+5",//EST Eastern Standard Time
	//"SERVER_TIMEZONE"=>"Etc/GMT+8",//PSC Pacific Stabard Time
	"db_timezone"=>"+7:00",//for rbWrapper3
);

$_conf_all_common_['flag_rb_freeze']=TRUE;//for red bean php wrapper
