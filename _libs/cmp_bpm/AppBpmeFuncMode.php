<?php
//@ref LgcBPME::getEngineInstance()
//@ref LgcBPME::BPM_MODE_DEFAULT
//fo/FO/FlowObject: @ref http://share.cmptech.info/reference/BPM.zh-cn.utf8.htm?20160413b
class AppBpmeFuncMode
	extends Observable //get notify() and addEventListener()//if use tObservable then comment this.
	implements iObserver //for onEvent
{
	//use tObservable;//from 5.4... for our framework still need to support 5.3.X...
	public $Q;
	public $H;
	public function __construct(){
		$this->addEventListener( "taskEnqueueed", $this );
		if(!$this->Q) $this->Q=new SplQueue;
		if(!$this->H) $this->H=Array();
	}

	public function onEvent( $eventName, $info ){
		$fo_id=$info['fo_id'];

		if( $eventName!="taskEnqueueed" ) throw new Exception(__CLASS__ .".onEvent not support $eventName ");

		//TODO using different algorithm ( i.e. include()) to implement
		//$c=0;
		//do{
		//	$c++;

		//	if($c>3) break;//NOTES: for Default/Sync Mode, should will not pulse for ever:

		//	$flag_emptyQ = $this->pulse();
		//}while($flag_emptyQ);
	}

	//build fo_id
	//may be improved future.
	public function buildFlowObjectID(){
		list($sec,$seq)=mg::getTimeSequence();
		return "$sec.$seq";
	}

	//enqueue the FlowObject
	public function enqueueFlowObject( $options ){

		$fo = $options['fo'];
		$env = $options['env'];

		if ( isset($options['flagAutoNotify']) )
		$flagAutoNotify = $options['flagAutoNotify'];

		if ( isset($options['auto_create_bp_id']) ) $auto_create_bp_id = $options['auto_create_bp_id'];
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

	public function queryFlowObjectStatus( $fo_id ){
		$fo = $this->H[$fo_id];
		$fo_status = $fo['status'];
		return $fo_status;
	}

	//PURPOSE:
	//Query FlowObject Latest Result Until Timeout
	public function queryLatestResultUntilTimeout( $param )
	{
		$fo_id = $param['fo_id'];
		$bpm_timeout = $param['bpm_timeout'];
		$time_c = 0;
		$time_0 = time();
		if(!$timeout_query) $timeout_query= 99;//safenet. 'coz time is important..

		$fo_cursor_id = $fo_id;//the beginining cursor
		$flag_loop_done = false;
		$c=0;
		while(!$flag_loop_done){
			$time_c++;
			if($time_c>3) break;

			println("DEBUG $time_c");
			$time_now = time();
			$time_diff = $time_now - $time_0;
			if ( $time_diff > $timeout_query ) {
				throw new Exception(__CLASS__.'.'.__METHOD__ .".Timeout[$time_diff]", LgcBPME::ERRCODE_TIMEOUT);
			}

			//task at cursor
			$cursor_fo = $this->H[$fo_cursor_id];

			if( !$cursor_fo ){
				throw new Exception(__CLASS__.'.'.__METHOD__ .".FoundNotTask[$fo_cursor_id]", LgcBPME::ERRCODE_NOTFOUNDTASK);
			}

			if ( !isset($cursor_fo['result']) ){
				//not result yet, sleep 1 and next round
				sleep(1);
				continue;
			}

			if ( isset($cursor_fo['next_id']) ){
				//if next_id set, jump next
				$fo_cursor_id = $cursor_fo['next_id'];
				continue;
			}

			//TODO load the FSM, find next.
			//if should stop then stop and return the $cursor_fo;

			$cursor_task_result = $cursor_fo['result'];
			if( is_string($result) || is_null($result) ){
				//have result, but it is string or null, we think it's done.
			}

			//if not have result, wait

			$task_status_cursor = $cursor_fo['status'];
			$next_task_token_id = $cursor_fo['next_token_id'];

			//if need to stop then stop

			$status = $this->queryFlowObjectStatus( $task_token_id );
			if ( $status == '???' ){
				//
			}elseif ( $status == 'SHIFT' ){
				//$next_token_id = 
			}

			$status = $this->queryFlowObjectStatus( $task_token_id );
			//if ( $status == 'STOPPED' ){
			//	break;
			//}

			//TODO if stopped && has next curor
			//$fo_cursor_id = $next_token_id;
		}
		//return the latest step
		return array(
			'latest'=>array(//the final result after stop?
				'fo_id'=>$id_latest,//the token of latest activity, for engine to query result.
				'_m'=>$_m,//the final step after stopped.
				'STS'=>$STS,//TODO
			),
		);
	}

	//return: true means flag_has_next_to_run
	public function pulse(){

		//Pop FlowObject from Q
		$fo = $this->Q->dequeue();
		//println("TMP DEBUG fo=");
		//println($fo);

		if(!$fo) return false;

		$fo_id = $fo['fo_id'];
		if(!$fo_id){
			$tmpcode = mg::getBarCode( 8 );
			quicklog_must("IT-CHECK", "$tmpcode AppBpmeFuncMode{");
			quicklog_must("IT-CHECK", $Q);
			quicklog_must("IT-CHECK", $fo);
			quicklog_must("IT-CHECK", "$tmpcode AppBpmeFuncMode}");
			throw new Exception(__CLASS__.'.'.__METHOD__.'(): Unpected Error no fo_id Please check IT-CHECK log ('.$tmpcode.')');
		}

		$bp_id = $fo['bp_id'];
		if (!$bp_id ){
			$tmpcode = mg::getBarCode( 8 );
			quicklog_must("IT-CHECK", "$tmpcode AppBpmeFuncMode{");
			quicklog_must("IT-CHECK", $Q);
			quicklog_must("IT-CHECK", $fo);
			quicklog_must("IT-CHECK", "$tmpcode AppBpmeFuncMode}");
			throw new Exception(__CLASS__.'.'.__METHOD__.'(): Unpected Error no bp_id Please check IT-CHECK log ('.$bp_id.')');
		}

		$fo['status']=LgcBPME::FO_STATUS_LOCK;

		//Store to Hash for later use
		$this->H[$fo_id]=$fo;
		//$H=& $this->H;
		//$H->$fo_id=$fo;

		$_c=$fo['_c'];
		$_m=$fo['_m'];

		try{
			//process the Task

			//$bpmn_class = 'Bp'.$_c;//dont use $_c directly is more safe...
			////$bpo = class_exists($bpmn_class) ? ( new $bpmn_class ) : ( new BpBase($_c) );
			//$bpo = new $bpmn_class;

			$bpo = new BpBase('Bp'.$_c);

			$fo_result= array('STS'=>'TODO');

			//$bpm_a=$bpo->bpm_a;
			//if(!$bpm_a){
			//	throw new Exception('DESIGN_ERROR bpm_a empty');
			//}

			//if(method_exists($bpo,$_m)){
			//	$fo_result = $bpo->$_m( $fo );
			//}else{
			//	$fo_result = array('STS'=>'OK');//default
			//}

		}catch(Exception $ex){
			$fo_result['STS']='KO';//TODO UnpectedError???
			//store error into the task
			$fo_result['errmsg']=$ex->getMessage();
			$fo_result['errcode']=$ex->getCode();
		}
		$this->H[$fo_id]['result']=$fo_result;
		$fo_result_STS=$fo_result['STS'];

		return !$this->Q->isEmpty();
		
		//TODO ???
		//if failed to jump, it should be the design error !!!
		//should jump the "design-error-handler", if not defined, the call the $this->_DesignErrorHandler() !!!
		//or just if design error, then jump _DesignErrorHandler ....
		//how about change name as unexpected error? 
		//unexpected-error divided two part, one is from
		//$this->H[$fo_id]['design_error']=$design_error;

		//println("fo_result=");
		//println($fo_result);

		//println("H=");
		//println($this->H);
		
		$name2id=$bpm_a['name2id'];
		$bpm_flow_objects=$bpm_a['all'];
		$_m_id = $name2id[$_m];
		$_m_o = $bpm_flow_objects[ $_m_id ];
		$_m_type = $_m_o['type'];

		$FSM=$bpm_a['FSM'];
		$FSM_idx_src=$bpm_a['FSM_idx_src'];
		$links=$FSM_idx_src[$_m_id];

		if(!$_m_type) throw new Exception("Empty Type of Currenty FlowObject");

		println("fo_result=");
		println($fo_result);
		
		$links_c=count($links);
		if($links_c<1){
			//no where to go, just stop...
			$this->H[$fo_id]['status']=LgcBPME::FO_STATUS_DONE;
		}elseif(count($links)==1){
			//just one way to go, test the STS then

			println("links[".count($links)."]=");
			println($links);
			
			$link = $links[0];
			$link_id = $link['link_id'];

			$link_fo = $bpm_flow_objects[$link_id];

			if (!$link_fo) {
				if(!$_m_type) throw new Exception("DESIGN_ERROR FlowObject not found for id=$link_id");
			}
			$link_name = $link_fo['name'];

			if($link_name){
				$link_name_a=self::_parseLinkName($link_name);
				//TODO to handle the case of if STS is array !!!
				if( in_array( $fo_result_STS, $link_name_a ) ){
					//PASS if STS of current result is in the acceptable range
				} else {
					throw new Exception("DESIGN_ERROR STS not design flow STS("
						.BpmeTool::o2s($fo_result_STS)
						.") in range("
						.BpmeTool::o2s($link_name_a)
						.".");
				}
			}else{
				if( !$fo_result_STS )
				{
					//PASS if link_name and STS is both empty
				} elseif ( $fo_result_STS=='OK' ){
					//PASS too if link_name empty and STS='OK'
				} else {
					throw new Exception("DESIGN_ERROR STS not design ".BpmeTool::o2s($fo_result_STS));
				}
			}

			$next_id=$link_fo['id'];
			println("next_id=$next_id");

			//$next_fo = ???
			//if($next_fo){
			//	$this->enqueueFlowObject( $next_fo );
			//}
			//no need to trigger here, because next pulse will do
		}else{
			println("C:links[".count($links)."]=");
			println($links);
			
			//Sounds like a Gateway with multi output?
			if( BpmeTool::endsWith($type,"Gateway") ){
				//jump base on the name of the event
			}
		}
		
		if( BpmeTool::endsWith($_m_type, 'Task')
			|| BpmeTool::endsWith($_m_type, 'Activity')
		){
			println("Program{");
			println($_m_o);
			println("Program}");
		}elseif( BpmeTool::endsWith($_m_type, 'Gateway' )){
			println("Gateway{");
			println($_m_o);
			println("Gateway}");
		}elseif( BpmeTool::endsWith($_m_type, 'Event' )){
			println("Event{");
			println($_m_o);
			println("Event}");
		}else{
			throw new Exception("Not Supported FlowObject Type $_m_type");
		}

		println("links=");
		//println($links);
		foreach($links as $link){
			$tgt_id=$link['tgt'];
			$tgt_o = $bpm_flow_objects[ $tgt_id ];
			println($tgt_o);
		}
		
		//println("bpm_flow_objects=");
		//println($name2id);
		//println($bpm_flow_objects);
		//println("_m_o=");
		//println($_m_o);
		
		//Current=>Event:
		//Check only path, or fail?
		//
		//Current=>Activity
		//Base on the result
		//if is_array & STS => find links with STS => if next have not result (?how to know?) push the result?
		//==> easily plan, $new_fo['prev_fo_id']=$fo_id => enqueueFlowObject
		//
		//GateWay
		//if is_array & STS => find links with STS
		//if not result, should use previous result ( $prev_fo_id=



		// $bpo, $fo (current fo) => $fo_next

		//get possible choices

		//  for the name of the lnks if (link name)
		//  links name => rt['STS'] OK, jump
		//  if not match. if rt['STS'] is array ? if yest, find it,if found, then jump

		//if (link_name==''){
		//}
		//$type_current = $bpm_flow_objects[];


		//println("links=");
		////println($links);
		//foreach($links as $link){
		//	$tgt_id=$link['tgt'];
		//	$tgt_o = $bpm_flow_objects[ $tgt_id ];
		//	println($tgt_o);
		//}

		//if more then one choice, then base on the STS to find the best link
		//TODO the link info should have title as eventname, if no title, then should have properties...if don't know how to go, then throw BPMN-DESIGN_ERROR at the BPMN DB?
		//$link=$FSM_idx_src[$_m][0];
		//	if($link){
		//		$tgt=$bpm_flow_objects[$link["tgt"]];
		//		if($tgt){
		//			$token['todo_next']=$tgt;
		//		}
		//	}

		//$task=$bpm_flow_objects[$_m];
		//if($task){
		//}

		//store the result into the task?

		//check result to see if need to build and enqueue next task to Q
		//$bpm = new $_c;



		//TODO Trigger/Notify toCleanUp very old one.
		//Anyway, for sync local mode, Q/H have a short life already

		
		println("TMP DEBUG pulse() done");
		return !$this->Q->isEmpty();
	}

	//parse the name of the link into programmable array
	public static function _parseLinkName($name_s){
		//println( "name_s=$name_s ");
		$name_s=str_replace(' ','',$name_s);//ignore space
		$name_s=str_replace('\\',',',$name_s);//change \ into ,
		$name_s=preg_replace(//"/[\r\n]/m"
			'/[\s,\/]+/m',',',$name_s);//change space(includes \r\n) as comma for seperate
		//println( "name_s=$name_s ");
		$name_a=explode(',',$name_s);//finally make comma as delimiter
		//println( "name_a= ".BpmeTool::o2s($name_a));
		return $name_a;
	}

}















////////////////////////////////////////////////////////////////////////////
//if($_g_probe_time){
//	$_tm_[]=array("LgcBPME.handle();",microtime(true));
//	//$token['_tm_']=$_tm_;
//}

//if($rt!==null){
//	$query_result = $bpme->queryWithToken($rt);//TODO 取得对应的 BP状态

//	if( $query_result['STS']=='WAIT' ){
//		sleep(1);//TODO until after $bpm_timeout
//		$query_result = $bpme->queryWithToken($rt);
//		if($queryWithToken){
//			return $queryWithToken;
//		}
//	}
//	return $rt;
//}

//@ref index.php
//$bpName.$bpActivity.bpm
//public static function handleWeb(){
//	throw new Exception("handleWeb TODO");

//	//TODO 要参考 cmp 的处理（特别是 session)

//	//或者可以直接重用cmp，只是初始处理下就转去handle执行
//	$q=array_merge(array(),$_REQUEST);//TODO 还有json，看看怎么样配合或者结合CMP...
//	$bpme = new LgcBPME;
//	$rt=$bpme->handle($q);
//	if($rt===null){
//		//DO NOTHING
//	}elseif(is_string($rt)){
//		print $rt;
//	}else{
//		println($rt);
//	}
//}


//返回 BPM 对象，如果bpm_id为空就新建?
//public static function getBPM($bpm_id){
//	$bpm = new AppBP;
//	//TODO 
//	//Build Context:
//	return $bpm;
//}
////返回 BPM 数据
//public static function getBPM($bpm_id){
//}
//public static function ListBPM($param){
//	//查询 BPM 列表，难点应该在 "状态"，因为是非线性的啊
//}

//public static function getEngine($engine_id){
//	//TODO 如果缓冲区有，就从缓冲区拿?
//	$bpme = new AppBPME;
//	$bpme->setEngineId($engine_id);
//	return $bpme;
//}
//
//if($modeDB=='NonDB'){
//	//like Sync Mode
//	//TODO
//	$type=$t['type'];
//	$token['todo_current']=$t;
//	$FSM=$bpm_a['FSM'];
//	$FSM_idx_src=$bpm_a['FSM_idx_src'];
//	//script_format 以后要支持，现在默认当 format=php
//	$link=$FSM_idx_src[$_m][0];
//	$script_s="";
//	if($link){
//		$tgt=$bpm_flow_objects[$link["tgt"]];
//		if($tgt){
//			$token['todo_next']=$tgt;
//			$script_s=$tgt['script'];//TODO script_before/script_after
//		}
//	}
//	if($script_s){
//		//eval($script_s);
//		$token['todo_script']=$script_s;
//	}

//	if(!$timeout)
//		$timeout=30;//TMP TODO getConf()

//	$bpm_timeout=$param['bpm_timeout'];
//	if(!$bpm_timeout) $bpm_timeout=getConf("bpm_timeout");//TODO
//}else{
//	//DB mode, Insert into DB for every activity...
//	$BpmFlowObject = new BpmFlowObject;
//	$insert_rt = $BpmFlowObject->Insert(array(
//		"_c"=>$_c,
//		"_m"=>$_m,
//		"system_code"=>$system_code,
//		"env_s"=>BpmeTool::o2s($env), //目前用的是mysql，不是 kvdb/mongoDB , 序列化用着先. 有损耗和冗余也没办法，BP嘛，总是复杂些的.
//	));

//	$token['insert_rt']=$insert_rt;

//	$token['search_rt_debug']=$BpmFlowObject->SearchList(array("LIMIT"=>2));
//}

