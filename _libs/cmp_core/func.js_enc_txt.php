<?php
//Usage print js_enc_txt(trim(file_get_contents($v))).";\n";
function js_enc_txt($js_txt, $enc_level=0){
	//require_once __DIR__ .'/'.'../_inc/JavaScriptPacker.php';
	###$jsp=new JavaScriptPacker($js_txt, 'Normal', true, false);
	//$jsp=new JavaScriptPacker($js_txt, 62, true, false);
	if($js_txt){
		$jsp=new JavaScriptPacker($js_txt, $enc_level, true, false);//NOTES:第二个参数用0表示不做混yao，不混性能好点...
		return $jsp->pack();
	}
}
function jsa_enc_txt($js_a,$enc_level=0){
	$js_txt="";
	foreach($js_a as $k=>$v){
		//$js_txt.=trim(file_get_contents($v)).";\n";//回车和分号用来隔开几段js以免得太长...
		$js_org=trim(file_get_contents($v));
		if(!$js_org) throw new Exception("empty $v");
		$js_txt.=js_enc_txt($js_org, $enc_level).";\n";
	}
	return $js_txt;
}

//其实也可以直接用上面的 js_enc_txt的，不过为了性能，这个也很快...
function css_enc_txt($css_txt){
	/* remove comments */
	$css_txt = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css_txt);
	/* remove tabs, spaces, newlines, etc. */
	$css_txt = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $css_txt);
	return $css_txt;
}

function js_enc_txt_quick($js_txt){
	/* remove comments */
	$js_txt = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js_txt);
	//$js_txt = preg_replace('!\s*//.*[\r\n]*!', "", $js_txt);
	$js_txt = preg_replace('!\s*//.*[\r\n]+!', "\n", $js_txt);//注释行去除
	$js_txt = preg_replace('!\s*[\r\n]+\s*!', "\n", $js_txt);//空白头尾去除
	/* remove tabs, spaces, newlines, etc. */
	//$js_txt = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $js_txt);
	return $js_txt;
}

function jsa_enc_txt_quick($js_a){
	$js_txt="";
	foreach($js_a as $k=>$v){
		//$js_txt.=trim(file_get_contents($v)).";\n";//回车和分号用来隔开几段js以免得太长...
		$js_org=trim(file_get_contents($v));
		if(!$js_org) throw new Exception("empty $v");
		$js_txt.=js_enc_txt_quick($js_org)."\n";
	}
	return $js_txt;
}

function cssa_enc_txt($css_a){
	$css_txt="";
	foreach($css_a as $k=>$v){
		$css_org=trim(file_get_contents($v));
		if(!$css_org) throw new Exception("empty $v");
		$css_txt.=css_enc_txt($css_org).";\n";
	}
	return $css_txt;
}

//@refer
//http://code.google.com/p/jsmin-php/ 对比研究一下...
//https://github.com/rgrove/jsmin-php/ (移了)...
//https://github.com/tedious/JShrink/tree/master/src/JShrink 又移了..
