/**
Dependency: namespace
Usage: @ref test_miniupload.htm
*/
namespace('cmp').miniupload=function(opt){
	if (!opt) opt={};
	this.form=opt.form;
	this.prefix=opt.prefix || "cmp_miniupload"
	//if(!opt.url) throw new Error("Need Opt url");
	this.url=opt.url;
	if(!this.i) this.i=0;

	var _AttachEvent = function(evnt, elem, func){
		if (elem.addEventListener)  // W3C DOM
			elem.addEventListener(evnt,func,false);
		else if (elem.attachEvent) { // IE DOM
			elem.attachEvent("on"+evnt, func);
		} else elem['on'+evnt] = func;
	}

	this.gen_ifrm_id=function(){
		this.i++;
		return this.prefix + "_ifrm_" + this.i;
	}

	this.upload=function(_callback,_form,_url)
	{
		if(!_form) _form=this.form;

		if(!_url) _url=this.url;
		var _ifrm_id=this.gen_ifrm_id();

		//var _form_new=false;
		if(!_form){ //buld one
		//NOTES: 不成功，因为这样的话就获得不到文件。。。所以放弃这个方法。。。
			//_form=document.createElement("form");
			//_form.method='post';
			//_form.enctype='multipart/form-data';
			//_form.encoding='multipart/form-data';//http://www.bennadel.com/blog/1273-setting-form-enctype-dynamically-to-multipart-form-data-in-ie-internet-explorer.htm
			//document.body.appendChild(_form);
			//_form_new=true;
			alert(".upload() needs _form");return false;
		}
		var ie678 = !-[1,];
		////alert(ie678);

		if(ie678){
			// The iframe must be appended as a string otherwise IE7 will pop up the response in a new window
			// http://stackoverflow.com/a/6222471/268669
			var _ifrm_el = document.createElement('<iframe name="'+_ifrm_id+'" id="'+_ifrm_id+'" style="display: none;" src="blank.htm"/>');
		}else{
			var _ifrm_el = document.createElement("iframe");
			_ifrm_el.id=_ifrm_id;
			_ifrm_el.name=_ifrm_id;
			_ifrm_el.style.display='none';
		}
		document.body.appendChild(_ifrm_el);

		_form.action=_url;
		_form.target=_ifrm_id;

		_AttachEvent("load",_ifrm_el,function(_evt){
			var _tgt = (_evt.target) ? _evt.target : _evt.srcElement;
			var ifrm  = _tgt;
			var win=(ifrm.contentWindow || ifrm.contentDocument);
			var raw_s='{errmsg:"Empty Response"}';
			if(win){
				try{
					var doc=win.document;
					if(doc){
						if (doc.XMLDocument){
							response = doc.XMLDocument;
						}else{
							if(doc.body){
								raw_s = doc.body.innerHTML;
							}
						}
					}
				}catch(ex){//alert(ex);
				}
			}
			if(_callback){_callback(raw_s);}
			//clean up.
			//if(_form_new){
			//	document.body.removeChild(_form);
			//}else{
				_form.reset();
			//}
			setTimeout(function(){
				document.body.removeChild(ifrm);
				ifrm=null;
			},555);
		});
		_form.submit();
		return false;
	};
	this.upload(opt.cb);
};

