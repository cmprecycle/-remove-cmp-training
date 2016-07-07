<?php
//@deprecated.
//TODO 有使用到本函数的，请移步去使用 http_req*() 系列
//如果需要SOCKS5的，需要使用CURL库，准备丰富 inc.func.web_request.php 或者 webclient类，TODO
function http_post($url, $postdata, $timeout=14){
	if(is_array($postdata)){
		$postdata_s=http_build_query($postdata);
	}else/*if(is_string($postdata))*/{
		$postdata_s=$postdata;
	}
	$data_len = strlen($postdata_s);

	$context=stream_context_create(array('http'=>array(
		'method'=>'POST',
		'timeout'=>$timeout,
		//"header"  => "User-agent: Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36", 
		//"proxy"   => "tcp://my-proxy.localnet:3128", 
		//'request_fulluri' => True //some proxy need full uri
		'header'=>"Connection: close\r\nContent-Length: $data_len\r\n",
		'content'=>$postdata_s,
	)));
	$rt=file_get_contents($url, false, $context);
	return $rt;
}
