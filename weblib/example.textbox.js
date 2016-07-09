//http://www.jeasyui.com/documentation/numberbox.php
//
//不要直接 .val() 要用 easyUI的语法:
//$("#filter_tenant_code").textbox('setValue','a');

//<input type="text" class="easyui-numberbox" value="100" data-options="min:0,precision:2,groupSeparator:',',max:99999">
//
Your Current Balance: <input name="acct_balance" href="javascript:void(0);"
class="easyui-numberbox" data-options="min:0,precision:2,groupSeparator:',',max:99999" style="width:90px" /><br/>

//,onChange:xxxx
//function xxxx(newV,oldV){
//	alert(newV+','+oldV);
//}
//
