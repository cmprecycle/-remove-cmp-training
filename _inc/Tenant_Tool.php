<?php
class Tenant_Tool
{
	static $saas_conf_file=null;
	public static function GetSaasConfFileName(){
		if(! self::$saas_conf_file){
			self::$saas_conf_file=_TMP_."/saas_conf.php";
		}
		if(self::$saas_conf_file) return self::$saas_conf_file;
		else throw new Exception("saas_conf_file empty?!");//不会出现这一步的.
	}
	public static function MergeSaasConf(){
		$saas_conf_file=self::GetSaasConfFileName();
		include $saas_conf_file;
		if($saas_conf){
			setConf("saas_conf",$saas_conf);

			//特别处理 db_conf
			$db_conf_saas=$saas_conf['db_conf'];
			if($db_conf_saas){
				$db_conf=getConf("db_conf");
				//if(!$db_conf)$db_conf=array();//IMPORTANT
				//$db_conf=array_merge($db_conf,$db_conf_saas);
				arr2arr($db_conf,$db_conf_saas);
				setConf("db_conf",$db_conf);
			}
		}
	}
	public static function BuildSaasConfFile(){
		$saas_conf_file=self::GetSaasConfFileName();

		$saas_info=getConf("saas_info");
		if(!$saas_info){
			$errmsg="System Not Config After Installation";
			throw new Exception($errmsg);
		}
		$secret=$saas_info['secret'];
		$code=$saas_info['code'];
		$entry=$saas_info['entry'];
		if($entry && $secret && $code){
		}else{
			$errmsg="Config(saas_info) Incorrect";
			throw new Exception($errmsg);
		}

		$_m="GetConf";
		$_c="ApiSaas";
		$url="$entry?_c=$_c&_m=$_m";
		require_once _LIB_CORE_ ."/inc.func.web_request.php";
		$s=web_request($url,array("code"=>$code,"secret"=>$secret));
		$o=my_json_decode($s);
		if($o && $o['conf']){
			//保存缓存...
			$conf=$o['conf'];
			//if($conf) quicklog_must("IT-CHECK",'conf='.my_json_encode($conf));
			if($conf['tenant_conf_s']){
				//$conf['tenant_conf']=my_json_decode($conf['tenant_conf_s']);
				$tenant_conf=my_json_decode($conf['tenant_conf_s']);
				$conf=array_merge($conf,(array)$tenant_conf);
			}
			if(!$conf){
				quicklog_must("IT-CHECK","Tenant_Tool s=$s");
				quicklog_must("IT-CHECK",'Tenant_Tool o='.my_json_encode($o));
				$errmsg="Config(saas_conf) Error";
				throw new Exception($errmsg);
			}
			$file_s=var_export($conf,true);
			$file_s="\$saas_conf=$file_s;";
			file_put_contents($saas_conf_file,"<"."?php\n$file_s");
		}else{
			quicklog_must("IT-CHECK","Tenant_Tool $url,$code,$secret =>");
			quicklog_must("IT-CHECK","Tenant_Tool $s");
			$errmsg="Config(saas_conf) Empty, Please Check LOG";
			throw new Exception($errmsg);
		}

		//load right away
		self::MergeSaasConf();
	}
	
	//检查Saas配置自缓存，如果有问题就重拿并缓存
	public static function CheckSaas(){
		$saas_conf_file=self::GetSaasConfFileName();

		$saas_conf=getConf("saas_conf");
		if(!$saas_conf){
			self::BuildSaasConfFile();//try build
			$saas_conf=getConf("saas_conf");
		}
		if(!$saas_conf){
			throw new Exception(getLang("404_saas_conf"));//why?!
		}
		$tenant_expire_time=$saas_conf['tenant_expire_time'];
		if(!$tenant_expire_time){
			quicklog_must("IT-CHECK","Tenant_Tool tenant_expire_time empty in ".my_json_encode($saas_conf));
			self::BuildSaasConfFile();
			throw new Exception(getLang("500_saas_expire_empty"));
		}
		/////////////////////////////////////
		$db_conf=getConf("db_conf");
		if(!$db_conf){
			self::BuildSaasConfFile();
			$db_conf=getConf("db_conf");
		}
		if(!$db_conf){
			quicklog_must("IT-CHECK","Tenant_Tool no db_conf in ".my_json_encode($saas_conf));
			self::BuildSaasConfFile();
			throw new Exception(getLang("404_db_conf"));//远程传过来的配置里面数据库有错
		}
		
		/////////////////////////////////////
		$now_ns=time();
		$tenant_expire_time_ns=my_strtotime($tenant_expire_time);
		$time_diff=($now_ns-$tenant_expire_time_ns);
		if($now_ns && $tenant_expire_time_ns && $time_diff < 0){
			//OK, not expire yet.
			//quicklog_must("Tenant_Tool","OK $tenant_expire_time,$tenant_expire_time_ns,$now_ns,$time_diff,".my_json_encode($saas_conf));
		}else{
			quicklog_must("IT-CHECK","Tenant_Tool 500_saas_expire_license $tenant_expire_time,$tenant_expire_time_ns,$now_ns,$time_diff,".my_json_encode($saas_conf));
			self::BuildSaasConfFile();
			throw new Exception(getLang("500_saas_expire_license"));
		}
		
		$tenant_root_a=$saas_conf['tenant_root_a'];
		if($tenant_root_a && count($tenant_root_a)>0){
			//OK
		}else{
			quicklog_must("IT-CHECK","Tenant_Tool 404-conf-root ".my_json_encode($saas_conf));
			throw new Exception(getLang("404-conf-root"));
			//远程的配置里面没有tenant_root_a，多数是因为远程的saas_da未配置好TenantRoot
		}

		$tenant_code=$saas_conf['tenant_code'];
		if($tenant_code){
			//OK
		}else{
			throw new Exception(getLang("404-tenant_code"));
		}
		
		//TODO
		//如果 Last Check In Time超过 x小时（因为我们是x小时轮询的，超x+1小时肯定表示系统有问题，要先暂停检查！）
		//$now=my_strtotime();
		//if($now-$last_checkin_time> (x+1)*3600){
		//quicklog_must("IT-CHECK","Tenant_Tool licent expire? $now-$last_checkin_time");
		//throw new Exception(getLang("500_db_conf"));
		//}
	}
}
