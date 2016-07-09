<?php
class ApiBPME
{
	public function CheckStatus($param,$json_obj){
		//TODO 如果没有 engine_id 就应该显示全部engine的状态.
		$engine_id=$param['engine_id'];
		//mg::checkCond( !$engine_id, array("engine_id") );
		BpmeTool::checkCond( !$engine_id, array("engine_id") );

		return LgcBPME::checkStatus($engine_id);
	}

	public function LockEngine($param){
		//检查触发心跳的源头.以防止hack
		LgcBPME::checkPulseIP();
		//TODO 正式点还要做 OAuth2
		return LgcBPME::forceUpdateStatus($engine_id,1);
	}
	public function UnlockEngine($param){
		//检查触发心跳的源头.以防止hack
		LgcBPME::checkPulseIP();
		//TODO 正式点还要做 OAuth2
		return LgcBPME::forceUpdateStatus($engine_id,0);
	}
	//@ref ApiBPME.pulse();
	public static function checkPulseIP(){

		$allow_pulse_ip_a=getConf("allow_pulse_ip_a");//TODO getConf if array(*) then all alow

		$client_ip = BpmeTool::getClientIP();
		if(! BpmeTool::isLocalIp($client_ip)){
			if( !in_array($client_ip, $allow_pulse_ip_a) ){
				//throw new Exception(BpmeTool::BuildMsg("NotAllowIP%s",array($client_ip)));
			}
		}
	}

	//心跳/通知引擎.
	public function Pulse($param){

		//检查触发心跳的源头.以防止hack
		self::checkPulseIP();

		$engine_id=$param['engine_id'];
		mg::checkCond( !$engine_id, array("engine_id") );
		BpmeTool::checkCond( !$engine_id, array("engine_id") );

		return LgcBPME::pulse($engine_id);
	}

	//TODO:
	//public function NewBP($param){
	//	return LgcBPME::createBP($param);
	//}
	//public function ContinueBP($param){
	//	return LgcBPME::continueBP($param);
	//}

	//NOTES: 当 BPME::handleWeb （类似cmp做法)解决后，这个函数拉口可以移除.
	//public function Activity($param){
	//	$bpme = new LgcBPME;
	//	$rt = $bpme->handle($param);
	//	return $rt;
	//}

	//ApiBPME.WebActivity.api?bpmn_c=$_c&bpmn_m=$_m
	//Round the Req-Bpme to BPME
	public function WebActivity($param){
		if( $param['bpmn_c'] )
		//TODO check the $param ... later... e.g. login? role?
		$bpme = new LgcBPME;
		$param['_c']=$param['bpmn_c'];
		$param['_m']=$param['bpmn_m'];
		$rt = $bpme->handle($param);
		return $rt;
	}
}

