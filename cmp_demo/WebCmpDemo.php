<?php
class WebCmpDemo
	extends ApiDemo
{
	public function DefaultIndex(){
		include($this->TPL("DefaultIndex","demo"));
	}

	//////////////////////////////////////////////////////
	//默认处理魔法函数.
	public function __call($__function__, $param_a){
		$sid=session_id();
		//$lang=$this->checkLang();
		//$device=$_SESSION['device'];
		include($this->TPL($__function__,"demo"));
	}
	/**
	 *  新增函数请放在 __call 上方 !
	 */
}
