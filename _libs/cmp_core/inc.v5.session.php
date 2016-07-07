<?php
function _change_session($sid){
	$_prev_sid=session_id();
	if($sid && $sid!=$_prev_sid){
		//万一session有变:
		session_write_close();

		session_id($sid);
		session_start();
		return $_prev_sid;
	}else{
		//如果一样就不用变了..
	}
}
//获得制定session id的，一般很少情况下用，多数是一些自动化cron job在用..
function getSession($sid){
	if($sid){
		//change session
		$_prev_sid=_change_session($sid);

		//steal
		$session_clone=$_SESSION;

		//restore prev
		session_id($_prev_sid);
		session_start();
		return $session_clone;
	}else{
		throw new Exception("getSession() needs param, if want to use SESSION, just access \$_SESSION for now");
	}
}
function getSessionVar($key, $sid="", & $probe_sid){
	if($sid){
		$_session= & getSession($sid);
	}else{
		$_session= & $_SESSION;
	}
	if($_session){
		//skip, already started.
	}else{
		$_s_cookie=$_COOKIE['_s'];
		if($_s_cookie){
			session_id($_s_cookie);
		}
		session_start();
		$sid=session_id();
		if($sid!=$_s_cookie){
			if($_s_cookie){
				//又串号了?!! (RpcController 或者 WebCore 不是已经处理过了么！)
				quicklog_must('KO-CHECK-SESSION-3',$_SESSION);
				session_start($_s_cookie);
				quicklog_must('KO-CHECK-SESSION-3',$_SESSION);
				throw new Exception('KO-SESS');
			}else{
				setcookie("_s",$sid);//通知一下浏览器的 _s变更
			}
		}
	}
	return $_session[$key];
}
//笔记：如果只需要写直接用 _SESSION[?]=? 更好，
//2、记得有写的话就用session_write_close 来提交更新.
function setSessionVar($key, $var, $sid=""){
	if($sid){
		$_prev_sid=_change_session($sid);
	}else{
		$current_sid=session_id();
		session_start();//open it for write
	}
	$_SESSION[$key]=$var;
	if($sid){
		_change_session($_prev_sid);
	}
	return true;
}

