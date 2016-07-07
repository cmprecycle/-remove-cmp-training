//简单的o2s_tmp方便debug或快速提示，不用于业务逻辑...
function o2s_tmp(o){
	var s="";
	if( (typeof o)=="string"){
		s=o;
	}else{
		for(x in o){
			s+=""+x+":"+o[x]+";";
		}
	}
	return s;
}
function s2o_tmp(s){
	return (new Function('return '+s))();
}
function my_log(o1,o2,o3){
	try{
		if((typeof console)!="undefined"){
			if(o3) console.log(o1,o2,o3);
			else if(o2) console.log(o1,o2);
			else console.log(o1);
		}else{
			window.status=o2s_tmp(o1);
		}
	}catch(ex){
		window.status="ex="+o2s_tmp(ex);
	}
}
var _d_=my_log;//短函数方便简易取代 console.log

//页面动态数据
function getPageData(){
	return window['page_data'] || {};
}

//页面语言
function getLang(){
	var pd=getPageData();
	return pd.lang;
}

function getQueryVar(sVar){
	return unescape(window.location.search.replace(new RegExp("^(?:.*[&\\?]" + escape(sVar).replace(/[\.\+\*]/g, "\\$&") + "(?:\\=([^&]*))?)?.*$", "i"), "$1"));
}
function getQueryStr(){
	var _search=new String(location.search);
	var reg=/^\?/g;
	var _search2=_search.replace(reg,"");
	return _search2;
}
//Session ID
function getSID(){
	if(window['_s']) return window['_s'];

	var _s=getPageData()._s;
	if(_s){
		window['_s']=_s;
		return _s;
	}
	_s=getQueryVar('_s');
	if(_s){
		window['_s']=_s;
		return _s;
	}
	//if("undefined"!=typeof my_cookie){
	//	_s=my_cookie('_s');
	//	if(_s){
	//		window['_s']=_s;
	//		return _s;
	//	}
	//}
	return _s;
}

//Usage: CheckAndCallBack(function(){ return true;/* OK */ },999,3999);
function CheckAndCallBack(func_check,timeout,max_timeout,callback,sum_timeout){
	if(!sum_timeout) sum_timeout=0;
	if(sum_timeout>max_timeout){
		callback("timeout");
	}else{
		if(func_check()){
			callback("ok");
		}else{
			setTimeout(function(){
				CheckAndCallBack(func_check,timeout,max_timeout,callback,sum_timeout+timeout);
			},timeout);
		}
	}
};

function getI18Na(){
	var pd=getPageData();
	return ( pd.lang_a || {} );
}
function getI18N(k){
	var lang_a=getI18Na();//TODO TODO_WJC 这里要有缓冲，否则会被多次调用影响性能....
	var rt=lang_a[k];
	if(!rt) rt='I18N_'+k;
	return rt;
}
function arr2arr(a1,a2){
	if(a1 && a2){
		for (k in a2){
			a1[k]=a2[k];
		}
	}
}
function shtml_load_page_data(shtml_module,callback){
	var _s=getSID();if(!_s)_s='';//don't use null....
	var _l=getQueryVar('lang');
	ajax.post(
		//_s+",,PageData,"+shtml_module+".json"+(_l?("?lang="+_l):"") 
		".PageData.api?_p="+shtml_module+"&"+"_s="+_s+(_l?("&lang="+_l):"")
		//".PageData.api?_p="+shtml_module+(_l?("&lang="+_l):"")
		,function(s){
		try{
			//因为这时o2s极有可能还未加载完，所以先用这个迷你函数!
			if(!window['page_data'])window['page_data']={};
			//window['page_data']=s2o_tmp(s);
			arr2arr(window['page_data'],s2o_tmp(s));
			var _pd=getPageData();
			var _s=_pd._s;
			if(_s){
				//NOTES: 2014-12-20: 因为实际部署后发现不同域有cookie的问题所以不用cookie了
				window['_s']=_s;//本页后续也可能直接用到，不用读cookie
				//my_cookie("_s",_s);//人肉保存到cookie中
				//if(my_cookie('_s')!=_s){
					//	//暂时不支持关闭 cookie,否则无法正确传递session号
					//	//alert('Cookie Config Error');setTimeout(function(){location.href="./?rnd"+Math.random();},7000);
					//alert('Cookie Disabled ?');
					//}
			}
			try{
			if(callback) callback(_pd);
			}catch(ex){}
		}
		catch(ex){
			alert(ex);
			//alert("page_data_"+shtml_module+".ex="+ex);setTimeout(function(){location.href="./login.shtml?rnd"+Math.random();},3000);
		}
	}//,"rnd="+Math.random()//干脆POST空,以后可能是 post 其它信息? 方便做日志...
	);
}
