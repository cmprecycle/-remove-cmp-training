<?php
// e.g.
// abc'ced
// abc''ced
function qstr2($s){
	//just replace ' to ''
	return str_replace("'","''",$s);
}

// e.g.
// abc'ced
// =>
// 'abc''ced''
function qstr($s){
	$x="'".str_replace("'","''",$s) ."'";
	return $x;
}

// e.g.
// array("x", "y");
// =>
// 'x','y'
function qstr_arr($_a){
	if(is_array($_a)){
	}else{
		//Let expect string...
		$_a=explode(",",$_a);
	}
	foreach($_a as $k=>$v){
		$_a[$k]=qstr($v);
	}
	return join(',',$_a);
}
