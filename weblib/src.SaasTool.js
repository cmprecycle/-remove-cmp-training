//Purpose: For Background System Only.
//Usage at the end of file.
//Dependency: jQuery.
var clsSaasTool=function(){

	var remoteapi = aj2014;//@ref src.mg.aj2014.js

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
	var my_warn = function (txt, callback, title) {
		if(!title) title='Warning';
		var _cb = callback ||
		(function () { try{event.cancelBubble=true;}catch(ex){};return false; });
		$.messager.alert(title, txt, 'warning', _cb);
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

	///////////////////////////////////////////////////////// PUBLIC
	this.Version="20151122";
	this.getVersion=function(){
		return this.Version;
	}

	this.my_msg=my_msg;
	this.my_warn=my_warn;
	this.form2obj=_func_form2obj;
	this.form2a=_func_form2a;
	this.series_wrap=_proc_series_wrap;
	this.series_wrap_result=_proc_series_wrap_result;

	//Common Remote Call (With UI handling) to simplified use of remoteapi
	//NOTES: 如果还不能用，就自己再复制并修改一下.
	this.remote=function(data_opt,cb){
		var remoteapi_default_opt = {
			onError:function(xhr, sts, err){
				var _msg = (err && err.message) ? err.message : ("" + sts + " " + err);
				if (xhr && xhr.responseText) {
					_msg += xhr.responseText;
				}
				//my_warn("AJ Error="+_msg);
				$.messager.show({
					title: 'Ajax Error'
					, msg: _msg
					, timeout: 30000
					, showType: 'slide'
				});
				//my_debug("AJ Error=" + _msg);
				if(cb){ cb("ERR",{errmsg:_msg}); }
			}
			,onCallback: function (o){
				if (!o) o = {STS: "EMPTY", "errmsg": "Server Return Nothing"};
				var flag_error = false;
				var errmsg = "";
				if (o && o.errmsg){
					my_debug(o2s(o));//记录在窗口.
					errmsg = o.errmsg;
					flag_error = true;
				}
				if (o.STS == 'OK') {
					$(".easyui-dialog").dialog('close');
				} else if (o.STS == 'SKIP') {
					my_warn(o.STS,function(){
						if(cb){ cb(o.STS,o); }
					});
					return false;
				} else {
					//不是OK和SKIP就当作ERR，要warn出来
					var _title = (o && o.log_id)? 'Warning ('+o.log_id+')' : 'Warning';
					if(errmsg){
						my_warn(errmsg,function(){
							if(cb){ cb(o.STS,o); }
						}, _title);
						return false;
					}
					flag_error = false;//表示已经没错了（因为已经处理了）.
				}
				if (flag_error) {
					//还有错，又不是约定的ERR，就先右下角提示出来:
					$.messager.show({
						title: 'Server Message'
						, msg: errmsg
						, timeout: 30000
						, showType: 'slide'
					});
				}
				if(cb){ cb(o.STS,o); }
			}//onCallback
		};//remoteapi_default_opt
		var _data_opt = $.extend({}, remoteapi_default_opt, data_opt);
		if (!_data_opt._m) {
			throw new Error("empty _m");
		}
		remoteapi(_data_opt);
	}
}
var SaasTool=new clsSaasTool();

/** Usage:
SaasTool.remote({_c:"XXXX",_m:"YYYY",_p:...},function(STS,o){});
*/
