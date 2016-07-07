<?php
class ApiDemo
	extends WebCore //at cmp_ext
{
	//最简单的 PingPong联通测试案例，测试 从 Web 通过 Ajax呼叫后台，后台收到前台的 ping参数，然后返回系统的时间做为 pong，并把两者的差做为 diff，返回到 Web端。
	//本用例能经过简单的扩展达到延伸 SESSION有效期的目的.
	public function PingPong($param){
		//$lang=$this->checkLang();
		//session_start();
		
		$ping = $param['ping'];
		$pong = microtime(true);
		$rt = array(
			"STS" => "OK",
			"ping" => $ping,
			"pong" => $pong,
			"diff" => $pong - $ping,
		);

		$_s=session_id();
		if ($_s){
			$rt['_s'] = $_s;
		}
		return $rt;
	}

	//异常测试案例:故意停超过默认设定30秒执行时间，php应该会执行中断，并丢出异常，异常经过框架封装并丢回给呼叫者进行处理.
	//预期错误结果:
	//{ "errmsg": "Maximum execution time of 30 seconds exceeded", "errno": 1, "line": 24, "module": "ApiDemo" }
	public function TestBlackHole1($param){
		sleep(31);
	}

	//异常测试案例:
	public function TestBlackHole2($param){
		//故意什么都不处理，看看WEB端的处理怎么样
	}

	//////////////////////////////////////////////////
	// 从 Api=>Lgc=>App=>Orm 的四层封装案例演示
	// Api层：负责接受外部呼叫参数，进行简单处理（有时甚至不处理）而转给Lgc（逻辑层）进行处理.
	// Lgc层：负责主要的《业务逻辑》。它一般会呼叫《数据对象》进行读写并应用业务逻辑。
	// App层：《应用数据对象》的封装，在我们的最佳实践经验认为可以它应该是对 Orm层的继承及扩展，从而理解为相对复杂的应用级别的数据对象
	// Orm层：《基本数据对象》的封装，在我们的最佳实践经验认为它适合对 Sql层进行封装、对数据库访问进行封装。

	//** 程序员总是比较懒的，有时在比较简单的业务逻辑时，会有时在 Lgc层会直接使用到 Orm层 而跳过 App层，
	//有时甚至直接在 Api就直接使用Orm层而跳过了Lgc和App层，
	//更有甚者甚至直接在 Api层直接编写完整的 SQL和DB访问 从而连 Orm层都跳过。
	//上述的行为我们非常反对，但是有时将就接受，但希望所有工程师能严格遵循四层规范.

	public function ListTestNote($param){
		//demo安例比较简单，所以先不做处理，直接转给逻辑层跟进.
		$lgc = new LgcDemo;
		return $lgc->ListTestNote($param);
	}
	public function DeleteTestNote($param){
		//啊，有人偷懒直接访问orm层
//		$cls = new OrmDemo(LgcDemo::getDSN());
//		$cls->deleteBean($param['id']);
//		return array("STS"=>"OK");
	
		//正确写法
		$lgc = new LgcDemo;
		return $lgc->DeleteTestNote($param);
	}
	
	public function SaveTestNote($param){
		if($param['id']){
			return self::UpdateTestNote($param);
		}else{
			return self::AddTestNote($param);
		}
	}
	
	public function AddTestNote($param){
		//正确写法
		$lgc = new LgcDemo;
		return $lgc->AddTestNote($param);

//		//啊，有人偷懒直接访问orm层
//		$param['note_status'] = "0";
//		$cls = new OrmDemo(LgcDemo::getDSN());
//		$info = $cls->Insert($param);
//		if($info){
//			return array("STS"=>"OK","data"=>$info);
//		}
//		return array("STS"=>"KO");
	}
	
	public function UpdateTestNote($param){
		//正确写法
		$lgc = new LgcDemo;
		return $lgc->UpdateTestNote($param);

//		//啊，有人偷懒直接访问orm层
//		$cls = new OrmDemo(LgcDemo::getDSN());
//		$info = $cls->Update($param);
//		if($info){
//			return array("STS"=>"OK","data"=>$info);
//		}
//		return array("STS"=>"KO");
	}
}
