// JavaScript Document

$.fn.datebox.defaults.formatter = function(date){
	var y = date.getFullYear();  
	var m = date.getMonth() + 1;  
	var d = date.getDate();  
	return y + '-' + (m < 10 ? '0' + m : m) + '-' + (d < 10 ? '0' + d : d);  
};  
//Modify by LYM
//Time: 2015年6月16日 11:26:28
//RT: 176
$.fn.datebox.defaults.parser = function(s) {  
	if (s) {
		var y,m,d;
		if(s.indexOf("-")=='-1'){
			y=s.substr(0,4);
			m=s.substr(4,2);
			d=s.substr(5,2);
		}else{
			var a = s.split('-');   
			y=a[0];
			m=a[1];
			d=a[2];
		}
		y = y?parseInt(y):2015;
		m = m?parseInt(m-1):0;
		d = d?parseInt(d):1;
		var _date = new Date(y,m,d);
		return _date;  
	} else {  
		return new Date();  
	}  
};
$.extend($.fn.validatebox.defaults.rules, {
 equals:{
	 validator: function(value,param){
		 return value == $(param[0]).val();
	 },
	 message:("The values you typed do not match!")//这种需要以后有js版的语言包
 }
});



/*
function easyui_date_formatter(date){
	var y = date.getFullYear();
	var m = date.getMonth()+1;
	var d = date.getDate();
	return y+'-'+(m<10?('0'+m):m)+'-'+(d<10?('0'+d):d);
}
function easyui_date_parser(s){
	if (!s) return new Date();
	var ss = (s.split('-'));
	var y = parseInt(ss[0],10);
	var m = parseInt(ss[1],10);
	var d = parseInt(ss[2],10);
	if (!isNaN(y) && !isNaN(m) && !isNaN(d)){
		return new Date(y,m-1,d);
	} else {
		return new Date();
	}
}
 */
