function my_hint(txt_hint,hint_tm){
	if(hint_tm<0 || hint_tm===0){
		$("#splash_loading").hide();
		return;
	}
	if(!txt_hint){
		txt_hint=getI18N("PlsWait")+"...";
	}
	$("#ftHint").html(txt_hint);
	$("#splash_loading").show();
	setTimeout(function(){
		$("#splash_loading").hide();
	},hint_tm);
}

//根据点下去的对象来判断是否需要全掩屏提示
function test_if_hint(ele){
		if($(ele).hasClass("menu_lvl_1") || $(ele).hasClass("combo-arrow")){
			return false;
		}
		var nn=ele.nodeName;
		var f_hint=true;
		if(nn=="A"){
		}else if(nn=="INPUT"){
			var nt=$(ele).attr("type");
			if(nt=="button"){
			}else{
				f_hint=false;
			}
		}else{
			f_hint=false;
		}
		if(f_hint){
			my_hint(null,1999);
		}
}
function FuncShowDbgTxt(_header_txt){
	if(!_header_txt) _header_txt="Debug";
	var _w=$('<div style="padding:10px;"></div>');
	var _t=$('<textarea style="width:99%;height:98%"></textarea>');
	var _text=my_debug();
	_t.text(_text);
	_w.append(_t);
	_w.dialog({
		title: _header_txt,
		width: 500,
		height: 300,
		draggable: true,
		resizable: true,
		maximizable:true,
		closed: false,
		cache: false,
		modal: true,
		buttons:[{text:'Close',iconCls:'icon-cancel',handler:function(){
			_w.dialog('close');
			//TODO ?? 释放了没有??
		}}]
	});
}

//get xy of node
function n2xy(node){
	var rt={x:-1,y:-1,w:-1,h:-1};
	if(node){
		var _node_o=$(node);
		var offset=_node_o.offset();
		rt.x=offset.left;
		rt.y=offset.top;
		rt.w=_node_o.width() || -1;
		rt.h=_node_o.height() || -1;
	}
	return rt;
}
//get xy and node to evt
function e2xy(evt){
	var out = {x:-1, y:-1};
	switch(evt.type){
		case 'touchstart':
		case 'touchmove':
		case 'touchend':
		case 'touchcancel':
			var touch = evt.originalEvent.touches[0] || evt.originalEvent.changedTouches[0];
			out.x = touch.pageX;
			out.y = touch.pageY;
			break;
		default:
			out.x = evt.pageX;
			out.y = evt.pageY;
			break;
	}
	out['nn']=evt.target.nodeName;
	return out;
}

function main(){
	//$(document).on('selectstart',function(evt){
	//	return false;
	//});
	/*
	$(document).on('dblclick',function(evt){
		if(evt) evt.cancelBubble=true;
		return false;
	});
	*/
	$(document).on("click",function(evt){
		if(evt && evt.target)
			test_if_hint(evt.target);
	});
	//$(document).on("mousedown",function(evt){
	//	if(evt && evt.target)
	//		test_if_hint(evt.target);
	//});

	var _page_data=getPageData();

	var _errmsg=_page_data['errmsg'] || "";
	if(_errmsg){
		my_debug(_errmsg);
		setTimeout(function(){
			FuncShowDbgTxt(getI18N("Error"));
		},1000);
	}
	
	$("#divContent").html(window['page_index']);//@ref shtml.content.htm

	//my_debug("content.shtml Ready");
}//function main

//js 格式化数字
function formatNumber(num, decLen) {
	var numSrc = num;
	//先去掉可能存在的逗号
	if (typeof(num) == "string") {
		if (num.indexOf(',') >= 0) {
			num = num.replace(/,/g, '');
		}
	}

	//如果去掉逗号后还不是数字，则返回
	if(isNaN(Number(num))){
		//return "N/A";
		return numSrc;
	}
	try {
		num = String(parseFloat(num).toFixed(decLen));
		if (num.indexOf('.') >= 0) {
			//整数位
			intPart = num.split('.')[0];
			//小数位
			decPart = num.split('.')[1];
		} else {
			intPart = num;
		}
		var intPart = intPart + '';
		var re = /(-?\d+)(\d{3})/;
		while (re.test(intPart)) {
			intPart = intPart.replace(re, '$1,$2')
		}
		if (num.indexOf(".") >= 0) {
			num = intPart + "." + decPart;
		}else if(decLen ==0 ){
			num = intPart;
		} else {
			num = intPart+'.00';
		}
		//      if (num.charAt(0)=='-') num=''+num+'';
		return num;
	} catch(e) {
		return num;
	}
}

//////////////////////////////////////////////////
//暂时需要用到
//CheckAndCallBack(function(){
//	if( ((typeof $) =="undefined")
//		|| !window['page_data'] ||!window['page_index']
//	)
//	{
//		return false;
//	}else{
//		return true;
//	}
//},333,7777,function(sts){
//	if(sts=="timeout"){
//		if( (typeof $)=="undefined" ) alert("Error JQ");
//		if( (typeof main)=="undefined" ) alert("Error main");
//		if( ! window['page_index'] ){
//			//page_index from _beforetpl.js
//			alert("Error index tpl");
//			location.reload();
//		}else
//		if( ! window['page_data'] ){
//			alert("Error page_data");
//			location.reload();
//		}else
//		alert("Page Init Error");
//		//location.reload();//ugly but works
//		location.href="./?";
//		return false;
//	}
//	main(sts);
//});
