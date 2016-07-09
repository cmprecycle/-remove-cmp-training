<?php
require_once "../inc.app.php";

//while(true){
try{
	//the entry to check the bpe status.
	$url_api_check_bpe=getConf("url_api_check_bpe");
	//$url_api_check_bpe="http://www.baidu.com/";
	if(!$url_api_check_bpe) throw new Exception("please check config for url_api_check_bpe");
	println("url_api_check_bpe=$url_api_check_bpe");

	//TODO 访问远程。
	$DIRECTORY_SEPARATOR=DIRECTORY_SEPARATOR;
	$LIB=_LIB_;
	//require "$LIB{$DIRECTORY_SEPARATOR}cmp_core{$DIRECTORY_SEPARATOR}inc.func.http_post.php";
	//echo http_get($url_api_check_bpe);
	//echo http_post($url_api_check_bpe);
	require_once "$LIB{$DIRECTORY_SEPARATOR}cmp_core{$DIRECTORY_SEPARATOR}inc.func.http_req.php";

	//echo http_req(array( 'url'=>$url_api_check_bpe ));

	/*
	echo http_req([ 'url'=>$url_api_check_bpe."?test3=3&test4=4",
		'data'=>my_json_encode([
		//NOTES: _c,_m,_p,_s 是CMP框架的保留字参数。。。
		//'_c'=>'XXXX',
		//'_m'=>'YYYY',
		//'_p'=>[
		//	'method'=>'干扰2',
		//],
			'test1'=>1,'test2'=>2,
			//'method'=>'干扰测试',
			'param'=>[
				'method'=>'干扰2',
			],
		])
	]);
*/
	#echo $url_api_check_bpe;
	println(http_req_quick($url_api_check_bpe, array(
		"engine_id"=>1,
	)));
	//TODO 计算是否要发送alertToAdmin() 
	//TODO if(STS!='OK') count (ALERTx1.5) times and then send alert();
	//else reset ALERT

}catch(Exception $ex){
	//BPE::dump($ex);
	print $ex;
}
sleep(1);//rest one second after one loop
//}
