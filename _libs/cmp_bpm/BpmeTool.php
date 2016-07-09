<?php
class BpmeTool
{
	public static function getClientIP(){
		return mg::get_ip();
	}
	public static function checkCond($flag,$msg_param, $msg_tpl="MSG_ParamIsRequired"){
		return mg::checkCond($flag,$msg_param,$msg_tpl);
	}
	public static function is_array_and_not_empty($a){
		if ( is_array($a) ) {
			if ( count($a)>0 ) return true;
		}
		return false;
	}
	public static function o2s($o){
		return mg::o2s($o);
	}
	public static function s2o($o){
		return mg::s2o($s);
	}

	//@ref http://php.net/manual/zh/function.php-check-syntax.php
	//if no error, returns false.
	//if error, return ($errmsg, $errcode);
	public static function php_syntax_error($code)
	{
		$braces = 0;
		$inString = 0;

		// First of all, we need to know if braces are correctly balanced.
		// This is not trivial due to variable interpolation which
		// occurs in heredoc, backticked and double quoted strings
		foreach (token_get_all('<?php ' . $code) as $token)
		{
			if (is_array($token))
			{
				switch ($token[0])
				{
				case T_CURLY_OPEN:
				case T_DOLLAR_OPEN_CURLY_BRACES:
				case T_START_HEREDOC: ++$inString; break;
				case T_END_HEREDOC:   --$inString; break;
				}
			}
			else if ($inString & 1)
			{
				switch ($token)
				{
				case '`':
				case '"': --$inString; break;
				}
			}
			else
			{
				switch ($token)
				{
				case '`':
				case '"': ++$inString; break;

				case '{': ++$braces; break;
				case '}':
					if ($inString) --$inString;
					else
					{
						--$braces;
						if ($braces < 0) break 2;
					}

					break;
				}
			}
		}

		// Display parse error messages and use output buffering to catch them
		$inString = @ini_set('log_errors', false);
		$token = @ini_set('display_errors', true);
		ob_start();

		// If $braces is not zero, then we are sure that $code is broken.
		// We run it anyway in order to catch the error message and line number.

		// Else, if $braces are correctly balanced, then we can safely put
		// $code in a dead code sandbox to prevent its execution.
		// Note that without this sandbox, a function or class declaration inside
		// $code could throw a "Cannot redeclare" fatal error.

		$braces || $code = "if(0){{$code}\n}";

		if (false === eval($code))
		{
			if ($braces) $braces = PHP_INT_MAX;
			else
			{
				// Get the maximum number of lines in $code to fix a border case
				false !== strpos($code, "\r") && $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
				$braces = substr_count($code, "\n");
			}

			$code = ob_get_clean();
			$code = strip_tags($code);

			// Get the error message and line number
			if (preg_match("'syntax error, (.+) in .+ on line (\d+)$'s", $code, $code))
			{
				$code[2] = (int) $code[2];
				$code = $code[2] <= $braces
					? array($code[1], $code[2])
					: array('unexpected $end' . substr($code[1], 14), $braces);
			}
			else $code = array('syntax error', 0);
		}
		else
		{
			ob_end_clean();
			$code = false;
		}

		@ini_set('display_errors', $token);
		@ini_set('log_errors', $inString);

		return $code;
	}

	//https://gist.github.com/1965669
	//is an associative-array as array("k"=>"v") not array("a","b","c")
	public static function is_assoc($array){
		return (array_values($array) !== $array);
	}

	public static function isLocalIp($ip){
		return in_array($ip,array('127.0.0.0','::1'));
	}
	public static function buildMsg($msg_tpl,$msg_param){
		return vsprintf(getLang($msg_tpl),$msg_param);
	}
	//Exception To Array
	public static function err2a($ex, $flag_no_trace=true){
		$rt= global_error_handler2($ex);//in inc.v5.globalerror
		$rt['file']=basename($rt['file'],".php");
		if(true==$flag_no_trace){
			unset($err['trace']);
			unset($err['trace_s']);
		}
		return $rt;
	}
	public static function startsWith($haystack, $needle){
		return $needle === "" || strpos(ltrim($haystack), $needle) === 0;
	}
	public static function endsWith($haystack, $needle){
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}

	//把ifdl文件编译成 bpm 数据结构
	//public static function compile_ifdl_to_bpm($ifdl_file){
	//}

	public static function compile_camunda_to_bpm($camunda_file, $flgCache=true){
		//$rt_start=array();
		//$rt_end=array();
		$properties=array();//predefined properties of the bpm...

		$all=array();
		self::checkCond(!file_exists($camunda_file),array(basename($camunda_file)),"File Not Exists %s");

		$filemtime=filemtime($camunda_file);
		if(!$filemtime){ throw new Exception("Unknow filemtime of ".basename($camunda_file)); }

		//cache handling...
		$cache_file=_TMP_ ."/$filemtime.".basename($camunda_file) .".cache";
		if($flgCache){
			$cache_file_mtime=filemtime($cache_file);
			if(!$cache_file_mtime){
				$rt=array();
			}else{
				//return cached result if valid.
				$full_s=file_get_contents($cache_file);
				eval("\$rt=".$full_s.";");//trick
				if($rt){
					return $rt;
				}else{
					$rt=array();
				}
			}
		}

		$s=file_get_contents($camunda_file);

		$s=str_ireplace("camunda:","camunda_",$s);//special tmp solution for camunda: tag or attr
		$xml=simplexml_load_string($s);

		$collaboration= $xml->xpath('.//bpmn:collaboration');
		foreach($collaboration as $c){
			foreach($c->xpath('.//camunda_property') as $e){
				$attr=$e->attributes();
				$name=(string) $attr->name;
				$value=(string) $attr->value;
				$properties[$name]=$value;
			}
		}

		$process_a = $xml->xpath('//bpmn:process');
		foreach($process_a as $p){
			foreach($p->xpath('*') as $e){
				$attr=$e->attributes();
				$id=(string)$attr->id;

				$_m=$id;

				$title=(string)$attr->name;
				$tagname=ucfirst($e->getName());

				$a_properties=array();
				foreach($e->xpath('.//camunda_property') as $ee){
					$attr=$ee->attributes();
					$name=(string) $attr->name;
					$value=(string) $attr->value;
					if($name!="")
					$a_properties[$name]=trim($value);
					if($name=='_m' && trim($value)){
						//IMPORTANT if found the _m, use it
						//$_m=$id=$a['_m']=trim($value);
						$_m=$id=$a['_m']=trim($value);
					}
				}

				$a=array('id'=>$id, 'type'=>$tagname, 'title'=>$title, 'name'=>$_m);

				if($all[$id]) throw new Exception("BPMN DESIGN ERROR: Element $id exists more than one");
				
				if($a_properties) $a['properties']=$a_properties;
				
				if(self::endsWith($tagname,"Event")){
					//special start event
					if(self::startsWith($tagname,"StartEvent")){
						$StartEvent[]=$a;
					}
					$Event[]=$a;
				}elseif(self::endsWith($tagname,"Gateway")){
				}elseif(self::endsWith($tagname,"SequenceFlow")){
					//SKIP
				}elseif(self::endsWith($tagname,"TextAnnotation")){
					//SKIP
				}elseif(self::endsWith($tagname,"Association")){
					//SKIP
				}elseif(self::startsWith($tagname,"DataObject")){
					//SKIP
				}elseif(self::endsWith($tagname,"Task") || self::endsWith($tagname,"Activity")){
					if($scriptFormat = (string)$attr['scriptFormat'])
						$a['scriptFormat']=$scriptFormat;

					//our quick tricks.
					//support inline script or method decared in camunda:resource
					$script_s="";
					foreach($e->xpath('bpmn:script') as $e1){
						$script_s.=trim((string)$e1);
					}
					//camunda ext
					foreach($e->xpath('.//camunda_script') as $e1){
						$script_s.=trim((string)$e1);
					}
					//@camunda:resource
					$camunda_resource=(string)$attr['camunda_resource'];
					if($camunda_resource){
						//TODO 如果是 [_a-zA-Z0-9]
						if(preg_match("/[_a-zA-Z0-9]*/",$camunda_resource)>0){
							$script_s.="\$this->".$camunda_resource."();";//design convention
						}else{
							throw new Exception("TODO camunda_resource $camunda_resource");
							//TODO maybe external file
							//resource not supported yet
						}
					}else{
						if(self::startsWith($tagname,"ScriptTask")){
							//For ScriptTask, if no [inline script] or [external resource] then use id instead as the quick way
							if(!$script_s) $script_s.="\$this->$id();";
						}
					}
					if($script_s){
						$check_result=self::php_syntax_error($script_s);
						if(!$check_result){
							//if no error
							//TODO WARNING 如果bpm不是开发者设计部署而是用户提交的话，脚本最好需要进行敏感词检查的....
							$a["script"]=$script_s;
						}else{
							list($errmsg,$errcode)=$check_result;
							$a["script_error"]=array("code"=>$script_s,"errmsg"=>$errmsg,"errcode"=>$errcode);
						}
					}
					$Activity[]=$a;
				}else{
					$Todo[]=$a;
				}
				if($id)
				$all[$id]=$a;
			}
		}

		//$sequenceFlow = $xml->xpath('.//bpmn:sequenceFlow');
		$sequenceFlow = $xml->xpath('//bpmn:sequenceFlow');
		foreach($sequenceFlow as $e){
			$type="SequenceFlow";
			$attr=$e->attributes();
			$id=(string)$attr->id;
			$name=(string)$attr->name;
			$src=(string)$attr->sourceRef;
			$tgt=(string)$attr->targetRef;
			$src_type=array();
			if($found=$all[$src]){
				$src_type[]=$found['type'];
			}
			$src_type_s=join(",",$src_type);

			$tgt_type=array();
			if($found=$all[$tgt]){
				$tgt_type[]=$found['type'];
			}
			$tgt_type_s=join(",",$tgt_type);

			if($src && !$src_type_s){
				//throw new Exception("Incorrect in bpmn $_c of $tgt");
			}
			if($tgt && !$tgt_type_s){
				//throw new Exception("Incorrect in bpmn $_c of $tgt");
			}
			if(!$name) $name=$id;
			//$FSM[$id]=$a=array("id"=>$id,"type"=>$type,//"name"=>$name,
			//	"src"=>$src,"tgt"=>$tgt,"src_type"=>$src_type_s,"tgt_type"=>$tgt_type_s);
			////if($all[$id]) throw new Exception("Element $id exists");
			////$all[$id]=$a;
			$idx_src[$src][]=array("link_id"=>$id,//"link_name"=>$name,
				"tgt"=>$tgt);
			$idx_tgt[$tgt][]=array("link_id"=>$id,//"link_name"=>$name,
				"src"=>$src);
		}

		if($Todo) $rt['Todo']=$Todo;

		$rt['all']=$all;//ID TO Element
		$rt['idx_src']=$idx_src;
		$rt['idx_tgt']=$idx_tgt;
		$rt['properties']=$properties;

		//cache writing...
		if($rt && $flgCache){
			$full_s=var_export($rt,true);
			$full_s=str_replace("&#38;","&",$full_s);//TMP HACK
			#file_put_contents($cache_file,"<"."?php\n\$getXlsArrFile_rt=$full_s;");
			file_put_contents($cache_file,$full_s);

			//TODO 顺便用工具生成 jpg
			//@ref https://bpmn.io/blog/posts/2014-bpmn-js-viewer-is-here.html
			//@ref https://github.com/bpmn-io/bpmn-js-examples/tree/master/simple-commonjs
			//@ref https://github.com/bpmn-io/bower-bpmn-js
			//@ref https://github.com/bpmn-io/bpmn-js/blob/master/lib/Viewer.js
			//@ref https://github.com/bpmn-io/diagram-js
			//NOTES: 思路就是用nodejs命令行、呼叫BpmnViewer得到渲染再保存.
			//Q: 非浏览器环境下 canvas好像无效，所以可能要用到NWJS...
			//A: 不用，我找到这个
			//https://changelog.com/node-canvas-render-and-stream-html5-canvas-using-node-js/
			//https://github.com/Automattic/node-canvas
			//即用server side 的 node-canvas 模仿html5的canvas特性，从而不需要浏览器以来也可以把 bpmn xml生成 svg图了
		}
		return $rt;
	}
	//TODO compile .pos(processon.com) into our array
	public static function compile_processon_to_bpm($processon_file, $flgCache=true){
		$debug=quicklog();
		//print "debug=".var_export($debug,true)."\n";
		//$rt_start=array();
		//$rt_end=array();
		$properties=array();//predefined properties of the bpm...

		$all=array();

		self::checkCond(!file_exists($processon_file),array(basename($processon_file)),"File Not Exists %s");

		$filemtime=filemtime($processon_file);
		if(!$filemtime){ throw new Exception("Unknow filemtime of ".basename($processon_file)); }

		//cache handling...
		$cache_file=_TMP_ ."/$filemtime.".basename($processon_file) .".cache";
		if($flgCache){
			$cache_file_mtime=filemtime($cache_file);
			if(!$cache_file_mtime){
				$rt=array();
			}else{
				//return cached result if valid.
				$full_s=file_get_contents($cache_file);
				eval("\$rt=".$full_s.";");//trick
				if($rt){
					return $rt;
				}else{
					$rt=array();
				}
			}
		}


		$s=file_get_contents($processon_file);
		$o=mg::s2o($s);

		$name2id=array();
		$id2name=array();
		$elements = $o['diagram']['elements']['elements'];
		
		foreach($elements as $id => $element){

			//$title=$element['title'];
			$textBlock=$element['textBlock'];
			$title_sub="";
			foreach($textBlock as $t){
				if($t['text']){
					if($title_sub){
						$title_sub.="\n";
					}
					$title_sub.=$t['text'];
				}
			}
			$a_properties=array();
			$dataAttributes=$element['dataAttributes'];
			foreach($dataAttributes as $t){
				if($t['value']){
					$a_properties[$t['name']]=$t['value'];
				}
			}

			$_m=$id;
			if($a_properties){
				//IMPORTANT: if found _m, use it as id !!
				if($a_properties['_m']){
					$_m=$a_properties['_m'];
					$name2id[$_m]=$id;
					$id2name[$id]=$_m;
					$id=$_m;
				}
			}
			$tagname=ucfirst($element['name']);
			$a=array("id"=>$id,"type"=>$tagname);
			
			if($all[$id]) throw new Exception("BPMN DESIGN ERROR: Element $id exists more than onetime");

			if($a_properties){
				$a['properties']=$a_properties;
			}

			if(self::endsWith($tagname,"Event")){
				if($title_sub)
				$a['title']=$title_sub;
				//special start event
				if(self::startsWith($tagname,"StartEvent")){
					$StartEvent[]=$a;
				}
				$Event[]=$a;
				$a['pos']=$element['props'];//info about pos etc.
			}elseif(self::endsWith($tagname,"Pool")){
				//SKIP
			}elseif(self::endsWith($tagname,"Gateway")){
				if($title_sub)
				$a['title']=$title_sub;
				$a['pos']=$element['props'];//info about pos etc.
			}elseif(self::endsWith($tagname,"Linker")){
				//$Todo[]=$element;
				$flag_skip=false;
				$lineStyle_0=$element['lineStyle'];
				if($lineStyle_0){
					$lineStyle=$lineStyle_0['lineStyle'];
					if($lineStyle=='dashed'){
						$flag_skip=true;
					}
				}
				if($flag_skip){
				}else{
					$Linker[]=$element;
					$a['pos']=$element['props'];//info about pos etc.
					$a['name']=$element['text'];//use the .text as .name for Linker(at)processon
				}
			}elseif(self::endsWith($tagname,"VerticalSeparator")){
				//SKIP
			}elseif(self::endsWith($tagname,"HorizontalSeparatorBar")){
				//SKIP
			}elseif(self::endsWith($tagname,"TextAnnotation")){
				//SKIP
			}elseif(self::endsWith($tagname,"Association")){
				//SKIP
			}elseif(self::endsWith($tagname,"DataStore")){
				//SKIP
			}elseif(self::startsWith($tagname,"DataObject")){
				//SKIP
				if($title_sub)
				$a['title']=$title_sub;
				$Activity[]=$a;
				//$a['pos']=$element['props'];//info about pos etc.
				if($title_sub=='BpmConfig'){
					//Assumption
					$properties=$a_properties;
				}
			}elseif(self::endsWith($tagname,"Task") || self::endsWith($tagname,"Activity")){

				if($title_sub)
				$a['title']=$title_sub;
				$Activity[]=$a;
				$a['pos']=$element['props'];//info about pos etc.
			}else{
				$Todo[]=$a;
				$a['pos']=$element['props'];//info about pos etc.
			}
			if($id) $all[$id]=$a;
		}

		foreach($Linker as $e){
			$type="SequenceFlow";

			$id=$e['id'];
			//$name=$e['name'];
			//$name=$all[$id]['name'];
			$src=$e['from']['id'];
			//if($name2id[$src]) $src=$name2id[$src];
			if($id2name[$src]) $src=$id2name[$src];
			$tgt=$e['to']['id'];
			//if($name2id[$tgt]) $tgt=$name2id[$tgt];
			if($id2name[$tgt]) $tgt=$id2name[$tgt];
			$src_type=array();
			if($found=$all[$src]){
				$src_type[]=$found['type'];
			}
			$src_type_s=join(",",$src_type);

			$tgt_type=array();
			if($found=$all[$tgt]){
				$tgt_type[]=$found['type'];
			}
			$tgt_type_s=join(",",$tgt_type);
			$idx_src[$src][]=array("link_id"=>$id,//"link_name"=>$name,
				"tgt"=>$tgt);
			$idx_tgt[$tgt][]=array("link_id"=>$id,//"link_name"=>$name,
				"src"=>$src);
		}

		if($Todo) $rt['Todo']=$Todo;

		$rt['all']=$all;
		$rt['idx_src']=$idx_src;
		$rt['idx_tgt']=$idx_tgt;
		$rt['properties']=$properties;
		$rt['name2id']=$name2id;
		$rt['id2name']=$id2name;

		//cache writing...
		if($rt && $flgCache){
			$pngdata = $o['diagram']['image']['pngdata'];
			if($pngdata){
				$rt['pngfile']=$cache_file.'.png';
				file_put_contents($cache_file.'.png',base64_decode($pngdata));
			}
			$full_s=var_export($rt,true);
			$full_s=str_replace("&#38;","&",$full_s);//TMP FIX
			file_put_contents($cache_file,$full_s);
		}
		return $rt;
	}
}

