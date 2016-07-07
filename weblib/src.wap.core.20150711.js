/**
* Good Func for Wap Core Dev.
*/

//Get the Query Param Array
var getQueryStringA = function(){
	var _qva=this._qva;
	if(! this._qva){
		_qva={};
		var str = window.location.search;
		str.replace(
			new RegExp( "([^?=&]+)(=([^&]*))?", "g" ),
			function( $0, $1, $2, $3 ){
				_qva[ $1 ] = $3;
			}
		);
		this._qva=_qva;
	}
	return _qva;
};

//Get specific Query Param Value
function getQueryVar(sVar){
	_qva=this._qva=getQueryStringA();
	return _qva[sVar];

	//Deprecated Codes:
	//var _qva=this._qva;
	//if(!_qva){
		//	_qva=this._qva={};
		//}
		//if(_qva[sVar]) return _qva[sVar];
		//var v=unescape(window.location.search.replace(new RegExp("^(?:.*[&\\?]" + escape(sVar).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));
		//_qva[sVar]=v;
		//return v;
}

//In Wap/Mini Env, we don't use jQuery to shrink the size of the files.
//@ref http://microjs.com/
//@ref https://github.com/james2doyle/saltjs/blob/master/js/salt.js
/*! Salt.js DOM Selector Lib. By @james2doyle */
window.$ = function(selector, context, undefined){
	// an object containing the matching keys and native get commands
	//var _d=selector[0];//IE6 not compatible...
	//var _d=(selector || "").substring(0,1);
	var _d=(selector || "").charAt(0);//Good for even IE6
	/*
	var matches = {
	'#': 'getElementById',
	'.': 'getElementsByClassName',
	'@': 'getElementsByName',
	'=': 'getElementsByTagName',
	'*': 'querySelectorAll'
	}[selector[0]]; // you can treat a string as an array of characters
	*/
	var matches = {
		'#': 'getElementById',
		'.': 'getElementsByClassName',
		'@': 'getElementsByName',
		'=': 'getElementsByTagName',
		'*': 'querySelectorAll'
	}[_d]; // you can treat a string as an array of characters

	// now pass the selector without the key/first character
	var el = (((context === undefined) ? document: context)[matches](selector.slice(1)));

	// if there is one element than return the 0 element
	return ((el.length < 2) ? el[0]: el);
};

//}

//TODO checkbox和radio暂未实地测试. [2015-2-24]
function form2a(form){
	if(!form){
		form=document.forms[0];
	}else
	//TODO 这里好像有BUG.
	if(typeof(form)=='string'){
		form = $(form); // || document.forms[0];
	}
	var elems = form.elements;
	var _a= [];
	var i, len = elems.length, str='';
	for(i = 0; i < len; i += 1) {
		var element = elems[i];
		var type = element.type;
		var name = element.name || "";
		if(name == "") continue;//skip empty name
		//name=name.toLowerCase();
		var value = element.value;
		switch(type){
			case 'radio'://TODO 可能要特别处理.
			case 'checkbox'://TODO 可能要特别处理.
				str = name + '=' + encodeURIComponent(value);
				_a.push(str);
				break;
			default:
				str = name + '=' + encodeURIComponent(value);
				_a.push(str);
				break;
		}
	}
	return _a;
}
//TODO 这里应该有BUG，待测试.
function form2s(form){
	var _a=form2a(form);
	var rt=_a.join('&');
	return rt;
}

//NOTES: diff of ( escape|encodeURI|encodeURIComponent) http://xkr.us/articles/javascript/encode-compare/
function BuildQueryStr(arr){
	var rt = [];
	for (var d in arr) rt.push(encodeURIComponent(d) + "=" + encodeURIComponent(arr[d]));
	return rt.join("&");
}

//把a2的某些抄到a1，如果ka是空就表示a2全部抄到a1
function arr2arr(a1,a2,ka){
	if(!a1)a1={};
	if(!ka)ka=a2;
	if(!ka)ka={};
	for(x in ka){
		a1[x]=a2[x];
	}
	return a1;
}

//////////////////////////////////////  Work With CMP Framework.
function J(_m,_p,_c){
	if(!_c)_c="WapAce";
	var ub="";
	if(_m){
		ub=","+_c+","+_m+".web";
	}else{
		ub=window.location.pathname;
	}
	var _a=arr2arr(getQueryStringA(),_p);
	var _u=ub+"?"+BuildQueryStr(_a);
	location.href=_u;
}

//////////////////////////////////
/* 桌面Web App 封禁新开窗口 */
if(("standalone" in window.navigator) && window.navigator.standalone){
	var noddy, remotes = true;
	document.addEventListener('click', function(event) {
		noddy = event.target;
		while(noddy.nodeName !== "A" && noddy.nodeName !== "HTML") {
			noddy = noddy.parentNode;
		}
		if('href' in noddy && noddy.href.indexOf('http') !== -1 && (noddy.href.indexOf(document.location.host) !== -1 || remotes))
		{
			event.preventDefault();
			document.location.href = noddy.href;
		}
	},false);
}

function _AttachEvent(evnt, elem, func){
	if (elem.addEventListener)  // W3C DOM
		elem.addEventListener(evnt,func,false);
	else if (elem.attachEvent) { // IE DOM
		elem.attachEvent("on"+evnt, func);
	}
	else { // No much to do
		elem['on'+evnt] = func;
	}
}

////为点击连接时有一个loading效果:
////_AttachEvent('load', window, function(){
////	function _doc_ontouch(evt){
////		var _evt = evt || window.event;
////		//var ld7 = document.getElementById("loading7");
////		var ld7 = $("#loading7");
////		var _tgt = (_evt.target) ? _evt.target : _evt.srcElement;
////		var _nn=_tgt.nodeName;
////		try{
////			switch(_evt.type){
////				case "click":
////				//case "touchstart":
////					if(_nn=='A'/* || _nn=='INPUT'*/){
////						ld7.style.visibility='visible';
////						//ld7.style.visibility='inline';
////					}
////					//ld7.style.display='inline';
////					setTimeout(function(){
////						ld7.style.visibility='hidden';
////						//ld7.style.display='none';
////					},7000);
////					break;
////				//case "touchend":
////					//	setTimeout(function(){
////						//		ld7.style.visibility='hidden';
////						//		//ld7.style.display='none';
//////	},3000);
////						//	break;
////			}
////		}catch(e){alert(e);}
////		return true;
////	}
////	//document.addEventListener('touchstart',ontouch, false);
////	//document.addEventListener('touchend',ontouch, false);
////	//document.addEventListener('click',ontouch, false);
////	_AttachEvent('click', document, _doc_ontouch);
////});

//TODO 原意是要设计不阻塞的.
function my_alert(s){
	return alert(s);
}
