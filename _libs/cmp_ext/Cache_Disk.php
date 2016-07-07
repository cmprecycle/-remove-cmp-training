<?php
class Cache_Disk
{
	public static function load($cache_id,$life_time=1){
		$conf=getConf("cache_lite");
		$dir_name=$conf['dir_name'];
		require_once(_LIB_ ."/$dir_name/Lite.php");

		$options = array( 'cacheDir' => $conf['cache_dir'], 'lifeTime' => $life_time);
		$Cache_Lite = new Cache_Lite($options);
		if (!$Cache_Lite) { throw new Exception(getLang("Cache_Lite_Config_Error")); }
		$data = $Cache_Lite->get($cache_id);
		if($data){
			//return my_json_decode($data);
			return json_decode($data,true);
		}
		return false;
	}
	public static function remove($cache_id,$life_time=1)
	{
		$conf=getConf("cache_lite");
		$dir_name=$conf['dir_name'];
		require_once(_LIB_ ."/$dir_name/Lite.php");

		$options = array( 'cacheDir' => $conf['cache_dir'], 'lifeTime' => $life_time);
		$Cache_Lite = new Cache_Lite($options);
		if (!$Cache_Lite) { throw new Exception(getLang("Cache_Lite_Config_Error")); }
		$Cache_Lite->Remove($cache_id);
		return true;
	}
	public static function save($cache_id,$data,$life_time=3){
		$conf=getConf("cache_lite");
		$dir_name=$conf['dir_name'];
		require_once(_LIB_ ."/$dir_name/Lite.php");

		$options = array( 'cacheDir' => $conf['cache_dir'], 'lifeTime' => $life_time);
		$Cache_Lite = new Cache_Lite($options);
		if (!$Cache_Lite) { throw new Exception(getLang("Cache_Lite_Config_Error")); }
		//if(is_array($val) || is_object($val)){
			$Cache_Lite->save(json_encode($data),$cache_id);
		//}else{
		//	$Cache_Lite->save("".$data,$cache_id);
		//}
	}
}
