//重要的事件消息为  initcomplete, GridAction和RowAction
//以后可能会增加 on data loaded
//外部方法： getData,getSchema,getSelected,getGrid,getCol,reload
var clsSimpleGrid=function(params){
	var _this_cls_name='clsSimpleGrid';
	var remoteapi = aj2014;
	var _hasHandler=function(o,event_name){
		//TODO 做JQ版本判断...
		//alert($().jquery);
		//alert($(o).data['events']);//旧版..
		var _event=$._data(o,"events") || {};//since jq 1.8...
		return _event[event_name];
	}
	//for hint at corner
	var my_msg=function(txt,title,timeout){
		if(!title) title='Hint';
		if(!txt) return false;
		//if($.messager)
		$.messager.show({
			title: title
			,msg: txt
			//, timeout: 30000
			//, showType: 'slide'
		});
	}
	//for alert at center modally
	var my_warn = function (txt, callback) {
		var _cb = callback
		|| (function () {
			return false;
		});
		$.messager.alert("Hint", txt, 'warning', _cb);
	}

	var _func_form2a=function(_fm){
		var _form_data = _fm.serializeArray();
		_fm.find("input:checkbox").each(function () {
			_form_data.push({name: this.name, value: this.checked});
		});
		return _form_data;
	}
	var _func_form2obj=function(_fm){
		var _a=_func_form2a(_fm);
		var rt={};
		for(var i=0;i<_a.length;i++){
			rt[ _a[i].name ]=_a[i].value;
		}
		return rt;
	}
	var _proc_toggle_filter_panel=function(){
		var _fp=me.params.filterPanel;
		if(_fp){
			_fp.toggle();
		}
	}

	////////////////////////////////////////////////////////////////////////
	//wrapper for async.waterfall
	var _proc_waterfall_wrap = function (func, timeout)
	{
		return function(){
			var _arg_a = arguments;
			var _callback = arguments[arguments.length - 1];
			//根据async.waterfall，因为参数是变化的，但是最后的参数就是原callback!
			try {
				func.apply(this, _arg_a);//不稳定参数数量用这个方法
				if (!timeout)
					timeout = 7000;//默认7秒金鱼极限，如果真的要等更久的call，就在wrap的第二个参数给一下.
				setTimeout(function () {
					if (!_callback.lastrst) {
						my_log("PossibleTimeout from:");
						my_log(func);
					}
				}, timeout);
			} catch (ex) {
				my_log(_this_cls_name+".UnexpectedError 710:");
				my_log(ex.stack);
				_callback(ex);
			}
		};
	};
	//wrapper for async.series
	var _proc_series_wrap= function(func, timeout){
		return function (_callback) {
			var _arg_a = arguments;
			try {
				//var _callback = arguments[arguments.length - 1];
				func(_callback);
				if (!timeout)
					timeout = 7000;//默认7秒金鱼极限，如果真的要等更久的call，就在wrap的第二个参数给一下.
				setTimeout(function () {
					if (!_callback.lastrst) //自己的约定.因为目前未有好办法判断一个function是否已经执行过..
					{
						my_log("PossibleTimeout from:");
						my_log(func);
					}
				}, timeout);
			} catch (ex) {
				my_log(ex.stack);
				my_log(_this_cls_name+".UnexpectedError 711:");
				my_log(func);
				_callback(ex);
			}
		};
	};
	//拿数或重新拿数【主要注意Pager】:
	var _proc_mggrid_load_remote_data= function(callback){
		$.messager.progress();
		async.series([
			_proc_series_wrap(_proc_mggrid_getdata)//拿数
			,_proc_series_wrap(_proc_mggrid_handledata)
			], function (err, async_result) {
				if(err) my_msg(err.message);
				if(callback) callback(err,async_result);
				$.messager.progress("close");
			}
		);
	};

	var _proc_mggrid_init_prepare = function(callback)
	{
		$.messager.progress();
		var _grid_div = me.params.targetDiv;
		_grid_div.empty();
		_grid_div.mygrid = null;//release
		$(me).off('GridAction').on("GridAction", function (evt, o)
		{
			if(!o) throw new Error('404 o at GridAction');
			var _grid = _grid_div.mygrid;
			if (!_grid) throw new Error("404 mygrid");
			switch(o.action){
				case 'Reload': _proc_mggrid_load_remote_data(); break;
				case 'Query': _proc_toggle_filter_panel(); break;
			}
			return false;
		});

		$(me).off('RowAction').on('RowAction',function(evt, o)
		{
			if(!o) throw new Error('404 o at RowAction');
			var _grid = _grid_div.mygrid;
			var _all_data_a = _grid.datagrid('getRows');//全部
			var _index = o.index;
			if (_index >= 0) {
				var _row_data = _all_data_a[_index];
				var _action_a=me.params.action_opt;
				var _action_hdl=null;
				if(_action_a[o.action]){
					_action_hdl=_action_a[o.action].handler;
				}
				if(_action_hdl && 'function'==typeof _action_hdl){
					_action_hdl(o.a,{values:_row_data,index:_index,options:me.params});//调用外部定义的action
				}
			} else {
				my_msg("UnexpectedError 706 " + o2s_tmp(o));
			}
			return false;
		});
		//这样可以让系统可以处理下一个时间片（单程技巧）
		setTimeout(function(){
			callback.lastrst = callback(null, null) || (new Date());
		},1);
	};//_proc_mggrid_init_prepare

	var _proc_handleFilter=function(filterPanel,params){
		if(!filterPanel) return false;
		var _fm=filterPanel.find('form');
		if(!_fm) return false;
		var _fo=_func_form2obj(_fm);
		$.extend(params,_fo);
	}
	
	var _proc_mggrid_getdata = function (callback)
	{
		var data_opt = me.params.data_opt;
		var list_default_opt = {
			onError: function (xhr, sts, err) {
				var _msg = (err && err.message) ? err.message : ("" + err);
				if (xhr && xhr.responseText) {
					_msg = xhr.responseText;
				}
				my_warn(_msg);
				throw err;
			}
			, onCallback: function (o) {
				if (!o) {
					callback.lastrst = callback(new Error("Return empty"), me.params) || (new Date());
					return false;
				}
				var errmsg = "";
				if (o && o.errmsg)
				{
					my_debug(o2s(o));//记录在窗口.
					errmsg = o.errmsg;
				}
				me._table_data=o.table_data || [];//for external
				if (errmsg) { my_msg(errmsg,'Server Message'); }
				me.params['remotedata'] = o;
				callback.lastrst = callback(null, null) || (new Date());
			}//onCallback
		};//list_default_opt
		var _data_opt = $.extend({}, list_default_opt, data_opt);
		if (!_data_opt._m) {
			throw new Error("data_opt incorrect "+o2s(data_opt));
			//throw new Error("data_opt incorrect ");
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

		var table_columns = o.table_columns || [[]];
		var _schema_line = table_columns[table_columns.length - 1];
		me._schema =_schema_line;//for external

		var _action_a = me.params.action_opt || {};
		//多行处理
		if(me.params.fMultiAction){
			_schema_line.splice(0, 0, {field:'ck',checkbox:true});
		}

		if(_action_a.length>0 || me.params.actionFormatter || me.params.iActionWidth){
			var _iActionWidth=me.params.iActionWidth || 240;
			var _action_opt={
				field: '_actions', title: getI18N('Actions'), width: _iActionWidth
				,formatter: function (value, row, index){
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
						_rt+=me.params.actionFormatter(value,row,index);
					}
					return _rt;
				}
			};
			_schema_line.push(_action_opt);
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
					if (_hdl){ return function(){ _hdl(); return false; } }
					return function(){
						var _this = $(this);
						$(me).trigger("GridAction", {a: _this, action: _key});
						return false;
					};
				};
				for (k in _ToolBar) {
					var _k = k;
					var _v = _ToolBar[_k] || {};
					var _text = _v.title || getI18N(_k);
					var _cls = (_v.iconCls?_v.iconCls:"icon-"+_k.toLowerCase());
					var _plain_s=(_v.plain!=false)?'plain="true"':'';
					var _tool_one=$('<a href="javascript:alert(718);" class="easyui-linkbutton" iconCls="'+_cls+'" '+_plain_s+'>'+_text+'</a>');
					_tool_one.on("click",_func_build_handler(_k,_v.onClick)//onClick从外部定义，有就用.
					);
					_tbBar_div.append(_tool_one);
				}
				_tbPanel.append(_tbBar_div);
				_tbPanel_flag=true;
			}
			if(me.params.filterPanel){
				//_tbPanel.append($(me.params.filterPanel).clone(true));//KO?
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
			//,autoRowHeight:false//better loading performance
			//, fitColumns: true
			, toolbar: _tbPanel
			, columns: table_columns
		};
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
						me.params.pageSize=pageSize;
						me.params.pageNumber=pageNumber;
						_proc_mggrid_load_remote_data(function(){});
					}
					,total: 1* o.maxRowCount//在后台可返回
					,pageSize:_pageSize//要搭配 total
				});
				_grid_div.pager = _pg;
			}
		}
		_grid.datagrid(_options);
		_grid_div.find(".mg-grid-action").off("click").on("click",function(evt)
		{
			var _this = $(this);
			var index = _this.attr("index");
			var action = _this.attr("action");
			$(me).trigger("RowAction", {a: _this, index: index, action: action});
			return false;//no bubble
		});
		$.parser.parse(_grid_div);
		callback.lastrst = callback(null, null) || (new Date());
	};//_proc_mggrid_handledata

	var _proc_mggrid_init_final = function (err, prev_result)
	{
		if (err) {
			my_msg(err.messager, 'Error');
		}
		//我们现在的应用不关心结果，只关心有没有异常，所以注释跳过忽略:
		//my_msg('result='+o2s(prev_result), 'Info');
		$.messager.progress("close");
		if(_hasHandler(me,'initcomplete')){
			$(me).trigger('initcomplete');
		}
		if(me.params.fLoadAfterInit===false){}
		else
		_proc_mggrid_load_remote_data();
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
			return _gd.datagrid("getSelected");//是数组.详情查 easyui datagrid
		}else{
			return null;
		}
	};
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
	//TODO :要把原始的filter给保存下来，给 _func.._reset用?

	var filterPanelId=me.params.filterPanelId;
	if(filterPanelId && !me.params.filterPanel){
		var _tbFilter_div=$('<div class="mg-grid-filterpanel"></div>');
		var s=mgrender_o({tpl_id:filterPanelId});
		_tbFilter_div.append(s);
		me.params.filterPanel=_tbFilter_div;
	}

	var _proc_init=function(){
		async.series([
			_proc_series_wrap(function(callback){
				callback.lastrst=callback()||(new Date());
			})
			,_proc_series_wrap(_proc_mggrid_init_prepare)//准备
			//,_proc_series_wrap(_proc_mggrid_getdata)//拿数
			//,_proc_series_wrap(_proc_mggrid_handledata)//处理
			],_proc_mggrid_init_final
		);
	}
	_proc_init();
};

var mgSimpleGrid = function (params)
{
	return new clsSimpleGrid(params);
}
