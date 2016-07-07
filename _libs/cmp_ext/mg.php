<?php
//Since 2015-1-4
//some useful static functions from mg
//will copy to CmpTool
class mg
{
	function println($s,$wellformat=false){
		if(is_array($s) || is_object($s)){
			$s=self::o2s($s,$wellformat);
		}
		print $s ."\n";//.PHP_EOL;
	}
	//judge array whether a associate array
	//https://gist.github.com/1965669
	public static function is_assoc($array){
		return (array_values($array) !== $array);
	}

	//short usage of json_encode
	public static function o2s($o,$wellformat=false){
		//return json_encode($o);
		if($wellformat){
			if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
				$s=json_encode($o,JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
			}else{
				//for <php5.4, using dirty code but seems work most case... for tmp...
				$s=json_encode($o);//will have {"a":"b"} instead of {a:"b"}, but encode speed might slightly inproved
				$s=preg_replace('/","/',"\",\n\"",$s);
			}
		}else{
			if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
				$s=json_encode($o,JSON_UNESCAPED_UNICODE);
			}else{
				$s=json_encode($o);//NOTES: official json_encode will have {"a":"b"} instead of {a:"b"}, but encode speed might slightly inproved
			}
		}
		return $s;
	}

	//short usage of json_decode
	public static function s2o($s){
		return json_decode($s,true);
	}

	//NOTES: dirty code to judge a os64
	//http://stackoverflow.com/questions/5423848/checking-if-your-code-is-running-on-64-bit-php
	//private static $_cached_isos64bit;
	//public static function is_os_64bit(){
	//	if(self::$_cached_isos64bit !== null){
	//		return self::$_cached_isos64bit;
	//	}
	//	self::$_cached_isos64bit = $isos64bit = (strstr(php_uname("m"), '64'))?true:false;
	//	return $isos64bit;
	//}

	//just in case.
	//public static function is_os_128bit(){
	//	if(self::$_cached_isos128bit !== null){
	//		return self::$_cached_isos128bit;
	//	}
	//	self::$_cached_isos128bit = $isos128bit = (strstr(php_uname("m"), '128'))?true:false;
	//	return $isos128bit;
	//}

	private static $_cached_isos64more;
	public static function is_os_64_more(){
		if(self::$_cached_isos64more !== null){
			return self::$_cached_isos64more;
		}
		$isos64bit = (strstr(php_uname("m"), '64'))?true:false;
		//$isos128bit = (strstr(php_uname("m"), '128'))?true:false;//future
		self::$_cached_isos64more = $isos64bit;
		//self::$_cached_isos64more = $isos64bit or $isos128bit;
		return $isos64bit;
	}
	
	//get the sequence of a single second (in single thread...)
	//Usage list($sec,$seq)=mg::getTimeSequence();echo "$sec.$seq";
	public static function getTimeSequence(){
		global $getTimeSequence;
		//list($sec,$microsec)=explode('.',microtime(true));
		//$sec = self::getTimeStamp();
		$sec = self::getYmdHis();
		if($getTimeSequence){
			if($getTimeSequence['sec']!=$sec){
				$getTimeSequence=array('sec'=>$sec, 'seq'=>1);
			}else{
				$getTimeSequence=array('sec'=>$sec, 'seq'=>$getTimeSequence['seq']+1);
			}
		}else{
			$getTimeSequence=array('sec'=>$sec, 'seq'=>1);
		}
		//return $getTimeSequence;
		return array($getTimeSequence['sec'], $getTimeSequence['seq']); 
	}

	public static function getBarCode( $defaultLen=23, $seed="0123456789ABCDEF" ){
		list($usec, $sec) = explode(" ", microtime());
		srand($sec + $usec * 100000);
		$len = strlen($seed) - 1;
		for ($i = 0; $i < $defaultLen; $i++) {
			$code .= substr($seed, rand(0, $len), 1);
		}
		return $code;
	}

	//if $s, translate to timestamp
	//if !$s, using now.
	public static function getTimeStamp( $s ){

		$strlen_s=strlen($s);

		if($strlen_s>10){
			//assume YYYY-MM-DD HH:ii:ss, @ref http://php.net/manual/en/datetime.createfromformat.php
			$o=date_create_from_format('Y-m-d H:i:s',$s,new DateTimeZone('UTC'));//DateTimeZone::UTC
		}elseif($strlen_s>9){
			//handle YYYY-MM-DD
			$o=date_create_from_format('Y-m-d H:i:s',$s.' 00:00:00',new DateTimeZone('UTC'));
		}elseif($strlen_s>0){
			throw new Exception(__CLASS__.".getTimeStamp() Unsupport $s");
		}else{
			if (self::is_os_64_more()){
				return time();
			}else{
				//32bit.
				$o=date_create("now",new DateTimeZone('UTC'));
			}
		}
		if(!$o) return null;
		return $o->format('U');
	}

	//take system time if no param. diff from rb one.
	public static function isoDate( $timestamp )
	{
		if(!$timestamp) throw new Exception(__CLASS__.".isoDate() need param timestamp"); //$time=$this->db_time();

		if($timestamp){
			$o=date_create_from_format('U',$timestamp);
			if(!$o){
				//try U.u
				$o=date_create_from_format('U.u',$timestamp);
			}
			if($o){
				return $o->format('Y-m-d');
			}else{
				throw new Exception(__CLASS__.".isoDate() Unknown timestamp=$timestamp");
			}
		}else{
			//return now of current system
			return date_create()->format('Y-m-d');
		}
	}
	//take system time if no param. diff from rb one.
	public static function isoDateTime( $timestamp )
	{
		if(!$timestamp) throw new Exception(__CLASS__.".isoDateTime() need param timestamp"); //$time=$this->db_time();
		if($timestamp){
			$o=date_create_from_format('U',$timestamp);
			if(!$o){
				//try U.u
				$o=date_create_from_format('U.u',$timestamp);
			}
			if($o){
				return $o->format('Y-m-d H:i:s');
			}else{
				throw new Exception(__CLASS__.".isoDateTime() Unknown timestamp=$timestamp");
			}
		}else{
			//return now of current system
			return date_create()->format('Y-m-d H:i:s');
		}
	}
	public static function getYmdHis( $timestamp, $timezone ){
		if (self::is_os_64_more()){
			if($timestamp){
				return date('YmdHis', $timestamp);
			}else{
				return date('YmdHis');
			}
		}else{
			//32bit
			if($s){
				$o=date_create("@$timestamp");
			}else{
				$o=date_create("now",new DateTimeZone('UTC'));
			}
			if(!$o) {throw new Exception(__CLASS__.".getYmdHis() failed for timestamp=$timestamp");};
			if($timezone!=''){
				date_timezone_set( $o, new DateTimeZone($timezone) );
			}else{
				//if not specifitied, using SERVER_TIMEZONE from getConf
				$SERVER_TIMEZONE=getConf("SERVER_TIMEZONE");
				if(!$SERVER_TIMEZONE) throw new Exception(__CLASS__.".getYmdHis() find no SERVER_TIMEZONE in config");
				date_timezone_set( $o, new DateTimeZone($SERVER_TIMEZONE) );
			}
			return $o->format('YmdHis');
		}
	}
	
	//新便捷函数，代替 checkMandatory，轮数组然后告诉哪个是需要的。
	//Example:
	//mg::checkRequired($param,array("name"=>getLang("name"));
	//or
	//mg::checkRequired($param,array("name");
	public static function checkRequired($arr,$key_a, $msg_tpl="MSG_ParamIsRequired"){
		$flag_is_assoc=self::is_assoc($key_a);
		foreach($key_a as $k=>$v){
			$kk=($flag_is_assoc)?$k:$v;
			//$vv=($flag_is_assoc)?$v:$k;
			$vv=$v;
			$f=$arr[$kk];
			if(!$f && $f!==0 && $f!=='0'){
				//参数1 $arr里面的 指定字段的值 如果不为真而且又不是 数字0或者字符串'0'，就理解为缺失，应抛出异常.
				throw new Exception( vsprintf(getLang($msg_tpl),array($vv)) );
			}
		}
	}

	//便捷函数: 如果 第一参数为真时，用第二、第三参数构造异常抛出.
	//第二参数是 第三参数模板的参数
	public static function checkCond($flag,$msg_param, $msg_tpl="MSG_ParamIsRequired"){
		if($flag){
			throw new Exception( vsprintf(getLang($msg_tpl),$msg_param));
		}
	}

	//@deprecated, 建议用上面checkRequired
	public static function checkMandatory($arr,$key_a){
		foreach($key_a as $v){
			$f=$arr[$v];
			if(!$f && $f!==0 && $f!=='0') throw new Exception(getLang('KO-checkMandatory')." $v");
		}
	}

	//TODO 还没想好 checkFormat怎么做.
	//public static function checkFormat($flag,$msg_param, $msg_tpl="MSG_ParamIsRequired"){
	//	if($flag){
	//		throw new Exception( vsprintf(getLang($msg_tpl),$msg_param));
	//	}
	//}

	public static function get_env($k){
		$rt=getenv($k);
		if($rt && $rt!="") return $rt;
		$rt=$_SERVER[$k];
		if($rt && $rt!="") return $rt;
		return null;
	}
	public static function get_ip(){
		static $_ip="";
		do{
			if($_ip!="") return $_ip;

			$LOCAL127="127.0.0.1";

			$HTTP_X_REAL_IP=self::get_env("HTTP_X_REAL_IP");
			if($HTTP_X_REAL_IP && $HTTP_X_REAL_IP!=$LOCAL127){
				$_ip=$HTTP_X_REAL_IP;break;
			}

			$HTTP_CLIENT_IP=self::get_env("HTTP_CLIENT_IP");
			if($HTTP_CLIENT_IP && $HTTP_CLIENT_IP!=$LOCAL127){
				$_ip=$HTTP_CLIENT_IP;break;
			}

			$HTTP_X_FORWARDED_FOR=self::get_env("HTTP_X_FORWARDED_FOR");
			if($HTTP_X_FORWARDED_FOR)
				list($HTTP_X_FORWARDED_FOR)= explode(",",$HTTP_X_FORWARDED_FOR);
			if($HTTP_X_FORWARDED_FOR && $HTTP_X_FORWARDED_FOR!=$LOCAL127){
				$_ip=$HTTP_X_FORWARDED_FOR;break;
			}

			$REMOTE_ADDR=self::get_env("REMOTE_ADDR");
			//		if($REMOTE_ADDR && $REMOTE_ADDR!=$LOCAL127){
			//			$_ip=$REMOTE_ADDR;break;
			//		}
			$_ip=$REMOTE_ADDR;
		}while(false);
		return($_ip);
	}
	//public static function check_ip(){
	//	if(@$_SESSION['_ip']!=_get_ip_()){
	//		throw new Exception("IP Changed, Please login again.",4444);
	//	}
	//}

	public static function __callStatic( $__function__, $param_a ){
		//TODO 查找相关的函数和函数...
		throw new Exception("TODO FUNC $__function__");
	}
}
