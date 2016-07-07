//depends: jquery/o2s/s2o

function aj2014(_option){
	if(!_option){alert('option empty for aj2014');return false;}

	var _dataType=_option.dataType||'text';

	var _url=_option.url||null;//optional for special url entry...
	if(!_url){
		//一般都没有url的,就自己拼一个:
		_url="./?";
		var _s=null;
		if("undefined"!=typeof my_cookie){
			//如果cookie已经有,jsonp要用显式..
			if('jsonp'==_dataType){
				_s=my_cookie('_s');
			}
		}
		if(!_s){
			//如果cookie没有,还真的要找找...
			if("undefined"!=typeof getSID){
				_s=getSID();
			}
		}
		if(!_s){
			if(window['_s']){
				_s=window['_s'];
			}
		}
		if(_s){
			_url+="&_s="+_s;
		}
		if(window['lang']){
			_url+="&lang="+window['lang'];
		}
	}
	var _c=_option['_c'] || _option['class'] || null;
	var _m=_option['_m'] || _option['method'] || null;
	var _p=_option['_p'] || _option['param'] || null;

	var _dataObj={};
	if(_c) _dataObj['class']=_c;
	if(_m) _dataObj['method']=_m;
	if(_p) _dataObj['param']=_p;

	var _callback=_option.onCallback|| null;
	var _callsend=_option.onSend||null;
	var _callcomplete=_option.onComplete||null;//When the request has completed (either in success or failure).
	var _callerror=_option.onError||null;//On Ajax Error with p is XMLHttpRequest(xhr)

	if(_dataType=='jsonp'){
		var _ajaxOptions={
			//data:o2s(_dataObj),//自己编码..
			//complete: 请求完成后的事件，无论请求成功与否，都将触发该事件。
			complete: function(XMLHttpRequest, textStatus){
				if(_callcomplete){_callcomplete(XMLHttpRequest, textStatus);}
			},
			//beforeSend: 请求发送前的事件，该属性为其设置事件处理程序，可用于发送前修改XMLHttpRequest的参数.
			beforeSend: function(XMLHttpRequest){
				if(_callsend){ _callsend(XMLHttpRequest); }
			},
			//error 请求执行失败时的事件:
			error: function(XMLHttpRequest, textStatus, errorThrown){
				try{ my_log("Ajax.error:",errorThrown); }catch(e){}
				if(_callerror){_callerror(XMLHttpRequest,textStatus,errorThrown);}
				//下面这些需要时实现在onComplete
				//try{hideTips();}catch(e){}
				//try{parent.hideTips();}catch(e){}
				//try{parent.parent.hideTips();}catch(e){}
			},
			success: function(txt, textStatus, XMLHttpRequest){
				var obj=null;
				//try{obj=s2o(txt);}catch(ex){}
				if(obj){
					if(obj['errmsg']){
						try{
							my_log('ServerErrorMsg',obj);
						}catch(e){}
					}
				}else{
					obj=txt;//let non-json-string return ..
				}
				if(_callback){
					_callback(obj);
				}
			},
			//processData:false,//好像对jsonp没用?
			dataType:"jsonp",
			//type:"POST",
			url:_url +'&json='+encodeURIComponent(o2s(_dataObj))//json是之前写好的可以接入的参数（jsonp时代替POST的，不过不能支持太长...），以后要不要做Base64编码呢?
		};
		return $.ajax(_ajaxOptions);
	}else{
		var _ajaxOptions={
			data:o2s(_dataObj),//自己编码..
			//complete: 请求完成后的事件，无论请求成功与否，都将触发该事件。
			complete: function(XMLHttpRequest, textStatus){
				if(_callcomplete){_callcomplete(XMLHttpRequest, textStatus);}
			},
			//beforeSend: 请求发送前的事件，该属性为其设置事件处理程序，可用于发送前修改XMLHttpRequest的参数.
			beforeSend: function(XMLHttpRequest){
				if(_callsend){ _callsend(XMLHttpRequest); }
			},
			//error 请求执行失败时的事件:
			error: function(XMLHttpRequest, textStatus, errorThrown){
				try{ my_log("Ajax.error:",errorThrown); }catch(e){}
				if(_callerror){_callerror(XMLHttpRequest,textStatus,errorThrown);}
				//下面这些需要时实现在onComplete
				//try{hideTips();}catch(e){}
				//try{parent.hideTips();}catch(e){}
				//try{parent.parent.hideTips();}catch(e){}
			},
			success: function(txt, textStatus, XMLHttpRequest){
				var obj=null;
				try{obj=s2o(txt);}catch(ex){}
				if(obj){
					if(obj['errmsg']){
						try{
							my_log('ServerErrorMsg',obj);
						}catch(e){}
					}
				}else{
					obj=txt;//let non-json-string return ..
				}
				if(_callback){
					_callback(obj);
				}
			},
			dataType:"text",//用纯的text做为上传的数据（即自己编码）..
			processData:false,//因为返回的还不一定是json可能是tpl(plain text/html)，所以要自己parse
			url:_url,
			type:"POST"//默认,能穿透缓存..
		};
		return $.ajax(_ajaxOptions);
	}
}

