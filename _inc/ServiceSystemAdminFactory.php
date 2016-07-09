<?php
class ServiceSystemAdminFactory
{
	public static function getInstance($mode='Local'){
		if($mode=='Local') return new ServiceSystemAdminLocalMode;
		else{
			throw new Exception('mode '.$mode.' is not yet supported');
		}
	}
}

