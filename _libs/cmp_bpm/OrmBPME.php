<?php
class OrmBPME
	extends OrmBpmBase
{
	public $NAME_R = LgcBPME::DEFAULT_BPME_TABLE;//'bpme';

	public $bean_name_a = array(
		'engine_id',
		//status
		//lmt
	);

	public function SearchList($param){
		$field_name_a=$this->bean_name_a;
		eval(arr2var("param",$field_name_a));

		//定制查询
		//$_status_like = $param['_status_like'];
		//$_status_in = $param['_status_in'];
		//$_status_not_in = $param['_status_not_in_like'];

		$rb=$this;

		//根据param构建查询条件:
		$where = "1=1";

		//if($status){
		//	$where .= " AND status=".qstr($status);
		//}
		//if($_status_like){
		//	$where .= " AND status LIKE ".qstr($_status_like);
		//}
		//if($_status_in){
		//	$where .= " AND status IN (".qstr_arr($_status_in) .")";
		//}
		//if($_status_not_in){
		//	$where .= " AND status NOT IN (".qstr_arr($_status_not_in) .")";
		//}
		if($engine_id){
			$where .=" AND engine_id=".qstr($engine_id);
		}

		//$_where_else= $param['_where_else'];
		//if($_where_else){
		//	$where.=$_where_else;
		//}
		//$minute=60*24;//one day
		//$_request_time_more_than = $param['_request_time_more_than'];
		//if($_request_time_more_than){
		//	//$where.=" AND request_time > NOW() - INTERVAL $minute MINUTE";
		//	$where.=" AND request_time > $_request_time_more_than";
		//}
		//if($_request_time_less_than){
		//	//$where.=" AND request_time > NOW() - INTERVAL $minute MINUTE";
		//	$where.=" AND request_time < $_request_time_more_than";
		//}
		$page_exec_param=array(
			//"SELECT"=>"*",
			"SELECT"=>"id",
			"FROM"=>$this->NAME_R,
			"WHERE"=>$where,
			"ORDERBY"=>"id DESC",
		);

		$pageSize=$p['pageSize'];
		if($pageSize>0){
			//$pageSize=1;
			$page_exec_param['pageSize']=$pageSize;
		}
		$pageNumber=$p['pageNumber'];
		if($pageNumber>0){
			//$pageNumber=1;
			$page_exec_param['pageNumber']=$pageNumber;
		}
		$LIMIT=$p['LIMIT'];
		if(!$LIMIT) $page_exec_param['LIMIT']=$LIMIT;

		//TODO binding

		$rs=$rb->PageExecute($page_exec_param);
		return $rs;
	}

}
