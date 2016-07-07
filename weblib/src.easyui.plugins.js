// JavaScript Document
/*
numberplain=>继承自numberbox,主要是去掉了边框
以后应该有个extbase累来封装怎么扩展easyui插件的问题
*/
(function($){
	$.fn.numberplain=function(_options,_params){
		if(typeof _options=="string"){
			var _a=$.fn.numberplain.methods[_options];
			if(_a){
				return _a(this,_params);
			}else{
				return this.numberbox(_options,_params);
			}
		};
		_options=_options||{};
		return this.each(function(){
			var _b=$.data(this,"numberplain");
			if(_b){
				$.extend(_b.options,_options);
			}else{
				$.data(this,"numberplain",{options:$.extend({},$.fn.numberplain.defaults,$.fn.numberplain.parseOptions(this),_options)});
			}
			$(this).addClass("numberplain-f");
			var _opt=$.data(this,"numberplain").options;
			$(this).numberbox(_opt);
			//$(this).find("span:first").addClass("numberplain");
			$(this).next().addClass("numberplain");
		});
	};

	$.fn.numberplain.methods={_options:function(jq){
		var _c=jq.numberbox("_options");
		return $.extend($.data(jq[0],"numberplain")._options,
		{width:_c.width,value:_c.value,originalValue:_c.originalValue,disabled:_c.disabled,readonly:_c.readonly}
		);
	}};
	$.fn.numberplain.parseOptions=function(_d){
		return $.extend({},$.fn.numberbox.parseOptions(_d),{
			//在这里添加选项格式化
		});
	};
	$.fn.numberplain.defaults=$.extend({},$.fn.numberbox.defaults,{border:"medium none"});
	$.parser.plugins.push('numberplain');
})(jQuery);
