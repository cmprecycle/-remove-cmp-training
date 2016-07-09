var remoteapi = aj2014;
//wrapper for async.series
var _proc_series_wrap= function(func, timeout){
	return function (_callback) {
		var _arg_a = arguments;
		try {
			func(_callback);
			if (!timeout) timeout = 7000;//默认7秒金鱼极限，如果真的要等更久的call，就在wrap的第二个参数换一下.
			setTimeout(function () {
				if (!_callback.lastrst) //自己的约定.因为目前未有好办法判断一个function是否已经执行过..
				{
					my_log("PossibleTimeout from:");
					my_log(func);
					_callback(new Error('PossibleTimeout'));
				}
			}, timeout);
		} catch (ex) {
			my_log(ex.stack);
			my_log(".UnexpectedError 711:");
			my_log(func);
			_callback(ex);//NOTES:这只是告诉cb出错了，不一定说完全没有执行过返回结果的呢.
		}
	};
};
var _proc_series_wrap_result=function(callback,err,result){
	setTimeout(function(){
		callback.lastrst = callback(err, result) || (new Date());
	},1);
};
//for hint at corner:
var my_msg=function(txt,title,timeout){
	if(!timeout) timeout=7000;
	if(!title) title='Hint';
	if(!txt) return false;
	//if($.messager)
		$.messager.show({
			title: title
			,msg: txt
			,timeout: timeout
			//, showType: 'slide'
		});
}
//for alert at center modally:
var my_warn = function (txt, callback) {
	var _cb = callback || (function () { return false; });
	$.messager.alert("Warn", txt, 'warning', _cb);
}
//convert form data to array
var _func_form2a=function(_fm){
	var _form_data = _fm.serializeArray();
	_fm.find("input:checkbox").each(function () {
		_form_data.push({name: this.name, value: this.checked});
	});
	return _form_data;
}
//convert form data to obj(for remote api)
var _func_form2obj=function(_fm){
	var _a=_func_form2a(_fm);
	var rt={};
	for(var i=0;i<_a.length;i++){
		rt[ _a[i].name ]=_a[i].value;
	}
	return rt;
}
//Usage: rt+=_func_create_action(index,'Deposit',getI18N('Deposit'));
var _func_create_action=function(index,action,action_disp){
	var _cancel_bubble_s = "return false;";
	return  '<a href="javascript:alert(720);" index="' + index + '" action="' + action + '" class="easyui-linkbutton mg-grid-action" plain="true" onclick="' + _cancel_bubble_s + '">' + action_disp+ '</a>&nbsp;';
}

/////////////////////////////////////////////////////////////
//重要的事件消息为  initcomplete, GridAction和RowAction
//以后可能会增加 on data loaded
//外部方法： getData,getSchema,getSelected,getGrid,getCol,reload
//action_opt 表示每行都有，如果是特别的要补actionFormatter和处理 RowAction消息
//options: action_opt{} 通用的Action
//,iActionWidth:240
//...TODO 写一个简单的范例...
var clsSimpleGrid=function(params){
	var _this_cls_name='clsSimpleGrid';
	var _proc_toggle_filter_panel=function(){
		var _fp=me.params.filterPanel;
		if(_fp){
			_fp.toggle();
		}
	}
	//拿数或重新拿数【TODO Pager】:
	var _proc_mggrid_load_remote_data= function(callback){
		$.messager.progress();
		async.series([
			_proc_series_wrap(_proc_mggrid_getdata)
			,_proc_series_wrap(_proc_mggrid_handledata)
			], function (err, async_result) {
				if(err) my_msg(err.message);
				if(callback) callback(err,async_result);
				else $(me).trigger('DataLoaded');
				$.messager.progress("close");
			}
		);
	};
	var _proc_mggrid_init_prepare = function(callback)
	{
		//$.messager.progress();
		var _grid_div = me.params.targetDiv;
		_grid_div.empty();
		_grid_div.mygrid = null;//release

		//处理基本的GridAction
		$(me).off('GridAction').on("GridAction", function (evt, o)
		{
			if(!o) throw new Error('404 o at GridAction');
			var _grid = _grid_div.mygrid;
			if (!_grid) throw new Error("404 mygrid");
			switch(o.action){
				//暂时默认的行为只有Reload和开关查询panel
				case 'Reload': _proc_mggrid_load_remote_data(); break;
				case 'Query': _proc_toggle_filter_panel(); break;
			}
			return false;
		});

		//处理一些通用的RowAction(要结合params.action_opt)
		$(me).off('RowAction').on('RowAction',function(evt, o)
		{
			if(!o) throw new Error('404 o at RowAction');
			var _grid = _grid_div.mygrid;
			var _index = o.index;
			if (_index >= 0) {
				var _action_a=me.params.action_opt;
				if(!_action_a) return false;

				var _action_hdl=null;
				if(_action_a[o.action]){
					_action_hdl=_action_a[o.action].handler;
					if('function'==typeof _action_hdl){
						_action_hdl(o.a,{values:o.rowdata,index:_index,options:me.params});//调用外部定义的action
					}
				}
			} else {
				my_msg("UnexpectedError 706 " + o2s_tmp(o));
			}
			return false;
		});
		$(me).off('DataLoaded').on('DataLoaded',function(evt, o){
			var _onDataLoaded=me.params.onDataLoaded;
			if(_onDataLoaded){
				_onDataLoaded();
			}
		});
		_proc_series_wrap_result(callback,null,null);
	};//_proc_mggrid_init_prepare

	var _proc_handleFilter=function(filterPanel,params){
		if(!filterPanel) return false;
		var _fm=filterPanel.find('form');
		if(!_fm) return false;
		var _fo=_func_form2obj(_fm);
		$.extend(params,_fo);
	}
	
	var _proc_mggrid_getdata = function (callback){
		var data_opt = me.params.data_opt;
		var default_opt = {
			onError:function(xhr, sts, err){
				if (xhr && xhr.responseText) {
					_msg = xhr.responseText;
				}else{
					_msg = (err && err.message) ? err.message : ("" + err);
				}
				my_warn(_msg);
				//throw err;
				_proc_series_wrap_result(callback,err,me.params);
			}
			,onCallback: function (o) {
				if (!o) {
					_proc_series_wrap_result(callback,new Error('ReturnEmpty'),me.params);
					return false;
				}
				var errmsg = "";
				if (o && o.errmsg){
					my_debug(o2s(o));//记录在窗口.
					errmsg = o.errmsg;
				}
				if (errmsg) { my_msg(errmsg,'Server Message'); }//提示给用户
				me.params['remotedata'] = o;//暂时只缓存最近一次的grid数据对应的remotedata
				_proc_series_wrap_result(callback,null,null);
			}//onCallback from remoteapi
		};//default_opt
		var _data_opt = $.extend({}, default_opt, data_opt);
		if (!_data_opt._m) {
			my_log(o2s(data_opt));
			throw new Error("data_opt empty _m");
		}
		//pager
		if (!_data_opt._p) _data_opt._p = {};
		var _p = _data_opt._p;
		var _pageSize=me.params.pageSize;
		if (_pageSize) {
			_p.pageNumber=me.params.pageNumber;
			if (!_p.pageNumber) _p.pageNumber = 1;
			_p.pageSize=_pageSize;
		}
		//filter
		_proc_handleFilter(me.params.filterPanel,_p);

		remoteapi(_data_opt);
	};//_proc_mggrid_getdata

	var _proc_mggrid_handledata = function (callback)
	{
		var o = me.params.remotedata; if (!o) throw new Error("Remote Date Error");

		var _grid_div = me.params.targetDiv;

		me._table_data=o.table_data || [];//for external

		var table_columns = o.table_columns || [[]];
		var _schema_line = table_columns[table_columns.length - 1];
		me._schema =_schema_line;//for external

		var _action_a = me.params.action_opt || {};

		//多行处理:
		if(me.params.fMultiAction){
			_schema_line.splice(0, 0, {field:'ck',checkbox:true});
		}

		if(_action_a.length>0 || me.params.actionFormatter || me.params.iActionWidth){
			var _iActionWidth=me.params.iActionWidth || 300;
			var _action_option={
				field: '_actions', title: getI18N('Actions')
				,formatter: function (cellValue, row, index){
					var _rt = "";
					var _cancel_bubble_s = "return false;";
					for (k in _action_a)
					{
						var _a_title="";
						if(typeof _action_a[k]==="string"){
							_a_title=_action_a[k];
						}else{
							_a_title=(_action_a[k].title) || k;
						}
						_rt += '<a href="javascript:alert(719);" index="' + index + '" action="' + k + '" class="easyui-linkbutton mg-grid-action" onclick="' + _cancel_bubble_s + '">' + _a_title + '</a>&nbsp;';
					}
					if(me.params.actionFormatter){
						_rt+=me.params.actionFormatter(cellValue,row,index);
					}
					return _rt;
				}
			};
			if(_iActionWidth) _action_option['width']=_iActionWidth;
			_schema_line.push(_action_option);
			//_schema_line.push({field:'vacant',width:50,colspan:2});
		}

		var _grid = _grid_div.mygrid;
		if (!_grid){
			//easyUI的datagrid的方法是在同一层而不是子层做的...
			_grid = $("<div style='width:100%'></div>");
			_grid_div.prepend(_grid);
			_grid_div.mygrid = _grid;
		}

		var _ToolBar = me.params.ToolBar;
		var _tbPanel=_grid_div.find('.mg-grid-toolbar');
		if(_tbPanel.length<1){
			_tbPanel=$('<div class="mg-grid-toolbar"></div>');
			var _tbPanel_flag=false;
			if(_ToolBar){
				var _tbBar_div=$('<div style="margin-bottom:5px"></div>');
				var _func_build_handler = function (_key, _hdl) {
					if (_hdl){
						return function(){
							var _this = $(this);
							var _selected=_grid.datagrid("getSelections");//是数组.详情查 easyui datagrid
							_hdl({a: _this, action: _key, selections:_selected});
							return false;
						}
					}
					return function(){
						var _this = $(this);
							var _selected=_grid.datagrid("getSelections");//是数组.详情查 easyui datagrid
						$(me).trigger("GridAction", {a: _this, action: _key, selections:_selected});
						return false;
					};
				};
				for (k in _ToolBar) {
					var _k = k;
					var _v = _ToolBar[_k] || {};
					var _text = _v.title || getI18N(_k);
					var _cls = (_v.iconCls?_v.iconCls:"icon-"+_k.toLowerCase());
					var _plain_s=(_v.plain!=false)?'plain="true"':'';
					var _tool_one=$('<a href="javascript:alert(\'ACTION ERROR\');" class="easyui-linkbutton" iconCls="'+_cls+'" '+_plain_s+'>'+_text+'</a>');
					_tool_one.on("click",_func_build_handler(_k,_v.onClick)//onClick从外部定义，有就用.
					);
					_tbBar_div.append(_tool_one);
				}
				_tbPanel.append(_tbBar_div);
				_tbPanel_flag=true;
			}
			if(me.params.filterPanel && me.params.filterPanelId){
				//_tbPanel.append($(me.params.filterPanel).clone(true));//fail?
				_tbPanel.append(me.params.filterPanel);//OK? 未知有没有内存泄漏..
				_tbPanel_flag=true;
			}
			if(me.params.filterPanel){
				if(me.params.fShowFilterPanel){
					me.params.filterPanel.show();
				}else{
					me.params.filterPanel.hide();
				}
			}
			if(_tbPanel_flag) $.parser.parse(_tbPanel);
		}

		var _options = me.params.options;
		var _options_default = {
			data: o.table_data
			//,rownumbers:true
			//,singleSelect: true
			//,selectOnCheck:true
			//,checkOnSelect:true
			,rowStyler: function (index, row){
				if (index % 2 == 1) {
					return {'class':'saas_tr_Even'};
				} else {
					return {'class':'saas_tr_Odd'};
				}
			}
			,loadMsg:null
			//,loadMsg:'loading'//这个只配合url有效....
			//,autoRowHeight:false//better loading performance
			,fitColumns: true
			//,fit:true
			//,width:780
			, toolbar: _tbPanel
			, columns: table_columns
		};
		if(me.params.fMultiAction){
			_options_default.selectOnCheck=true;
			_options_default.checkOnSelect=true;
			_options_default.ctrlSelect=true;
		}else{
			_options_default.singleSelect=true;
		}
		if (_options) {
			_options = $.extend({}, _options_default, _options);
		} else {
			_options = _options_default;
		}

		//处理pager
		var _pageSize=me.params.pageSize;
		if (_pageSize) {
			var _pg=_grid_div.find('.easyui-pagination');
			if(_pg.length<1){
				_pg = $('<div class="easyui-pagination" style="border:1px solid #FFFFFF;"></div>');
				_grid_div.append(_pg);
				_pg.pagination({
					//showPageList:false,
					showRefresh:false,
					//layout:['first','links','last'],
					pageList:[_pageSize,50,100],
					displayMsg:"",
					onSelectPage: function (pageNumber, pageSize) {
						me.isReload = true;
						me.params.pageSize=pageSize;
						me.params.pageNumber=pageNumber;
						_proc_mggrid_load_remote_data();
					}
					,total: 1* o.maxRowCount//注：在后台返回才会让pager生效.
					,pageSize:_pageSize//要搭配 total才真正有用.
				});
			}else{
				//查询后reload表格数目发生变化，page也要重新变
				_pg.pagination({total: 1* o.maxRowCount//注：在后台返回才会让pager生效.
						,pageSize:_pageSize});
			}
			_grid_div.pager = _pg;
		}
		_grid.datagrid(_options);
		_grid_div.find(".mg-grid-action").off("click").on("click",function(evt)
		{
			var _this = $(this);
			var _index = _this.attr("index");
			var action = _this.attr("action");
			var _all_data_a = _grid.datagrid('getRows') || {};//全部:
			var _rowdata=_all_data_a[_index];
			$(me).trigger("RowAction", {a: _this, index: _index, action: action, rowdata:_rowdata});
			return false;//no bubble
		});
		if(me.isReload){//如果是重新加载的，就只是渲染表格，不渲染查询的div等其他的。
			$.parser.onComplete = function(){
				if(me.isReload){
					me.isReload = false;
					$.parser.parse($("td[field=_actions]"));
				}
			};
			$.parser.parse(_grid);
		}else
			$.parser.parse(_grid_div);
		_proc_series_wrap_result(callback,null,null);
	};//_proc_mggrid_handledata

	var _proc_mggrid_init_final = function (err, prev_result)
	{
		if (err) {
			my_msg(err.messager, 'Error');
		}

		//$.messager.progress("close");
		$(me).trigger('initcomplete');
		if(me.params.fLoadAfterInit===false){
		//跳过读数据...
		}
		else{
			_proc_mggrid_load_remote_data();
		}
	};

	//=======================  外部方法:
	//返回grid的所有数据: (WJC:其实是从后台返回来的最新的table_data
	this.getData=function(){
		//var _all_data_a = _grid.datagrid('getRows');//grid中的data跟后台提交过来的有少许不同...
		return me._table_data;
	};
	//其实是从后台返回来的最近的table_columns[0]
	this.getSchema=function(){
		return me._schema;
	};

	this.getSelected=function(){
		var _gd=me.getGrid();
		if(_gd){
			return _gd.datagrid("getSelected");//是選中的第一個！！！！详情查 easyui datagrid
		}else{
			return null;
		}
	};

	this.getSelections=function(){
		var _gd=me.getGrid();
		if(_gd){
			return _gd.datagrid("getSelections");//這個才是數組,详情查 easyui datagrid
		}else{
			return null;
		}
	};
	this.getIDS=function(id_field_name){
		var ss=grid_user.getSelections();
		var ids_a=[];
		for(i in ss){
			if(!id_field_name || id_field_name=='id')
				ids_a.push(ss[i].id);
			//TODO 如果 不是这个字段，再FIX这个函数....
		}
		var ids_s=(ids_a.join(','));
		return ids_s;
	}
	
	this.getGrid=function(){
		return me.params.targetDiv.mygrid;
	};
	//拿取schema中的指定列
	this.getCol=function(_fld){
		var _schema=me.getSchema() || [];
		for(var _k in _schema){
			if(_schema[_k].field==_fld){
				return _schema[_k];
				break;
			}
		}
		return null;
	};
	this.reload = function(_callback,_param){
		me.isReload = true;
		if(_param) me.params.data_opt = _param;//TODO 要解决 使用 原默认参数+filterPanel 还是叠加，还是只用这个_param
		_proc_mggrid_load_remote_data(_callback);
	}

	///////////////////////////////////////////////////////// 类初始化
	if (!params) {
		throw new Error(_this_cls_name+" need params");
		return false;
	};
	var targetDiv = params.targetDiv;
	if (!targetDiv) {
		var targetDivId = params.targetDivId;
		if (!targetDivId) {
			throw new Error("need targetDivId");
			return false;
		}
		targetDiv = $("#" + targetDivId);
	}
	//data_opt 主要是用来拿grid数据，即主grid的 table_data和 table_columns:
	if(!params.data_opt){
		throw new Error("need data_opt");
		return false;
	}

	var me=this;
	me.params = params || {};
	me.params['startTime'] = (new Date());
	me.params['targetDiv'] = targetDiv;

	//NOTES:filterPanelId是模板的id....
	var filterPanelId=me.params.filterPanelId;
	if(filterPanelId && !me.params.filterPanel){
		var _tbFilter_div=$('<div class="mg-grid-filterpanel"></div>');
		var s=mgrender_o({tpl_id:filterPanelId});
		_tbFilter_div.append(s);
		me.params.filterPanel=_tbFilter_div;
	}

	//TODO :要把原始的filter给保存下来，给 _func.._reload用:
	
	async.series([
		_proc_series_wrap(_proc_mggrid_init_prepare)
		],_proc_mggrid_init_final
	);
};
clsSimpleGrid.create_action=_func_create_action;

var mgSimpleGrid = function (params)
{
	return new clsSimpleGrid(params);
}


/** Example
		var grid_user=mgSimpleGrid({
			targetDivId:"divGrid"//目标操作区.
			//,fMultiAction:false
			,iActionWidth:300
			,pageSize:2//TODO,先用2来测试.
			,data_opt:{_c:"ApiUserMgmt",_m:"getTAList"}
			,filterPanelId:'tplFilter'//内嵌方式，用模板.
			//,filterPanel:$("#divFilter")//外部方式，直接用.
			//,fShowFilterPanel:true//是否一开始就显示filter,默认不显示
			,actionFormatter:function(cellValue,row,index){
				var rt="";
				if(!row) return rt;
				var _cancel_bubble_s = "return false;";
				//自定义actions:
				rt += '<a href="javascript:alert(720);" index="' + index + '" action="' + 'Edit' + '" class="easyui-linkbutton mg-grid-action" plain="true" onclick="' + _cancel_bubble_s + '">' + getI18N('Edit')+ '</a>&nbsp;';
				rt += '<a href="javascript:alert(720);" index="' + index + '" action="' + 'Config' + '" class="easyui-linkbutton mg-grid-action" plain="true" onclick="' + _cancel_bubble_s + '">' + getI18N('Config')+ '</a>&nbsp;';
				rt += '<a href="javascript:alert(720);" index="' + index + '" action="' + 'Test' + '" class="easyui-linkbutton mg-grid-action" plain="true" onclick="' + _cancel_bubble_s + '">' + getI18N('Test')+ '</a>&nbsp;';
				return rt;
			}
			//for GridAction:
			//Reload和Query是默认设计的两种功能.
			,ToolBar:{
				"Reload":{ title:getI18N("Reload") }
				,"Query":{ title:getI18N("Search") ,iconCls:'icon-filter' } //NOTES：要结合filterPanelId或者filterPanel
				,"Add":{
					title:getI18N("Add")
					,onClick:function(o){
						_func_open_crud_dlg(
						{"action": 'Add',values:{},a:o.a}
						,{tpl_crud_id:'tplCRUD',div_crud_id:'divCRUD'}
						);
					}
				}
			}
		});
 */
