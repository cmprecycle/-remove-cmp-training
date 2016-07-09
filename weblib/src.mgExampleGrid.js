//
var remoteapi = aj2014;
/*mgGrid
*
* 回调handlers说明
*
* beforeShowCRUD(dlg,callback) ----这个是CRUD窗口显示前的回调，
* 注意：在函数执行完必须调用 callback()，这是要照顾里面是异步的情况
*
* beforeShowFilter(panel,callback)-----这个是Filter窗口显示前的回调，参数同上
*
* afterCreated(grid,easyui_grid)----这是grid创建完成的回调，作用巨大，很多外部操作可以在这里完成 【根本就没开始用....所以很可能还要改参数...】
* grid表示clsMgGrid的实例,easyui_grid是grid里的easyUI目标，content.datagrid({})
*
* e.g.要处理grid的行点击事件:
* clsMgGrid({
*	...
*	handlers:{
*		afterCreated:function(e,easyui_grid){
*			easyui_grid.datagrid({//easyUI的datagrid的所有原始操作
*				onClickRow:function(rowIndex, rowData){
*					//do sth....
*				}
*			});
*		}
*	}
* })
*
*todo：
1. 如果有defaultValues的filter条件合并
2. boolean的row-〉formatter
3. boelean类型的数据加载到form有bug
4. 改写clickRow或者删除
5. .off().on()的handler关联方式，只是模仿jq的事件而已
*
*/
var clsMgGrid=function(params){
	var my_warn = function (txt, callback) {
		var _cb = callback || (function () {
			return false;
		});
		$.messager.alert("Hint", txt, 'warning', _cb);
	}
	var remoteapi_default_opt = {
		onError: function (xhr, sts, err) {
			var _msg = (err && err.message) ? err.message : ("" + err);
			if (xhr && xhr.responseText) {
				_msg = xhr.responseText;
			}
			my_debug("AJ Error=" + _msg);
			throw err;
		}
		, onCallback: function (o) {
			if (!o)
				o = {STS: "EMPTY", "errmsg": "Server Return Nothing"};

			var flag_error = false;
			var errmsg = "";
			if (o && o.errmsg)
			{
				my_debug(o2s(o));//记录在窗口.
				errmsg = o.errmsg;
				flag_error = true;
			}

			$.messager.progress('close');

			if (o.STS == 'OK') {
				//my_warn(o.STS, function () {
					$(".easyui-dialog").dialog('close');
					$(me).trigger("GridAction", {a: null, action: "Reload"});
					//return false;
				//});
			} else if (o.STS == 'SKIP') {
				my_warn(o.STS);
			} else {
				my_warn(o.errmsg || o2s(o));
				flag_error = false;//skip the following hint...
			}

			if (flag_error) {
				$.messager.show({
					title: 'Server Message'
					, msg: errmsg
					, timeout: 30000
					, showType: 'slide'
				});
			}
		}
	};//remoteapi_default_opt

	var _func_form2o=function(_fm){
		var _form_data = _fm.serializeArray();
		_fm.find("input:checkbox").each(function () {
			_form_data.push({name: this.name, value: this.checked});
		});
		return _form_data;
	}
	var _func_toggle_filter=function(o){
		var _this=o.a;
		if(_this.attr("toggle")=="false"){
			_this.attr("toggle","true");
			if(me.params.filterPanel){
				me.params.filterPanel.hide();
			}
		}else{
			_this.attr("toggle","false");
			if(me.params.filterPanel){
				me.params.filterPanel.show();
			}
		}
	}
	var _func_handleFilter=function(){//处理filter的参数
		var _div=me.params.filterPanel;
		if(!_div) return;
		var _fm = _div.find("form");
		if(!_fm) return;

		//主要是从form获取数据,其实这个处理应该放在一个另一个类才好
		//外部自定义filter的时候可能一样需要format参数
		var _rd=_func_form2o(_fm);
		var _cols=me._schema || [];
		var _items={};
		for(var _i in _rd){
			var _v=_rd[_i].value;
			var _k=_rd[_i].name;
			for(var _ck in _cols){//这个效率好低压,一是必须作检查过滤，二是才能识别参数
				var _cname=_cols[_ck].field;
				if(_cname==_k){
					if(!_items[_cname]){
						_items[_cname]={};
						_items[_cname]['value']=_v;
					}else{
						_items[_cname]['value']=_v;
					}
					break;
				}

				if(_cname+"_start"==_k){
					if(!_items[_cname]){
						_items[_cname]={};
						_items[_cname]['relative']='between';
						_items[_cname]['value']=_v;
					}else{
						_items[_cname]['value']=_v;
					}
					break;
				}
				if(_cname+"_end"==_k){
					if(!_items[_cname]){
						_items[_cname]={};
						_items[_cname]['relative']='between';
						_items[_cname]['value2']=_v;
					}else{
						_items[_cname]['value2']=_v;
					}
					break;
				}
				if(_cname+"_relative"==_k){
					if(!_items[_cname]){
						_items[_cname]={};
						_items[_cname]['relative']=_v;
					}else{
						_items[_cname]['relative']=_v;
					}
					break;
				}
				if(_cname+"_value"==_k){
					if(!_items[_cname]){
						_items[_cname]={};
						_items[_cname]['value']=_v;
					}else{
						_items[_cname]['value']=_v;
					}
					break;
				}
				if(_cname+"_value2"==_k){
					if(!_items[_cname]){
						_items[_cname]={};
						_items[_cname]['value2']=_v;
					}else{
						_items[_cname]['value2']=_v;
					}
					break;
				}
			}
		}
		var _fp=[];
		for(var _i in _items){
			var _item=_items[_i];
			if(_item.value!==false && _item.value!==0){//这是checkbox不过滤
				if(_item.value=="" || !_item.value){
					continue;//认为这样的字段不是用户想过滤的范围
				}
			}
			_item.field=_i;
			if(!_item.relative) _item.relative="=";
			_fp.push(_item);
		}
		me.doFilter(_fp);
	}

	var _func_open_crud_dlg = function (o, passon) {
		var tpl_id=passon.tpl_crud_id;
		var div_id=passon.div_crud_id;

		var _div = $("#" + div_id);
		_div.off().empty();
		mgrender_quick(tpl_id, div_id, {action: o.action});

		var _dlg = _div.find(".easyui-dialog");
		var _fm = _div.find("form");

		var _func_on_confirm =function(){
			_fm.form('submit', {
				url: "javascript:alert(701);",
				onSubmit: function () {
					var _v = _fm.form('validate');
					if (_v) {
						var _form_data= _func_form2o(_fm) || {};
						//这里做default设置
						if(me.params.defaultValues){
							for(var _x in me.params.defaultValues){
								var _y=me.params.defaultValues[_x];
								//如果_form_data里有则覆盖
								var _form_item=null;
								for(var _j in _form_data){//这是form_data的结构，最好那结构可以改为key:value
									if(_form_data[_j].name==_x){
										_form_item=_form_data[_j];
										break;
									}
								}
								if(_form_item){
									_form_item.value=_y;
								}else{
									_form_data.push({name:_x,value:_y});
								}
							}
						}
						$.messager.confirm('Confirm ' + o.action, getI18N('R_U_SURE') + '?', function (r) {
							if (r) {
								var f_remote = true;
								var action_opt = {};
								switch (o.action) {
									case "Edit":
										action_opt = params.update_opt;
										break;
									case "Delete":
										action_opt = params.delete_opt;
										break;
									case "Add":
										action_opt = params.create_opt;
										break;
									default:
										f_remote = false;
										alert("TODO 706 " + o.action);
								}
								if (f_remote) {
									$.messager.progress();
									var _data_opt = $.extend({}, remoteapi_default_opt, action_opt);
									if (!_data_opt._m) {
										throw new Error("_opt incorrect");
									}
									if(!_data_opt._p){
										_data_opt['_p'] = _form_data;
									}else{
										_data_opt._p.form_data=_form_data;
									}
									remoteapi(_data_opt);
								}
							}
							return false;//no bubble
						});
					} else {
						$.messager.show({
							title: 'Hint'
							, msg: 'Input Not Correct!!!'
							, timeout: 3000
							//,showType:'slide'
						});
					}
					return false;//no bubble
				}
				,success: function(result){}
			});
		};

		_dlg.dialog({
			title: o.action
			, iconCls: 'icon-' + o.action//TODO 做个简单翻译，把找不到的换一个.
			, top: this.window.document.body.scrollTop + 100//因为_dlg的高度是变化的，不能计算正中，也许应该写在onShow之类的func里
			//,width: 400
			//,height: 200
			,closed: true
			,resizable: true
			,cache: false
			//,href: 'get_content.php',
			,modal: true
			,buttons: [{
				text: o.action
				, handler: _func_on_confirm
			}, {
				text: 'Cancel'
				, handler: function () {
					_dlg.dialog('close');
				}
			}]
			,onClose: function () {
				_dlg.dialog('destroy');
			}
			,onDestroy: function(x){}
		});
		//因动态加载select的话。form不会赋值。原因是先赋值了后去渲染。先改到下面渲染完了再复制。by zhb
//		if (o.action != 'Add')
//			_fm.form('clear').form('load', o.values);

		async.series([
			function(cb){
				if(passon.handlers.beforeShowCRUD){
					passon.handlers.beforeShowCRUD(_dlg,function(){
						cb(null,1);
					});
				}else{
					cb(null,1);
				}
			},
			function(cb){
				$(window).off('keydown').on('keydown', function (e) {
					if (e.keyCode == 27) {
						$(".panel-tool-close").trigger("click");
					}
				});
				//setTimeout(function(){
					_dlg.dialog('open');
					$.parser.parse(_dlg);
					//因动态加载select的话。form不会赋值。原因是先赋值了后去渲染。先改到这里渲染完了再复制。by zhb
					if (o.action != 'Add')
						_fm.form('clear').form('load', o.values);
					cb(null,2);
		//},3333);
			}
			],function(err,results){});

	};//_func_open_crud_dlg

	////////////////////////////////////////////////////////////////////////
	//为了更好地使用async.waterfall，所以做简单封装，把异常给接管下来.
	var _func_wrap = function (func, timeout)
	{
		return function (callback) {
			var _arg_a = arguments;
			var _callback = arguments[arguments.length - 1];//根据async.waterfall，最后的参数就是原定的callback...
			try {
				func.apply(this, _arg_a);
				if (!timeout)
					timeout = 7000;//默认7秒金鱼极限，如果真的要等更久的call，就在wrap的第二个参数给一下.
				setTimeout(function () {
					if (!_callback.lastrst) {
						my_log("PossibleTimeout from:");
						my_log(_callback);
					}
				}, timeout);
			} catch (ex) {
				my_log("clsMgGrid.UnexpectedError:");
				my_log(ex.stack);
				_callback(ex);
			}
		};
	};//_func_wrap
	var _func_mg_crud_refresh=function(passon){//这是供刷新按钮用
		var _p=passon.list_opt._p;
		if(_p){
			_p.filter=[];//清空fitler，todo:如果grid有设置默认的filter应该要用另一个参数来和这个合并
		}
		_func_mg_crud_reload(passon);

	};
	var _func_mg_crud_reload = function (passon) {
		$.messager.progress();
		async.waterfall([
			function (callback) {
				//需要去掉list_opt里的filter
				var _p=passon.list_opt._p;
				if(!_p) _p={};
				if(passon.showPager==true){
					if(!_p.page) _p.page=1;
					if(!_p.pageSize) _p.pageSize=10;
					if(passon.targetDiv){
						if(passon.targetDiv.pager){
							_p.page=passon.targetDiv.pager.pagination("options").pageNumber;
							_p.pageSize=passon.targetDiv.pager.pagination("options").pageSize;
						}
					}
				}
				callback(null, passon);
			},
			_func_mggrid_getdata, //拿数
			_func_wrap(function (prev_result, callback) {
				var o = prev_result.remotedata;
				if (o && o.table_data) {
					var _grid_div = prev_result.targetDiv;
					var _grid = _grid_div.mygrid;

					_grid_div.find(".mg-grid-action", _grid_div).off("click");
					_grid.datagrid("loadData", o.table_data);
					_grid_div.find(".mg-grid-action", _grid_div).on("click", function (evt)
					{
						var _this = $(this);
						var index = _this.attr("index");
						var action = _this.attr("action");
						$(me).trigger("RowAction", {a: _this, index: index, action: action});
						return false;//no bubble
					});
					if (_grid_div.pager) {
						_grid_div.pager.pagination({
							total: o.maxRowCount
						});
					}
				}
				callback.lastrst = callback(null, prev_result) || (new Date());
			})
			], function (err, prev_result) {
				$(".panel-tool-close").trigger("click");
				$.messager.progress("close");
			}
		);
	};

	var _func_open_crud_multidel = function (passon)
	{
		var _grid_div=params.targetDiv;
		var _grid = _grid_div.mygrid;
		if(!_grid){alert("Error 810");return false;}
		var _checked=_grid.datagrid("getChecked");
		if(_checked.length>0){
		}else{
			my_warn('Select None?');
			return false;
		}
		var ckval=[];
		for(i in _checked){
			ckval.push(_checked[i].id);
		}
		var selectedvalue = ckval.join(",");
		$.messager.confirm('Confirm Multi Delete', getI18N('R_U_SURE') + '?', function (r) {
			if (r) {
				$.messager.progress();
				var action_opt = params.multi_delete_opt;
				var _data_opt = $.extend({}, remoteapi_default_opt, action_opt);
				if(_data_opt._p){
					_data_opt._p.ids=selectedvalue;
				}else{
					_data_opt['_p'] = selectedvalue;
				}

				if (!_data_opt._m) {
					throw new Error("707 _opt incorrect");
				}
				remoteapi(_data_opt);
			}
			return false;//no bubble
		});
	};

	var _func_mggrid_init_prepare = _func_wrap(function (passon, callback)
	{
		$.messager.progress();

		var _grid_div = passon.targetDiv;

		_grid_div.empty();

		_grid_div.mygrid = null;//release

		$(me).off();

		$(me).on("GridAction", function (evt, o)
		{
			try
			{
				var _grid = _grid_div.mygrid;
				if (!_grid)
					throw new Error("404 mygrid");

				if (o.action == 'Add') {
					_func_open_crud_dlg(
						{"action": 'Add'}
						,passon
					);
				} else if (o.action == 'Reload') {
					_func_mg_crud_refresh(passon);//Query List again
				} else if (o.action == 'Filter'){
					_func_toggle_filter(o);
					//_func_open_filter_dlg({"action":'Filter'},passon);
				} else if (o.action == 'Remove') {//Multi Delete
					_func_open_crud_multidel(passon);
				} else {
					alert("TODO 709 " + o2s_tmp(o));
				}
			} catch (ex) {
				alert("GridAction.UnexpectedError=" + ex);
				my_debug(ex);
			}
			return false;
		});

		$(me).on("RowAction", function (evt, o)
		{
			try
			{
				var _grid = _grid_div.mygrid;
				var _all_data_a = _grid.datagrid('getRows');//全部
				var _index = o.index;
				if (_index >= 0) {
					var _row_data = _all_data_a[_index];
					var _action_a=me.params.action_opt;
					var _action_hdl=_action_a[o.action].handler;
					if(_action_hdl && typeof _action_hdl=='function'){
						_action_hdl(o.a,{values:_row_data,index:_index,options:passon});//调用外部定义的action
					}else{
						switch (o.action) {
							case 'Edit':
							case 'Delete':
								_func_open_crud_dlg({
									"action": o.action,
									"values": _row_data
								}
								, passon
								);
								break;
							default:
								alert("TODO 708 " + o2s_tmp(o));
						}
					}

				} else {
					alert("TODO 706 " + o2s_tmp(o));
				}
			} catch (ex) {
				alert("RowAction.UnexpectedError=" + ex);
				my_debug(ex);
			}
			return false;
		});

		callback.lastrst = callback(null, passon) || (new Date());
	});//_func_mggrid_init_prepare

	var _func_mggrid_getdata = _func_wrap(function (passon, callback)
	{
		var list_opt = passon.list_opt;
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
					callback.lastrst = callback(new Error("Return empty"), passon) || (new Date());
				}
				var flag_error = false;
				var errmsg = "";
				if (o && o.errmsg)
				{
					my_debug(o2s(o));//记录在窗口.
					errmsg = o.errmsg;
					flag_error = true;
				}
				me._data=o.table_data || [];
				if (!o.table_columns) {//todo:这个是否需要解除限制，比如refresh就不用。。。
					if (!flag_error) {
						callback.lastrst = callback(new Error("Column Empty")) || (new Date());
						flag_error = true;
					} else {
						callback.lastrst = callback(new Error(errmsg)) || (new Date());
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
					passon['remotedata'] = o;
					callback.lastrst = callback(null, passon) || (new Date());
				}
			}//onCallback
		};//list_default_opt
		if (passon.showPager == true) {
			if (!list_opt._p)
				list_opt._p = {};
			var _p = list_opt._p;
			if (!_p.page)
				_p.page = 1;
			if (!_p.pageSize)
				_p.pageSize = 10;
		}
		var _data_opt = $.extend({}, list_default_opt, list_opt);

		if (!_data_opt._m) {
			throw new Error("list_opt incorrect");
		}
		remoteapi(_data_opt);
	});//_func_mggrid_getdata

	var _func_mggrid_handledata = _func_wrap(function (passon, callback)
	{
		var o = passon.remotedata;
		if (!o)
			throw new Error("Remote Date Error");
		var _grid_div = passon.targetDiv;

		var table_columns = o.table_columns || [[]];
		me._schema =table_columns[0];
		var _schema_line = table_columns[table_columns.length - 1];

		/*处理columns(checkColumn & RowAction)--------------------------------*/

		var _action_a = passon.action_opt || {};//{"Edit": "Edit", "Delete": "Delete"};
		if(passon.multi_delete_opt){
			table_columns[table_columns.length - 1].splice(0, 0, {
				field:'ck'
				,checkbox:true
			});
		}
		table_columns[table_columns.length - 1].push(
			{
				field: '_actions', title: 'Actions', width: 80//奇怪，为什么是百分比的？
				, formatter: function (value, row, index)
				{
					var _rt = "";
					_cancel_bubble_s = "return false;";
					for (k in _action_a)
					{
						var _a_title="";
						if(typeof _action_a[k]==="string"){
							_a_title=_action_a[k];
						}else{
							_a_title=_action_a[k].title || k;
						}
						_rt += '<a href="javascript:alert(0);" index="' + index + '" action="' + k + '" class="easyui-linkbutton mg-grid-action" onclick="' + _cancel_bubble_s + '">' + _a_title + '</a>&nbsp;';
					}
					return _rt;
				}
			});

			var _grid = _grid_div.mygrid;
			if (!_grid)
			{
				//easyUI的datagrid的方法是在同一层而不是子层做的...
				_grid = $("<div style='width:100%'></div>");
				_grid_div.prepend(_grid);
				_grid_div.mygrid = _grid;
			}
			/*处理toolbar-------------------原来的代码，待删除-------------*/

			/*
			var _ToolBar = passon.ToolBar || {};
			var _toolbar = [];
			var _toolbar_default = {
			Reload: {title: getI18N("Reload")}
			};
			for (var k in _toolbar_default) {
			if (_ToolBar[k]) {
			} else {
			_ToolBar[k] = _toolbar_default[k];
			}
			}
			var _build_handler = function (_key, _hdl) {
			if (_hdl)
			return _hdl;
			return function () {
			var _this = $(this);
			_grid_div.trigger("GridAction", {a: _this, action: _key});
			return false;
			};
			};
			for (k in _ToolBar) {
			var _k = k;
			var _v = _ToolBar[_k];
			var _text = _v.title || getI18N(_k);
			var _cls = _k.toLowerCase();
			var _toolbar_one = {
			iconCls: 'icon-' + _cls
			, text: _text
			, handler: _build_handler(_k, _v.onClick)
			};
			_toolbar.push(_toolbar_one);
			}
			*/

			var _ToolBar = passon.ToolBar || {};
			var _toolbar = [];
			var _toolbar_default = {
				//Reload: {title: getI18N("Reload")}
			};
			for (var k in _toolbar_default) {
				if (_ToolBar[k]) {
				} else {
					_ToolBar[k] = _toolbar_default[k];
				}
			}
			var _build_handler = function (_key, _hdl) {
				if (_hdl)
					return _hdl;
				return function () {
					var _this = $(this);
					$(me).trigger("GridAction", {a: _this, action: _key});
					return false;
				};
			};
			var _tbPanel=$('<div class=""></div>');//TODO css 要补
			var _tbBar_div=$('<div style="margin-bottom:5px"></div>');
			var _hasFilter=false;
			for (k in _ToolBar) {
				var _k = k;
				var _v = _ToolBar[_k];
				var _text = _v.title || getI18N(_k);
				var _cls = (_v.iconCls?_v.iconCls:"icon-"+_k.toLowerCase());
				var _hdl=_v.onClick;
				var _toggle=(_k=='Filter')?'toggle="false"':'';
				var _tool_one=$('<a href="javascript:void(0);" class="easyui-linkbutton" key="'+_k+'" iconCls="'+_cls+'" '+_toggle+' plain="true">'+_text+'</a>');
				_tool_one.on("click",function(){
					var _this = $(this);
					var _tk=_this.attr("key");
					var _t_conf=me.params.ToolBar[_tk];
					if(_t_conf.onClick) return _t_conf.onClick(_this);
					$(me).trigger("GridAction", {a: _this, action: _tk});
					return false;
				});
				_tbBar_div.append(_tool_one);
			}
			_tbPanel.append(_tbBar_div);

			var _tbFilter_div=$('<div class="QueryFilter"></div>');//TODO css 要补
			var tpl_filter_id=passon.tpl_filter_id;
			if(tpl_filter_id){
				if(typeof tpl_filter_id==="string"){
					var tpl_s=$("#"+tpl_filter_id)[0];
					tpl_s=tpl_s?$("#"+tpl_filter_id).html():"";
					var _tpl_filter=mgrender({},tpl_s,tpl_filter_id);
				}else{
					var _tpl_filter=tpl_filter_id;
				}
				_tbFilter_div.append(_tpl_filter);
				me.params.filterPanel=_tbFilter_div;
				_tbPanel.append(_tbFilter_div);

				//TODO 重写，不用自己放按钮，由模板自己放..这里只应该挂 trigger/handler
				//var _tpl_filter_btns=$('<div style="padding-left:50px;padding-bottom:5px"></div>')
				//var _tpl_filter_submit=$('<a class="easyui-linkbutton" iconCls="icon-search" style="margin-right:20px">Search</a>');
				//_tpl_filter_submit.on("click",function(){
				//	_func_handleFilter();
				//});
				//_tpl_filter_btns.append(_tpl_filter_submit);
				//var _tpl_filter_reset=$('<a class="easyui-linkbutton" iconCls="icon-undo">Reset</a>');
				//_tpl_filter_reset.on("click",function(){
				//	var _div=me.params.filterPanel;
				//	if(!_div) return;
				//	var _fm = _div.find("form");
				//	if(!_fm) return;
				//	_fm.form("clear");
				//});
				//_tpl_filter_btns.append(_tpl_filter_reset);
				//_tbFilter_div.append(_tpl_filter_btns);
			}
			$.parser.parse(_tbPanel);

			_toolbar=_tbPanel;

			var _options = passon.options;
			var _options_default = {
				data: o.table_data
				//,rownumbers:true
				, singleSelect: true
				//,selectOnCheck:true
				//,checkOnSelect
				, rowStyler: function (index, row)
				{
					if (index % 2 == 1)
					{
						return {"class": 'saas_tr_Even'};
					} else {
						return {"class": 'saas_tr_Odd'};
					}
				}
				,loadMsg:null
				//,autoRowHeight:false//better loading performance
				, fitColumns: true
				, toolbar: _toolbar
				, columns: table_columns
			};
			if (_options) {
				_options = $.extend({}, _options_default, _options);
			} else {
				_options = _options_default;
			}

			//处理pager
			if (passon.showPager == true) {
				_pg = $('<div class="easyui-pagination" style="border:1px solid #fff;"></div>');
				_grid_div.append(_pg);
				_pg.pagination({
					onSelectPage: function (pageNumber, pageSize) {
						var _lst_opt = passon.list_opt;
						if (!_lst_opt._p)
							_lst_opt.p = {};
						_lst_opt._p.page = pageNumber;
						_lst_opt._p.pageSize = pageSize;
						$(me).trigger("GridAction", {a: null, action: "Reload"});
					},
					total: parseInt(o.maxRowCount)//这是在后台必须返回的
				});
				_grid_div.pager = _pg;
			}
			_grid.datagrid(_options);
			_grid_div.find(".mg-grid-action", _grid_div).off("click").on("click", function (evt)
			{
				var _this = $(this);
				var index = _this.attr("index");
				var action = _this.attr("action");
				$(me).trigger("RowAction", {a: _this, index: index, action: action});
				return false;//no bubble
			});
			_grid_div.find(".datagrid-row").off("click").on("click", function(evt){
				var code = $(this).find("td:eq(2)").text();
				if(params.rowClick)
					params.rowClick(code);
			});
			callback.lastrst = callback(null, passon) || (new Date());
	});//_func_mggrid_handledata
	var _func_mggrid_before_init_final = _func_wrap(function (passon, callback){
	//在grid创建后可能需要的一系列初始化
		if(passon.filterPanel){
			if(passon.handlers && passon.handlers.beforeShowFilter){
				passon.handlers.beforeShowFilter(passon.filterPanel,function(){
					callback.lastrst = callback(null, passon) || (new Date());
				});
			}
		}
		callback.lastrst = callback(null, passon) || (new Date());
	});

	var _func_mggrid_init_final = function (err, passon)
	{
		if (err) {
			$.messager.show({
				title: 'Error'
				, msg: err.message
				, timeout: 30000
				, showType: 'slide'
			});
		}
		//增加一个收工的回调
		if(passon){
			if(passon.handlers && passon.handlers.afterCreated){
				passon.handlers.afterCreated(me, passon.targetDiv.mygrid);
			}
		}

		$.messager.progress("close");
		$(me).trigger('initcomplete',me.mygrid);
	};
	//************************************************分割线：外部方法***//
	this.doFilter=function(_args){
		if(me.params){
			var _p=me.params.list_opt._p;
			if(!_p) _p={};
			if(me.params.showPager==true){
				_p.page=1;//filter的时候必须重置到第一页
			}
			_p.filter=_args;
			_func_mg_crud_reload(me.params);
		}
	};
	this.getData=function(){//返回grid的所有数据
		return me._data;
	};
	this.getSchema=function(){
		return me._schema;
	};
	this.getSelected=function(){
		var _gd=this.getGrid();
		if(_gd){
			return _gd.datagrid("getSelected");
		}else{
			return null;
		}
	};
	this.getGrid=function(){
		return me.params.targetDiv.mygrid;
	};
	this.getCol=function(_fld){
		var _she=this.getSchema() || [];
		for(var _k in _she){
			if(_she[_k].field==_fld){
				return _she[_k];
				break;
			}
		}
		return null;
	};
	this.setDefaultValues=function(values){
		if(!me.params.defaultValues) me.params.defaultValues={};
		var _def=me.params.defaultValues;
		var _she=this.getSchema() || [];
		for(var _k in values){
			var _col=this.getCol(_k);//做schema限制,其实还应该有数据类型检查
			if(_col){
				_def[_k]=values[_k];
			}
		}
	}
	this.reload = function(_p){//这个应该删除的....
		params.list_opt = _p;
		_func_mg_crud_refresh(params);
	}


	////////////////////////////////////////////////////////////////////////////////////
	if (!params) {
		throw new Error("clsMgGrid need params");
		return false;
	};
	////参数调整{
		var targetDiv = params.targetDiv;
		if (!targetDiv) {
			var targetDivId = params.targetDivId;
			if (!targetDivId) {
				throw new Error("need targetDivId");
				return false;
			}
			targetDiv = $("#" + targetDivId);
		}
		if (!params.list_opt) {
			throw new Error("need list_opt");
			return false;
		}
		if(!params.handlers) params.handlers={};
		////参数调整}

		var me=this;//scope的问题，防止this被串

		var _func_init=function(){
			//初始化grid
			async.waterfall([
				function (callback) {

					var passon = params;//NOTES: not clone, just assign. maybe need clone future, u never known.
					passon['startTime'] = (new Date());
					passon['targetDiv'] = targetDiv;

					me.params=passon;
					callback(null, passon);
				},
				_func_mggrid_init_prepare, //准备
				_func_mggrid_getdata, //拿数
				_func_mggrid_handledata,//处理
				_func_mggrid_before_init_final //grid创建后的一些初始化
			],_func_mggrid_init_final //收工
			);
		}
		_func_init();
};//clsMgGrid

var mgExampleGrid = function (params)
{
	return new clsMgGrid(params);
}
