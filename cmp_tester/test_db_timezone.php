<?php
require_once "../cmp_demo/inc.app.php";

$APP_NAME="test";

print "db_time<pre>";

println(microtime(true)."<hr/>");

$orm=new OrmTest("db_local");

$rsa=$orm->getAll("SHOW VARIABLES WHERE variable_name LIKE ?",array('%zone%'));
println("rsa=");
println($rsa);
println("<br/>");

println($orm->isoDateTime());
die;

