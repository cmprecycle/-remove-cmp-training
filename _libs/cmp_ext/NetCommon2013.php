<?php
/* vim: set tabstop=2 shiftwidth=2 softtabstop=2: */


//class to simulate a web client
//v 2013 removed memcached support..now using cache disk (IO)

//usage:
//$nc=new NetCommon2013
//$nc->setSessionKey($sess_key);
////or:
//$nc->setCacheDisk($sess_key);
class NetCommon2013 {
	var $conn_info="";
	var $_sess_key="";
	var $_flag_cache = false;
	var $callback = false;
	var $ch=null;
	var $cookiefile="";
	var $HTTP_USER_AGENT="Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; Maxthon; .NET CLR 1.1.4322; .NET CLR 2.0.50727)";
	var $refer="";
	var $call_c=0;
	var $verbose=false;
	var $header=null;
	var $return_header="";
	var $return_body="";
	var $freemem_max=2;
	var $timeout=300;//5 min...as default
	var $proxy = array();
	var $phpwebproxy = null;
	var $basic_auth=false;
	var $_cd=null;//new Cache_Disk();

	public function setCacheDisk($sess_key){
		$this->_cd=new Cache_Disk();
		if($sess_key) $this->_sess_key=$sess_key;
		$this->_flag_cache=true;
	}
	public function setBasicAuth($ba){
		$this->basic_auth=$ba;
	}
	public function getConnInfo(){
		return $this->conn_info;
	}
	public function saveSessionVar($key,$value){
		if($this->_cd!=null){
			return $this->_cd->save($this->_sess_key ."_" .$key, $value,300);//5 min.
		}
		throw new Exception("NetCommon.saveSessionVar:cache not config");
	}
	public function loadSessionVar($key){
		if($this->_cd!=null){
			return $this->_cd->load($this->_sess_key ."_" .$key, 300);//5 min.
		}
		throw new Exception("NetCommon.loadSessionVar:cache not config");
	}
	public function setSessionKey($key){
		$this->_sess_key=$key;
		$this->cookiefile=$key;
	}
	public function httpGet($url,$data){
		return $this->httpRequest("GET",$url,$data);
	}
	public function httpPost($url,$data){
		return $this->httpRequest("POST",$url,$data);
	}
	//Notes header for out, not of return
	public function setHeader($hd){
		$this->header=$hd;
	}
	public function getHeader($hd){
		return $this->header;
	}
	public function setAgentHeader($s){
		$this->HTTP_USER_AGENT=$s;
	}
	public function setPHPWebProxy($params){
		$this->phpwebproxy=$params;
	}
	public function setProxy($params){
		#if(is_array($params) && $params)
			$this->proxy = $params;
	}
	public function clsWebClient(){
		if($this->ch==null)
			$ch=curl_init();
		$this->ch=$ch;
	}
	public function setCookieFile($key){
		$this->cookiefile=$key;
	}
	public function setTimeout($t){
		$this->timeout=$t;
	}
	public function setCallback($func_name) {
		$this->callback = $func_name;
	}
	public function setRefer($r){
		$this->refer=$r;//temp solution
	}
	public function setFreeMemCount($c){
		if($c>0)$this->freemem_max=$c;
	}
	public function getReturnBody(){
		return $this->return_body;
	}
	public function getReturnHeader(){
		return $this->return_header;
	}
	function _onBody($ch,$body_str){
		$this->return_body.=$body_str;
		return strlen($body_str);
	}
	function _onHeader($ch,$header){
		$this->return_header.=$header;

		$header_arr = explode("\r\n",$header);
		if($this->_flag_cache){
			//$cookies=unserialize($this->loadSessionVar("COOKIE"));
			$cookies=my_json_decode($this->loadSessionVar("COOKIE"));
			if( !is_array($cookies) ) $cookies=array();
			for($x=0; $x<count($header_arr); $x++){
				if(preg_match('/^set-cookie:[\s]+([^=]+)=([^;]+)/i', $header_arr[$x],$match)){
					$cookies[$match[1]] = urldecode($match[2]);
				}
			}
			$this->saveSessionVar("COOKIE",my_json_encode($cookies));
		}else{
			//TODO how to remember the cookie?
			//TODO using Cache_Disk!!
		}

		$strlen_header=strlen($header);
		return $strlen_header;
	}
	function _http_post($url, $data){
		$data_url = http_build_query ($data);
		#$data_url=$data;//string
		$data_len = strlen ($data_url);

		$rt=file_get_contents($url, false, stream_context_create (array ('http'=>array ('method'=>'POST'
			, 'header'=>"Connection: close\r\nContent-Length: $data_len\r\n"
			, 'content'=>$data_url
		))));
		return $rt;//TODO
	}
	public function httpRequest($method, $url, $vars) {
		$this->call_c=($this->call_c+1) % $this->freemem_max;
		if($this->call_c==0){
			if($this->ch!=null){
				curl_close($this->ch);
				unset($this->ch);
			}
		}
		if($this->phpwebproxy){
			$this->return_header="";//clear
			#$this->return_body=PHPWebProxyClient::httpRequest($method, $url, $vars);
			$data=array("METHOD"=>$method,"URL"=>base64_encode($url),"POST"=>base64_encode($vars),"_s"=>session_id());
			$result_s= $this->_http_post($this->phpwebproxy['url'],$data);
			$result_a=my_json_decode($result_s);
			$this->return_header=base64_decode($result_a['return_header']);
			$this->return_body=base64_decode($result_a['return_body']);
		}
		else
		{
			//curl way
			if(!$this->ch) $this->ch=curl_init();
			$ch = $this->ch;

			if($this->header!=null){
				curl_setopt($ch,CURLOPT_HTTPHEADER,(array)$this->header);
			}
			curl_setopt($ch, CURLOPT_USERAGENT, $this->HTTP_USER_AGENT);
			$HTTP_USER_AGENT=$_SERVER['HTTP_USER_AGENT'];
			if($HTTP_USER_AGENT!="") curl_setopt($ch, CURLOPT_USERAGENT, $HTTP_USER_AGENT);
			if($this->_flag_cache){
				$cookie_mem=$this->loadSessionVar("COOKIE");
				//$cookie=unserialize($cookie_mem);
				$cookie=my_json_decode($cookie_mem);
				if ( count($cookie) > 0 ) {
					//$cookie_str = "Cookie:\t";
					foreach ( $cookie as $cookieKey => $cookieVal ) { $cookie_str .= $cookieKey."=".urlencode($cookieVal)."; "; }
				}
				curl_setopt($ch, CURLOPT_COOKIESESSION, false);
				if($cookie_str != ""){
					curl_setopt($ch, CURLOPT_COOKIE, $cookie_str);
				}
			}else if($this->cookiefile!=""){
				curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiefile .'.txt');
				curl_setopt($ch, CURLOPT_COOKIEFILE,$this->cookiefile .'.txt');
			}
			if($this->refer!=""){
				curl_setopt($ch, CURLOPT_REFERER, $this->refer);
				$this->refer="";//clear
			}else{
				curl_setopt($ch, CURLOPT_AUTOREFERER, true);
			}
			//CURLOPT_PROXYTYPE Either CURLPROXY_HTTP (default) or CURLPROXY_SOCKS5.  
			if($this->basic_auth){
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_USERPWD, $this->basic_auth);
			}
			if($this->proxy){
				curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
				curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				curl_setopt($ch, CURLOPT_PROXY, $this->proxy['host']);
				curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy['port']);
				if($this->proxy['user']) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxy['user'].":".$this->proxy['pwd']);
			}
			if ($method == "POST") {
				curl_setopt($ch, CURLOPT_HTTPGET, false);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
			}else{
				curl_setopt($ch, CURLOPT_POST, false);
				curl_setopt($ch, CURLOPT_POSTFIELDS, false);
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}
			////////////////////////////////////////////////////////////
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_ENCODING,"gzip, deflate");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_VERBOSE, $this->verbose);
			////////////////////////////////////////////////////////////
			curl_setopt($ch, CURLOPT_URL, $url);
			//curl_setopt($ch, CURLOPT_HEADER,1);
			curl_setopt($ch, CURLOPT_HEADER,0);//true 启用时会将头文件的信息作为数据流输出..
			curl_setopt($ch, CURLOPT_HEADERFUNCTION,array($this,'_onHeader'));
			curl_setopt($ch, CURLOPT_WRITEFUNCTION,array($this,'_onBody'));
			$this->return_body="";
			$this->return_header="";
			$_st=microtime(true);
			$data = curl_exec($ch);
			$_ed=microtime(true);
			$_df=$_ed-$_st;
			if($_df>10){
				quicklog_must("NetCommon-time-debug","NetCommon.httpRequest $url time=$_df");
			}
			$this->conn_info = curl_getinfo($ch);
			if(!$data){
				//sleep(1);//hope some dirty luck after 1 more second
				$data=$this->return_body;
			}
		}
		if ($data) {
			if ($this->callback)
			{
				$callback = $this->callback;
				$this->callback = false;
				call_user_func($callback, $this->return_body);
			} else {
				return $this->return_body;
			}
		} else {
			if($this->phpwebproxy){
				$ex=new Exception("phpwebproxy return error",999);//TMP, TODO
				throw $ex;
			}else{
				$errmsg=curl_error($ch);
				$errno=curl_errno($ch);
				$ex=new Exception("($errmsg,$errno)",$errno);
				if(28!=$errno){
					quicklog_must("NetCommon-Error","url=$url");
					quicklog_must("NetCommon-Error","header={");
					quicklog_must("NetCommon-Error",$this->getHeader());
					quicklog_must("NetCommon-Error","header=}");
					quicklog_must("NetCommon-Error","data={");
					quicklog_must("NetCommon-Error",$data);
					quicklog_must("NetCommon-Error","data=}");
					quicklog_must("NetCommon-Error","returnheader={");
					quicklog_must("NetCommon-Error",$this->getReturnHeader());
					quicklog_must("NetCommon-Error","returnheader=}");
					quicklog_must("NetCommon-Error","ex=($errmsg,$errno)");
				}else{
					quicklog_must("NetCommon-Error","url=$url");
					quicklog_must("NetCommon-Error","data={");
					quicklog_must("NetCommon-Error",$data);
					quicklog_must("NetCommon-Error","data=}");
					quicklog_must("NetCommon-Error","ex=($errmsg,$errno)");
					//CURL_OPERATION_TIMEDOUT (28)
				}
				throw $ex;
			}
		}
	}
	public function readCookie($key){
		if($this->_flag_cache){
			$cookies=my_json_decode($this->loadSessionVar("COOKIE"));
			if( !is_array($cookies) ) $cookies=array();
			return $cookies[$key];
		}else if($this->cookiefile!=""){
			throw new Exception("TODO for CURLOPT_COOKIEFILE");
		}
	}
	public function updateCookie($key,$val){
		if($this->_flag_cache){
			$cookies=my_json_decode($this->loadSessionVar("COOKIE"));
			if( !is_array($cookies) ) $cookies=array();
			$cookies[$key]=$val;
			$this->saveSessionVar("COOKIE",my_json_encode($cookies));
		}else if($this->cookiefile!=""){
			#curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookiefile .'.txt');
			#curl_setopt($ch, CURLOPT_COOKIEFILE,$this->cookiefile .'.txt');
			//print file_get_contents($this->cookiefile.'.txt');
			throw new Exception("TODO for CURLOPT_COOKIEFILE");
		}
	}


	function __destruct() {
		if($this->ch!=null) {curl_close($this->ch); $this->ch=null;}
	}
}

