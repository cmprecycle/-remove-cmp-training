		/*
		<form method="post" class="easyui-form" action="javascript:alert('UnexpectedError Form tplTransfer');">
			<table width="100%">
				<tr class="<%=id_invisible_s%>">
					<td><font class="fitem_label">id</td>
					<td><input name="id" class="easyui-textbox" readonly="true"></td>
				</tr>
				<tr>
					<td><font class="fitem_label">{I18N_tenant_user_user_login}:</font></td>
					<td><input name="user_login" class="easyui-textbox" data-options="required:true"></td>
				</tr>
				<tr>
					<td><font class="fitem_label">pass:</font></td>
					<td><input name="user_pass" class="easyui-textbox" required="true"></td>
				</tr>
				<tr>
					<td><font class="fitem_label">core role:</font></td>
					<td><input name="user_core_role" class="easyui-textbox" value="TestUser">
					</td>
				</tr>
				<tr>
					<td><font class="fitem_label">other role:</font></td>
					<td><input name="user_other_role" class="easyui-textbox" value="TestUser">
					</td>
				</tr>
				<tr>
					<td><font class="fitem_label">auth:</font></td>
					<td><input name="user_other_auth" class="easyui-textbox" value="TestUser">
					</td>
				</tr>
				<tr>
					<td><font class="fitem_label">Enable</font></td>
					<td><input name="user_enable" class="easyui-checkbox" type="checkbox" value="Y"></td>
				</tr>
				<tr>
					<td><font class="fitem_label">nickname:</font></td>
					<td><input name="user_nickname" class="easyui-textbox"></td>
				</tr>
				<tr>
					<td><font class="fitem_label">openid:</font></td>
					<td><input name="user_openid" class="easyui-textbox"></td>
				</tr>
				<tr>
					<td><font class="fitem_label">remark:</font></td>
					<td><input name="user_remark" class="easyui-textbox"></td>
				</tr>
				<tr _class="nodisplay">
					<td><font class="fitem_label">tenant_code:</font></td>
					<td><input name="tenant_code" class="easyui-textbox"></td>
				</tr>
			</form>
		 */ 
		var _dlg = _div.find(".easyui-dialog");
		var _fm = _div.find("form");

		var _func_dlg_crud_on_confirm =function(){
			_fm.form('submit', {
				url: "javascript:alert(701);",
				onSubmit: function () {
					var _v = _fm.form('validate');
					if (_v) {
						var _form_data= _func_form2obj(_fm) || {};
						$.messager.confirm('Confirm ' + o.action, getI18N('R_U_SURE') + '?', function (r) {
							if (r) {
								var f_remote = true;
								var action_opt = {};
								switch (o.action) {
									case "Edit": action_opt = params.update_opt; break;
									case "Delete": action_opt = params.delete_opt; break;
									case "Add": action_opt = params.create_opt; break;
									default: f_remote = false; my_msg("UnexpectedError 706 " + o.action);
								}
								if (f_remote) {
									//$.messager.progress();
									var _data_opt = $.extend({}, remoteapi_default_opt, action_opt);
									if (!_data_opt._m) {
										throw new Error("_opt incorrect");
									}
									if(!_data_opt._p){
										_data_opt['_p'] = _form_data;
									}else{
										_data_opt._p.form_data=_form_data;
									}
									remoteapi(_data_opt);
								}
							}
							return false;//no bubble
						});
					} else {
						my_msg(getI18N('InputNotCorrect'));
					}
					return false;//no bubble
				}
				,success: function(result){}
			});
		};//_func_dlg_crud_on_confirm


//下面那个是最近的实例。。。要从dialog那里找form

				var _func_dlg_transfer_confirm=function(evt_click,_dlg){
					var _fm = _dlg.find("form");
					_fm.form('submit',{
						url:"javascript:alert(901);"//故意的测试不应该出现这个
						,onSubmit:function(x){
							alert('onSubmit='+o2s(x));
							/*
							var _v = _fm.form('validate');
							if (_v) {
								var _form_data= _func_form2obj(_fm) || {};
								$.messager.confirm('Confirm ' + o.action, getI18N('R_U_SURE') + '?', function (r) {
									if (r) {
										var f_remote = true;
										var action_opt = {};
										switch (o.action) {
											case "Edit": action_opt = params.update_opt; break;
											case "Delete": action_opt = params.delete_opt; break;
											case "Add": action_opt = params.create_opt; break;
											default: f_remote = false; my_msg("UnexpectedError 706 " + o.action);
										}
										if (f_remote) {
											//$.messager.progress();
											var _data_opt = $.extend({}, remoteapi_default_opt, action_opt);
											if (!_data_opt._m) {
												throw new Error("_opt incorrect");
											}
											if(!_data_opt._p){
												_data_opt['_p'] = _form_data;
												}else{
												_data_opt._p.form_data=_form_data;
											}
											remoteapi(_data_opt);
										}
									}
									return false;//no bubble
								});
								} else {
								my_msg(getI18N('InputNotCorrect'));
							}
							*/
							return false;//no bubble
						}
						,success: function(result){alert('success='+o2s(result));}//这个不应该走到，因为onSubmit那里其实没有提交...
					});
				};

