<?php
//NOTES:  @deprecated, mostly for php-php link
/*
 * Usage:
 $url_root="http://192.168.131.1/~admin/2010/web/";
$clt=new WebAPIClient;
$clt->setEntryUrl($url_root);
$_s="62eqpn91q1ioii1e9le3vbeqj1";//hardcode so that can continue previous test.
if($_s=="") $_s=Util::getBarCode(23,"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");//getBarCode in _conf/index.php
$debug=0;
$lang="en";

$GET=array(
	"_s"=>$_s,
	"debug"=>$debug,
	"lang"=>$lang,
);
$POST=array(
	//"class"=>"appUser",//empty for system default
	"method"=>"Login",
	"param"=>array(
		"usr"=>"test",
		"pwd"=>"test1234",
	),
);
println($POST);
$resp=$clt->call($GET,$POST,"api_user/");
 */
class WebAPIClient
	extends NetCommon
{
	var $_url;
	public function setEntryUrl($url){
		$this->_url=$url;
	}
	public function getEntryUrl($url){
		return $this->_url;
	}
	//please use call() as public method
	private function callRemoteApi($entry,$GET,$Data4POST){
		$url=$entry?$entry:($this->getEntryUrl());
		$url_get="";
		$url_get_a=array();
		foreach($GET as $k=>$v){
			$url_get_a[$k]="$k=".urlencode($v);
		}
		$url_get=join('&',$url_get_a);
		if($url_get) $url_get="?".$url_get;
		$resp=$this->httpPost("$url$url_get",my_json_encode($Data4POST));

//TODO if $resp in error of 404? 500? etc  throw exceptions, ok?!

		$rt=my_json_decode($resp);
		if(!$rt) $rt=$resp;
		return $rt;
	}
	/*
	public function callRemote($GET,$POST){
		return $this->callRemoteApi(null,$GET,$POST);
	}
	 */
	public function call($GET,$POST,$url_entry,$url_root){
		if(!$url_root) $url_root=$this->getEntryUrl();
		$url=$url_root . $url_entry;
		$rt=$this->callRemoteApi($url,$GET,$POST);
		return $rt;
	}
}
