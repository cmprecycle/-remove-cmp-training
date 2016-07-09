<?php
/**
 * Q: 这个文件干哈的？
 * A: getConf() / setConf() 是这个框架一个很重要的特性便捷函数，为了让配置的灵活性，所以getConf需要这个文件.
 *
 * Q: 为什么框架不把这个文件合并到core/getConf中？
 * A: 因为代码多样性，框架需要这个文件就可以了，之后都交给应用，换取更大的灵活定制.
 *
 * Q: 为什么有了这个文件还要弄多个 .tmp文件，看上去有点多余？
 * A: 算是最佳实践之一，因为.tmp一般我们不上传，从而让实际环境是要有一个手动配置过程
 * 
 * Q: 可以改写这个文件吗？
 * A: 我们的灵活性超过你的想象，随意根据你的应用改写就可以了. 由于 getConf是框架重大特性，所以最起码要保留 _conf 目录并要有这一行：
 $_conf_all_[$_switch_conf]=$_conf_all_common_;
具体原因要自行看 getConf/setConf
	*
 */
#throw new Exception("Must manually edit config.switch.php");
$SAE=defined('SAE_TMP_PATH') && !$argv[0];//dirty tricks
if($SAE){
	$_switch_conf="dev_sae";//Using SAE config on SAE Env
}else{
	if(file_exists(__DIR__."/config.switch.override.tmp"))
		require(__DIR__."/config.switch.override.tmp");
	else{
		//print "404 config.switch.override.tmp";die;//正常的项目是用这句来提醒部署者要用这个做“开关”文件.
		$_switch_conf="cmp_demo";//demo先跳过.tmp
	}
}

