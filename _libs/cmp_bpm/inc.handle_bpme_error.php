<?php
if($flag_throw_ex){
	if(!$errmsg) throw new Exception("errmsg is empty?");
	throw new Exception($errmsg);
}else{
	$rt['STS']='KO';
	if($errcode)
		$rt['errcode']=$errcode;
	if($errmsg)
	$rt['errmsg']=$errmsg;
	return $rt;
}

