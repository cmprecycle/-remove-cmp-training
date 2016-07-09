<?php
class OrmBpmBase
	extends ORM_Base
{
	public function forceUpdateStatus($id,$status){
		//$bean=$this->dispenseBean();//rbWrapper
		//$bean->status=$status;
		//$this->store($bean);

		if(is_numeric($status)){
			//LET PASS
		}else{
			throw new Exception("forceUpdateStatus need status");
		}
		$this->update(array("id"=>$id,"status"=>$status));
	}
	//ugly works.
	public function tryLockStatus($id, $fromStatus, $toStatus, $flag_throw_ex){
		$rt=array("STS"=>"TODO");
		if(!$id){
			$errmsg="tryLockStatus needs id";
			require "inc.handle_bpme_error.php";
		}
		$table=$this->getBeanType();
		if(!$table){
			$errmsg="empty bean name";
			require "inc.handle_bpme_error.php";
		}
		try{
			$af=$this->exec("UPDATE $table SET status=?,lmt=now() WHERE id=? AND status=?",array(
				$toStatus,$id,$fromStatus
			));
		}catch(Exception $ex){
			$errmsg=$ex->getMessage();
			$errcode=$ex->getCode();
			require "inc.handle_bpme_error.php";
		}
		if( is_numeric($af) ){
			if( $af==1 ){
				$rt['STS']='OK';
			}else{
				$rt["af"]=$af;
				$errmsg="LockFailed.af".(($af===NULL)?'NULL':$af);
				require "inc.handle_bpme_error.php";
			}
		}else{
			$errmsg="LockFailed.af".(($af===NULL)?'NULL':$af);
			require "inc.handle_bpme_error.php";
		}
		return $rt;
	}
}


