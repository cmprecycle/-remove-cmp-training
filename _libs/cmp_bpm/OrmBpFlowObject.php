<?php
/**
 * WARNING:
 * 大量的工作物累计会造成表过大，注意要做清洗家务：
 * 一、定期（比如每天把已经完成的 BP移到 BpFlowObjectHist，准备进行清洗）
 * 二、BpFlowObjectHist 的完全不关键的 BP 进行打包并移除 （假设 < 2天前的0点前）
 * 三、BpFlowObjectHist 的较为关键的 BP进行打包并投递到相关归档目标 （假设归档时间是 上个日历月
 */
class OrmBpFlowObject
	extends OrmBpmBase
{
	public $NAME_R = LgcBPME::DEFAULT_BP_FLOWOBJECT_TABLE;//'bpflowobject';

	public $bean_name_a = array(
		'bp_id',
		'system_code',//different system_code maybe same bpmn_name.   for Saas, reuse the tenant_code.
		'bpmn_name',
		'activity_code',
		'env_s',
		'type',
		'name',
		//status//auto gen from OrmBpmBase
		//lmt
	);
	public function SearchList($param){
		$rb=$this;

		$field_name_a=$rb->bean_name_a;
		eval(arr2var("param",$field_name_a));

		//定制查询
		//$_status_like = $param['_status_like'];
		//$_status_in = $param['_status_in'];
		//$_status_not_in = $param['_status_not_in_like'];
		//$_env_s_like = $param['_env_s_like'];


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
		if($system_code){
			$where .=" AND system_code=".qstr($system_code);
		}
		if($bpmn_name){
			$where .=" AND bpmn_name=".qstr($bpmn_name);
		}
		if($activity_code){
			$where .=" AND activity_code=".qstr($activity_code);
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
			"SELECT"=>"id",//TODO
			"FROM"=>$rb->NAME_R,
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

		//TODO binding..

		$rs=$rb->PageExecute($page_exec_param);
		return $rs;
	}

}
