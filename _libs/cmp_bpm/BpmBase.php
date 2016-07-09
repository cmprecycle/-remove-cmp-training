<?php
//TODO going to move to upper level as AppBase ?
class BpmBase
	extends Observable
{
	public function __call($funcName, $args){
		//return call_user_func_array( array($this->_orm, $funcName), $args );

		$call_ee=array($this->_orm, $funcName);
		if ( !is_callable($call_ee) ){
			throw new Exception("Undefined Method $funcName");
		}
		return call_user_func_array( $call_ee, $args );
	}
}

