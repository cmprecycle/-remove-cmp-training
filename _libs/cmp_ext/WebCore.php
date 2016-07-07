<?php
/**
 * If the Class is not good for you usage, please clone and overwrite, but not alter unless u r invited to.
 * 如果这个类不合适你的使用，请自己复制修改写一份，未经授权不要修改这个文件
 */
class WebCore
{
	//public function GetTpl($t,$suffix="htm"){
	//	if(!$t)$t=$_REQUEST['_t'];
	//	$page_tpl_file_name="$t.$suffix";
	//	require_once _LIB_CORE_ ."/inc.microtemplate.php";
	//	include(TPL($page_tpl_file_name));
	//}
	
	//模板可以用到前置的变量
	//代码使用： include($this->TPL($t));
	protected function TPL($t,$prefix="tpl",$suffix="htm"){
		$page_tpl_file_name="$prefix.$t";
		if($suffix){
			$page_tpl_file_name.=".$suffix";
		}
		require_once _LIB_CORE_ ."/inc.microtemplate.php";
		return(TPL($page_tpl_file_name));
	}
	//对变量封闭，特殊情况下有用....
	protected function TPL2($t,$prefix="tpl",$suffix="htm"){
		$page_tpl_file_name="$prefix.$t";
		if($suffix){
			$page_tpl_file_name.=".$suffix";
		}
		require_once _LIB_CORE_ ."/inc.microtemplate.php";
		include(TPL($page_tpl_file_name));
	}

	//已经过时、旧式保守的用法:
	//public function TPL3($t){
	//	if(!$t)$t=$_REQUEST['_t'];
	//	$page_tpl_file_name="$t.htm";
	//	require_once _LIB_CORE_ ."/inc.microtemplate.php";
	//	include(TPL($page_tpl_file_name));
	//}

	//WARNING... Deprecated!!!
	//TODO keyex1/keyex2/keyex3 是旧的密码交换用法，即将移到 Service中.
	public function keyex1($param){
		$rt_o=array();
		$keylen=$param['keylen'];
		if(!$keylen) throw new Exception("keylen needed");
		$lang=$param['lang'];

		session_start();
		$session=& $_SESSION;

		if($lang){
			$session['lang']=$lang;//这个有机会会被用户重新重置
		}

		require_once(_LIB_.'/_xxtea/bigint.php');
		$keylen=256;//TODO
		require_once(_LIB_.'/_xxtea/dhparams.php');
		$DHParams = new DHParams($keylen);
		$keylen = $DHParams->getL();
		$encrypt = $DHParams->getDHParams();
		if(!$encrypt) throw new Exception("Unexpected Error XXTEA.1");//for HHVM
		$x = bigint_random($keylen - 1, true);

		$session['x'] = bigint_num2dec($x);
		$rt_o['p']=$session['p'] = $encrypt['p'];
		$rt_o['keylen']=$keylen;
		$num_g=bigint_dec2num("".$encrypt['g']);
		$num_p=bigint_dec2num("".$encrypt['p']);
		$powmod=bigint_powmod($num_g, $x, $num_p);
		$rt_o['y']= $encrypt['y'] = bigint_num2dec($powmod);
		$rt_o['g']=$encrypt['g'];

		session_write_close();
		return $rt_o;
	}
	public function keyex2($param){
		$rt_o=array();
		$enc=$param['enc'];
		$keylen=$param['keylen'];
		if(!$keylen) throw new Exception("keylen needed");

		session_start();
		$session=& $_SESSION;

		require_once(_LIB_.'/_xxtea/bigint.php');
		require_once(_LIB_.'/_xxtea/dhparams.php');

		$y = bigint_dec2num($enc);
		$sess_x=$session['x'];
		$x = bigint_dec2num($sess_x);
		$sess_p=$session['p'];
		$p = bigint_dec2num($sess_p);
		$key = bigint_powmod($y, $x, $p);

		$dbg="$enc,$y,$x($sess_x),$p($sess_p),$key";
		if ($keylen == 128) {
			$key = bigint_num2str($key);
		}
		else {
			$key = pack('H*', md5(bigint_num2dec($key)));
		}
		$session['auth_key']=$auth_key= str_pad($key, 16, "\0", STR_PAD_LEFT);
		//NOTES: 注意这个之后也可能会用到（比如说修改密码）！用法见下面的 xxtea_encrypt 和 _m=login_enc

		session_write_close();

		require_once(_LIB_.'/_xxtea/xxtea.php');
		$rt_o['enc']=base64_encode(xxtea_encrypt(json_encode("okok"),$auth_key));
		$rt_o['keylen']=$keylen;
		return $rt_o;
	}

	//把提交的callback也返回去，客户端就知道是否 keyex完成了
	public function keyex3($param){
		$rt_o=array();
		$rt_o['STS']="KO";
		session_start();
		$session=& $_SESSION;
		$auth_key=$session['auth_key'];
		session_write_close();

		require_once(_LIB_.'/_xxtea/xxtea.php');
		$enc=$param['enc'];

		$d0=$enc;
		$d1=xxtea_decrypt(base64_decode($d0),$auth_key);
		$d2=json_decode($d1,true);
		if($d2){
			$rt_o['callback']=$d2['callback'];
		}else{
			$rt_o['errmsg']="Input Error";
		}
		return $rt_o;
	}

	//WARNING... Deprecated!!!
	public static function CheckAndStartSession(){
		$sid=session_id();
		$_s_cookie=$_COOKIE['_s'];
		if($_SESSION && $sid!=""){
			//OK if have a session
		}else{
			if($_s_cookie!="") session_id($_s_cookie);
			session_start();
			$sid=session_id();
		}
		if($sid==""){
			session_id(_getbarcode(13));
			session_start();
			$sid=session_id();
			if($sid=="") throw new Exception("fail start session");
		}
		if($sid!=$_s_cookie && $_s_cookie!=""){
			quicklog_must('KO-CHECK-SESSION-2',$_SESSION);
			session_start($_s_cookie);
			quicklog_must('KO-CHECK-SESSION-2',$_SESSION);
			throw new Exception('KO-SESS');
		}
		session_write_close();//注意：之后要写的话就再呼叫session_start()或者使用 updateSession()
		return $sid;
	}

	public static function updateSession($to_update_a){
		session_start();
		foreach($to_update_a as $k=>$v){
			$_SESSION[$k]=$v;
		}
		session_write_close();
		return true;
	}
	private function _start_session_and_write_lang($lang){
		session_start();
		if(""==session_id()){
			//faint bug since php5.6
			session_id(_getbarcode(13));
			session_start();
			if(""==session_id()){
				throw new Exception("session_id empty after session_start, please check system env");
			}
		}
		$_SESSION['lang']=$lang;
		session_write_close();
	}
	//get the lang from request or session, and start the session BTW.
	protected function checkLang( $defaultLang=array('en','zh-cn','zh-tw','vn','kh') ){
		$lang=$_REQUEST['lang'];
		if($lang){
			$this->_start_session_and_write_lang($lang);
			return $lang;
		}
		$lang=$_SESSION['lang'];//试看看session有没有.
		if(!$lang){
			$lang=calcLangFromBrowser();//@ref quick func defined in 'inc.v5.lang.php'
			if ( in_array($lang, $defaultLang) ) {
				//OK
			}else{
				$lang='en';
			}
			$this->_start_session_and_write_lang($lang);
		}
		return $lang;
	}

	//convenient tool
	//Usage:  list($lang,$_s)=$this->checkLangAndSessionId();
	protected function checkLangAndSessionId(){
		$lang=$this->checkLang();
		$sid=session_id();
		return array($lang,$sid);
	}

	public function logout($param){
		$url=$_REQUEST['url'];
		if(!$url){
			$url=$param['url'];
		}
		$_s_cookie=$_COOKIE['_s'];

		//clean default
		session_start();
		$_old_sid=session_id();
		$_SESSION['auth_id']="";
		unset($_SESSION['auth_id']);
		session_destroy();

		//clean this one in cookie also
		if($_s_cookie && $_s_cookie!=$_old_sid){
			session_id($_s_cookie);
			session_start();
			$_SESSION['auth_id']="";
			unset($_SESSION['auth_id']);
			session_destroy();
		}
		if(!$url){
			$url="./?trace=logout,$_old_sid,$_s_cookie";
		}
		header("Location:$url");
	}

}
