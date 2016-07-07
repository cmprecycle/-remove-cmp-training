<?php
class OrmDemo
	extends OrmBase
{
	public $NAME_R = 'tbl_note';

	public $bean_name_a = array(
		"note_key",
		"note_status",
		"note_remark"
	);

	public function SearchList($param){ throw new Exception("TODO"); }
	public function Upsert($param){ throw new Exception("TODO"); }
	//public function Insert($param){ throw new Exception("TODO"); }
	//public function Update($param){ throw new Exception("TODO"); }
	public function MarkDelete($param){ throw new Exception("TODO"); }
	public function RealDelete($param){ throw new Exception("TODO"); }

	public function Insert($ormInfo,$flag_just_id=false){
		//拆出变量:
		$field_name_a = $this->bean_name_a;
		eval(arr2var("ormInfo",$field_name_a));
		$bean=$this->dispenseBean($this->NAME_R);

		//变量写回属性:
		eval(var2arr("bean",$field_name_a));

		//保存.
		$id = $this->store($bean);
		if($flag_just_id) return $id;
		$rt = $this->loadBean($id);
		return $rt;
	}

	public function Update($ormInfo){
		$orm_id = $ormInfo['id'];
		$orm = $this->loadBean($orm_id);

		//拆出变量:
		$field_name_a = $this->bean_name_a;
		eval(arr2var("ormInfo",$field_name_a));

		//变量写回属性:
		eval(var2arr("orm",$field_name_a));

		$id = $this->store($orm);
		$rt = $this->loadBean($id);
		return $rt;
	}
}

