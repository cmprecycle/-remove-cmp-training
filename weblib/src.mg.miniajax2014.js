/*
var ajax=function(){};
ajax.x=ajax.prototype.x=(window.XMLHttpRequest)?(//Mozilla
	function(){ try { netscape.security.PrivilegeManager.enablePrivilege("UniversalBrowserRead"); } catch (e) { }
	var rt=new XMLHttpRequest(); if(rt.overrideMimeType){rt.overrideMimeType('text/plain');} if (!rt) { alert('Cannot create XMLHTTP instance'); } return rt;}
):((window.ActiveXObject)?(function(){return new ActiveXObject("Msxml2.XMLHTTP");}):(function(){return new ActiveXObject("Microsoft.XMLHTTP");}));
*/
//ref http://www.cnblogs.com/index-html/archive/2011/10/09/xmlhttp_code_min.html
//new(-[1,]?XMLHttpRequest:ActiveXObject)("Microsoft.XMLHTTP")
//new(self.XMLHttpRequest||ActiveXObject)("Microsoft.XMLHTTP")
//ajax.x=new(this.XMLHttpRequest||ActiveXObject)("Microsoft.XMLHTTP");
/*
var ajax={};
ajax.x=new(self.XMLHttpRequest||ActiveXObject)("Microsoft.XMLHTTP");
ajax.send=ajax.prototype.send=function(u,f,m,a){ var x=this.x(); x.open(m,u,true); x.onreadystatechange=function(){ if(x.readyState==4){ f(x.responseText,x.status,u); } }; if(m=='POST')x.setRequestHeader('Content-type','application/x-www-form-urlencoded'); x.send(a); };
ajax.get=ajax.prototype.get=function(url,func){this.send(url,func,'GET')};
ajax.gets=ajax.prototype.gets=function(url){var x=this.x();x.open('GET',url,false);x.send(null);return x.responseText};
ajax.post=ajax.prototype.post=function(url,func,args){this.send(url,func,'POST',args)};
*/
//NOTES: 注意，用迷你版本，是没有 error handler的，就是说如果网络不好，出了错没有返回或者超时处理。需要封装一下的.
//所以用在比较小的情况下，可能以后还要打一下 超时和网络失败的异常处理.
//如果用来后台台，先用着 aj2014 先，它用的是 jQuery里面的ajax，会比较完备一点.
//TODO 先比较急的是弄一个 miniajax2015版本，解决上述的 异常处理....
ajax={
x:function(){return new(self.XMLHttpRequest||ActiveXObject)("Microsoft.XMLHTTP");}
,send:function(u,f,m,a){var x=this.x();x.open(m,u,true);x.onreadystatechange=function(){if(x.readyState==4){f(x.responseText,x.status,u);}};if(m=='POST')x.setRequestHeader('Content-type','application/x-www-form-urlencoded');x.send(a);}
,get:function(url,func){this.send(url,func,'GET');}
,gets:function(url){var x=this.x();x.open('GET',url,false);x.send(null);return x.responseText;}
,post:function(url,func,args){this.send(url,func,'POST',args);}
};
