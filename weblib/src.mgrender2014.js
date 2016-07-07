//deprends: _d_
//
//NOTES: 暂时似乎对 &amp; 有少少BUG，注意留意一下...
//mgrender_o 和 mgrender_quick最大不同的是参数是 {} object，更好用一点.
//Usage { o, tpl_id | tpl_s, target_id }
//如果没有target_id 就返回模板的内容.
function mgrender_o(opt){
	var o=opt.o || {};
	var tpl_s=opt.tpl_s;
	var tpl_id=opt.tpl_id;
	if(tpl_id || tpl_s){
	}else throw new Error('mgrender_o needs tpl_id or tpl_s');
	if(tpl_id && !tpl_s) tpl_s=$('#'+tpl_id).html();
	//if(!tpl_id) tpl_id=Math.random();
	var target_id=opt.target_id;
	var h=mgrender(o,tpl_s,tpl_id);
	if(target_id){
		//$('#'+target_id).off().empty().html(h);
		var _node=$('#'+target_id).off().html(h);
		return _node;
	}else{
		return h;
	}
}
function mgrender_quick(tpl_id,target_id,dataobj,tpl_s){
	if (!tpl_s){
		var o=$("#"+tpl_id)[0];
		if(o){
			tpl_s=$("#"+tpl_id).html();
			//tpl_s=o.innerHTML;
		}else{
			//_d_("found no tpl_id");
			return "";
		}
	}
	var h=mgrender(dataobj,tpl_s,tpl_id);
	$("#"+target_id).html(h);
}

function mgrender(json_obj,tpl_s,name_tpl)
{
	var _micro_templates_=window['_micro_templates_'];
	if(!_micro_templates_){
		_micro_templates_=window['_micro_templates_']={};
	}
	//临时变量，用于compile出现问题时来得到临时返回...
	var _micro_templates_s_=window['_micro_templates_s_'];
	if(!_micro_templates_s_){
		_micro_templates_s_=window['_micro_templates_s_']={};
	}

	var _func_tmp=function(){
		var rt= arguments[0].replace(/'|\\/g, "\\$&").replace(/\n/g, "\\n");
		//_d_(rt);
		return rt;
	};//这个函数返回一个可以把单引号或者反斜杠全换成\$&，以及把真回车换成字符串\n

	if(!_micro_templates_[name_tpl]){
		var tpl=tpl_s;
		tpl=tpl
		.replace(/&lt;%/g, "<%")//因为有时把html拿出来的时候是会做了这样的转换
		.replace(/%&gt;/g, "%>")
		.replace(/\r|\*+="/g, ' ')//把换行或者连续的空格变成单一空格..
		.split('<%').join("\r")
		.replace(/(?:^|%>)[^\r]*/g, _func_tmp) //这一个暂时还不是很明白，似乎是把 %>之后的空行给处理一下??
		.replace(/\r=(.*?)%>/g, "',$1,'")
		.split("\r").join("');");

		tpl=tpl.split('%>').join("\n"+"_write.push('");

		tpl=tpl
		.replace(/&gt;/g, ">")
		.replace(/&lt;/g, "<")
		.replace(/&amp;/g, "&");

		_micro_templates_s_[name_tpl]=tpl;
		var obj_name="_mgrender_arg_obj";
		var _s="";
		try{
			_s="try{";
			_s+="var _write=[];with("+obj_name+"){"+"\n"+"_write.push('"+ tpl +"');};return _write.join('');";
			_s+="}catch(ex){try{_d_('err in tpl "+name_tpl+"');_d_(window['_micro_templates_s_']['"+name_tpl+"']);_d_(''+ex);}catch(e){alert(e);}}";
			var nf= new Function(obj_name, _s);
		}catch(ex){
			try{
				_d_("tpl error");
				_d_(tpl);
				_d_(_micro_templates_s_[name_tpl]);
			}catch(e){}
			throw ex;
		}
		window['_micro_templates_'][name_tpl]=_micro_templates_[name_tpl] = nf;
	}

	var _nf=_micro_templates_[name_tpl];
	if(typeof(_nf)=="function"){
		return _nf(json_obj);
	}else{
		throw new Error(""+name_tpl+"tpl not found");
	}

	//如果玩的是debug模式，把编译好的模板缓存给清掉...
	if(window['debug'] && window['debug']>0){
		_micro_templates_[name_tpl]=null;//no cache for debug mode
	}
}

