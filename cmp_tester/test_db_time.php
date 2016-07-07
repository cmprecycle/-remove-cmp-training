<?php
require_once "../cmp_demo/inc.app.php";

#$APP_NAME="test";

setConf("flag_rb_freeze",false);//初次跑可能还没有结构

print "db_time<pre>";

#println(microtime(true)."<hr/>");

##$orm=new OrmTest;//会报错，因为现在ORM要求显式定义DSN入口
$dsn="db_local";//配置在../cmp_demo/_conf.cmp_demo/
$orm=new OrmTest($dsn);

println(microtime(true)."<hr/>");
$db_time=$orm->getDbTimeStamp();
println($db_time);
println(microtime(true)."<hr/>");

$db_time=$orm->getDbTimeStamp();
println($db_time);
//println($orm->isoDateTime($db_time));
println($orm->isoDateTime());
println(microtime(true)."<hr/>");

$db_isoDate=$orm->isoDate();
$db_isoDateTime=$orm->isoDateTime();
println("isoDate=$db_isoDate,isoDateTime=$db_isoDateTime");
