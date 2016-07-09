<?php
class BpBase
{
	public $bpm_a;//the bpm data structure array

	//protected $_bpmn_name;
	public function __construct($bpmn_name){
		if(!$bpmn_name) throw new Exception("BpBase __construct need bpmn_name");

		$this->_bpmn_name=$bpmn_name;

		$bpmn_path=getConf("bpmn_path");
		if(!$bpmn_path) $bpmn_path=__DIR__ . "/";//default to same folder.

		$pos_file=$bpmn_path . $bpmn_name .".pos";
		if(file_exists($pos_file)){
			//processon pos file
			$this->bpm_a = BpmeTool::compile_processon_to_bpm($pos_file, $flgCache=true);
		}else{
			$bpmn_file=$bpmn_path . $bpmn_name .".bpmn";
			if(file_exists($bpmn_file)){
				//camunda bpmn file (TODO to tune future)
				$this->bpm_a = BpmeTool::compile_camunda_to_bpm($bpmn_file, $flgCache=true);
			}else{
				throw new Exception("BPMN NOT FOUND $bpmn_name");
			}
		}
	}

	//protected $__env;
	public function _setBPME($BPME){
		$this->__BPME=$BPME;
	}
	public function _getBPME(){
		return $this->__BPME;
	}
	
	public function defaultHandleFatalError($input){
		$output = array('STS'=>'FATAL');
		$output['errmsg']=$input['errmsg'];
		$errcode=$input['errcode'];
		$output['errcode']=$errcode;
		//$output['input']=$input;
		return $output;
	}
}

