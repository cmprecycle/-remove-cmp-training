<?php
class AppBpmeOrmMode
	extends Observable //get notify() and addEventListener()//if use tObservable then comment this.
	implements iObserver //for onEvent
{
	protected $debug=true;
	//use tObservable;//trait from 5.4... seems for our framework still need to support 5.3.X...

	public $Q;//Queue
	public $H;//Hashtable

	public function __construct(){
		$this->addEventListener( "taskEnqueueed", $this );

		if(!$this->Q) $this->Q=new SplQueue;
		if(!$this->H) $this->H=Array();
	}

	public function onEvent( $eventName, $info ){
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
	public function buildFlowObjectID(){
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

		$bpo->_setBPME($this, $fo);

		$next_fo_a = null;
		try{
			do{
				if ( LgcBPME::DefaultHandleFatalError == $_m ) {
					$fo_result = $bpo->$_m( $_p );

					if(is_array($fo_result)){
						$STS_a = self::_parseStsName($fo_result['STS']);
					}elseif(is_string($fo_result)){
						$STS_a = array('UI');
					}else{
						throw new Exception("DefaultHandleFatalError Unsupported ".var_export($fo_result,true));
					}
					break;//break do-while
				}

				$bpm_a=$bpo->bpm_a;
				if(!$bpm_a){ throw new Exception('DESIGN_ERROR bpm_a empty'); }

				//$STS_a=array();
				if(method_exists($bpo,$_m)){
					//TODO 如果template, the return is void/null, should get from buffer...what then?
					$s_before_exec_m = ob_get_clean();
					if($s_before_exec_m){
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
					$s_after_exec_m = ob_get_clean();
					if($s_after_exec_m){
						$fo_result=$s_after_exec_m;
					}
					if(is_array($fo_result)){
						$STS_a = self::_parseStsName($fo_result['STS']);
					}elseif(is_string($fo_result)){
						$STS_a = array('UI');
					}else{
						throw new Exception("$_m.Unsupported result= ".var_export($fo_result,true));
					}
				}else{
					$STS='OK';
					$fo_result = array('STS'=>$STS, 'fo_m'=>$_m);

					if($prev_fo_STS_a){
						$STS_a = $prev_fo_STS_a;
					}else{
						$STS_a = array($STS);
					}
				}

				$bpm_flow_objects=$bpm_a['all'];
				$_m_o = $bpm_flow_objects[ $_m ];
				$_m_type = $_m_o['type'];
				if(!$_m_type) throw new Exception("DESIGN_ERROR cannot find FlowObject=".$_m);
				//LgcBPME::M_TYPE_PROGRAM/GATEWAY/EVENT
				$_m_type_quick = self::_getType($_m_type, $_m_o);
				//println('_m_type_quick=');
				//println($_m_type_quick);
				$idx_src = $bpm_a['idx_src'];
				$name2id = $bpm_a['name2id'];
				$id2name = $bpm_a['id2name'];
				$links = $idx_src[$_m];

				//////////////////////////////////////////////// calc next jump
				$next_fo_a_func=function() use ($_c,$fo_id,$STS_a,$links,$bpm_flow_objects){

					//println('STS_a=');
					//println($STS_a);
					//quicklog_must("TMPDEBUG", '.STS_a=');
					//quicklog_must("TMPDEBUG", $STS_a);
					//quicklog_must("TMPDEBUG", '.links=');
					//quicklog_must("TMPDEBUG", $links);

					$next_fo_a=array();
					foreach($links as $link){
						$link_id = $link['link_id'];

						$link_fo = $bpm_flow_objects[$link_id];

						if (!$link_fo) {
							throw new Exception("DESIGN_ERROR FlowObject not found for id=$link_id");
						}

						$link_name = $link_fo['name'];

						if($link_name){
							//quicklog_must("TMPDEBUG", 'link_name=');
							//quicklog_must("TMPDEBUG", $link_name);

							$link_name_a=self::_parseStsName($link_name);

							$flag_should_jump=false;
							foreach($STS_a as $STS){

								//skip UI
								if($STS=='UI'){
									continue;
								}

								if(in_array($STS, $link_name_a)){
									$flag_should_jump=true;
								}
							}
							if($flag_should_jump){
								$tgt=$link['tgt'];
								$_m_jump_o=$bpm_flow_objects[$tgt];
								$_m_jump = $tgt;
								$next_fo_a[]=array('_c'=>$_c, '_m'=>$_m_jump);
							}

						}else{
							//quicklog_must("TMPDEBUG", 'STS_a=');
							//quicklog_must("TMPDEBUG", $STS_a);
							$link_name_a=array('OK');

							$flag_should_jump=false;
							foreach($STS_a as $STS){

								//skip UI
								if($STS=='UI'){
									continue;
								}

								if(in_array($STS, $link_name_a)){
									$flag_should_jump=true;
								}
							}
							if($flag_should_jump){
								$tgt=$link['tgt'];
								$_m_jump_o=$bpm_flow_objects[$tgt];
								$_m_jump = $tgt;
								$next_fo_a[]=array('_c'=>$_c, '_m'=>$_m_jump);
							}
						}
						//quicklog_must("TMPDEBUG", 'link_name=');
						//quicklog_must("TMPDEBUG", $link_name);
					}
					return $next_fo_a;
				};
				$next_fo_a = $next_fo_a_func();
				//array==null is true...
				if($next_fo_a===null){
					print('TMPDEBUG._m_o=');
					println($_m_o);
					println('bpm_flow_objects=');
					println($bpm_flow_objects);
					println('idx_src=');
					println($idx_src);
					println('name2id=');
					println($name2id);
					println('id2name=');
					println($id2name);
					println('prev_fo_id=');
					println($prev_fo_id);
					println('prev_fo_STS_a=');
					println($prev_fo_STS_a);
					throw new Exception('fail to calc next jump for '.$_m);
				}
			}while(false);

			if ( is_array($next_fo_a) ) {
				$next_fo_a_c = count($next_fo_a);
				if( $next_fo_a_c>1 ){
					if($_m_type!='ParallelGateWay'){
						print('next_fo_a=');
						println($next_fo_a);
						throw new Exception('not support multi jump if not a ParallelGateWay');
					}
				}
			}
		}catch(Exception $ex){
			$errmsg=$ex->getMessage();
			$errcode=$ex->getCode();
			//NOTES: in this catch to handle the special case that BPMN DESIGN ERROR
			if(!$fo_result){
				$fo_result['STS']='FATAL';//TODO UnpectedError???
				$fo_result['errmsg']=$errmsg;
				$fo_result['errcode']=$errcode;
			}
			$next_fo_a = array(
				array('_c'=>$_c,'_m'=>LgcBPME::DefaultHandleFatalError,
				'_p'=>array('errmsg'=>$errmsg,'errcode'=>$errcode)),
			);
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
	public static function _parseStsName($name_s){
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

	public static function _getType($_m_type, $_m_o){
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

}
