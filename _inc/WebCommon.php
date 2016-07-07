<?php
class WebCommon
	extends WebCore
{
	//saas_da和 saas_app 都有机会用到，检查登录时间?
	//NOTES: 不合适用可以子类覆盖.
	protected function _check_sess(){
		if(!$_SESSION['auth_login_time']
		){
			//TODO 还要检查这个时间是否已经超时？
			//throw new Exception(getLang("SessionExpired"));//因为入口是htm-js，所以这里的处理不科学，要转成  js跳转
?>
location.href="./?rnd="+Math.random();
<?php
			die;
		}
	}
	
	//http://zh.wikipedia.org/wiki/HTTP%E7%8A%B6%E6%80%81%E7%A0%81
	protected function HttpError($code,$comment){
		switch($code){
		case 404:
			break;
		case 401:
			if($comment){
				header('WWW-Authenticate: Basic realm='.$comment.'"');
			}else{
				header('WWW-Authenticate: Basic realm="PlsReadCode"');
			}
			header('HTTP/1.0 401 Unauthorized');
			break;
		default:
			throw new Exception("Uncoded $code");
		}
	}
	
	//暂时只用在诸如 SystemInstall这样的入口...
	protected function _tmpCheckAuth(){
		$strAuthUser= $_SERVER['PHP_AUTH_USER'];
		$strAuthPass= $_SERVER['PHP_AUTH_PW'];
		if(
			!($strAuthUser
			&& $strAuthPass
			&& $strAuthUser == getConf("PHP_AUTH_USER")
			&& $strAuthPass == getConf("PHP_AUTH_PW")
		)){
			$this->HttpError(401,"Please_Read_Code");
			die;
		}
	}

	//NOTES: 不合适可以override...
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
