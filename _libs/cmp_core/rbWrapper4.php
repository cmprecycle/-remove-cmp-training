<?php
//@ref http://www.redbeanphp.com/crud
//RedBeanPHP will build all the necessary structures to store your data. However custom indexes and constraints have to be added manually (after freezing your web application).
//require_once(_LIB_CORE_."/rb4_cmp.php");
#require_once(_LIB_CORE_."/rb4.2.5_cmp.php");
require_once(_LIB_CORE_."/rb4.3_cmp.php");
//require_once(_LIB_CORE_."/FacadeNonStatic.php");//cmp hack for non static mode of facade of redbean
//require_once(_LIB_CORE_."/FacadeNonStaticCmp425.php");//cmp hack for non static mode of facade of redbean
require_once(_LIB_CORE_."/FacadeNonStaticCmp43.php");//cmp hack for non static mode of facade of redbean

//NOTES:
//
// This is a RedBeanPHP-Wrapper

//sqlite mode:
//$rb=new rbWrapper4("sqlite:"._APP_DIR_.DIRECTORY_SEPARATOR."../test_rb.db");

class rbWrapper4
	//extends \RedBeanPHP\FacadeNonStatic
	//extends \RedBeanPHP\FacadeNonStaticCmp425
	//extends \RedBeanPHP\FacadeNonStaticCmp43
{
	//对应的表名...
	public $NAME_R=null;

	public function setBeanType($n){
		$this->NAME_R=$n;
	}
	public function getBeanType($n){
		return $this->NAME_R;
	}

	private $_default_bean=null;

	//NOTES 如果第二参数不显式，就会用getConf('flag_rb_freeze');
	public function __construct($dsn,$freeze){
		$this->_default_bean=new \RedBeanPHP\FacadeNonStaticCmp43;//!!!!
		
		//失败尝试，静态类确实很难简单污染...
		//$md5=md5($dsn);
		//$newClass="R_$md5";
		//if (!class_exists($newClass)) {
		//	eval("class $newClass extends \RedBeanPHP\Facade{ public static \$toolboxes = array(); };");
		//}
		//$this->_default_bean=new $newClass;
		
		$this->R_setup($dsn,$freeze);

		$this->useWriterCache(false);//20151019 no cache for default...
	}
	
	//返回的是一个bean （注意，是未store保存的）....
	//跟dispense 的不同主要是如果参数缺省，就拿 NAME_R
	public function dispenseBean($t){
		if(is_string($t) && $t!=""){
		}else{
			$t=$this->NAME_R;
		}
		//if($t && is_array($t)){
		//	$t=$this->NAME_R;
		//}
		//if(!$t) $t=$this->NAME_R;
		if(!$t) throw new Exception(getLang("404_dispenseBean_need_param"));
		return $this->dispense($t);
	}

	protected function R_setup($dsn_req,$freeze){

		if(!$dsn_req) throw new Exception("404 DSN");//新版起需要显式dsn

		$db_conf=getConf("db_conf");

		if($dsn_req){
			$dsn_key=$dsn_req;
		}

		$db_a=$db_conf[$dsn_key];
		if($db_conf && $db_a){
			$db_type=$db_a["db_type"];
			$db_host=$db_a["db_host"];
			$db_user=$db_a["db_user"];
			$db_pwd =$db_a["db_pwd"];
			$db_name=$db_a["db_name"];
			$db_port=$db_a["db_port"];

			$this->setup($db_type.":host=$db_host;port=$db_port;dbname=$db_name",$db_user,$db_pwd);
		}else{
			$this->setup($dsn_req);//尝试直接用 RedBean 自带的 dsn适应...
		}

		if($freeze===false || $freeze===true){
			$this->freeze($freeze);
		}else{
			//参数非明确，就看配置...
			$flag_rb_freeze=getConf("flag_rb_freeze");
			//NOTES: 除非显式，否则默认不freeze
			if($flag_rb_freeze==true){
				$this->freeze();
			}else{
				$this->freeze( FALSE );
			}
		}
		//出于性能考虑，下面直接拉到 rb4里，违反了设计原则（which is "尽量不影响别人的库代码"），所以是复制 rb{$rbVersion}_cmp.php
		//解释：setup应该是还不要直接建立连接的，而应该是在第一次connect时做设定，但因为我们的应用需要重置时区才能保障某些地方计算正确。
		//暂时的处理方法只能是去 hack rb的代码先用着。后面如果 rb那边能接受我的修改合并进去（可能要增加 action_when_connect()）
		//$db_timezone=getConf("db_timezone");
		//if(!$db_timezone) throw new Exception("db_timezone not config");
		//$this->exec("set time_zone=?",array($db_timezone));
	}
	public function deleteBean($id){
		$bean=$this->loadBean($id);
		return $this->trash($bean);
	}
	public function loadBean($id){
		$rt=$this->_default_bean->load($this->NAME_R,$id);
		if($rt && $rt->id){
			return $rt;
		}else throw new Exception(getLang('KO-loadBean-'.$this->NAME_R).".$id");
		//return $rt;
	}
	public function loadBeanArr($id){
		$bean=$this->loadBean($id);
		return $bean->export();
	}

	//返回的是 beans array （注意不是数字索引的，索引的key是 id！！！)...
	public function findBeanArr($q1,$q2=array()){
		$rt=$this->_default_bean->find($this->NAME_R,$q1,$q2);
		if(is_array($rt) && count($rt)>0){
			return $rt;
		}else{
			//php的空数组==null，但不===null
			return array();
		}
	}

	//返回的是 beans array 的随意(天然顺序)一个...
	public function findBeanOne($sql_piece,$binding=array()){
		if(!is_string($sql_piece)) throw new Exception("findBeanOne need correct param");
		$rt=$this->_default_bean->find($this->NAME_R,$sql_piece.' LIMIT 1',$binding);
		if(is_array($rt) && count($rt)>0){
			return array_pop($rt);
		}else{
			return null;
		}
	}
	//不严谨。如果需要ACID，需要另外写..
	public function findOneBeanOrDispense($q1,$q2,$q3){
		if($q3===null)
		{
			$bean_type=$this->NAME_R;
			$rsa=$this->_default_bean->find($bean_type,$q1,$q2);
		}else{
			$bean_type=$q1;
			if(!$q2) $q2=array();
			$rsa=$this->_default_bean->find($bean_type,$q2,$q3);
		}
		if(is_array($rsa) && count($rsa)>0){
			return array_pop($rt);
		}else{
			return $this->dispense($bean_type);
		}
		return $rsa;
	}

	/**
	 * getArr, findAndExport, getAll的区别看 test/test_rb_findAndExport_getAll.php
	 *
	 * 但是我们代码里面尽量不用 findAndExport，要么用 getArr或者 getAll...
	 */
	public function getArr($q1,$q2,$q3){
		if($q3===null)
		{
			$bean_type=$this->NAME_R;
			if(!$q2) $q2=array();
			$rsa=$this->_default_bean->findAndExport($bean_type,$q1,$q2);
		}else{
			$bean_type=$q1;
			$rsa=$this->_default_bean->findAndExport($bean_type,$q2,$q3);
		}
		if(is_array($rsa) && count($rsa)>0){
		}else{
			//php的空数组==null，但不===null
			return array();
		}
		return $rsa;
	}

	public function hasTable($bean_type){
		if(!$bean_type) $bean_type=$this->NAME_R;
		if(!$bean_type) throw new Exception(getLang('KO-hasTable'));
		//$rt=$this->getCell("SHOW TABLES LIKE ?",array($bean_type));//只支持 mysql
		$rt=$this->getWriter()->tableExists($bean_type);//RB通用.
		if(!$rt) return false;
		return $rt;
	}

	//NOTES:其实....exec返回的就已经是af了!!!
	public function af(){
		//$af=$this->getDatabaseAdapter()->getAffectedRows();
		$af=$this->Affected_Rows();
		return $af;
	}

	//Usage:
	//$af=$rb->exec($sql);
	public function exec($sql,$binding=array()){
		/**
			在 【rb和我们框架】 里面，exec返回的是 af，不是返回 rsa的。如果要使用SELECT获得返回，统一使用 PageExecute 或者 getAll等
		 */
		$rt=$this->_default_bean->exec($sql,$binding);
		if($rt===NULL){
			throw new Exception("exec return null");
		}elseif(is_numeric($rt)){
			//OK
		}else{
			//tell RND
			quicklog_must("IT-CHECK","sql ($sql) return ".my_json_encode($rt));
			//TODO 要判断是不是select，如果是select应该是数组，如果不是SELECT就throw new Exception("SQL OR DB ERROR");
		}
		return $rt;
	}
	//换多个名字方便一点:$af=$rb->execute($sql);
	public function execute($sql,$binding){
		return $this->exec($sql,$binding);
		//这里一般适用于增删改，查询的话只会返回记录条数，而不会返回记录本身
		//[如果你要查询且返回记录本身的话，需要使用getAll]...
	}


	//慢慢完善:
	//$rs_page=$rb->PageExecute(array(
	//	'SELECT'=>'*',
	//	'FROM'=>'test',
	//	//'WHERE'=>'id=?',
	//	'ORDER'=>'XXX asc/desc',
	//	'LIMIT'=>6,//当pageNumber和pageSize不同时有但有它时，会生效....
	//	'pageNumber'=>2,
	//	'pageSize'=>3,
	//	'binding'=>array($id)
	//));
	//NOTES: 第二参数还有个作用，就是如果 有 pageNumber和pageSize时，max非正数的话还会跳过计算记录总数，这样能让翻页计算快很多（和少了一个 sql）.
	public function PageExecute($p, $max=999){
		$SELECT=$p['SELECT'];
		if($SELECT){
			$SELECT_s="SELECT $SELECT";
		}else{
			$SELECT_s="SELECT *";
		}
		$FROM=$p['FROM'];
		if($FROM){
			$FROM_s="FROM $FROM";
		}
		$WHERE=$p['WHERE'];
		if($WHERE){
			$WHERE_s="WHERE $WHERE";
		}else{
			$WHERE_s="WHERE 1=1";
		}
		$ORDER=$p['ORDERBY']?$p['ORDERBY']:$p['ORDER'];
		if($ORDER){
			$ORDER_s="ORDER BY $ORDER";
		}else{
			$ORDER_s="";
		}
		$GROUPBY=$p['GROUPBY']?$p['GROUPBY']:$p['GROUP'];
		if($GROUPBY){
			$GROUPBY_s="GROUP BY $GROUPBY";
		}else{
			$GROUPBY_s="";
		}
		$pageNumber=$p['pageNumber'];
		$pageSize=$p['pageSize'];
		$binding=$p['binding'];

		$LIMIT_s ="";
		if ($pageNumber > 0 && $pageSize > 0){
			$limit_start = ($pageNumber - 1) * $pageSize;
			$LIMIT_s =" LIMIT $limit_start,$pageSize";

			if($max>0){
				$total=$this->getCell("SELECT COUNT(*) $FROM_s $WHERE_s",$binding);
				if($total=="")$total=0;
				$rt['maxRowCount']=$total;
				$rt['total']=$total;
			}else{
				//约定如果第二参数为负数，跳过取总这一步.
			}
		}else{
			$limit=$p['LIMIT'];
			if($limit>0){
				$LIMIT_s =" LIMIT $limit";
			}else{
				//SafeNet
			$LIMIT_s =" LIMIT $max";
			}
		}
		$sql="$SELECT_s $FROM_s $WHERE_s $GROUPBY_s $ORDER_s $LIMIT_s";
		$rt['sql']=$sql;
		if($binding){
			$rt['binding']=$binding;
		}
		$rt['rst']=$this->getAll($sql,$binding);
		return $rt;
	}

	private function _startsWith($haystack, $needle){
		return $needle === "" || strpos(ltrim($haystack), $needle) === 0;
	}
	private function _endsWith($haystack, $needle){
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}

	public function db_uuid(){
		//$sql='';
		$db_type=$this->getDatabaseAdapter()->getDatabase()->getDatabaseType();
		switch($db_type){
			//case "sqlite":
			//$sql="SELECT uuid()";
			//break;
		case "mysql":
		case "mysqli":
			$sql="SELECT uuid()";
			break;
		default:
			throw new Exception("db_uuid not supported db_type=$db_type");
		}
		$uuid=($this->getCell($sql));
		if(!$uuid) throw new Exception("db_uuid failed $db_type($sql)");
		return $uuid;
	}
	
	protected $_cache_php_time;
	protected $_cache_db_time;

	///////////////////////////////////////////////////////
	//NOTES: 这个db_time是跟数据库的时区的，并不是你本地的时区，请用isoDate和isoDateTime
	//如果坚持要用，请用新的public函数 getDbTimeStamp() 但要注意时区的风险
	private function db_time(
		$cache_time=7 //7秒类静态变量内存缓存算法（防 同一php进程 由于代码错误导致意外访问数据库太频繁....）
		, & $flag_cache //探针获得是否取的标志是否缓存..
	){
		$php_now=microtime(true);
		$timediff=$php_now-$this->_cache_php_time;
		if($cache_time && $this->_cache_php_time && ($timediff < $cache_time)){
			$flag_cache=1;
			$db_time=$this->_cache_db_time+$timediff;
		}else{
			$flag_cache=0;
			$db_type=$this->getDatabaseAdapter()->getDatabase()->getDatabaseType();

			//建立sql for unix timestamp （注Unix Timestamp有32bit-2038年的BUG，所以直接取出 Y-m-d H:i:s）..
			switch($db_type){
			case "sqlite":
				//$sql_now="SELECT strftime('%s','now')";
				$sql_now="SELECT datetime('now')";//Y-m-d H:i:s
				//$sql_now="SELECT datetime()";//?
				break;
			case "mysql":
			case "mysqli":
				$sql_now="SELECT NOW()";//Y-m-d H:i:s
				break;
			default:
				throw new Exception("db_time not supported db_type=$db_type");
			}
			//把 Y-m-d H:i:s => UNIX TIMESTAMP
			$db_time=my_strtotime($this->getCell($sql_now));//32bit-2038
			if(!$db_time) throw new Exception("db_time failed $db_type($sql_now)");
			$this->_cache_db_time=$db_time;
			$this->_cache_php_time=$php_now;
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
	public function getDbTimeZone(){
		//@ref http://stackoverflow.com/questions/2934258/how-do-i-get-the-current-time-zone-of-mysql
		//select timediff(now(),convert_tz(now(),@@session.time_zone,'+00:00'));
		//SELECT @@system_time_zone;
		//SELECT IF(@@session.time_zone = 'SYSTEM', @@system_time_zone, @@session.time_zone);
		//SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP);
		//@ref http://stackoverflow.com/questions/930900/how-to-set-time-zone-of-mysql
		//SELECT TIMEDIFF(NOW(), UTC_TIMESTAMP);
		throw new Exception('KO-TODO-getDbTimeZone');
	}
	//@ref ServiceDateTime->getDefaultDbTimeStamp() will call this.
	public function getDbTimeStamp(){
		return $this->db_time();
	}

	//NOTES: 利用 FILE IO 做缓存，再把 内存映射成目录来给FILE IO，目前应该是最快的本地缓存方案.
	public static function set_cache($key,$val,$lifetime=3600){
		return Cache_Disk::save("cache_$key",$val,$lifetime);
	}
	public static function get_cache($key,$lifetime=3600){
		return Cache_Disk::load("cache_$key",$lifetime);
	}

	//public function DATE_TOMORROW($time){
	//	if(!$time) $time=$this->db_time();
	//	return date('Y-m-d',$time+24*60*60);
	//}
	//public function DATE_TODAY($time){
	//	if(!$time) $time=$this->db_time();
	//	return date('Y-m-d',$time);
	//}
	//public function DATE_LASTDAY($time){
	//	if(!$time) $time=$this->db_time();
	//	return date('Y-m-d',$time-24*60*60);
	//}

	public function __call($func, $args){
		$call_ee=array($this->_default_bean, $func);
		if ( !is_callable($call_ee) ){
			throw new Exception("Unknown Func $func");
		}
		return call_user_func_array( $call_ee, $args );
	}
	
}

