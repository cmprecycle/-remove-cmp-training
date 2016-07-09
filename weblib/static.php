<?php
error_reporting(E_ERROR|E_COMPILE_ERROR|E_PARSE|E_CORE_ERROR|E_USER_ERROR);
//NOTES:  _c._m.static is a super cache to _c._m.api for special use.  going to kill for the .shtml....

$SAE=defined('SAE_TMP_PATH') && !$argv[0];//dirty code
if(!defined("_STATIC_DIR_")){
	if($SAE){
		define("_STATIC_DIR_", "saemc://");//提醒：新建 SAE应用要打开 memcache 服务...
	}else{
		require "config.switch.override.tmp";
		if(defined("_TMP_")){
			define("_STATIC_DIR_", _TMP_);
		}else{
			define("_STATIC_DIR_", __DIR__."/_tmp/");
		}
	}
}

$REQUEST_URI=$_SERVER['REQUEST_URI'];
//移除 _s 参数，否则太多缓存了...
$REQUEST_URI=preg_replace("([^a-zA-Z0-9]_s=[a-zA-Z0-9]*)","",$REQUEST_URI);
//print $REQUEST_URI;die;

$cache_time=$_GET['cache_time'];
if(is_numeric($cache_time) && $cache_time>0 && $cache_time< 99999){
	//OK, use it.
}else{
	$cache_time=120;//Default
}

read_cache_and_judge:{
	$REQUEST_METHOD=$_SERVER['REQUEST_METHOD'];
	if("GET"!=$REQUEST_METHOD) //要GET才做缓存.
		goto do_normal_only;

	//TODO 检查其它 明显 不需要缓存的情况？好像没了。

	function _handle304($lmt, $tag, $client_cache_time=3600){
		$md5 = md5($lmt.$tag);
		$etag = '"' . $md5 . '"';

		$flag_time=true;
		$flag_etag=true;

		$flag_check=false;//假设默认 !(HTTP_IF_MODIFIED_SINCE || HTTP_IF_UNMODIFIED_SINCE || HTTP_IF_NONE_MATCH)

		//$lmt+=1;//测试 PASS
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])){
			$flag_check=true;
			if(strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) < $lmt){
				$flag_time=false;
			}
		}
		if(isset($_SERVER['HTTP_IF_UNMODIFIED_SINCE'])){
			$flag_check=true;
			if(strtotime($_SERVER['HTTP_IF_UNMODIFIED_SINCE']) >= $lmt) {
				$flag_time=false;
			}
		}
		if(isset($_SERVER['HTTP_IF_NONE_MATCH'])){
			$flag_check=true;
			if($_SERVER['HTTP_IF_NONE_MATCH'] != $etag){
				$flag_etag=false;
			}
		}

		#session_cache_limiter("public");

		header('Cache-Control: public, max-age='.$client_cache_time);

		header('Expires: '.gmdate('D, d M Y H:i:s',time() + $client_cache_time).' GMT');//设置页面缓存时间

		header('Pragma: public');

		header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lmt ).' GMT');

		header("ETag: $etag");
		if($flag_check && $flag_time && $flag_etag)
		{
			header("HTTP/1.1 304 Not Modified");
			//exit(0);
			return false;
		}
		return true;
	}

	//if($flag_need_judge_static_cache){
	$cache_tag=md5($REQUEST_URI);
	$cache_file_base =_STATIC_DIR_.$cache_tag;
	$cache_file = $cache_file_base .".cache";
	$cache_lmt_file = $cache_file_base.".lmt";
	if(file_exists($cache_file) && file_exists($cache_lmt_file)){
		$cache_lmt=file_get_contents($cache_lmt_file);
		if( time() < $cache_lmt + $cache_time ){
			$cache_content=file_get_contents($cache_file);
			if($cache_content!=""){
				$Content_type=$_GET['Content_type'];
				if($Content_type!=""){
					if($Content_type=='text/css'){
						header("Content-Type: $Content_type");
					}else{
						//先忽略，以后再看情况.
						//header("Content-Type: ".$Content_type);
					}
				}
				ignore_user_abort(true);//304后客户端可能会主动断开。这时我也先不断开，还要判断下要不要做步进缓存.
				if ( _handle304($cache_lmt, $cache_tag, $cache_time )){
					//304失败，改为把缓存输出也收工...
					//$headers_from_client=getallheaders();
					header("PageTime: ".date_create()->format('Y-m-d H:i:s'));
					//TODO 把上面的.lmt也格式化后输出.
					echo $cache_content;
					flush();
				}else{
					//成功 304 不会输出的...
					//header("PageTime304: ".date_create()->format('Y-m-d H:i:s'));
				}
				if ( time() < $cache_lmt + $cache_time/2 ){
					//上面已经输出缓存了,还在步长一半以内，不用生成.
					goto section_end;
				}else{
					//已过步长一半，要生成一下缓存.
					goto do_normal_and_write_cache_only;
				}
			}
		}
	}
	//} if ($flag_need_judge_static_cache)
}//read_cache_and_judge

do_normal_and_write_cache:{
	$REQUEST_URI=str_replace(".static",".api",$REQUEST_URI);//Not 100% safe... but...anyway...
	$_prev_HTTP_ACCEPT_ENCODING=$_SERVER['HTTP_ACCEPT_ENCODING'];
	$_SERVER['HTTP_ACCEPT_ENCODING']='deflate';
	header("StaticNormal: ".date_create()->format('Y-m-d H:i:s'));
	#header("DBG: $dbg_1 ".$cache_file);
	@ob_start();
	include 'index.php';
	$output = ob_get_clean();
	file_put_contents($cache_file, $output);
	file_put_contents($cache_lmt_file, time());
	$_SERVER['HTTP_ACCEPT_ENCODING']=$_prev_HTTP_ACCEPT_ENCODING;
	_gzip_output($output);
	goto section_end;
}

do_normal_and_write_cache_only:{
	$REQUEST_URI=str_replace(".static",".api",$REQUEST_URI);//Not 100% safe... but...anyway...
	$_prev_HTTP_ACCEPT_ENCODING=$_SERVER['HTTP_ACCEPT_ENCODING'];
	$_SERVER['HTTP_ACCEPT_ENCODING']='deflate';
	#header("StaticSkip: ".date_create()->format('Y-m-d H:i:s'));
	@ob_start();
	include 'index.php';
	$output = ob_get_clean();
	file_put_contents($cache_file, $output);
	file_put_contents($cache_lmt_file, time());
	$_SERVER['HTTP_ACCEPT_ENCODING']=$_prev_HTTP_ACCEPT_ENCODING;
	#_gzip_output($output); //纯生成不用显示.
	goto section_end;
}

do_normal_only:{
	include 'index.php';
	goto section_end;
}

section_end:{}

