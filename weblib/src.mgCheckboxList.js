/**
使用说明：
var _box=new checkBoxList(config);
config说明：
config.isEnum 说明这是最简单的list构造，只需要 config.data=['v1','v2','v3'] 
config.isDialog 如果true表示用的是easyui dialog模式，false的话，则可用.content嵌入任何容器
config.data and config.cols 一起做本地数据配置.(类似远程的 table_data和table_columns)
config.list_opt 远程获取cols和data(这里有约定，需要ajax返回的object存在.table_data和.table_columns)

config.valueFld 值对应的那一列
config.checkFld 是选中之后的值列名（如果缺省就是valueFld） 主要是 setChecked时有影响，要设置的值和要选中的列特别情况时的换名

handlers 暂时有onCreated(elm,grid)、onChecked(elm,selected)
*/
var checkBoxList=function(params){
	if(!params) {alert("Invalid Config");return;}
	var _grid=$("<div></div>");
	if(params.isDialog!==false){//说明是用dialog包装了grid的
		var _list=$('<div class="easyui-dialog" style="width:400px;padding:10px 20px"></div>');
		this.content=_list;
		_list.append(_grid);
	}else{
		var _list=$('<div></div>');
		this.content=_list;
		_list.append(_grid);
	}

	this.grid=_grid;
	this.cols=[];
	this.rows=[];
	var me=this;
	me.config=params;

	var initData=function(cb){//计算cols\rows
		if(params.isEnum===true){
			//最简单的数据,构造cols
			if(!params.data) {alert("Invalid Data");return;}
			me.cols.push({field:'enum',title:'enum'});
			me.config.valueFld="enum";
			for(var _k in params.data){
				if(typeof params.data[_k]==='string'){
					me.rows.push({"enum":params.data[_k]});
				}
			}
			cb();
		}else if(params.list_opt){
			var list_default_opt = {
				onError: function (xhr, sts, err) {
					var _msg = (err && err.message) ? err.message : ("" + err);
					if (xhr && xhr.responseText) {
						_msg = xhr.responseText;
					}
					my_debug("AJ Error=" + _msg);
					throw err;
				}
				, onCallback: function (o) {
					if (!o) {
						cb(new Error("Return empty")) || (new Date());
					}
					var flag_error = false;
					var errmsg = "";
					if (o && o.errmsg)
					{
						my_debug(o2s(o));//记录在窗口.
						errmsg = o.errmsg;
						flag_error = true;
					}
					me.rows=o.table_data || [];
					if (!o.table_columns) {
						if (!flag_error) {
							cb(new Error("Column Empty")) || (new Date());
							flag_error = true;
						} else {
							cb(new Error(errmsg)) || (new Date());
						}
					} else {
						//handle warning
						if (errmsg) {
							$.messager.show({
								title: 'Server Error'
								, msg: errmsg
								, timeout: 30000
								, showType: 'slide'
							});
						}
						me.cols=o.table_columns[0];
						cb(null);
					}
				}//onCallback
			};//list_default_opt
			var _list_opt=$.extend({},list_default_opt,me.config.list_opt);
			aj2014(_list_opt);
		}else{
			if(!params.columns) {alert("Columns is not define!");return;}
			if(!params.data) {alert("Invalid Data");return;}
			me.cols=params.columns;
			me.rows=params.data;

			cb();
		}

	}


	var initContent=function(cb){
		if(!me.cols){
			cb(new Error("Columns is not define!"));
		}

		if(me.config.isDialog!==false){
			var _dlg_default_options={
				modal:true,
				height:me.rows.length>10?412:300
				,buttons:[
					{text:'OK',iconCls:"icon-ok",handler:function(){
						if(me.config.handlers && me.config.handlers.onChecked){
							me.config.handlers.onChecked(me,me.getChecked());
						}
					}},
					{text:'Cancel',handler:function(){
						me.content.dialog("close");
					}}
				]
			};
			var _dlg_options=me.config.dialog || {};
			var _dlg_config=$.extend({},_dlg_default_options,_dlg_options);
			me.content.dialog(_dlg_config);
		}

		var _grid_default_options={
			columns:[me.cols],
			data:me.rows,
			fitColumns:true,
			showHeader:me.config.isEnum?false:true
			,height:me.rows.length>10?312:200
		};
		var _grid_options=me.config.grid || {};
		var _grid_config=$.extend({},_grid_default_options,_grid_options);
		me.grid.datagrid(_grid_config);

		if(cb) cb();
	}

	var init=function(){
		async.series([
			initData,
			//initContent
			],function(err,res){

				if(me.cols){
					me.cols.unshift({field:'checklist',checkbox:true});
				}
				if(me.config.isDialog==false){
					initContent();
					$.parser.parse(me.content);
				}
				if (err) {
					$.messager.show({
						title: 'Error'
						, msg: err.message
						, timeout: 30000
						, showType: 'slide'
					});
				}
				if(params.handlers && params.handlers.onCreated){
					params.handlers.onCreated(me,me.grid);
				}
			});


	}
	this.show=function(_callback){
		if(me.config.isDialog==false){
			throw new Error("The List do not support dialog!");
		}
		initContent();
		$.parser.parse(me.content);
	}
	this.hide=function(_callback){
		me.content.dialog("close");
	}
	this.setChecked=function(o){
		if(!me.grid) return;
		if(!me.rows) return;
		if(!o) return;
		if(!me.config.valueFld) return;
		if(me.config.isEnum===true){
			for(var _k in o){
				for(var _m in me.rows){
					var _item=me.rows[_m][me.config.valueFld];
					if(!_item) continue;
					if(o[_k].toString()===_item.toString()){
						me.grid.datagrid("selectRow",_m);
						break;
					}
				}
			}
		}else{
			for(var _k in o){
				for(var _m in me.rows){
					//修正了一下。因存在不同表字段不同的情况
					var _checkKey = me.config.checkFld?me.config.checkFld:me.config.valueFld;
					var _item=me.rows[_m][me.config.valueFld];
					if(!_item) continue;
					if(o[_k][_checkKey].toString()===_item.toString()){
						me.grid.datagrid("selectRow",_m);
						break;
					}
				}
			}//end for _k
		}//end else
	}
	this.getChecked=function(){
		if(!me.grid) return [];
		if(!me.config.valueFld) return [];
		var _rows=me.grid.datagrid("getSelections");
		var _rt=[];
		for(var _k in _rows){
			_rt.push(_rows[_k][me.config.valueFld]);
		}
		return _rt;
	}
	init();
}
/* 测试1
var _data=[];
for(var _m=0;_m<7;_m++){
_data.push("value"+_m.toString());
}
var _box=new checkBoxList({
isEnum:true,
data:_data
,handlers:{
onCreated:function(e,g){
e.show();
e.setChecked(['value2','value5']);
},
onChecked:function(rows){
console.log(rows);
}
},
dialog:{
title:"Please Select.."
}

});
*/

/* 测试2
var _data=[];
for(var _m=0;_m<7;_m++){
_data.push({id:_m,code:'code'+_m.toString(),remark:'remark'+_m.toString()});
}
var _cols=[{field:"id",title:'I18N_saas_auth_id',hidden:true},{field:'code',title:'I18N_saas_auth_Code'},{field:'remark',title:'I18N_saas_auth_Remark'}];
var _box=new checkBoxList({
columns:_cols,
valueFld:'id',
data:_data
,handlers:{
onCreated:function(e,g){
e.show();
e.setChecked(['2','6']);
},
onChecked:function(rows){
console.log(rows);
}
},
dialog:{
title:"Please Select.."
}

});
*/


/*/测试3
var _box=new checkBoxList({
list_opt:{_c:"ApiSaasSample",_m:"GetList",_p:{orm:'Saas_auth'}}
,valueFld:'auth_code'
,handlers:{
onCreated:function(e,g){
e.show();
//e.setChecked(['2','6']);
},
onChecked:function(rows){
console.log(rows);
}
},
dialog:{
title:"Please Select.."
}

});

*/
