<?php
require_once "../inc.ace.php";

$s=file_get_contents("test0.bpmn");
BpmeTool::checkCond(!$s,array("bpmnfile"));

$xml=simplexml_load_string($s);
BpmeTool::checkCond(!$xml,array("bpmnxml"));

//$startEvent = $xml->xpath('*/bpmn:startEvent');
//$a=$startEvent[0]->attributes();
//println($a['id']);
//foreach($startEvent[0]->attributes() as $a => $b) {
//	echo $a,'="',$b,"\"\n";
//}
$startEvent = $xml->xpath('*/bpmn:startEvent');
foreach($startEvent as $e){
	//println($e);
	//println($e->attributes()['id']);
	//println($e->getName());
	println((string)$e->attributes()->id);
}
exit;
$bpme = new BPME;
$p=array(
	'bpmn_name'=>'test0',//通过name locate到id，否则异常；注意权限检查防黑
	//'bp_id'=>'',
);
$next_task = $bpme->buildTask($p);//TODO
$push_result = $bpme->pushTaskStack($next_task);
println($push_result);
sleep(3);
$bp_id=$push_result['bp_id'];
$p=array(
	'bp_id'=>$bp_id,
);
$query_result = $bpme->queryTask($p);//TODO 取得对应的 BP状态
println($query_result);

$query_result = $bpme->queryTask($p);
println($query_result);

/**
	BPMN 类
	BP 实例
	BP_Task BP.Task 实例的每一步
	简化成两种，一种是自动继续的，一种是非自动的（需要等其它参与）
 人机	=> API 层
 机 => Lgc层心跳
 BP_Event 
 */

/**
 * 测试启动一个BPMN实例
 *
 * 编译 BPMN XML=>BPMN缓冲
 *
 * 从 BPMN 查找任务
 *
 * 堆栈新BP及任务，静候处理结果.
 *
 * BPMN( Start=>End )
 */

