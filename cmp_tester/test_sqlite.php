<?php
require_once "../cmp_demo/inc.app.php";

setConf("flag_rb_freeze",false);//before new the rb, important before the database creation

//$rb=new rbWrapper4("sqlite:"._APP_DIR_.DIRECTORY_SEPARATOR."../test_sqlite.db");
#$dsn="db_app";//see test mysql

//$dsn="sqlite:".__DIR__."/test_sqlite.db";//PASSED
$dsn="sqlite:"._TMP_."/test_sqlite.db";
//$dsn="sqlite::memory:";//KO: 这个 不是进程内享的，试出来如果 在不同的句柄打开的话，其它是不同的内存块！
//$dsn="sqlite:memory:;cache=shared";//好像OK

//$rb=new rbWrapper4($dsn);//Passed
$rb=new OrmTest($dsn);

$book = $rb->dispense( 'book' );
//$book2=$rb->load(1);
#$book = $rb->dispenseBean( 'book' );
$book->title='what'.rand();
$book->rate=rand(1,10);
#$book->id=2;
$id=$rb->store($book);
println("id=$id");
println("book=".var_export($book->export(),true));

##example 2
$book2=$rb->load('book',$id);
println("book2=".var_export($book2->export(),true));

##example 3
class OrmBook
	extends OrmTest
{
	var $NAME_R="book";
}

$book_cls=new OrmBook($dsn);
$book3_a=$book_cls->loadBeanArr($id);
println("book3_a=".var_export($book3_a,true));

########## 列出头7个
if(true){
$rsa=$book_cls->PageExecute(array(
	"SELECT"=>"*",
	"FROM"=>"book",

	//TEST 2
	"pageSize"=>7,
	"pageNumber"=>1,

)//,7 TEST 4
);
}else{
//TEST 3
$rsa=$book_cls->PageExecute(array(
	"SELECT"=>"*",
	"FROM"=>"book",
	"LIMIT"=>7,
));
}
//println("PageExecute=".var_export($rsa,true));
println("PageExecute=".mg::o2s($rsa));
//println("count(*)=".var_export($book3_a,true));

