<?php
//NOTES: deprecated, but kept as a history.
/**
 * Wrapper for the database access
 * Version: V0.6alpha.20110323
 */
//for stupid windows
//if (!extension_loaded('mysql')) {
//	if (!dl('php_mysql.dll')) {
//		exit;
//	}
//}

require_once(_LIB_ . "/lib.adodb511.strip/adodb.inc.php");
//include_once("adodb/adodb-exceptions.inc.php");

class appDB{
	public $db;//public, for convenience only
	public $db_name="";//for convenience only

	var $in_trans = false; //if in transaction status.  MGTTT: for essential stuff, try use lock-free algorithm for performance issue
	var $dbtype="";
	var $_Affected_Rows=0;
	var $debug=1;
	var $_last_exec_time=-1;//for debug purpose, used to monitor the performance of execute
	public function getType($dbtype){
		return $this->dbtype;
	}

	public function getName(){
		return $this->db_name;
	}

	static function _getDB($dbtype,$host,$user,$pwd,$name,$port){
		$debug=2;//don't use quicklog here, as the _getDB is a freq func
		global $ADODB_FETCH_MODE;
		$db_fetch_mode=getConf("ADODB_FETCH_MODE",null,false);
		if($db_fetch_mode=="") $ADODB_FETCH_MODE=ADODB_FETCH_ASSOC;
		else $ADODB_FETCH_MODE=$db_fetch_mode;
		if($dbtype!=""){
			if($debug>2)
			#	wq_log_internal("_getDB before NewADOConnection($dbtype){");
				quicklog_must("err_db","_getDB before NewADOConnection($dbtype){");
			$db_= NewADOConnection($dbtype);
			if($debug>2)
			#	wq_log_internal("_getDB after NewADOConnection($dbtype)}");
				quicklog_must("err_db","_getDB after NewADOConnection($dbtype)}");
			//$db_=& ADONewConnection($dbtype);
			if (!$db_){
				$err_="error=_getDB($dbtype,$host,$user,$pwd,$name,$port)";
				quicklog_must("err_db",$err_);
				#wq_log_internal("[err_db]$err_");
				throw new Exception("db not found: $dbtype,$name");
			}
			if($port!='') $db_->port=$port;
			if($dbtype=='mysqli'){
				$db_->PConnect($host,$user,$pwd,$name,true);
				$db_->Execute("set names utf8");//dirty hack
			}
			elseif($dbtype=='mysql'){
				if(!extension_loaded('mysql')){
					throw new Exception("mysql ext not config");
					/*
					if(getSystemType()=='WIN'){
						if(!dl('php_mysql.dll')){
							throw new Exception("load php_mysql.dll failed, please check php.ini 'enable_dl'");
						}
					} else {
						quicklog_must("IT-CHECK","For Unix please config to use mysql module 'mysql.so'");
						throw new Exception("For Unix please config to use mysql module 'mysql.so'");
					}
					 */
				}
				if($debug>2)
				#	wq_log_internal("before mysql Connect");
					quicklog_must("err_db","before mysql Connect");

				if (!getConf("ADODB_SINGLE_CONNECTION", null, false)) {
					$db_->forceNewConnect = true;
				}
				
				if(! $db_->Connect($host,$user,$pwd,$name)){
					quicklog_must("err_db","DB_Error($host,$user,$name):".$db_->ErrorMsg(),$db_->ErrorNo());
					#wq_log_internal("fail mysql Connect: ".$db_->ErrorMsg(),$db_->ErrorNo());
					throw new Exception("DB_Error:".$db_->ErrorMsg(),$db_->ErrorNo());
				}
				if($debug>2)
					#wq_log_internal("before Execute set names utf8");
					quicklog_must("err_db","before Execute set names utf8");
				$db_->Execute("set names utf8");//dirty hack
			}
			elseif($dbtype=='pdo'){
				if(@$db_->Connect($host)){//REMARK: @ is noted
				}else{
					throw new Exception($db_->ErrorMsg());
				}
			}
			elseif($dbtype=='sqlite'){
				throw new Exception("For sqlite, please use PDO now.");
			}
			elseif($dbtype=='mssql'){
				$db_->Connect($host . ($port!=""?(",$port"):"") ,$user,$pwd,$name,true);
				//$db_->Execute("set names utf8");//这个好像OK
			}
			else throw new Exception("not support db type $dbtype yet");
		}else{
			throw new Exception("not support empty \$dbtype");
		}
		if($debug>2)
			#wq_log_internal("_getDB before return");
			quicklog_must("err_db","_getDB before return");
		return $db_;
	}

	//always new
	public static function newInstance($dns="",$dbtypeOrIgnoreLog=true){
		return new appDB($dns,$dbtypeOrIgnoreLog);
	}

	//some buffering stuff for db{
	static $_db=array();
	public static function getDB($dns,$dbtypeOrIgnoreLog=true){
		$db= & self::$_db;
		if(!$db[$dns]){
			$db[$dns] = self::newInstance($dns,$dbtypeOrIgnoreLog);
		} else {
			$db[$dns]->execute("use ".$db[$dns]->db_name);
		}
		
		return $db[$dns];
	}
	public static function getInstance($dns,$dbtypeOrIgnoreLog=true){
		return self::getDB($dns,$dbtypeOrIgnoreLog);
	}
	//some buffering stuff for db}

	function __construct($dns="",$dbtypeOrIgnoreLog=true){
		$debug_downgrade=0;
		if($dbtypeOrIgnoreLog===true){
			$debug_downgrade=1;
		}else{
			if(is_numeric($dbtypeOrIgnoreLog)) $debug_downgrade=1*$dbtypeOrIgnoreLog;
		}
		$this->debug=quicklog();
		$APP_DNS=getConf("db_conf");

		//$ADODB_COUNTRECS = true; //count number of records returned

		if($dns==''){ $dns='default'; }
		if(@$APP_DNS[$dns]){
			if($dbtypeOrIgnoreLog==true){
				$this->debug-=$debug_downgrade;
			}
			if(!isset($this->db) || $this->db==null){
				$dbtype= @$APP_DNS[$dns]["db_type"];
				$this->db=self::_getDB(
						$dbtype,
						@$APP_DNS[$dns]["db_host"],
						@$APP_DNS[$dns]["db_user"],
						@$APP_DNS[$dns]["db_pwd"],
						@$APP_DNS[$dns]["db_name"],
						@$APP_DNS[$dns]["db_port"]
						);
				$this->db_name=@$APP_DNS[$dns]["db_name"];
			}
		}else{
			//if the direct DNS mode for tools-scripting, the second param became the dbtype....
			$dbtype=$dbtypeOrIgnoreLog;
			$this->debug-=$debug_downgrade;//lower down the logging level for non-pre-config database connections
			$this->db=self::_getDB($dbtype,$dns);//try last method...direct call
		}
		if(!isset($this->db) || !$this->db || $this->db==null){
			throw new Exception("DB_CONFIG_ERROR");
		}
		$this->dbtype=$dbtype;
	}

	function Affected_Rows(){
		$af=$this->_Affected_Rows;
		return $af;
	}

	function af(){
		return $this->Affected_Rows();
	}

	//PageExecute, SelectLimit
	function logSQL($sql,$result){
		if($this->debug>0){}else{
			//for some key/essential performance related core part, we might want to skip the sql logging part to keep the system run quick...
			return;
		}
		$ULID=getSessionVar("LOGINID");
		$AUID=getSessionVar("UUID");
		$REMOTE_ADDR=@$_SERVER["REMOTE_ADDR"];
		$REMOTE_HOST=getenv("HTTP_X_FORWARDED_FOR");
		$db=$this->db;

		$sql=trim($sql);
		$flagSelect=0;

		if (strtoupper(substr(ltrim($sql),0,6))=='SELECT'){
			$flagSelect=1;
		}else{
			$this->_Affected_Rows=$db->Affected_Rows();
			$tmp_s="\n$sql\n--return".(($result)?("=".$this->_Affected_Rows):(" ".$db->ErrorMsg())).",".($time_after-$time_before) .",/".$REMOTE_ADDR."/".$REMOTE_HOST;
			if( ($AUID!='' || $ULID!='') && $this->debug>0){
				quicklog("sql-$AUID-$ULID",$tmp_s,true);
			}
			if($this->_Affected_Rows>0 && $this->debug>1)
				quicklog("sql",$tmp_s,true);
		}

		if($result!=false){
			if ($result->EOF){
				if ($flagSelect==1 && $this->debug>2){
					quicklog("sql_EOF-$AUID-$ULID","$sql;return no result.");
				}
			}
		}else{
			$ErrorMsg=$db->ErrorMsg();
			quicklog_must("err_sql",$ErrorMsg."--$sql");
		}
	}

	function Execute($sql,$inputarr=false,&$info="???"){
		$db=$this->db;

		if($this->debug>0){
			//normal
			return $this->TransExecute($sql,$inputarr,$info);
		}else{
			//as we said in logSQL(), for some essential proc, we just need to log the most "err" sql instead of anything big
			$result = $db->Execute($sql,$inputarr);
			if(!$result){
				$ErrorMsg=$db->ErrorMsg();
				quicklog_must("err_sql",$ErrorMsg."--$sql");
				throw new Exception($db->ErrorMsg(),$db->ErrorNo());
			}
			$this->_Affected_Rows=$db->Affected_Rows();
			return $result;
		}
	}

	private function _Execute($sql,$inputarr=false,&$info="???"){
		$db=$this->db;

		$flagDie=($info=='???')?true:false;
		if($this->debug>0){
		}else{
			//skip logging
			$result = $db->Execute($sql,$inputarr);
			if(!$result){
				$ErrorMsg=$db->ErrorMsg();
				if($flagDie){
					quicklog_must("err_sql",$ErrorMsg."--$sql");
					throw new Exception("DB Exception($ErrorMsg) please check log.");
					//throw new Exception("This Record has existed.");
				}
			}
			$this->_Affected_Rows=$db->Affected_Rows();
			return $result;
		}
		$ULID=getSessionVar("LOGINID");
		$AUID=getSessionVar("UUID");
		$REMOTE_ADDR=@$_SERVER["REMOTE_ADDR"];
		$REMOTE_HOST=getenv("HTTP_X_FORWARDED_FOR");
		$sql=ltrim($sql);
		$time_before=time();
		$result = $db->Execute($sql,$inputarr);
		$time_after=time();
		$this->_last_exec_time=$time_after-$time_before;
		//print "debug:".($time_after-$time_before).",$time_after,$time_before\n";

		$flagSelect=0;
		if($flagDie) $info="";//clear it if no second arg
		if (strtoupper(substr($sql,0,6))=='SELECT'){
			$flagSelect=1;
		}else{
			$this->_Affected_Rows=$db->Affected_Rows();
			$tmp_s="\n$sql\n--return".(($result)?("=".$this->_Affected_Rows):(" ".$db->ErrorMsg())).",".($time_after-$time_before) .",/".$REMOTE_ADDR."/".$REMOTE_HOST;
			if( ($AUID!='' || $ULID!='') && $this->debug>0){
				quicklog("sql-$AUID-$ULID",$tmp_s,true);
			}
			if($this->_Affected_Rows>0 || $this->debug>1)
				quicklog("sql",$tmp_s,true);
		}

		if (!$result){
			$ErrorMsg=$db->ErrorMsg();
			if($flagDie){
				quicklog_must("err_sql",$ErrorMsg."--$sql");
				throw new Exception("DB Exception($ErrorMsg) please check log.");
				//throw new Exception("This Record has existed.");
			}
			else $info.=$ErrorMsg;
		}else{
			if(($time_after-$time_before)>2)
			{//log the time-consumed query to optimize the system!
				quicklog("time_sql-$AUID-$ULID",($time_after-$time_before)."\n$sql");
			}
			if ($result->EOF){
				if ($flagSelect==1 && $this->debug>2 )
					quicklog("sql_EOF-$AUID-$ULID","$sql;return no result.");
			}
		}
		return $result;
	}

	function GetOne($sql,$inputarr=false,&$info="???"){
		$result=$this->Execute($sql,$inputarr,$info);
		if($result!=false){
			if(!$result->EOF && count($result->fields)>0){
				$result=array_pop($result->fields);
			}else{
				$result=false;
			}
		}
		return $result;
	}

	function PageExecute($sql, $pageSize, & $page,& $total,&$info="???"){
		//if($this->dbtype=='mssql'){
		//	$this->db->execute("SET ROWCOUNT $pageSize");
		//}
		$result = $this->db->PageExecute($sql, $pageSize, $page);
		//if($this->dbtype=='mssql'){
		//	$this->db->execute("SET ROWCOUNT 0");
		//}

		$total = $result->_maxRecordCount;
		if($result) $page = $result->AbsolutePage();
		$this->logSQL($sql,$result);
		$flagDie=($info=='???')?true:false;
		if($flagDie) $info="";//clear it if no second arg
		if (!$result){
			$ErrorMsg=$this->db->ErrorMsg();
			if($flagDie) throw new Exception("SQL Error $ErrorMsg");
			else $info.=$this->db->ErrorMsg();
		}
		return $result;
	}

	function SelectLimit($sql,$pageSize,$start,&$info="???"){
		$result = $this->db->SelectLimit($sql,$pageSize,$start);
		$this->logSQL($sql,$result);
		$flagDie=($info=='???')?true:false;
		if($flagDie) $info="";
		if (!$result){
			$ErrorMsg=$this->db->ErrorMsg();
			if($flagDie) throw new Exception("SQL Error $ErrorMsg");
			else $info.=$this->db->ErrorMsg();
		}
		return $result;
	}

	function Insert_ID(){
		$dbtype=$this->dbtype;
		if("pdo"==$dbtype //&&  $dbtype startsWith("sqlite:") ..
			){
			return $this->db->_insertid();//dirty patch for pdo
		}
		return  $this->db->Insert_ID();
	}

	///////////////////////////////// 关于Transaction的处理（很少用） {
	function HasTransation(){
		return $this->in_trans;
	}

	function StartTrans(){
		if(!$this->in_trans){
			$this->in_trans = true;
			$dbtype=$this->dbtype;
			if("mysql"==$dbtype || "mysqli"==$dbtype){
				//println("SET AUTOCOMMIT=0");
				$this->db->Execute("SET AUTOCOMMIT=0");
			}
			$this->db->StartTrans();
		}
	}
	//function BeginTrans(){
	//	return $this->StartTrans();
	//}
	//function CommitTrans(){
	//	return $this->CompleteTrans();
	//}
	//function EndTrans(){
	//	return $this->CompleteTrans();
	//}

	/** adodb doc:
		Improved method of initiating a transaction. Used together with CompleteTrans().
		Advantages include:
		
		a. StartTrans/CompleteTrans is nestable, unlike BeginTrans/CommitTrans/RollbackTrans.
		   Only the outermost block is treated as a transaction.<br>
		b. CompleteTrans auto-detects SQL errors, and will rollback on errors, commit otherwise.<br>
		c. All BeginTrans/CommitTrans/RollbackTrans inside a StartTrans/CompleteTrans block
		   are disabled, making it backward compatible.
	*/
	function CompleteTrans(){
		if($this->in_trans){
			if(!$this->db->HasFailedTrans()){
				$dbtype=$this->dbtype;
				if("mysql"==$dbtype || "mysqli"==$dbtype){
					$this->db->Execute("SET AUTOCOMMIT=1");//把之前的一次过commit掉
				}
				$c=$this->db->CompleteTrans();
				println("c=$c");
				$this->in_trans = false;
				if($c){
				}else{
					throw new Exception("CompleteTrans() failed.");
				}
			}
			else
			{
				$errmsg=$this->db->ErrorMsg();
				$this->db->RollbackTrans();
				$this->in_trans = false;
				throw new Exception("Transation failed (with try rollback)[$errmsg]");
			}
		}
	}

	function TransExecute($sql,$inputarr,&$info="???"){
		if(!$this->in_trans){
			$result=$this->_Execute($sql,$inputarr,$info);
			return $result;
		}
		if(!$this->db->HasFailedTrans()){
			$info="Transation execute sql [$sql]";
			$result=$this->_Execute($sql,$inputarr,$info);
			if(!$result){
				$this->db->FailTrans();
				$c=$this->CompleteTrans();
				throw new Exception("Transation execute failed:".$sql);
			}
			return $result;
		}else{
			$ULID=getSessionVar("LOGINID");
			$AUID=getSessionVar("UUID");
			quicklog("err_sql-$AUID_$ULID", "--Transation failed already before this [$sql]");
			throw new Exception("Transation failed already before this call");
		}
	}
	///////////////////////////////// 关于Transaction的处理（很少用） }
	
	function Date_Add($date,$expr,$type){
		switch($this->dbtype){
			case "mssql":
				switch($type) {
					case "SECOND":
						return "DATEADD($date,s,$expr)";
					case "MINUTE":
						return "DATEADD($date,n,$expr)";
					case "HOUR":
						return "DATEADD($date,h,$expr)";
					case "DAY":
						return "DATEADD($date,d,$expr)";
					case "MONTH":
						return "DATEADD($date,m,$expr)";
					case "YEAR":
						return "DATEADD($date,yyyy,$expr)";
					default:
					throw new Exception("Invalid type.");
				}
			case "mysql":
			case 'mysqli':
				switch($type) {
					case "SECOND":
						case "MINUTE":
						case "HOUR":
						case "DAY":			
						case "MONTH":
						case "YEAR":
						return "DATE_ADD($date,INTERVAL $expr $type)";
					default:
					throw new Exception("Invalid type.");
				}
			default:
				throw new Exception("Unsupported dbtype.");
				break;
		}		
	}

	public function __call($name, $arguments){
		$db=$this->db;
		//$rt=call_user_func(array($db,$name),$arguments,$a2,$a3,$a4,$a5,$a6);
		//$rt=call_user_func_array(array($db,$name),$arguments);//TODO seems buggy since 5.3.3 ?!
		$rt=$db->$name($arguments,$a2,$a3,$a4,$a5,$a6);//暂时...
		return $rt;
	}

	/////////////////////////////////////
	//Since 2014-12-12
	static protected $_cache_php_time;
	static protected $_cache_db_time;

	public function db_time(
		$cache_time=7 //7秒类静态变量内存缓存算法（防 同一php进程 由于代码错误导致意外访问数据库太频繁....）
		, & $flag_cache //探针获得是否取的标志是否缓存..
	){
		$php_now=microtime(true);
		$timediff=$php_now-self::$_cache_php_time;
		if($cache_time && self::$_cache_php_time && ($timediff < $cache_time)){
			$flag_cache=1;
			$db_time=self::$_cache_db_time+$timediff;
		}else{
			$flag_cache=0;
			$db_type=$this->getType();

			//建立sql for unix timestamp （注Unix Timestamp有32bit-2038年的BUG，所以直接取出 Y-m-d H:i:s）..
			switch($db_type){
			case "sqlite":
				//$sql_now="SELECT strftime('%s','now')";
				$sql_now="SELECT datetime('now')";//Y-m-d H:i:s
				break;
			case "mysql":
			case "mysqli":
				$sql_now="SELECT NOW()";//Y-m-d H:i:s
				break;
			default:
				throw new Exception("db_time not supported db_type=$db_type");
			}
			//把 Y-m-d H:i:s => UNIX TIMESTAMP
			$db_time=my_strtotime($this->GetOne($sql_now));//32bit-2038
			if(!$db_time) throw new Exception("db_time failed $db_type($sql_now)");
			self::$_cache_db_time=$db_time;
			self::$_cache_php_time=$php_now;
		}
		return $db_time;
	}

	public function isoDate( $time = NULL )
	{
		if(!$time) $time=$this->db_time();
		return my_isoDate($time);
	}
	public function isoDateTime( $time = NULL )
	{
		if(!$time) $time=$this->db_time();
		return my_isoDateTime($time);
	}
	
	public function db_uuid(){
		$db_type=$this->getType();
		switch($db_type){
		case "sqlite":
			throw new Exception('UUID for SQLite is TODO');
			//$sql="SELECT uuid()";
			break;
		case "mysql":
		case "mysqli":
			$sql="SELECT uuid()";
			break;
		default:
			throw new Exception("db_uuid not supported db_type=$db_type");
		}
		$uuid=($this->GetOne($sql));
		if(!$uuid) throw new Exception("db_uuid failed $db_type($sql)");
		return $uuid;
	}
	/////////////////////////////////////
}

