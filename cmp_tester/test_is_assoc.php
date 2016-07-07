<?php
require_once "../cmp_demo/inc.app.php";

$test1=array(
	1,2,3
);
println("test1 is_assoc=".var_export(mg::is_assoc($test1),true));
#println(mg::is_assoc($test1));
$test2=array(
	"1"=>true,"2"=>false,
);
println("test2 is_assoc=");
println(mg::o2s(mg::is_assoc($test2)));
//println(mg::o2s($test2));

