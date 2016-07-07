<?php
require_once "../saas_ace/inc.ace.php";

$rt=cmp2015::handleWeb(array(
	"defaultClass"=>"WebCmpDemo",
	"defaultMethod"=>"DefaultIndex",
	"returnFormat"=>$g_return_format,//shtml|plain|json|jsonp|xml
	"APP_NAME"=>"cmp_demo",
));

