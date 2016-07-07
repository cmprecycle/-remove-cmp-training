<?php
/* vim: set tabstop=2 shiftwidth=2 softtabstop=2: */

function getXlsArr($nameXls){
	global $_tm_, $_g_probe_time;
	if($_g_probe_time>2) $_tm_[]=array("before getXlsArrFile",microtime(true));
	$file=getXlsArrFile($nameXls);
	if($_g_probe_time>2) $_tm_[]=array("after getXlsArrFile",microtime(true));
	require($file);
	if($_g_probe_time>2) $_tm_[]=array("after require $file",microtime(true));
	$rt=$getXlsArrFile_rt;//约定.
	return $rt;
}

function xls_zip_extract_tmp($zip_file,$extract_to_file){
	$zip = zip_open($zip_file);
	if ($zip) {
		while ($zip_entry = zip_read($zip)) {
			$fp = fopen(
				//_TMP_ ."/".zip_entry_name($zip_entry)
				$extract_to_file
				, "w");
			if (zip_entry_open($zip, $zip_entry, "r")) {
				$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
				fwrite($fp,"$buf");
				zip_entry_close($zip_entry);
				fclose($fp);
			}
		}
		zip_close($zip);
	}
}
function getXlsArrFile($nameXls){
	if(!$nameXls) throw new Exception("KO-404-param-nameXls");

	$xls_file=_APP_DIR_.DIRECTORY_SEPARATOR.$nameXls;
	$filemtime=filemtime($xls_file);

	if(!$filemtime){
		//2015-3-13 没有的话试试zip
		$nameXlsZip=$nameXls.".zip";
		$xls_zip_file=_APP_DIR_.DIRECTORY_SEPARATOR.$nameXlsZip;
		$filemtime=filemtime($xls_zip_file);
		if(!$filemtime){ throw new Exception("KO-404-".$nameXlsZip); }

		//$xls_file=_TMP_ ."/$filemtime.".basename($nameXls);
		$xls_file=_TMP_ ."/$filemtime.".basename($nameXls);
		$xls_file_mtime=filemtime($xls_file);
		if(!$xls_file_mtime){
			xls_zip_extract_tmp($xls_zip_file,$xls_file);
			$xls_file_mtime=filemtime($xls_file);
		}
		if(!$xls_file_mtime){
			throw new Exception("Fail Extract $nameXlsZip");
		}
	}
	if(!$filemtime){ throw new Exception("KO-404-".$nameXls); }

	$target_cache_file=_TMP_."/".basename($xls_file).".".$filemtime.".php.cache";
	$cache_file_mtime=filemtime($target_cache_file);
	if(!$cache_file_mtime){
		//gen cache file
		require_once _LIB_."/faisalman-simple-excel-php-9bcff4b/src/SimpleExcel/SimpleExcel.php";
		$excel = new SimpleExcel\SimpleExcel('xml');
		$excel->parser->loadFile("$xls_file");
		$csv_a=$excel->parser->getField();

		$csv_a_1st=array_shift($csv_a);
		array_shift($csv_a);//第二行忽略...（约定）.
		array_shift($csv_a_1st);//不要第一行的第一个，其它为key
		$key_a=array();
		$_full_a=array();
		foreach($csv_a as $k=>$v){
			$key_a[]=$v[0];
			foreach($csv_a_1st as $kk=>$vv){
				if(!$_full_a[$vv])$_full_a[$vv]=array();
				$_full_a[$vv][$v[0]]=$v[$kk+1];
			}
		}
		$_full_a['KEYS']=$key_a;
		$full_s=var_export($_full_a,true);
		$full_s=str_replace("&#38;","&",$full_s);
		file_put_contents($target_cache_file,"<"."?php\n\$getXlsArrFile_rt=$full_s;");
		$cache_file_mtime=filemtime($target_cache_file);
		if(!$cache_file_mtime) throw new Exception("KO FOR COMPILE pack");
	}
	return $target_cache_file;
}

//变体：返回以行 为数组的结果, 暂时使用到的地方有 AceTool、ApiTopup
function getXlsArr2($nameXls){
	$file=getXlsArrFile2($nameXls);
	require($file);
	$rt=$getXlsArrFile_rt;//约定.
	return $rt;
}

function getXlsArrFile2($nameXls){
	if(!$nameXls) throw new Exception("KO-404-param-nameXls");

	$xls_file=_APP_DIR_.DIRECTORY_SEPARATOR.$nameXls;
	$filemtime=filemtime($xls_file);
	if(!$filemtime){
		//copy by zhb 2015-3-13 没有的话试试zip
		$nameXlsZip=$nameXls.".zip";
		$xls_zip_file=_APP_DIR_.DIRECTORY_SEPARATOR.$nameXlsZip;
		$filemtime=filemtime($xls_zip_file);
		if(!$filemtime){ throw new Exception("KO-404-".$nameXlsZip); }

		//$xls_file=_TMP_ ."/$filemtime.".basename($nameXls);
		$xls_file=_TMP_ ."/$filemtime.".basename($nameXls);
		$xls_file_mtime=filemtime($xls_file);
		if(!$xls_file_mtime){
			xls_zip_extract_tmp($xls_zip_file,$xls_file);
			$xls_file_mtime=filemtime($xls_file);
		}
		if(!$xls_file_mtime){
			throw new Exception("Fail Extract $nameXlsZip");
		}
	}
	if(!$filemtime){ throw new Exception("KO-404-".$nameXls); }

	$target_cache_file=_TMP_."/".basename($xls_file).".".$filemtime.".php.cache";
	$cache_file_mtime=filemtime($target_cache_file);
	if(!$cache_file_mtime){
		//gen cache file
		require_once _LIB_."/faisalman-simple-excel-php-9bcff4b/src/SimpleExcel/SimpleExcel.php";
		$excel = new SimpleExcel\SimpleExcel('xml');
		$excel->parser->loadFile("$xls_file");
		$csv_a=$excel->parser->getField();

		$csv_a_1st=array_shift($csv_a);
		array_shift($csv_a);//第二行忽略...（约定）.
		//这个函数最大的修改是下面的逻辑：
		$_full_a=array();
		foreach($csv_a as $k=>$v){
			$_singleRow = array();
			foreach($csv_a_1st as $kk=>$vv){
				$_singleRow[$vv] = $v[$kk];
			}
			$_full_a[] = $_singleRow;
		}
		$full_s=var_export($_full_a,true);
		$full_s=str_replace("&#38;","&",$full_s);
		file_put_contents($target_cache_file,"<"."?php\n\$getXlsArrFile_rt=$full_s;");
		$cache_file_mtime=filemtime($target_cache_file);
		if(!$cache_file_mtime) throw new Exception("KO FOR COMPILE pack");
	}
	return $target_cache_file;
}

function getLang_a($lang){
	static $lang_a=null;
	if($lang_a) return $lang_a[$lang];//静态有的话就用静态的.
	if(!$lang)$lang=$_SESSION['lang'];
	if(!$lang)$lang=getConf("default_lang");

	$lang_pack_conf=getConf("lang_pack_conf");//注意是相对目录
	$lang_a=$a=getXlsArr($lang_pack_conf);
	return $a[$lang];
}

function calcLangFromBrowser(){
	preg_match('/^([a-z\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
	$lang = strtolower($matches[1]);
	if(!$lang) $lang='en';
	return $lang;
}

function getLang($k,$lang=null){
	if(!$k) throw new Exception("KO: getLang(null) is not supported");
	static $lang_static=null;//初值为空
	static $lang_static_en=null;//初值为空
	if(!$lang){
		if($lang_static) $lang=$lang_static;//静态已经有，直接用静态的.
		else{
			if(!$lang)$lang=$_REQUEST['lang'];
			if(!$lang)$lang=$_SESSION['lang'];
			if(!$lang)$lang=$_COOKIE['lang'];
			if(!$lang)$lang="en";//实在不行才用en做为保底...
			$lang_static=$lang;//保存到静态给下一个用.
		}
	}
	$lang_a=getLang_a($lang);
	if(in_array($lang,array("type","column","remark","uidefault"))){
		//special...
		$rt=$lang_a[$k];
	}else{
		if($lang_static_en) $lang_a_en=$lang_static_en;
		else $lang_static_en=$lang_a_en=getLang_a("en");

		if(!$lang_a) $lang_a=$lang_a_en;
		$rt=$lang_a[$k];
		if(!$rt) $rt=$lang_a_en[$k];
		if(!$rt) $rt="I18N_".$k;
	}
	return $rt;
}
