var tpl_id='tplTransfer';
var target_id='divTransfer';
var _div=mgrender_o({tpl_id:tpl_id,o:o,target_id:target_id});
var _dlg = _div.find(".easyui-dialog");
_dlg.dialog({
	title: _action
	, iconCls: 'icon-' + _action
	,top:_top
	//,width: 400
	//,height: 200
	//,closed: true//先不显示...还有些初始化工作要先做..
	,resizable: true
	,cache: false//cache the panel content that loaded from href.
	//,href: 'get_content.php',//我们自己放数据，所以不用这个参数
	,modal: true
	,buttons: [{
		text: _action
		, handler: _func_dlg_transfer_confirm
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
//if (o.action != 'Add')
//_fm.form('clear').form('load', o.values);

async.series([
	function(cb){
		$(window).off('keydown').on('keydown', function (e) {
			if (e.keyCode == 27) {
				$(".panel-tool-close").trigger("click");
			}
		});
		$.parser.parse(_dlg);
		//_dlg.dialog('open');
		cb(null,2);
	}
	],function(err,results){});

