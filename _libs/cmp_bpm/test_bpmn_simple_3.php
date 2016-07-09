<?php
require_once "../inc.ace.php";

//is_array_and_not_empty
//$a=array("0"=>"0");
//var_dump( BpmeTool::is_array_and_not_empty( $a ));
//exit;

//compile_camunda_to_bpm
//$bpmn_path=getConf("bpmn_path");
//$bpmn_file=$bpmn_path
//	//."test0.bpmn"
//	."test1.bpmn"
//	;
//$rt=BpmeTool::compile_camunda_to_bpm($bpmn_file);////TODO check bpmn_file name as "[a-zA-Z0-9]" for security!
//#println($rt,true);


$bpme = new BPME;
/**
 * 启动新的 BPM 
 * $bpme->
 */
$q=array(
	'bpmn_name'=>'Bp_test1',//通过name locate到id，找不到就抛异常；【注意权限检查，防黑】
	'activity_id'=>'start',
	//bp_id=>$bp_id,
	//'timeout'=>3,//等多少秒，如果有结果就带上结果，如果没有结果，STS=WAIT，而且带上 bp_id 和 activity_id 
);

//Q: session怎么样处理，进入了 BPM之后，session就不能再写了哦，但有时是需要写session的？
//A: 对哦，所以 BPM 跟 USER SESSION要有所分开了，不能再有 SESSION的读写了，要交互数据要通过 context存取了.
//InitContext={ req, session }
//$baseContext=array($REQUEST,$GET,$POST,$SESSION,$SERVER);

//$ctxCurrent=BpmeTool::buildContext(array("SERVER"=>$_SERVER,"SESSION"=>$_SESSION,"REQUEST"=>$_REQUEST,"GET"=>$_GET,"POST"=>$_POST));

//$env=array("SERVER"=>$_SERVER,"SESSION"=>$_SESSION,"REQUEST"=>$_REQUEST,"GET"=>$_GET,"POST"=>$_POST);
//$timeout=30;

//$bpme->continueOrNewActivity($q,$baseContext);
//$token = $bpme->call($q,$baseContext);
//$token = $bpme->handle($q,$baseContext,$timeout);
//$token_handle = $bpme->handle($q,$ctxCurrent,$timeout);//简化再简化，一个入口搞定它.
//$token_handle = $bpme->handle($q,$env,$timeout=30);//简化再简化，一个入口搞定它.

$token_handle = $bpme->handle($q);//简化再简化，一个入口搞定它.
println($token_handle);

$query_result = $bpme->queryWithToken($token_handle);//TODO 取得对应的 BP状态
println($query_result);

if( $query_result['STS']=='WAIT' ){
	sleep(1);
	$query_result = $bpme->queryWithToken($token_handle);
	println($query_result);
}


//TODO 如果有bp_id，就用 activity_id 找到定义交新建任务、堆栈，等结果；
//如果有bp_id表示继续该BP，否则就可能是要新建，但是前提也是要看 activity_type 是否 StartEvent?

exit;
///////////////////////////////////////////////////

//$bpme->buildBpTaskAndPushStack();

//$q=>(BpnTask)
$next_task = $bpme->buildBpmTask($p);//TODO
$push_result = $bpme->pushTaskStack($next_task);
println($push_result);
sleep(3);
$bp_id=$push_result['bp_id'];
$p=array(
	'bp_id'=>$bp_id,
);

//正常等待引擎处理，这里先模拟心跳执行一次{
$bpme = new BPME;
$bpme->setEngineId(99);//模拟
$c=$bpme->checkAndRunTask();//TODO $bpme->updateStatus($engine_id, $status_a);//更新最新状态,注意历史.
//}

$query_result = $bpme->queryTask($p);//TODO 取得对应的 BP状态
println($query_result);

$query_result = $bpme->queryTask($p);
println($query_result);

