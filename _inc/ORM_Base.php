<?php
/**
 * 定义标准接口 insert/update/upsert/searchOne/searchList/markDelete/realDelete
 * 其中:
 * 1, realDelete执行 sql delete (一般不建议!) ;
 * markDelete (一般是 set .status=-1, .lmt=$isoDateTime 然后等 housekeeping 例程(routine)来清理);
 *
 * 2, searchOne (理论上直接呼叫searchList并拿第一个，否则就返回null) 不建议重载
 *
 * 3, searchList返回的是记录集（一行一个记录对象），
 * 如果用于GRIDTABLE，表头等需要自己在Lgc逻辑层再处理
 */
//{
//	//必须实现:
//	public function searchList($param);//Remember to implement at sub-class
//	
//	//可重载
//	public function upsert($param);//通常是 insert/update的混合体，有时可以节省代码量.但一般外部不用呼叫，默认不够用的可以重载.
//	//public function Replace($param);//因为是整个换掉，跟upsert有点不同，逻辑也不些复杂，暂时未要求实现，需要时在子类写吧。
//	public function insert($param,$flag_just_id=false);
//	public function update($param,$flag_just_id=false);
//
//	public function searchOne($param);//get the first by calling searchList();
//
//	public function markDelete($param);
//	public function markDeleteAll($param);
//	public function realDelete($param);//not suggest to use unless you are sure
//	public function realDeleteAll($param);//please dont use unless you are very very sure.
//}
class ORM_Base
	extends rbWrapper4 //@ cmp_core\
{
	public static $DSN='db_app';
	//public $bean_name_a;//please override by the children

	//return Array of one, not bean
	//can use $one['id'] to get a bean with .loadBean($id);
	public function searchOne($param){
		$param['LIMIT']=1;
		$rs=$this->searchList($param);
		$rst=$rs['rst'];
		if($rst && count($rst)>0){
			return $rst[0];
		}
		return null;
	}
	//@ref https://en.wikipedia.org/wiki/Merge_(SQL)
	//MERGE 或 UPSERT:
	//1,将参数中的属性抄过去，跟 REPLACE 有不同，REPLACE 是把前者没有的参数都给删除掉....不好...
	//2,如果不存在就根据参数新建.
	//NOTES: 如果这个函数甚至这个类不合适用，可以在自己的空间重载!!!!!!!!!!!!!!!!!!!
	public function upsert($param,$flagNew=true){
		$id=$param['id'];
		$flagReallyNew=false;
		if($id){
			$bean=$this->loadBean($id);//rbWrapper
		}else{
			if($flagNew){
				$bean=$this->dispenseBean();//rbWrapper
				$flagReallyNew=true;
			}else{
				throw new Exception(getLang("KO-need-id"));
			}
		}
		//拆出变量:
		$field_name_a=$this->bean_name_a;

		////如果重载本函数，下面的代码可以参考{
		//eval(arr2var("param",$field_name_a));
		//变量写入属性:
		//eval(var2arr("bean",$field_name_a));
		////如果重载本函数，这段代码可以参考}

		//FILTER PARAMS
		//如果这个param在预定义的field_name_a中，就复制值过去
		foreach ($param as $kk=>$vv){
			if( in_array($kk,$field_name_a) ||
				array_key_exists($kk,$field_name_a))
			{
				$bean[$kk] = $vv;
			}
		}

		//******** 其它特别处理{
		//基本通用:
		$isoDateTime = $this->isoDateTime();
		$bean->lmt = $isoDateTime;//LastModifiedTime
		if( $flagReallyNew ){
			$bean->status=0;
			$bean->create_time = $isoDateTime;//Time When Create
		}
		//其他字段:
		//******** 其它特别处理}

		$id = $this->store($bean);
		return $id;
	}//upsert

	public function insert($param,$flag_just_id=false){
		$id=$this->upsert($param,true);
		if($flag_just_id) return $id;
		$rt = $this->loadBean($id);
		return $rt;
	}

	public function update($param,$flag_just_id=false){
		$id=$this->upsert($param,false);
		if($flag_just_id) return $id;
		$rt = $this->loadBean($id);
		return $rt;
	}

	public function markDelete($param){
		throw new Exception("DENY markDelete");
	}
	public function realDelete($param){
		throw new Exception("DENY realDelete");
	}
	public function markDeleteAll($param){
		throw new Exception("DENY markDeleteAll");
	}
	public function realDeleteAll($param){
		throw new Exception("DENY realDeleteAll");
	}

	//由子类覆盖的查找 => Array 函数.
	public function searchList($param){
		throw new Exception("searchList() Need Override");
	}

	//WARNING: duplicate item created if design not good.
	//try to find first, if found then return, if not found then do insert()
	public function findOrInsert($param,$flag_just_id=true){
		$one=$this->searchOne($param);
		if($one){
			if(!$flag_just_id) return $one;
			$id=$one['id'];
		}else{
			$id=$this->insert($param);
		}
		if($flag_just_id) return $id;
		$rt = $this->loadBean($id);
		return $rt;
	}
	
}
