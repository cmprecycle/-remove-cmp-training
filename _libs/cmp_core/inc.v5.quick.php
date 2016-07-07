<?php
//常用快速函数.能缩减代码量!

//usage: eval(arr2var_all("param"));
function arr2var_all($name_of_arr){
	return <<<EOS
eval(arr2var("$name_of_arr",array_keys(\$$name_of_arr)));
EOS
	;
}
function arr2var($name_arr,$arr,$prefix=""){
	if(!is_array($arr)){
		throw new Exception(getLang("KO-arr2var-notarray").my_json_encode($arr));
	}
	$rt="";
	foreach($arr as $key){$rt.='$'.$prefix.$key.'=$'.$name_arr.'["'.$key.'"];';}
	return $rt;
}

function var2arr($name_arr,$arr){
	$rt="";
	foreach($arr as $key){$rt.='$'.$name_arr.'["'.$key.'"]=$'.$key.';';}
	$rt.=";";
	return $rt;
}

//把 src中 索引为 key_a中的 拷贝去 target
//Usage
//arr2arr($rt,$_SESSION,array('auth_user_id'));
function arr2arr(& $target, $src, $key_a,$prefix=""){
	if (is_null($target)){
		$target=array();
	}
	if (is_array($key_a)) {
		foreach($key_a as $k){
			$target[$prefix.$k]=$src[$k];
		}
	}else{
		//or all:
		foreach($src as $k=>$v){
			$target[$prefix.$k]=$v;
		}
	}
}
