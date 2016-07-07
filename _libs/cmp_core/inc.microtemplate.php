<?php
//url生成函数（用在 template.class.php里面）
function _dz_function_url($params_str, &$smarty){
	$pa=explode(' ',$params_str);
	$p=array();
	foreach($pa as $pal){
		list($k,$v)=explode('=',$pal);
		$p[$k]=$v;
	}
	return "<?=url(my_json_decode(\"".str_replace("\"","\\\"",my_json_encode($p))."\"))?".">";
}
//filetime（用在 template.class.php里面）
function _dz_function_filetime($fn){
	if(file_exists($fn)){
		return filemtime($fn);
	}
}

///////////////////////////////////////////////////////////////
//Usage: $html=eval(evalTpl($fn));
function evalTpl($fn){
	return <<<EOS
ob_start();
if(!file_exists('$fn')){
	ob_end_clean();
	throw new Exception('$fn not exists');
}
include(fetchCache('$fn'));
\$_tmp_ob_get_contents = ob_get_contents();
ob_end_clean();
return \$_tmp_ob_get_contents;
EOS;
}
function fetchCache($fn){
	if(!file_exists($fn))
		//throw new Exception("file not found ".basename($fn));
	throw new Exception("file not found $fn");

	$Viewer = DzTemplate::getInstance();

	header("Content-type: text/html; charset=UTF-8", 1);
	return $Viewer->fetchCache($fn);
}

//NOTES: 用 模板引擎 编译 $fn 对应的 模板文件，并把编译后的文件的文件名(含路径)返回
//Usage: include(TPL($fn));
function TPL($fn){
	//检查模板文件是否存在:
	if(!file_exists($fn))
		throw new Exception("404 FILE ".basename($fn,'.htm'));
	if(!defined("_TMP_")){
		throw new Exception("_TMP_ is not defined");
	}

	//引入模板引擎.
	//注：模板引擎的位置有过变换，历史原因.简单兼容一下.
	if(defined("_LIB_CORE_")){
		$_clsFile=_LIB_CORE_ ."/DzTemplate.php";
	}else{
		$_clsFile="template.class.php";
	}
	require_once($_clsFile);
	$Viewer = DzTemplate::getInstance();
	$Viewer->cache_dir = _TMP_;

	chdir(dirname($fn));//跳去模板文件所在的目录..
	$fn=basename($fn);

	//模板引擎编译（自带缓冲算法）:
	$compiled_file_path=fetchCache($fn);

	//返回编译后的文件的文件名（含路径）:
	return $compiled_file_path;
}

