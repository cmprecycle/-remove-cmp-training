<?php
require_once "../cmp_demo/inc.app.php";

println("date_default_timezone_get=".date_default_timezone_get());
println("my_isoDateTime=");
println(my_isoDateTime());
adjust_timezone();
println("my_isoDateTime=");
println(my_isoDateTime());
println("date_default_timezone_get=".date_default_timezone_get());

