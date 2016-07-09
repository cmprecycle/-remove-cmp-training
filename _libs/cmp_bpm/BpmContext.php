<?php
//Biz Proc Context
class BpmContext
	extends BpmBase
	//先用数据库，线性（string）来放非线性数据（主要是树状数组json式）
	//以后MongoDB成熟再优化
{
	public $_orm;//@ref BPME_Base
	public function __construct($dsn){
		if(!$dsn){
			$dsn = LgcBPME::getDefaultDSN();
		}
		$this->instanceId = date("ymdGis"). _getbarcode(6);
		$this->_orm=new OrmBpContenxt($dsn);
	}
}


