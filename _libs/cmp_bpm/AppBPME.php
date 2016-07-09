<?php
class AppBPME
	extends BpmBase
{
	//public $_orm;
	public function __construct($dsn){
		if(!$dsn){
			$dsn = LgcBPME::getDefaultDSN();
		}
		$this->instanceId = date("ymdGis"). _getbarcode(6);
		$this->_orm=new OrmBPME($dsn);
	}

	public function getInstanceId(){
		return $this->instanceId;
	}

	//public $_engine_id;
	public function setEngineId($engine_id){
		if(!$engine_id) throw new Exception("setEngineId 404 engine_id");
		$this->_engine_id = $engine_id;
		return $engine_id;
	}

	public function getEngineId(){
		$engine_id=$this->_engine_id;
		if(!$engine_id) throw new Exception("getEngineId 404 engine_id");
		return $engine_id;
	}

	//public function findIdByEngineId($p){
	//	$id = $this->findOrInsert($p);
	//	return $id;

	//	//$bean_type=$this->getBeanType();
	//	//$engine_id=$p['engine_id'];
	//	//if(!$engine_id) throw new Exception("engine_id needed for findIdByEngineId");
	//	//$rsa=$this->find($bean_type,"engine_id=?",array($engine_id));
	//	//if(is_array($rsa) && count($rsa)>0){
	//	//	$found=array_pop($rt);
	//	//	$id=$found->id;
	//	//}else{
	//	//	//$found=$this->dispense($bean_type);
	//	//	//$found->engine_id=$engine_id;
	//	//	//$id=$this->store($found);
	//	//	$id=$this->Upsert($p);
	//	//}
	//	//return $id;
	//}

	public function tryLock($throw_ex=false){
		$engine_id=$this->getEngineId();
		$id = $this->findOrInsert(array("engine_id"=>$engine_id));
		return $this->tryLockStatus($id,0,1,$throw_ex);//OrmBPM_Base
	}

	public function tryUnlock($throw_ex=false){
		$engine_id=$this->getEngineId();
		$id = $this->findOrInsert(array("engine_id"=>$engine_id));
		return $this->tryLockStatus($id,1,0,$throw_ex);//OrmBPM_Base
	}

	//TODO......................
	public function checkAndRunTask(){
		//$engine_id=$this->getEngineId();
		sleep(2);//TODO
		$c=0;
		do{
			//查找待处理任务
			$task = $this->getTaskToDo();//BpmTask
			if($task){
				$rt_process = $this->processTask($task);
				if(BpmeTool::is_array_and_not_empty($rt_process)){
					$next_task = $this->findNextTask($task, $rt_process);//如果结果是对的，根据 $rt_process['STS']
					if ( $this->isAutoTask($next_task) ){
						$this->pushStack($next_task);
					}else{
						//TODO 如果是其它非 机 任务，先假设要等待，所以回填 状态?
					}
					$c++;
				}else{
					//TODO no result??
				}
			}else{
				//TODO write log?
			}
		}while($task);
		return $c;
	}

	//public function buildBpmTask($p){
	//	//from $p find the bp
	//	//
	//	//build new
	//	//BpmeTool::buildBpmContext($p);
	//	$ctx=new BpContext;
	//	$task=new BpTask;
	//	$task->setCtx($ctx);
	//}
	//array("name"=>,"bpm_name"=>);
	public function findTask($p){
	}
	public function getTaskToDo(){
	}
	public function processTask($task){
		$rt=false;
		//$rt_lock = $this->tryLockTask($task);
		$rt_lock = $task->tryLock();
		if($rt_lock){
			$rt_proceed = $task->proceed();
			//TODO
			/**
			 * 根据 Task的内容，尝试进行处理：
			 * bp 业务流程，相当于实例，正在处理的一个BP，如果涉及交易的话，Trx应该要有一个新字段 bp_id 来映射
			 * bpmn 业务流程流程标识，相当于类，是一个BP对应的【设计】，对应的是一个 bpmn 文件（编译后的xml=>php)
			 * task 任务，$task->bp_id => $bp
			 *   对应 bpmn里面要有个 task_pattern_id 才行，否则找到 bpmn 找不到 子id就定不了位
			 * 从 (activity_code, bpmn_name) => find 到 对应的 bpmn_task 的 task_subid
			 * 从bpmn类找到 这个 task_subid 的对应的 php代码(camunda:executionListener)，并执行
			 *
			 * $bp=$task->bp;
			 */
			//$rt=true;
			$task->tryUnlock();
		}
		return $rt;
	}

	//IN=array(
	//"bpmn_name"//BPMN Name, like $c
	//"bpmn_id"//optional, id of Biz Proc, if presented then 
	//"activity_code"//like $m
	//"task_id"//directly check result? TODO
	//), $timeout
	//OUT($token){
	//"ac"
	//}
}


