<?php
/**
 * Default Mode 适合【同步、单进程、不需要追踪业务BID的】的 较简单流程
 *
 * 其它
 * Func Mode 是函数体模式，适合更细粒度而且更高计算强度
 *
 * 需要跟踪业务ID的异步模式一般考虑 Orm 模式 （部分也可以用 Redis/MongoDB/ObjectDB/JsonDB/BsonDB等等模式代替Orm模式）
 *
 * 以后可能增加其它实现，比如
 * Swoole或ReactPHP，实现更丰富的异步编程
 *
 * 同时我们也准备开展 BPME-NODEJS 的研发
 *
 */
//@ref LgcBPME::getEngineInstance()
//@ref LgcBPME::BPM_MODE_DEFAULT
//fo/FO/FlowObject: @ref http://share.cmptech.info/reference/BPM.zh-cn.utf8.htm?20160413b
class AppBpmeDefaultMode
	implements iObservable,iObserver //for onEvent
{
	protected $_debug=3;
	//use tObservable;//trait from 5.4... seems for our framework still need to support 5.3.X...

	public $Q;//Queue
	public $H;//Hashtable

	public function __construct(){
		$this->_debug=quicklog(false);//the debug level

		//quicklog_must('BPM-DEBUG', '_debug='.$this->_debug);
		//quicklog_must('BPM-DEBUG', '_debug='.var_export(getConf('debug_a'),true));
		
		$this->addEventListener( "taskEnqueueed", $this );

		if(!$this->Q) $this->Q=new SplQueue;
		if(!$this->H) $this->H=Array();
	}

	public function onEvent( $eventName, $info ){
		if($this->_debug>2){
			quicklog_must('BPM-DEBUG', 'onEvent{'.$eventName);
			if($info){
				quicklog_must('BPM-DEBUG', 'info=');
				quicklog_must('BPM-DEBUG', $info);
			}
			quicklog_must('BPM-DEBUG', 'onEvent}');
		}
		if( $eventName!="taskEnqueueed" ) throw new Exception(__CLASS__ .".onEvent not support $eventName ");

		do{
			try {
				$flag_has_next_to_run = $this->pulse();
			} catch( Throwable $ex ) {
				quicklog_must('IT-CHECK-BPM', $ex->getMessage());
				quicklog_must('IT-CHECK-BPM', $ex->getCode());
				quicklog_must('IT-CHECK-BPM', $ex->getTraceAsString());

				$ssa = ServiceSystemAdminFactory::getInstance();
				$ssa->notify( 'IT-CHECK-BPM', $ex );
				throw $ex;
			}
		}while($flag_has_next_to_run);
	}

	//build fo_id, may be improved future.
	protected function buildFlowObjectID(){
		list($sec,$seq)=mg::getTimeSequence();
		return "$sec.$seq";
	}

	public function enqueueFlowObject( $options ){
		$fo = $options['fo'];

		if ( isset($options['flagAutoNotify']) )
			$flagAutoNotify = $options['flagAutoNotify'];

		if ( isset($options['auto_create_bp_id']) )
			$auto_create_bp_id = $options['auto_create_bp_id'];
		else $auto_create_bp_id=true;//default

		$fo['fo_id'] = $fo_id = $this->buildFlowObjectID(); 

		$bp_id=$fo['bp_id'];
		if ( !$bp_id ){
			if ( $auto_create_bp_id ){
				$bp_id = $fo_id;
				$fo['bp_id']=$bp_id;
			}else{
				throw new Exception(__CLASS__.'.'.__METHOD__.' need bp_id');
			}
		}

		$fo['status'] = LgcBPME::FO_STATUS_NEW;

		$this->Q->enqueue( $fo );

		if($flagAutoNotify)
			$this->notify("taskEnqueueed");
		return $fo_id;
	}

	//public function queryFlowObjectStatus( $fo_id ){
	//	$fo = $this->H[$fo_id];
	//	$fo_status = $fo['status'];
	//	return $fo_status;
	//}

	//PURPOSE:
	//Query FlowObject Latest Result Until Timeout
	//output: STRING | NULL | JSON
	public function queryLatestResultUntilTimeout( $param )
	{
		$fo_id = $param['fo_id'];
		$bpm_timeout = $param['bpm_timeout'];
		$time_c = 0;
		$time_0 = time();
		if(!$timeout_query) $timeout_query= 99;//safenet. 'coz time is important..

		$found_a=array();
		$fo_cursor_id = $fo_id;//the beginining cursor
		$c=0;
		do{
			$time_c++;

			$time_now = time();
			$time_diff = $time_now - $time_0;

			//NOTES: for Default Mode, loop few time then quit...
			if ( $time_c>9 ) break;

			if ( $time_diff > $timeout_query ) {
				throw new Exception(__CLASS__.'.'.__METHOD__ .".Timeout[$time_diff]", LgcBPME::ERRCODE_TIMEOUT);
			}

			$fo = $this->H[$fo_cursor_id];

			if ( !$fo ) {
				throw new Exception(__CLASS__.'.'.__METHOD__ .".FoundNoFlowObject[$fo_cursor_id]", LgcBPME::ERRCODE_NOTFOUNDTASK);
			}

			if ( !isset($fo['result']) ){
				//not result yet, sleep 1 and next round
				sleep(1);
				continue;
			}

			//NOTES: 如果是其它 Mode, 虽然有结果，但是要做权限检查...

			$cursor_result = $fo['result'];
			$cursor_status = $fo['status'];

			$next_fo_id=$fo['next_fo_id'];
			if($next_fo_id){
				if(is_string($next_fo_id)){
					$fo_cursor_id = $next_fo_id;//continue to find
				}elseif(is_array($next_fo_id)){
					//$fo_cursor_id = $next_fo_id;
					break;
				}else{
					//IT-CHECK
					throw new Exception('unknown next_fo_id='.var_export($next_fo_id,true));
				}
			}else{
				break;//break the loop
			}
		}while(true);

		return array(
			'fo'=>$fo,
			'fo_id'=>$fo_cursor_id,
			'result'=>$cursor_result,
			'status'=>$cursor_status,
			'next_fo_id'=>$next_fo_id,
		);
	}//queryLatestResultUntilTimeout

	//return as flag_has_next_to_run
	public function pulse(){

		//Pop FlowObject from Q
		$fo = $this->Q->dequeue();
		if(!$fo) return false;

		$fo_id = $fo['fo_id'];
		if(!$fo_id){
			$tmpcode = mg::getBarCode( 8 );
			quicklog_must("IT-CHECK-BPM", "$tmpcode AppBpmeDefaultMode{");
			quicklog_must("IT-CHECK-BPM", $Q);
			quicklog_must("IT-CHECK-BPM", $fo);
			quicklog_must("IT-CHECK-BPM", "$tmpcode AppBpmeDefaultMode}");
			throw new Exception(__CLASS__.'.'.__METHOD__.'(): Unpected Error no fo_id Please check IT-CHECK log ('.$tmpcode.')');
		}

		$bp_id = $fo['bp_id'];
		if (!$bp_id ){
			$tmpcode = mg::getBarCode( 8 );
			quicklog_must("IT-CHECK-BPM", "$tmpcode AppBpmeDefaultMode{");
			quicklog_must("IT-CHECK-BPM", $Q);
			quicklog_must("IT-CHECK-BPM", $fo);
			quicklog_must("IT-CHECK-BPM", "$tmpcode AppBpmeDefaultMode}");
			throw new Exception(__CLASS__.'.'.__METHOD__.'(): Unpected Error no bp_id Please check IT-CHECK log ('.$tmpcode.')');
		}

		$fo['status']=LgcBPME::FO_STATUS_LOCK;

		$this->H[$fo_id]=$fo;
		//$H=& $this->H;$H->$fo_id=$fo;

		$_c=$fo['_c'];
		$_m=$fo['_m'];
		$_p=$fo['_p'];
		$_m_type=$fo['type'];
		$prev_fo_STS_a=$fo['prev_fo_STS_a'];
		$prev_fo_id=$fo['prev_fo_id'];

		//$bpmn_class = $_c;
		if(!$_c){
			print('fo=');
			println($fo);
			throw new Exception('_c is empty');
		}
		$bpmn_class = 'Bp'.$_c;//safer if not to use $_c directly

		$bpo = new $bpmn_class( $bpmn_class );

		$bpo->_setBPME($this, $fo);

		$next_fo_a = null;
		try{
			do{
				if ( LgcBPME::DefaultHandleFatalError == $_m ) {
					if($this->_debug>2){
						quicklog_must('BPM-DEBUG', "$_m(");
						quicklog_must('BPM-DEBUG', $_p);
						quicklog_must('BPM-DEBUG', ")=");
					}
					
					$fo_result = $bpo->$_m( $_p );
					if($this->_debug>2){
						quicklog_must('BPM-DEBUG', $fo_result);
					}

					if(is_array($fo_result)){
						//将结果中的STS切成数组.
						$STS_a = self::_parseStsName($fo_result['STS']);
					}elseif(is_string($fo_result)){
						//假设是UI...
						$STS_a = array('UI');
					}else{
						throw new Exception("DefaultHandleFatalError Unsupported ".var_export($fo_result,true));
					}
					if($this->_debug>2){
						quicklog_must('BPM-DEBUG', 'STS_a=');
						quicklog_must('BPM-DEBUG', $STS_a);
					}
					
					break;//break do-while
				}//DefaultHandleFatalError

				$bpm_a=$bpo->bpm_a;
				if(!$bpm_a){ throw new Exception('DESIGN_ERROR bpm_a empty'); }

				//$STS_a=array();
				if(method_exists($bpo,$_m)){
					if($this->_debug>2){
						quicklog_must('BPM-DEBUG', "$_m(");
						quicklog_must('BPM-DEBUG', $_p);
						quicklog_must('BPM-DEBUG', ")=");
					}
					//TODO 如果template, the return is void/null, should get from buffer...what then?
					$s_before_exec_m = ob_get_clean();
					if($s_before_exec_m){
						if($this->_debug>2){
							quicklog_must('BPM-DEBUG', "s_before_exec_m=[$s_before_exec_m]");
						}
						
						$tmpcode = mg::getBarCode( 8 );
						quicklog_must("IT-CHECK-BPM", "$tmpcode s_before_exec_m{");
						quicklog_must("IT-CHECK-BPM", $s_before_exec_m);
						quicklog_must("IT-CHECK-BPM", $Q);
						quicklog_must("IT-CHECK-BPM", $fo);
						quicklog_must("IT-CHECK-BPM", "$tmpcode s_before_exec_m}");
						throw new Exception(__CLASS__.'.'.__METHOD__.'(): Unpected Error. Please check IT-CHECK log ('.$tmpcode.')');
					}

					@ob_start();
					$fo_result = $bpo->$_m( $_p );
					if($this->_debug>2){
						quicklog_must('BPM-DEBUG', $fo_result);
					}
					
					$s_after_exec_m = ob_get_clean();
					if($s_after_exec_m){
						//如果有输出，用输出来覆盖为结果.
						$fo_result=$s_after_exec_m;
					}
					if(is_array($fo_result)){
						$STS_a = self::_parseStsName($fo_result['STS']);
					}elseif(is_string($fo_result)){
						$STS_a = array('UI');
					}else{
						throw new Exception("$_m.Unsupported result= ".var_export($fo_result,true));
					}
					if($this->_debug>2){
						quicklog_must('BPM-DEBUG', 'STS_a=');
						quicklog_must('BPM-DEBUG', $STS_a);
					}
				}else{
					if($prev_fo_STS_a){
						if($this->_debug>2){
							quicklog_must('BPM-DEBUG', "override by prev_fo_STS_a=");
							quicklog_must('BPM-DEBUG', $prev_fo_STS_a);
						}
						$STS_a = $prev_fo_STS_a;
					}else{
						if($this->_debug>2){
							quicklog_must('BPM-DEBUG', "Default to OK for _m=$_m");
						}

						$STS_a = array($STS);
					}
					$fo_result = array('STS'=>'FATAL', 'errmsg'=>'Not found .'.$_m);
				}

				$bpm_flow_objects=$bpm_a['all'];
				$_m_o = $bpm_flow_objects[ $_m ];
				$_m_type = $_m_o['type'];
				if(!$_m_type) throw new Exception("DESIGN_ERROR cannot find FlowObject=".$_m);

				$_m_type_quick = self::_getType($_m_type, $_m_o);

				$idx_src = $bpm_a['idx_src'];
				$name2id = $bpm_a['name2id'];
				$id2name = $bpm_a['id2name'];
				$links = $idx_src[$_m];

				//////////////////////////////////////////////// calc next jump
				$next_fo_a_func=function() use ($_c,$fo_id,$STS_a,$links,$bpm_flow_objects){

					$next_fo_a=array();
					foreach($links as $link){
						$link_id = $link['link_id'];

						$link_fo = $bpm_flow_objects[$link_id];

						if (!$link_fo) {
							throw new Exception("DESIGN_ERROR FlowObject not found for id=$link_id");
						}

						$link_name = $link_fo['name'];
						if($link_name){
							$link_name_a=self::_parseStsName($link_name);
						}else{
							$link_name_a=array('OK');
						}
						$flag_should_jump=false;
						foreach($STS_a as $STS){
							if(in_array($STS, $link_name_a)){
								$flag_should_jump=true;break;
							}
						}
						if($flag_should_jump){
							$tgt=$link['tgt'];
							$_m_jump_o=$bpm_flow_objects[$tgt];
							$_m_jump = $tgt;
							$next_fo_a[]=array('_c'=>$_c, '_m'=>$_m_jump);
						}
					}
					return $next_fo_a;
				};

				$next_fo_a = $next_fo_a_func();

				//NOTES: array==null is true...特别小心...
				if($next_fo_a===null){
					if($this->_debug>0){
						//研究为什么无处可跳:
						quicklog_must('BPM-DEBUG', '_m_o=');
						quicklog_must('BPM-DEBUG', $_m_o);
						quicklog_must('BPM-DEBUG', 'bpm_flow_objects=');
						quicklog_must('BPM-DEBUG', $bpm_flow_objects);
						quicklog_must('BPM-DEBUG', 'idx_src=');
						quicklog_must('BPM-DEBUG', $idx_src);
						quicklog_must('BPM-DEBUG', 'name2id=');
						quicklog_must('BPM-DEBUG', $name2id);
						quicklog_must('BPM-DEBUG', 'id2name=');
						quicklog_must('BPM-DEBUG', $id2name);
						quicklog_must('BPM-DEBUG', 'prev_fo_id=');
						quicklog_must('BPM-DEBUG', $prev_fo_id);
						quicklog_must('BPM-DEBUG', 'prev_fo_STS_a=');
						quicklog_must('BPM-DEBUG', $prev_fo_STS_a);
					}
					throw new Exception('Fail to calc next jump for .'.$_m);
				}
			}while(false);

			//TODO 这个还要完善..特别是要记录栈层量，以防止递归等爆厂.
			if ( is_array($next_fo_a) ) {
				$next_fo_a_c = count($next_fo_a);
				if( $next_fo_a_c>1 ){
					//如果计算出有超过1个的方向跳转，可以理解为异步并发，如果不是平行网关暂时先不允许...
					if($_m_type!='ParallelGateWay'){
						if($this->_debug>0){
							quicklog_must('BPM-DEBUG', 'Not support multi jump if not a ParallelGateWay');
							quicklog_must('BPM-DEBUG', 'next_fo_a=');
							quicklog_must('BPM-DEBUG', $next_fo_a);
						}
						throw new Exception('Not support multi jump if not a ParallelGateWay');
					}
				}
			}
		}catch(Exception $ex){
			$errmsg=$ex->getMessage();
			$errcode=$ex->getCode();
			//NOTES: in this catch to handle the special case that BPMN DESIGN ERROR
			//if(!$fo_result){
			$fo_result['STS']='FATAL';
			$fo_result['errmsg']=$errmsg;
			$fo_result['errcode']=$errcode;
			//}
			$next_fo_a = array(
				array('_c'=>$_c,'_m'=>LgcBPME::DefaultHandleFatalError,
				'_p'=>array('errmsg'=>$errmsg,'errcode'=>$errcode)),
			);
			if($this->_debug>0){
				//研究为什么计算不出.
				quicklog_must('BPM-DEBUG', 'next_fo_a=');
				quicklog_must('BPM-DEBUG', $next_fo_a);
			}
		}

		$this->H[$fo_id]['status']=LgcBPME::FO_STATUS_DONE;
		$this->H[$fo_id]['result']=$fo_result;

		$next_fo_id_a = array();
		if ( is_array($next_fo_a) ) {
			foreach($next_fo_a as $next_fo){
				$next_fo['prev_fo_id']=$fo_id;
				$next_fo['prev_fo_STS_a']=$STS_a;
				$flagAutoNotify=false;
				$next_fo_id = $this->enqueueFlowObject(array(
					"fo"=>$next_fo,
					"flagAutoNotify"=>$flagAutoNotify,//true will notify() automatically
				));
				$next_fo_id_a[]=$next_fo_id;
			}
			$next_fo_a_c = count($next_fo_a);
			if( $next_fo_a_c==1 ){
				$this->H[$fo_id]['status']=LgcBPME::FO_STATUS_JUMP;
				$this->H[$fo_id]['next_fo_id']=$next_fo_id;
			}elseif( $next_fo_a_c>1 ){
				if($_m_type=='ParallelGateWay'){
					$this->H[$fo_id]['status']=LgcBPME::FO_STATUS_JUMP;
					$this->H[$fo_id]['next_fo_id']=$next_fo_id_a;
				}else{
					//should not come here, 'coz above already rejected
					throw new Exception('not support multi jump if not a ParallelGateWay');
				}
			}
		}

		return !$this->Q->isEmpty();
	}

	//Test  { [S1/S2]\nS3 } => array('S1','S2','S3');
	//parse the name of the link into programmable array
	protected static function _parseStsName($name_s){
		//println( "name_s=$name_s ");
		$name_s=str_replace(' ','',$name_s);//ignore space
		$name_s=str_replace('\\',',',$name_s);//change \ into ,
		//$name_s=preg_replace(//"/[\r\n]/m"
		//'/[\s,\[\]\/]+/m',',',$name_s);//change space(includes \r\n) as comma for seperate
		$name_s=preg_replace('/[^a-zA-Z0-9_]+/m',',',$name_s);//change space(includes \r\n) as comma for seperate
		//println( "name_s=$name_s ");
		$name_s=preg_replace('/^[,]+/m','',$name_s);//removed prefix , if any
		$name_s=preg_replace('/[,]+$/m','',$name_s);//removed surfix , if any
		//print "name_s=$name_s";
		$name_a=explode(',',$name_s);//finally make comma as delimiter
		//println( "name_a= ".BpmeTool::o2s($name_a));
		return $name_a;
	}

	protected static function _getType($_m_type, $_m_o){
		if( BpmeTool::endsWith($_m_type, 'Task')
			|| BpmeTool::endsWith($_m_type, 'Activity')
		){
			$_m_type_quick = LgcBPME::M_TYPE_PROGRAM;
		}elseif( BpmeTool::endsWith($_m_type, 'Gateway' )){
			$_m_type_quick = LgcBPME::M_TYPE_GATEWAY;
		}elseif( BpmeTool::endsWith($_m_type, 'Event' )){
			$_m_type_quick = LgcBPME::M_TYPE_EVENT;
		}else{
			throw new Exception("Not Yet Supported FlowObject Type $_m_type");
		}
		return $_m_type_quick;
	}

	//public getFirstFo(){

	//$env=$fo['env'];
	//if(!$env){
	//	//要取第一个的env...
	//	//$H0=array_slice($this->H,0,1,false);
	//	$H0=current($this->H);
	//	$env=$H0['env'];
	//	if(!$_p){
	//		$_p=$H0['_p'];
	//	}
	//}
	//$bpo->_setEnv($env);

	//}

	private $observers = array();

	public function addEventListener( $eventname, iObserver $observer )
	{
		if ( !isset( $this->observers[$eventname] ) ) {
			$this->observers[$eventname] = array();
		}

		foreach ( $this->observers[$eventname] as $o ) {
			if ( $o == $observer ) {
				return;
			}
		}

		$this->observers[$eventname][] = $observer;
	}

	public function notify( $eventname, $info )
	{
		if ( !isset( $this->observers[$eventname] ) ) {
			$this->observers[$eventname] = array();
		}

		foreach ( $this->observers[$eventname] as $observer ) {
			$observer->onEvent( $eventname, $info );
		}
	}
	
}

	/*public function queryResultUntilTimeout( $param )
	{
		global $_tm_;
		$_tm_[]=array("queryResultUntilTimeout;",microtime(true));
		$fo_id = $param['fo_id'];
		$bpm_timeout = $param['bpm_timeout'];
		$time_c = 0;
		$time_0 = time();
		if(!$timeout_query) $timeout_query= 99;//safenet. 'coz time is important..

		$found_a=array();
		$fo_cursor_id = $fo_id;//the beginining cursor
		$flag_loop_done = false;
		$c=0;
		while(!$flag_loop_done){
			$time_c++;

			$time_now = time();
			$time_diff = $time_now - $time_0;

			//NOTES: for Default Mode, loop one time quit.  
			if ( $time_c>1 ) break;

			if ( $time_diff > $timeout_query ) {
				throw new Exception(__CLASS__.'.'.__METHOD__ .".Timeout[$time_diff]", LgcBPME::ERRCODE_TIMEOUT);
			}

			$fo = $this->H[$fo_cursor_id];

			if( !$fo ){
				throw new Exception(__CLASS__.'.'.__METHOD__ .".FoundNoFlowObject[$fo_cursor_id]", LgcBPME::ERRCODE_NOTFOUNDTASK);
			}

			if ( !isset($fo['result']) ){
				//not result yet, sleep 1 and next round
				sleep(1);
				continue;
			}
			//NOTES: 如果是其它 Mode, 虽然有结果，但是要做权限检查...

			$result = $fo['result'];
		}
		$_tm_[]=array("before return result;",microtime(true));
		return array(
			'fo_id'=>$fo_id,
			'result'=>$result,
			//'fo'=>$fo,
			'next_fo_id'=>$fo['next_fo_id'],
			'_tm_'=>$_tm_,
		);
	} */
