<?php

//Great Services provided by CmpTech

class CmpService
{
	/**
	 * Usage:
	 *   e.g.  include(CmpService::TPL('test'));//will load tpl.test.htm and render it
	 */
	public static function TPL($t,$prefix="tpl",$suffix="htm"){
		if($prefix){
			$page_tpl_file_name=$prefix.'.'.$t;
		}else{
			$page_tpl_file_name=$t;
		}
		if($suffix){
			$page_tpl_file_name.='.'.$suffix;
		}
		require_once _LIB_CORE_ ."/inc.microtemplate.php";
		return(TPL($page_tpl_file_name));
	}
}


