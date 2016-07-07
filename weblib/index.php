<?php
/**
 * Eg.
 * http://..../weblib/?jsa=json4ie,jstorage
 * http://..../weblib/?js=jstorage
 */

//TODO 未做针对 js和jsa参数所生产的制作缓存....所以还不能大量使用
//TODO 还需要做一个静态处理，比如  weblib/static.{$jsa}.js =>映射 weblib/?jsa=$jsa.js

error_reporting(E_ERROR|E_COMPILE_ERROR|E_PARSE|E_CORE_ERROR|E_USER_ERROR);
if(!defined("_APP_DIR_")) define("_APP_DIR_",realpath(dirname(__FILE__)));

#微调目录结构...
#define("_LIB_",realpath(__DIR__ ."/../_libs/"));
#if(_LIB_=="")throw new Exception("empty _LIB_");

#adjust_timezone();//...

require "../_libs/cmp_core/inc.cmp_core.php";
require_once _LIB_CORE_ ."/func.js_enc_txt.php";


//初步想法是算出需要的文件的 MD5 ？

$js=$_REQUEST['js'];
$jsa=$_REQUEST['jsa'];

$raw=$_REQUEST['raw'];

if($js){
	$js_file="src.$js.js";
	if(file_exists($js_file)){
		header('Content-Type: application/javascript');
		if($raw){
			print js_enc_txt_quick(file_get_contents($js_file));//TESTING
		}else{
			print jsa_enc_txt(array(
				$js_file,
			));
		}
	}
}elseif($jsa){
	header('Content-Type: application/javascript');
	foreach(explode(',',$jsa) as $k=>$v){
		if($raw){
			print js_enc_txt_quick(file_get_contents("src.$v.js"));//TESTING
		}else{
			$realjsa[]="src.$v.js";
		}
	}
	if(!$raw){
		print jsa_enc_txt($realjsa);
	}
}
