<?php
#require_once "../inc.app.php";
require_once "../inc.ace.php";

for($engine_id=1;$engine_id<=2;$engine_id++){
	$bpme = new BPME;
	$bpme->setEngineId($engine_id);
	$unlock_result = $bpme->tryUnlock();
	println($unlock_result);
}

//TODO 针对状态1（lock）而且超时的进行反LOCK。。。

//临时的方法，后面还要再来优化才行（比如说解锁要手动 + 自动开新 engine）
//
//house keeping还要检查是不是有任务而且没有engine可用，也要做alarm/alert
