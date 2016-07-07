<?php
/**
Usage Example1:
require_once _LIB_CORE_ ."/inc.handle304.php";
$page_mtime=filemtime(__FILE__);//注：可以用其它来判断的.
handle304($page_mtime);

Example2:
handle304($file_last_cache_time, "", 60);//用分钟控制客户频繁的值.这个可以用在 static.php

 */
function handle304($lmt, $tag, $client_cache_time=3600){
	$md5 = md5($lmt.$tag);
	$etag = '"' . $md5 . '"';

	header('Cache-Control: public, max-age='.$client_cache_time);

	//NOTES: 这里用php时间不是特别，但是用db时间又有消耗!
	header('Expires: '.gmdate('D, d M Y H:i:s',time() + $client_cache_time).' GMT');//设置页面缓存时间

	header('Pragma: public');

	header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lmt ).' GMT');

	header("ETag: $etag");

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

	if($flag_check && $flag_time && $flag_etag)
	{
		header("HTTP/1.1 304 Not Modified");
		exit(0);//Important.
	}
}

function _handle304_v2($now, $lmt, $tag, $client_cache_time=3600){
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
		if($_SERVER['HTTP_IF_NONE_MATCH'] != preg_replace("^W/","",$etag)){
			$flag_etag=false;
		}
	}

	#session_cache_limiter("public");

	header('Cache-Control: public, max-age='.$client_cache_time);

	header('Expires: '.gmdate('D, d M Y H:i:s', $now + $client_cache_time).' GMT');//设置页面缓存时间

	header('Pragma: public');

	header('Last-Modified: '.gmdate('D, d M Y H:i:s',$lmt ).' GMT');

	header("ETag: $etag");
	if($flag_check && $flag_time && $flag_etag)
	{
		header("HTTP/1.1 304 Not Modified");
		//exit(0);
		return false;
	}
	header("FailReason: Fail".($flag_etag?"_1":"_ETAG").($flag_time?"_2":"_TIME").($flag_check?"_3":"_CHCK"));
	return true;
}//function _handle304

