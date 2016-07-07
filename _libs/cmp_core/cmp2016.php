<?php
/**
 * 如果这个类不合适你的使用，请自己复制修改写一份，不要直接修改这个文件..
 */
class cmp2016
{
	static $_preSetData=array();
	static $_last_json_obj=array();//FOR DEBUG

	public static function PreSetData($_data){
		self::$_preSetData=$_data;
	}
	public static function is_array_and_not_empty($a){
		if ( is_array($a) ) {
			if ( count($a)>0 ) return true;
		}
		return false;
	}

	//NOTES: 
	//Q: Why need handleWeb() since already a Run() ?
	//A: Run() now focus on Run, but handleWeb has more feature for caller
	public static function Run($_json_string){
		global $argv;
		$json_string="";

		$php_input = file_get_contents('php://input');
		if($php_input){
			if(!$GLOBALS['HTTP_RAW_POST_DATA'])
				$GLOBALS['HTTP_RAW_POST_DATA']=$php_input;//store for later usage if needed
		}else{
			if($GLOBALS['HTTP_RAW_POST_DATA'])
				$php_input=$GLOBALS['HTTP_RAW_POST_DATA'];
		}

		if($_json_string===true){
			$flag_console_mode=true;//console mode as Run(true)
		}

		if (is_array($_json_string) || is_object($_json_string)) {
			$flag_console_mode=true;//console mode as Run(Obj) or Run(Array);
			$json_obj=$_json_string;
		}elseif ( is_string($_json_string) && $_json_string!="") {
			$flag_console_mode=true;//console mode as Run("string which is not empy");
			$json_string= $_json_string;
		}elseif ($_REQUEST['json']) {
			$json_string = $_REQUEST['json'];
			if (get_magic_quotes_gpc()) {//小补丁.
				$json_string=stripslashes($json_string);
			}
		}elseif ($argv[1]) {
			$flag_console_mode=true;//really console mode O.O
			if ("-"==$argv[1]) {
				//get from stdin
				$json_string=file_get_contents("php://stdin");
			} else {
				$argv_clone=$argv;
				unset($argv_clone[0]);
				$json_string=join(" ",$argv_clone);
			}
		} else {
			$json_string = $php_input;
		}

		if ( $json_obj || $json_string || self::is_array_and_not_empty(self::$_preSetData) ) {
		}else{
			//应该不会出现到这里，所以一旦出现就要严格记录慢慢研究...
			quicklog_must("IT-CHECK","EmptyRequestError9999");
			quicklog_must("IT-CHECK","_SERVER-------".var_export($_SERVER,true));

			quicklog_must("IT-CHECK","REQUEST_URI---------".$_SERVER['REQUEST_URI']);
			//quicklog_must("IT-CHECK","php_input---------".$php_input);
			quicklog_must("IT-CHECK","HTTP_RAW_POST_DATA---------".$GLOBALS['HTTP_RAW_POST_DATA']);
			quicklog_must("IT-CHECK","_REQUEST---------".var_export($_REQUEST,true));

			if($_POST)
				quicklog_must("IT-CHECK","_POST---------".var_export($_POST,true));

			quicklog_must("IT-CHECK","json_string---------".$json_string);
			quicklog_must("IT-CHECK","predata------------".var_export(self::$_preSetData,true));
			quicklog_must("IT-CHECK","callparam----------".var_export($_json_string,true));
			//以前的php不知道在哪里有一次提交但是有两个静态缓存的情况，奇异的问题，后来好像再也没见过了:
			quicklog_must("IT-CHECK","last json obj----------".var_export(self::$_last_json_obj,true));
			throw new Exception("Empty Request (9999) Please Call IT To Check Log.",9999);
		}

		//---------------------------------------------------------------------------------------
		//在Controller中用GET和POST的，甚至用提交来的参数，
		//不要用_COOKIE中的，因为那个有可能是错误的，需要在代码中进行再处理..
		//NOTES: _REQUEST= _GET + _POST + _COOKIE
		//$_s=$_REQUEST['_s'];//don't, bad idea
		$_s=$_GET['_s'];
		if(!$_s) $_s=$_POST['_s'];

		if( (!$json_obj) && $json_string ){
			$json_obj=my_json_decode($json_string);
		}

		$json_obj=array_merge((array)self::$_preSetData,(array)$json_obj);
		//$_preSetData=self::$_preSetData;

		self::$_last_json_obj=$json_obj;//FOR NEXT DEBUG IF CALL TWICE...

		if(! $_s) $_s=$json_obj['_s'];

		$_s_cookie=$_COOKIE['_s'];
		if($_s){
			if($_s_cookie){
				if($_s_cookie!=$_s){
					//cookie中的_s和要求的_s不同? 为什么会窜号? 要专门写个LOG来跟踪!!
					quicklog_must('Hacker_Check',"$_s,$_s_cookie");
					quicklog_must('Hacker_Check',$_SESSION);
					session_start($_s_cookie);
					quicklog_must('Hacker_Check',$_SESSION);
					throw new Exception('KO-SESS');
				}else{
					//skip
				}
			}
		}else{
			if($_s_cookie){
				//请求的没有，但是cookie有，应该用:
				$_s=$_s_cookie;
			}else{
				//都没有，skip...
			}
		}

		$request_class=$json_obj['class'];
		if(!$request_class){
			$request_class=$json_obj['_c'];
		}
		if(!$request_class){
			$request_class=$json_obj['defaultClass'];
			//$request_class=$_preSetData['defaultClass'];
		}
		$request_method=$json_obj['method'];
		if(!$request_method){
			$request_method=$json_obj['_m'];
		}
		if(!$request_method){
			$request_method=$json_obj['defaultMethod'];
			//$request_method=$_preSetData['defaultMethod'];
		}
		$request_method_param=$json_obj['param'];
		if(!$request_method_param){
			$request_method_param=$json_obj['_p'];
		}

		if(!$request_method_param){
			//没有显式的 p时:
			if($json_obj){
				$request_method_param=array_merge(array(),$_REQUEST, $json_obj);
				//NOTES 会有 _c/_m/class/method/saeut等，总之用不到就别理，要用到就精选，不要期待 _p一定就是完全提交上来的
			}else{
				$request_method_param=array_merge(array(),$_REQUEST);
			}
		}else{
			//当有显式要求的 _p 时
			//TODO
			//暂时兼容不起来,有些旧代码变态而吃不下（要找出来一个个清掉。。。不容易）
			//（因为有些旧代码假设param就是全部要的东西，结果碰到 其它 lang/_s/_c/_m 时出错:
			//$request_method_param=array_merge(array(), $_REQUEST, $request_method_param);
		}

		if(! $flag_console_mode){
			@ob_start();
		}

		//------------------------------------------------- session id handling
		if($_s){
			session_id($_s);
			session_start();
		}else{
			//NOTES: 如果没有，要代码自行处理，参见WebCore::CheckAndStartSession
			//原因？为了让代码更宽松一点，不一定要生成session，把生成session的权利还给应用代码（毕竟盲目建立session会有一定的损耗）
		}

		if(! $request_class) throw new Exception("request_class not found");
		if(! $request_method) throw new Exception("request_method not found");

		session_write_close();//NOTES!! close first, if u need to write, need to manually do ur session_start...

		AppAuth::checkApiAccess($request_class,$request_method,$request_method_param);

		$obj=new $request_class;
		/** NOTES:
		 * 历史原因，有几种方式呼叫，包括
		 * URL重写会带来 REQUEST中的 [_c]和_m，搭配POST上来是_p不含_c,_m
		 * POST提交上来要兼容{_c,_m,_p}
		 * 总之 $request_method_param 的目标是 _p，而 json_obj 目前基本没有地方用到，所以是用来DEBUG的
		 * 为了向REQUEST做兼容，所以要把REQUEST的合并，但是以 _p的值有限.
		 */
		$rt=$obj->$request_method($request_method_param, $json_obj);
		$response_obj=($rt);

		return $response_obj;
	}

	//PAGE=(STYLE==HTML)?(HEAD+BODY):((STYLE==json)?:json_encode(rt_obj):"");
	//NOTS: Quick Loading Page Logic:
	//BODY=FIRST_COMMON_JS,FIRST_COMMON_CSS,[FIRST_PAGE_JS],[FIRST_PAGE_CSS],MAIN_TPL,[encrypted COMMON_JS],encrypted PAGE_JS,
	//////// 特别注意：shtm是用于超高度缓存的，所以不要涉及到写cookie和session!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//如果要用到 cookie/lang/session，要在page data等使用单独的处理办法，切记!!!
	protected static function _shtml(){
		$_p=$_REQUEST['_p'];
		if($_p){
			$p_a=explode(",",$_p);
			$_p0=$p_a[0];
			$_p1=$p_a[1];

			$page_tpl_file_name="shtml.$_p0.htm";
			if(file_exists($page_tpl_file_name)){
				if($_p1){
					//约定: p1=lang
					$_COOKIE['lang']=$lang=$_p1;
				}
			}else{
				$page_tpl_file_name="shtml.$_p0.$_p1.htm";
			}

			if(file_exists($page_tpl_file_name)){
				$page_mtime=filemtime($page_tpl_file_name);
				require_once _LIB_CORE_ ."/inc.handle304.php";
				handle304($page_mtime);

				require_once _LIB_CORE_ ."/func.js_enc_txt.php";
				require_once _LIB_CORE_ ."/inc.microtemplate.php";
				try{
					$shtml_module=$_p0;
					include(TPL($page_tpl_file_name));
				}catch(Exception $ex){
					ob_get_clean();
					$response_obj=(global_error_handler(basename($ex->getFile(),".php"),
						$ex->getLine(),$ex->getMessage(),$ex->getTraceAsString(),$ex->getCode()));
					quicklog_must("TPL_ERR",$response_obj);
					unset($response_obj['trace_s']);
					unset($response_obj['trace']);
					return $response_obj;
				}
			}else{
				print json_encode(array(
					"errmsg"=>"404 $page_tpl_file_name",
					"GET"=>$_GET,
				));
			}
		}else{
			print json_encode(array(
				"errmsg"=>"404 EMPTY M",
				"GET"=>$_GET,
			));
		}
	}

	//public static function get_env($k){
	//	$rt=getenv($k);
	//	if($rt && $rt!="") return $rt;
	//	$rt=$_SERVER[$k];
	//	if($rt && $rt!="") return $rt;
	//	return null;
	//}

	// Usage: 
	// $p0,$p1,.shtml
	// => ?_p=$p0,$p1
	// => shtml.$m.htm
	public static function handleShtml($_p){
		self::_shtml();
	}

	//return JSON/PLAIN depends the running result === null
	public static function handleWeb($_p){
		global $APP_NAME;
		$_php_start_time=microtime(true);
		//---------------------------------------------------------------------------------------

		$rt=array();
		try{

			$defaultClass=$_p['defaultClass'];
			$defaultMethod=$_p['defaultMethod'];
			if($_p['APP_NAME']){
				//write to global for later use.
				$APP_NAME=$_p['APP_NAME'];
			}

			//cls/class/default adjust..
			if ($_REQUEST['_c']) {
				$_PreSetData['class']= $_REQUEST['_c'];
			} elseif ($_REQUEST['cls']) {
				$_PreSetData['class']= $_REQUEST['cls'];
			} else {
				if ($_REQUEST['class']) {
					$_PreSetData['class']=$_REQUEST['class'];
				} else {
					$_PreSetData['defaultClass']=$defaultClass;
				}
			}

			if($_REQUEST['_m']){
				$_PreSetData['method']= $_REQUEST['_m'];
			}elseif($_REQUEST['method']){
				$_PreSetData['method']=$_REQUEST['method'];
			}else{
				$_PreSetData['defaultMethod']=$defaultMethod;
			}

			self::PreSetData($_PreSetData);

			@ob_start();
			$rt=self::Run(true);
		}catch(Exception $ex){
			$rt_err=global_error_handler($ex->getFile(),$ex->getLine(),$ex->getMessage(),""/*$ex->getTraceAsString()*/,$ex->getCode()
			);
			$rt=array_merge($rt,$rt_err);
			$trace_s=$ex->getTraceAsString();
			$trace_s=substr($trace_s,0,4096);
			if($trace_s)
				$rt['trace_s']=$rt['trace_s'].$trace_s;
		}

		if(is_array($rt) && isset($rt['errmsg'])){
			$errmsg=$rt['errmsg'];

			if(my_json_encode($errmsg)=="null")//Ugly bug for some PDO Exception
			{
				//TODO this is just temp solution... need to update better later...
				//$errmsg=iconv("ISO-8859-1","UTF-8",$rt['errmsg']);
				$errmsg=iconv("GBK","UTF-8",$errmsg);
				//if(my_json_encode($errmsg)=="null"){
				//}else{
				$rt['errmsg']=$errmsg;
				//}
			}

			$logid=_getbarcode(6,"ABCDEFGHJKLMNPQRSTUVWXYZ12356789");//for easier to trace ...
			if(isset($rt['trace'])){
				unset($rt['trace']);//这个trace没必要写LOG了，有trace_s了.
			}
			if($rt['errno']==0){
				unset($rt['errno']);
			}
			$logfile=quicklog_must($APP_NAME,$logid."\n".var_export($rt,true));
			require_once(_LIB_CORE_ .'/inc.v5.secure.php');//要拿IP地址..
			quicklog_must($APP_NAME, $logid
				//."\n$trace_s"
				//."\nHTTP_X_FORWARDED_FOR=".self::get_env('HTTP_X_FORWARDED_FOR')
				//."\nHTTP_CLIENT_IP=".self::get_env('HTTP_CLIENT_IP')
				//."\nHTTP_X_REAL_IP=".self::get_env('HTTP_X_REAL_IP')
				//."\nHTTP_USER_AGENT=".($_SERVER['HTTP_USER_AGENT'])
				."\n_get_ip_="._get_ip_()
				."\n_SERVER=".var_export($_SERVER,true)
				."\n_SESSION=".var_export($_SESSION,true)
				."\nHTTP_RAW_POST_DATA=".($GLOBALS['HTTP_RAW_POST_DATA'])
				."\n_REQUEST=".var_export($_REQUEST,true)
			);

			$rt['file']=basename($rt['file'],".php");
			$rt['log_id']=$logid;
			$rt['log_file']=basename($logfile);
			//$rt['nav_helper']="<a href='javascript:top.location.reload();'>Refresh";//有时方便客户刷新。兼容旧的代码而已，已经没有太大作用了.
		}

		//如果结果是 === null，就是没有返回object，就是用PLAIN模式返回，否则就是用JSON_S返回:
		if(!($rt===null))
		{
			//JSON 式返回
			if(is_array($rt)){
				unset($rt['trace']);//不给客户看到...
				unset($rt['trace_s']);//不给客户看到...
			}
			if($_GET['jsonp']){
				//jsonp special
				$callback=$_GET['jsonp'];
				$response_txt=my_json_encode($rt);
				$response_txt="$callback($response_txt);";
			}else{
				$response_txt=my_json_encode($rt, ($rt['errmsg'])?true:false);//NOTES: 如果是有错误信息，可以格式化得好看点的.
			}
			//$_g=$_REQUEST['_g'];//if 1 force try gzip...
			//if($_g==1){
			//	ob_get_clean();//clean first
			//	_gzip_output($response_txt);//try gzip output
			//	//ob_end_flush();
			//}else{
			if($rt['errmsg']){
				ob_get_clean();//clean first if have errmsg in result to kill the wrong output not json.
			}
			print $response_txt;
			//}
		}else{
			//如果结果是 === null，就是没有返回object，就是用PLAIN模式返回:
			$output = ob_get_clean();
			_gzip_output($output);
			//ob_end_flush();
		}

		//---------------------------------------------------------------------------------------
		$_php_end_time=microtime(true);
		$_php_exec_time=$_php_end_time-$_php_start_time;
		$_max_exec_time_then_log=($flag_console_mode)?60:7;//超时的要做日志，提醒要分析.
		if($_php_exec_time>$_max_exec_time_then_log){
			quicklog_must($APP_NAME, "$json_string,\$_php_exec_time=".$_php_exec_time);
		}

		return $rt;
	}//handleWeb()

	//Since 2016, we now support BPM-WEB Interface
	//@ref http://cmptech.info/
	//Input {_c, _m, _p, _s, lang}  _p should includes _c,_m (historical reason).
	public static function RunBpm($_json_string){

		global $argv;
		$json_string="";

		$php_input = file_get_contents('php://input');
		if($php_input){
			if(!$GLOBALS['HTTP_RAW_POST_DATA'])
				$GLOBALS['HTTP_RAW_POST_DATA']=$php_input;//store for later usage if needed
		}else{
			if($GLOBALS['HTTP_RAW_POST_DATA'])
				$php_input=$GLOBALS['HTTP_RAW_POST_DATA'];
		}

		if($_json_string===true){
			$flag_console_mode=true;//console mode as Run(true)
		}

		if (is_array($_json_string) || is_object($_json_string)) {
			$flag_console_mode=true;//console mode as Run(Obj) or Run(Array);
			$json_obj=$_json_string;
		}elseif ( is_string($_json_string) && $_json_string!="") {
			$flag_console_mode=true;//console mode as Run("string which is not empy");
			$json_string= $_json_string;
		}elseif ($_REQUEST['json']) {
			$json_string = $_REQUEST['json'];
			if (get_magic_quotes_gpc()) {
				$json_string=stripslashes($json_string);
			}
		}elseif ($argv[1]) {
			$flag_console_mode=true;//really console mode O.O
			if ("-"==$argv[1]) {
				//get from stdin
				$json_string=file_get_contents("php://stdin");
			} else {
				$argv_clone=$argv;
				unset($argv_clone[0]);
				$json_string=join(" ",$argv_clone);
			}
		} else {
			$json_string = $php_input;
		}

		if ( $json_obj || $json_string || self::is_array_and_not_empty(self::$_preSetData) ) {
		}else{
			quicklog_must("IT-CHECK","EmptyRequestError9999");
			quicklog_must("IT-CHECK","_SERVER-------".var_export($_SERVER,true));

			quicklog_must("IT-CHECK","REQUEST_URI---------".$_SERVER['REQUEST_URI']);
			//quicklog_must("IT-CHECK","php_input---------".$php_input);
			quicklog_must("IT-CHECK","HTTP_RAW_POST_DATA---------".$GLOBALS['HTTP_RAW_POST_DATA']);
			quicklog_must("IT-CHECK","_REQUEST---------".var_export($_REQUEST,true));

			if($_POST)
				quicklog_must("IT-CHECK","_POST---------".var_export($_POST,true));

			quicklog_must("IT-CHECK","json_string---------".$json_string);
			quicklog_must("IT-CHECK","predata------------".var_export(self::$_preSetData,true));
			quicklog_must("IT-CHECK","callparam----------".var_export($_json_string,true));
			quicklog_must("IT-CHECK","last json obj----------".var_export(self::$_last_json_obj,true));
			throw new Exception("Empty Request (9999) Please Call IT To Check Log.",9999);
		}

		//---------------------------------------------------------------------------------------
		//在Controller中用GET和POST的，甚至用提交来的参数，
		//不要用_COOKIE中的，因为那个有可能是错误的，需要在代码中进行再处理..
		//NOTES: _REQUEST= _GET + _POST + _COOKIE
		//$_s=$_REQUEST['_s'];//don't, bad idea
		$_s=$_GET['_s'];
		if(!$_s) $_s=$_POST['_s'];

		if( (!$json_obj) && $json_string ){
			$json_obj=my_json_decode($json_string);
		}

		$_preSetData = self::$_preSetData;

		$json_obj=array_merge((array) $_preSetData,(array)$json_obj);

		self::$_last_json_obj=$json_obj;//FOR NEXT DEBUG IF CALL TWICE...

		if(! $_s) $_s=$json_obj['_s'];

		$_s_cookie=$_COOKIE['_s'];
		if($_s){
			if($_s_cookie){
				if($_s_cookie!=$_s){
					quicklog_must('Hacker_Check',"$_s,$_s_cookie");
					quicklog_must('Hacker_Check',$_SESSION);
					session_start($_s_cookie);
					quicklog_must('Hacker_Check',$_SESSION);
					throw new Exception('KO-SESS');
				}
			}
		}else{
			if($_s_cookie){
				//if no _s from request but cookie, use cookie one
				$_s=$_s_cookie;
			}
		}

		//////////////////////////  prepare the P
		//try 'param' first
		$request_method_param=$json_obj['param'];
		if(!$request_method_param){
			//fall back to '_p'
			$request_method_param=$json_obj['_p'];
		}

		if(!$request_method_param){
			if($json_obj){
				//try use the whole json_obj if any
				$request_method_param=array_merge(array(),$_REQUEST, $json_obj);
				//WARNING && NOTES: don't expect _p === request !!!
			}else{
				//fall back to use the _REQUEST directly
				$request_method_param=array_merge(array(),$_REQUEST);
			}
		}else{
			//因为是新代码系，所以没有了旧代码的变态，所以 REQUEST 与 P 可以合并了.
			//use the _REQUEST override by the request_method_param
			$request_method_param=array_merge(array(), $_REQUEST, $request_method_param);
		}

		if(! $flag_console_mode){
			@ob_start();
		}

		//------------------------------------------------- session id handling
		if($_s){
			session_id($_s);
			session_start();
		}else{
			//if no _s, please handle by own login, refer to WebCore::CheckAndStartSession
			//coz not every flow need session, which may cause some resource anyway
		}

		session_write_close();//NOTES!! close first, if u need to write, need to manually do ur session_start...

		$bpme = new LgcBPME;
		$response_obj = $bpme->handle($request_method_param);
		
		return $response_obj;
	}//RunBpm
	
	public static function handleBpmWeb($_p){
		global $APP_NAME;
		$_php_start_time=microtime(true);
		//---------------------------------------------------------------------------------------

		$rt=array();
		try{
			//$defaultClass=$_p['defaultClass'];
			//$defaultMethod=$_p['defaultMethod'];
			if($_p['APP_NAME']){
				//write to global for later use.
				$APP_NAME=$_p['APP_NAME'];
			}

			if ($_REQUEST['_c']) {
				$_PreSetData['_c']= $_REQUEST['_c'];
			}

			$_p['bpmn_entry_channel']='WEB';//IMPORTANT to tell engine run in WEB channel...

			self::PreSetData($_PreSetData);

			@ob_start();
			$rt=self::RunBpm(true);
		}catch(Exception $ex){
			$rt_err=global_error_handler($ex->getFile(),$ex->getLine(),$ex->getMessage(),""/*$ex->getTraceAsString()*/,$ex->getCode()
			);
			$rt=array_merge($rt,$rt_err);
			$trace_s=$ex->getTraceAsString();
			$trace_s=substr($trace_s,0,4096);
			if($trace_s)
				$rt['trace_s']=$rt['trace_s'].$trace_s;
		}

		if(is_array($rt) && isset($rt['errmsg'])){
			$errmsg=$rt['errmsg'];

			if(my_json_encode($errmsg)=="null")//Ugly bug for some PDO Exception
			{
				//$errmsg=iconv("ISO-8859-1","UTF-8",$rt['errmsg']);
				$errmsg=iconv("GBK","UTF-8",$errmsg);
				//if(my_json_encode($errmsg)=="null"){
				//}else{
				$rt['errmsg']=$errmsg;
				//}
			}

			$logid=_getbarcode(6,"ABCDEFGHJKLMNPQRSTUVWXYZ12356789");//for easier to trace ...
			if(isset($rt['trace'])){
				unset($rt['trace']);//这个trace没必要写LOG了，有trace_s了.
			}
			if($rt['errno']==0){
				unset($rt['errno']);
			}
			$logfile=quicklog_must($APP_NAME,$logid."\n".var_export($rt,true));
			require_once(_LIB_CORE_ .'/inc.v5.secure.php');//要拿IP地址..
			quicklog_must($APP_NAME, $logid
				//."\n$trace_s"
				//."\nHTTP_X_FORWARDED_FOR=".self::get_env('HTTP_X_FORWARDED_FOR')
				//."\nHTTP_CLIENT_IP=".self::get_env('HTTP_CLIENT_IP')
				//."\nHTTP_X_REAL_IP=".self::get_env('HTTP_X_REAL_IP')
				//."\nHTTP_USER_AGENT=".($_SERVER['HTTP_USER_AGENT'])
				."\n_get_ip_="._get_ip_()
				."\n_SERVER=".var_export($_SERVER,true)
				."\n_SESSION=".var_export($_SESSION,true)
				."\nHTTP_RAW_POST_DATA=".($GLOBALS['HTTP_RAW_POST_DATA'])
				."\n_REQUEST=".var_export($_REQUEST,true)
			);

			$rt['file']=basename($rt['file'],".php");
			$rt['log_id']=$logid;
			$rt['log_file']=basename($logfile);
			$rt['nav_helper']="<a href='javascript:top.location.reload();'>Refresh";//有时方便客户刷新。兼容旧的代码而已，已经没有太大作用了.
		}

		//如果结果是 === null，就是没有返回object，就是用PLAIN模式返回，否则就是用JSON_S返回:
		if(!($rt===null))
		{
			//JSON 式返回
			if(is_array($rt)){
				unset($rt['trace']);//不给客户看到...
				unset($rt['trace_s']);//不给客户看到...
			}
			if($_GET['jsonp']){
				//jsonp special
				$callback=$_GET['jsonp'];
				$response_txt=my_json_encode($rt);
				$response_txt="$callback($response_txt);";
			}else{
				$response_txt=my_json_encode($rt, ($rt['errmsg'])?true:false);//NOTES: 如果是有错误信息，可以格式化得好看点的.
			}
			//$_g=$_REQUEST['_g'];//if 1 force try gzip...
			//if($_g==1){
			//	ob_get_clean();//clean first
			//	_gzip_output($response_txt);//try gzip output
			//	//ob_end_flush();
			//}else{
			if($rt['errmsg']){
				ob_get_clean();//clean first if have errmsg in result to kill the wrong output not json.
			}
			print $response_txt;
			//}
		}else{
			//如果结果是 === null，就是没有返回object，就是用PLAIN模式返回:
			$output = ob_get_clean();
			_gzip_output($output);
			//ob_end_flush();
		}

		//---------------------------------------------------------------------------------------
		$_php_end_time=microtime(true);
		$_php_exec_time=$_php_end_time-$_php_start_time;
		$_max_exec_time_then_log=($flag_console_mode)?60:7;//超时的要做日志，提醒要分析.
		if($_php_exec_time>$_max_exec_time_then_log){
			quicklog_must($APP_NAME, "$json_string,\$_php_exec_time=".$_php_exec_time);
		}

		return $rt;
	}//handleBpmWeb()
	
}

