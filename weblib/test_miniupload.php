<?php
//error_reporting(E_ERROR|E_COMPILE_ERROR|E_PARSE|E_CORE_ERROR|E_USER_ERROR);
if(!defined("_APP_DIR_")) define("_APP_DIR_",realpath(dirname(__FILE__)));
require "../_libs/cmp_core/inc.cmp_core.php";

$whitelist_image = array('jpg', 'jpeg', 'png', 'gif','bmp','tiff');
$whitelist_attach = array('jpg',"jpeg","bmp","png","ico","ppt","pptx","doc","docx","xls","xlsx","txt","rar","pdf");

function getUniqueID(){
	$ip = ip2long($_SERVER['REMOTE_ADDR']);//WHY REMOTE IP...
	$ip = base_convert(sprintf('%u', $ip), 10, 36);
	$ip = str_pad($ip, 7, '0');
	$ip = substr($ip, 0, 7);
	$timestamp=microtime(true);
	$timestamp=str_replace(".","_",$timestamp);
	$id=$ip."_".$timestamp;
	return $id;
}

$whitelist=$whitelist_attach;

do{
	//$uploadDir=getConf("UPLOAD_DIR");
	//if(!$uploadDir){
	//	$rt['errmsg']="not configurated UPLOAD_DIR";
	//	break;
	//}

	//测试用TMP
	$uploadDir=_APP_DIR_."/_tmp";
	if(!is_dir($uploadDir)){
		if(mkdir($uploadDir)){
		}else{
			$rt['errmsg']='failed to mkdir';
			quicklog_must("IT-CHECK","unable to mkdir $uploadDir");
			break;
		}
	}
	$log_id=_getbarcode(8);
	//if($rt['errmsg']){
	//$php_input = file_get_contents('php://input');
	//if($php_input){
	//	if(!$GLOBALS['HTTP_RAW_POST_DATA'])
	//		$GLOBALS['HTTP_RAW_POST_DATA']=$php_input;//store for later usage if needed
	//}else{
	//	if($GLOBALS['HTTP_RAW_POST_DATA'])
	//		$php_input=$GLOBALS['HTTP_RAW_POST_DATA'];
	//}
	//quicklog_must("IT-CHECK", "$log_id php_input=".$php_input);
	//quicklog_must("IT-CHECK", "$log_id POST=");
	//quicklog_must("IT-CHECK", $_POST);
	//}
	quicklog_must("IT-CHECK","$log_id _FILES=");
	quicklog_must("IT-CHECK",$_FILES);
	$rt['log_id']=$log_id;

	//TODO
	//$ymd = date("Ymd");
	//$this->base_dir .= "/".$ymd;
	//$this->relative_dir.="/".$ymd;
	
	if (isset($_FILES) && count($_FILES)>0){
		foreach($_FILES as $upload_key=>$file_info){

			//$file_info=$_FILES[$upload_key];

			$file_size = $file_info['size'];
			$file_type = $file_info['type'];
			$file_name = $file_info['name'];
			$file_error= $file_info['error'];

			//获得文件扩展名
			$temp_arr = explode(".", $file_name);
			$file_ext = array_pop($temp_arr);
			$file_name_only=substr($file_name,0,strlen($file_name)-strlen($file_ext)-1);
			$file_ext = trim($file_ext);
			$file_ext = strtolower($file_ext);
			
			$file_tmp_name=$file_info['tmp_name'];

			//TMP PATCH FOR special file name...
			$file_name=iconv('UTF-8','GB2312',$file_name);
			$file_tmp_name=iconv('UTF-8','GB2312',$file_tmp_name);

			$rt['file_upload_name']=$file_name;
			$rt['file_size']=$file_size;
			$rt['file_type']=$file_type;
			$rt['file_ext']=$file_ext;
			$rt['file_name']=$file_name;
			$rt['file_name_only']=$file_name_only;

			if ($file_error === UPLOAD_ERR_OK) {
				$extension = pathinfo($file_name, PATHINFO_EXTENSION);

				if (!in_array($extension, $whitelist)) {
					$rt['errmsg']='Wrong file type';
				} else {
					//$tmp_file_name=utf8_encode($file_name);//TODO

					$_funq=date("YmdHis")."-".getUniqueID();
					$file_unique_name=$_funq."-".$file_name;
					$file_icon_name=$_funq."-".$file_name_only."-icon.".$file_ext;

					if(move_uploaded_file($file_tmp_name, "$uploadDir/$file_unique_name")){
						//安全原因，LIVE要隐藏. 
						$rt['file_tmp_name']=$file_tmp_name;
						$rt['file_unique_name']=$file_unique_name;
						$rt['file_icon_name']=$file_icon_name;
						$rt['file_url']=$file_name;
						//$rt['STS']='OK';
						$rt['success']=$rt['STS']='OK';
						quicklog_must("IT-CHECK", var_export($rt,true));

						//TODO
						//if($this->saveIcon){
						//	$rt_small=dealPicture::createSmallImag($base_file_url,$this->iconW,$this->iconH,$base_icon_url);//创建缩略图
						//}
						
						//TODO
						//$rt['relative_url']=$this->relative_dir."/".$file_unique_name;
						//$rt['relative_icon_url']=$this->relative_dir."/".$file_icon_name;
					}else{
						$rt['errmsg']='failed move the uploaded file';
						quicklog_must("IT-CHECK","$log_id failed $file_tmp_name => $uploadDir/$file_name");
						break;
					}
				}
			}else{
				if($file_error){
					$rt['errmsg']="Upload Error $log_id($file_error)";//TODO 用CODE找出这个错误的TEXT.
				}else{
					$rt['errmsg']="Upload Error $log_id";
				}
			}
			break;//TODO 暂时先处理一个文件、省事！
		}
	}else{
		$rt['errmsg']='Not found upload:_FILES';
	}
}while(false);//Only Once

//print(json_encode($rt));
println($rt);
/* UTF-8 头
<!DOCTYPE html><head><meta charset="utf-8" /><meta name="apple-mobile-web-app-title" content="Loading..." />
<meta name="viewport" content="target-densitydpi=medium-dpi,width=device-width,initial-scale=1.0,minimum-scale=0.5,maximum-scale=2.0,user-scalable=yes" />
<meta name="format-detection" content="telephone=no" ><meta name="apple-mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="white" /></head><body>
 */
