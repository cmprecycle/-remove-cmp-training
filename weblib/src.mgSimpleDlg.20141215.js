//depends:
//SaasTool

//等稳定之后再删除这个检查:
if('undefined'==typeof SaasTool) throw new Error("mgSimpleGrid depends SaasTool");

var clsSimpleDlg=function(params){
	var me=this;
	var my_warn=SaasTool.my_warn;
	var _func_form2obj=SaasTool.form2obj;

	var _func_init = function(){
		//var _tm1=new Date();
		var tpl_id=params.tpl_id;
		if(!tpl_id){ my_warn('KO.clsSimpleDlg tpl_id mandatory'); return false;}

		var target_id=params.target_id;
		if(!target_id){ my_warn('KO.clsSimpleDlg target_id mandatory'); return false;}

		var param_action=params.action;
		if(!param_action){ my_warn('KO.clsSimpleDlg action mandatory'); return false;}
		var _wintitle=params.wintitle || param_action;
		var _btnname=params.button_name || param_action;

		var cb=params.onCallback;
		//if(!cb){ my_warn('KO.clsSimpleDlg onCallback mandatory'); return false;}

		if(!params._m){ my_warn('KO.clsSimpleDlg _m mandatory'); return false;}

		var params_a=params.a;//事件源元素，对弹出的窗口位置有影响.

		var params_values=params.values;
		//var _div = $("#" + target_id);
		//_div.off().empty();//清空要使用的div
		//mgrender_quick(tpl_id, target_id, {action: action});//把模板写进去.
		//var _div=mgrender_o({tpl_id:tpl_id,target_id:target_id,o:{action:action}});
		var _o={action:param_action};
		_o=$.extend({}, _o, params_values);
		var _div=mgrender_o({tpl_id:tpl_id,target_id:target_id
			,o:_o
		});
		me.div=_div;

		var _dlg = _div.find(".easyui-dialog");
		me.dlg=_dlg;

		var _fm = _div.find("form");
		if(!_fm.length>0){ my_warn('KO.clsSimpleDlg._func_init:<br/> form not found'); return false;}
		me.fm=_fm;

		var _func_remote_onerror= function (xhr, sts, err) {
			var _msg = (err && err.message) ? err.message : ("" + err);
			if (xhr && xhr.responseText) {
				_msg = xhr.responseText;
			}
			my_debug("AJ Error=" + _msg);
			my_warn(_msg);
		};

		var _func_on_confirm =function(){
			_fm.form('submit', {
				url: "javascript:alert(701);",
				onSubmit: function () {
					var _v = _fm.form('validate');
					if (_v) {
						var _form_data= _func_form2obj(_fm) || {};
						var _fn_before_confirm=params.onBeforeConfirm;
						if(_fn_before_confirm){
							var _fn_rt=_fn_before_confirm(_form_data);
							if(_fn_rt===false){
								return false;
							}
						}
						var _func_after_confirm=function(r){
							if (r) {
								var action_opt = {};
								if(params._c) action_opt._c=params._c;
								action_opt._m=params._m;
								var _data_opt=$.extend({},action_opt);
								_data_opt._p=$.extend({},_data_opt._p,_form_data);
								$.messager.progress();
								setTimeout(function(){
									$.messager.progress("close");
								},6666);
								SaasTool.remote(_data_opt,function(STS,o){
									cb(STS,o);
									$.messager.progress("close");
								});
							}else{
								//user cancel
							}
							//.cancelBubble=true;
							return false;//no bubble
						};
						$.messager.confirm(_btnname, getI18N('R_U_SURE') + '?', _func_after_confirm);
					}
					else {
						$.messager.show({
							title: 'Hint'
							, msg: 'Input Not Correct!!!'
							, timeout: 3000
							//,showType:'slide'
						});
					}
					return false;//no bubble
				}//onSubmit
				,success: function(result){}
			});
		};

		var _pos=n2xy(params_a);
		//var _left=_pos.x+_pos.w;
		var _top=_pos.y+_pos.h+4;
		var _scrollTop=window.document.body.scrollTop;
		if(_top>_scrollTop+240) _top=_scrollTop + 20;

		var _dlg_option={
			title: _wintitle
			, iconCls: 'icon-' + param_action
			//,left:_left//不设置就对中.
			,top:_top
			//,width: 400
			//,height: 200
			,closed: true//先close，等下open会快很多.
			,resizable: true
			,cache: false//不要缓存，每次打开都刷一次.
			//,href: 'get_content.php',//不用这种远程方法.
			,modal: true
			,buttons: [
				{ text: _btnname , handler: _func_on_confirm },
				{ text: 'Cancel' , handler: function () { _dlg.dialog('close'); }}
			]
			,onClose: function () {
				_dlg.dialog('destroy');
			}
			,onDestroy: function(x){
				//确保清除...下次会快很多.
				$(this).remove();
			}
		};
		//if(_left) _dlg_option['left']=_left;
		_dlg.dialog(_dlg_option);

		_dlg.dialog('open');
		setTimeout(function(){
			$.parser.parse(_dlg);
		},1);

		_fm.form('clear').form('load', params.values);
		//$(window).off('keydown').on('keydown', function (e) {
		//	if (e.keyCode == 27) {
		//		$(".panel-tool-close").trigger("click");
		//	}
		//});
	};//_func_init
	_func_init();//保护函数内部变量.
};

var mgSimpleDlg = function (params){
	var rt=new clsSimpleDlg(params);
	return rt;
}

////让用户按下 ESC 时关闭掉弹出的窗口:
//$(window).off('keydown').on('keydown',function(e){
//	if (e.keyCode == 27) {$(".panel-tool-close").trigger("click");}
//});

/**
* Usage
var dlg=new mgSimpleDlg({
values:o.rowdata,
a:o.a,
tpl_id:'tplPlus',
target_id:'divDlg',
_c:'ApiAceUser',_m:'PlusBalanceHQ',//<Remote for the action>
onCallback:function(STS,o){
my_msg(STS+','+o2s(o));
},
action: 'Plus'
});
*/
