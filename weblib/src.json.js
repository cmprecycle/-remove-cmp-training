function s2o(s){try{return (new Function('return '+s))();}catch(ex){}};
function o2s(o,f,t){
	if(null==o)return "null";
	f=arguments.callee;
	t=typeof o;
	var r=[];
	if('object'==t){if(Array==o.constructor)t='array';else if(RegExp==o.constructor)t='regexp'};
	switch(t){
		case 'undefined':case 'unknown':return;
		case 'function':return !('prototype' in o)?"function(){}":(""+o);break;
		case 'boolean':case 'regexp':return o.toString(); break;
		case 'number':return isFinite(o)?o.toString():'null';break;
		case 'string':return '"'+o.replace(/(\\|\")/g,"\\$1").replace(/\n/g,"\\n").replace(/\r/g,"\\r")+'"';break;
		case 'object':
			try{for(var p in o){v=f(o[p]);if(v!==undefined)r.push('"'+p+'":'+v);}}catch(e){};return '{'+r.join(',')+'}';break;
		case 'array':
			if(o.length>=0){
				for(var i=0;i<o.length;i++){var v=f(o[i]);if (v!==undefined)r.push(v);};return '['+r.join(',')+']';
			}else{
				for(var k in o){var v=f(o[k]);if(v!==undefined)r.push('"'+k+'":'+v);};return '{'+r.join(',')+'}';
			}
	}
};
