<?php
class ApiCommon
	extends WebCommon
{
	//给经典的html form提交用..
	/**
	wap 版:	
		var _data = _frm.serializeArray();
	_data.push({"name": "user_enable", "value": "1"});
	_data.push({"name":"nologin","value":"1"});
	wep 版:
		web版可以用src.SaasTool.js中的 SaasTool.form2a和form2o 动态决定怎么用.
	 */
	protected function form2obj($form_data){
		$rt=array();
		foreach($form_data as $k=>$v){
			$name=$v['name'];
			$value=$v['value'];
			if($name) $rt[$name]=$value;
		}
		return $rt;
	}

	protected function checkMandatory($arr,$key_a,$msg_tpl,$msg_args){
		foreach($key_a as $v){
			$f=$arr[$v];
			if($msg_tpl){
				if(is_array($msg_args)){
					$errmsg=vsprintf($msg_tpl,$msg_args);
				}else{
					$errmsg=$msg_tpl;
				}
			}else{
				$errmsg=getLang('KO-ApiCommon.checkMandatory')." $v";
			}
			if(!$f && $f!=='0') throw new Exception($errmsg);
		}
	}

	public $my_orm_class=null;
	//Get Default Orm Wrapper Class for Api Class.  For Coding Quick...
	protected function getOrmClass($dsn="db_app"){
		if(!$this->defaultBeanCls){
			throw new Exception(getLang("KO-NotDefine-defaultBeanCls"));
		}
		$ormCls=$this->defaultBeanCls;
		if(!$this->my_orm_class)
			$this->my_orm_class=new $ormCls($dsn);
		return $this->my_orm_class;
	}
	protected function _updateSession($to_update_a){
		session_start();
		foreach($to_update_a as $k=>$v){
			$_SESSION[$k]=$v;
		}
		session_write_close();
		return true;
	}

	public function ChangeLang(){
		$lang=$_REQUEST['lang'];
		$rt=array();
		if($lang){
			$this->_updateSession(array(
				"lang"=>$lang,
			));
		}
		$rt['STS']="OK";
		return $rt;
	}
}
