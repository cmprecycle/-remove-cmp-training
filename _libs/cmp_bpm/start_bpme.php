<?php
/**
 * 驱动 BPME 的 心跳驱动入口。 远程/本地
 # 要每心跳 N 下就退出（免得php有内存错误)
 # $engine_id = 【业务流程引擎】 编号
 # 注：Local 模式要等代码稳定后才放到LIVE.
 */
require_once "../inc.app.php";

//TODO FUTURE: get from launch param if more then 1, and confirm only one for every number...by tryLock.
$engine_id=$argv[1];
if(!$engine_id) $engine_id=1;
$pulse_mode=0;//TODO 0 for remote mode, 1 for local mode.

function call_local_bpme_pulse($engine_id){
	$rt=LgcBPME::pulse($engine_id);
	return $rt;
}
function call_remote_bpme_pulse($engine_id){
	$DIRECTORY_SEPARATOR=DIRECTORY_SEPARATOR;
	$LIB=_LIB_;

	require_once "$LIB{$DIRECTORY_SEPARATOR}cmp_core{$DIRECTORY_SEPARATOR}inc.func.http_req.php";
	$url_api_engine_pulse=getConf("url_api_engine_pulse");//ApiBPME.Pulse.api
	return my_json_decode(http_req_quick($url_api_engine_pulse,array("engine_id"=>$engine_id),35));
}
function handle_result($rt){
	//TODO 判断要不要写一下 BPME Log & Send Alert
}
function handle_exception($ex){
	//TODO 判断要不要写一下 BPME Log & Send Alert
	println($ex);
}

//TODO 按理来说，Local模式目标当然是可以无限循环运行直到被杀，但如果每次心跳后判断内存是否合理，不合理就应该退出，以备下一个循环再进来
//TODO 心跳 理论上应该支持多并发，即应该支持 N个 核心在跑，一个核心的每一个心跳应该只在操作一个BP
//TODO swoole应该是这个设计的最佳实践思路： 如果收到心跳的驱动事件，就判断(看有没有对应的锁）是否需要新增一个核心去跑这个BP（其实直接新增核心同时看是否锁成功去做）
//等等，上面的思路不就是心跳的基本思路么？差少少的，因为事件驱动会有一点不同.
//如果未能应用 到swoole之前，就要用下面这种循环心跳的驱动模式，这样通过调整心跳间的时间差，来控制是否加速心跳.

//TODO 未有SWOOLE时的新算法：
// 1，心跳返回时 要判断目前还有要处理的 BP数 减去 正在工作的 工作线程数 去启动新程
// 2，或者以后直接启动 N个工作进程，互相去抢夺工作，但这样会有一个轮询任务的一个轮询压力，所以要么优化 工作堆栈的算法，要么 工作完的线程应该要有一个休息期，别轮询得太狠。。。

//TODO 下面只是单工作程去驱动心跳的临时算法，还不是优化算法.
//LgcBPME::startEngine($launch_mode_local_or_remote, $algorithm_id);
try{
	$N=($pulse_mode==1)?10:100;//shorter round for local mode
	for($i=0;$i<$N;$i++){
		print ($pulse_mode==1)?"$i,":"[$i] ";
		println(my_isoDateTime());
		$rt=($pulse_mode==1)?
			call_local_bpme_pulse($engine_id)
			:
			call_remote_bpme_pulse($engine_id);
		if(!$rt) println("Server Error?");
		println($rt);
		handle_result($rt);
		usleep(($pulse_mode==1)?200000:1000000);
	}
	exit;
}catch(Exception $ex){
	handle_exception($ex);
	usleep(200000);
}

