<?php
class BpmFlowObject
	extends BpmBase
{
	public $_orm;
	public function __construct($dsn){
		if(!$dsn){
			$dsn = LgcBPME::getDefaultDSN();
		}
		$this->instanceId = date("ymdGis"). _getbarcode(6);
		$this->_orm=new OrmBpFlowObject($dsn);
	}
}

