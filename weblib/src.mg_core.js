//depends: [none]
//mg nodejs 一些用得比较频繁的短函数...

function date_pattern(fmt){
	var o = {
		"M+" : this.getMonth()+1, //月份
		"d+" : this.getDate(), //日
		"h+" : this.getHours()%12 == 0 ? 12 : this.getHours()%12, //小时
		"H+" : this.getHours(), //小时
		"m+" : this.getMinutes(), //分
		"s+" : this.getSeconds(), //秒
		"q+" : Math.floor((this.getMonth()+3)/3), //季度
		"S" : this.getMilliseconds() //毫秒
	};
	var week = {
		"0" : "\u65e5",
		"1" : "\u4e00",
		"2" : "\u4e8c",
		"3" : "\u4e09",
		"4" : "\u56db",
		"5" : "\u4e94",
		"6" : "\u516d"
	};
	if(/(y+)/.test(fmt)){
		fmt=fmt.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
	}
	if(/(E+)/.test(fmt)){
		fmt=fmt.replace(RegExp.$1, ((RegExp.$1.length>1) ? (RegExp.$1.length>2 ? "\u661f\u671f" : "\u5468") : "")+week[this.getDay()+""]);
	}
	for(var k in o){
		if(new RegExp("("+ k +")").test(fmt)){
			fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));
		}
	}
	return fmt;
};
function string_endsWith(suffix){
	return this.indexOf(suffix, this.length - suffix.length) !== -1;
};
function s2o(strJson) {
	var myjson=null;
	//try{myjson=JSON;}catch(e){};
	if( (typeof JSON)!="undefined" ) myjson=JSON;
	if(myjson) return myjson.parse(strJson);
	//return eval( "(" + strJson + ")");
	return (new Function('return '+strJson))();
}
function o2s(object){
	//var myjson=null;
	////try{myjson=JSON;}catch(e){};
	//if( (typeof JSON)!="undefined" ) myjson=JSON;
	//if(myjson) return myjson.stringify(object);
	//操，官方浏览器的JSON.stringify都有问题，说好的人与人的信任呢?!

	//还是自己来..


	if(null==object)return "null";

	var type = typeof object;

	if('object'== type){
		if (Array == object.constructor) type = 'array';
		else if (RegExp == object.constructor) type = 'regexp';
		else type = 'object';
	}
	switch(type){
		case 'undefined':
		case 'unknown':
			return; break;//return undefined
		case 'function':
		case 'boolean':
		case 'regexp':
			return object.toString(); break;
		case 'number':
			return isFinite(object) ? object.toString() : 'null'; break;
		case 'string':
			return '"' + object.replace(/(\\|\")/g,"\\$1").replace(/\n|\r|\t/g, function(){ var a = arguments[0]; return (a == '\n') ? '\\n': (a == '\r') ? '\\r': (a == '\t') ? '\\t': "" }) + '"'; break;
		case 'object':
			var pp="";var value ="";
			var results = []; 
			try{
				for (var property in object)
				{
					pp=object[property];
					value = o2s(pp);
					if (value !== undefined)
					results.push('"'+property + '":' + value);
				};
			}
			catch(e){ }
			return '{' + results.join(',') + '}';
			break;
		case 'array':
			var results = [];
			if(object.length>=0){
				for(var i = 0; i < object.length; i++){
					var value = o2s(object[i]);
					if (value !== undefined)
						results.push(value);
				};
				return '[' + results.join(',') + ']';
			}
			else{
				for(k in object) {
					var kk=k; var value = o2s(object[k]);
					if (value !== undefined)
						results.push('"'+kk+'":'+value);
				}
				return '{' + results.join(',') + '}';
			}
			break;
	}
}

//function str_len(o){
//	if(o){
//		if(o.length) return o.length;
//		var c=0;
//		for(x in o){
//			c++;
//		}
//		return c;
//	}else{
//		return -1;
//	}
//}

function UniqTimeSeq(){
	if("undefined"==typeof(this._timeseq_lock)){
		this._timeseq_lock=0;
	}
	var _now=(new Date()).pattern("yyyyMMddhhmmss");
	return ""+_now+"."+((++ this._timeseq_lock) % 99999);
}

var _tm_a={};
function UniqTimer(funcToCall,time_c,name){
	if(!name) throw new Error("UniqTimer()");
	var _tm_prev=_tm_a['_tm_'+name];
	try{
		if(_tm_prev) clearTimeout(_tm_prev);
	}catch(e){};

	_tm_a['_tm_'+name]=setTimeout(funcToCall,time_c);
}

if(typeof(exports)!="undefined"){
	exports.UniqTimer=UniqTimer;
	exports.UniqTimeSeq=UniqTimeSeq;
	exports.len=str_len;
	exports.o2s=o2s;
	exports.s2o=s2o;
	exports.string_endsWith=string_endsWith;
	exports.date_pattern=date_pattern;
}
Date.prototype.pattern=date_pattern;
String.prototype.endsWith =string_endsWith;
