<?php
//CMP-NOTE: 我们从大概2008年开始引入这个 DzTemplate 类
/**
 * Note that DIRECTORY_SEPARATOR is still useful for things like exploding a path that the system gave you.
 * The proper way to write it in Windows would be "\" while in Unix it would be "/".
 */
if(!defined('DIR_SEP')) {
	define('DIR_SEP', DIRECTORY_SEPARATOR);
}

//Func for Callback
require_once('DzTemplate.func.php');

class DzTemplate {
	private static $flagSincePHP7= false;//20150926.wjc
	/**
	 * the instance of DzTemplate Class
	 *
	 * @static
	 * @var object
	 * @access private
	 */
	private static $_instance = null;

	/**
	 * the config options of DzTemplate Class
	 *
	 * @var array
	 * @access private
	 */
	private $_options = array();

	/**
	 * the class constructor
	 *
	 * @access private
	 * @return void
	 */
	private function __construct() {
		self::$flagSincePHP7=(version_compare(PHP_VERSION,"6")>0);//20150926.wjc
		$this->_options = array(
			'template_dir' => 'templates'.DIR_SEP,  // The name of the directory where templates are located
			'cache_dir' => 'cache'.DIR_SEP,         // The name of the directory for cache files
			'left_delimiter' => '{',                // The left delimiter used for the template tags
			'right_delimiter' => '}',               // The right delimiter used for the template tags
			'compile_check' => true,                // This tells DzTemplate whether to check for recompiling or not
			'cache_lifetime' => 0                   // Number of seconds cached content will persist, 0 = never expires
		);
	}

	/**
	 * get the instance of DzTemplate Calss
	 *
	 * @static
	 * @access public
	 * @return void
	 */
	public static function getInstance() {
		if(self::$_instance == null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * set the config options of DzTemplate by array
	 *
	 * @param array $options
	 * @access public
	 * @return void
	 */
	public function setOptions(array $options) {
		foreach($options as $name => $value) {
			$this->set($name, $value);
		}
	}

	/**
	 * assign value to the given option
	 *
	 * @param string $name
	 * @param mixed $value
	 * @access public
	 * @return void
	 */
	public function set($name, $value) {
		switch($name) {
		case 'template_dir':
			$value = $this->trimPath($value);
			if(!file_exists($value)) {
				$this->throwException("Template directory \"$value\" not found or have no access!");
			}
			$this->_options['template_dir'] = $value;
			break;
		case 'cache_dir':
			#$value = $this->trimPath($value);
			if(!file_exists($value)) {
				#$this->throwException("$value not found or have no access!");
			}
			#chmod($value, 0755);
			$this->_options['cache_dir'] = $value;
			break;
		case 'left_delimiter':
			$this->_options['left_delimiter'] = preg_quote($value);
			break;
		case 'right_delimiter':
			$this->_options['right_delimiter'] = preg_quote($value);
			break;    			
		case 'compile_check':
			$this->_options['compile_check'] = (boolean) $value;
			break;
		case 'cache_lifetime':
			$this->_options['cache_lifetime'] = (int) $value;
			break;
		default:
			$this->throwException("Unknown config option \"$name\"");
		}
	}

	/**
	 * __set
	 *
	 * @see DzTemplate::set()
	 * @param string $name
	 * @param mixed $value
	 * @access public
	 * @return void
	 */
	public function __set($name, $value) {
		$this->set($name, $value);
	}

	/**
	 * trying to clone an instance results in an error
	 *
	 * @access public
	 * @return void
	 */
	public function __clone() {
		$this->throwException("Instance is not allowed to clone!");
	}

	/**
	 * throw a new exception with message
	 *
	 * @param string $msg
	 * @access protected
	 * @return void
	 */
	protected function throwException($msg) {
		throw new Exception($msg);
	}

	/**
	 * trim path according to OS (Windows or Unix)
	 *
	 * @param string $path
	 * @access protected
	 * @return void
	 */
	protected function trimPath($path) {
		return str_replace(array('/', '\\', '//', '\\\\'), DIR_SEP, $path);
	}

	/**
	 * get the absolute path of this template
	 *
	 * @param string $tpl
	 * @access protected
	 * @return void
	 */
	protected function getTemplatePath($tpl){
		if(file_exists($tpl)){
			$_p=$tpl;
		}else{
			$_p=$this->_options['template_dir'].DIR_SEP.$tpl;
		}
		$rt=$this->trimPath($_p);
		return $rt;
	}

	protected function genCacheFileName($filename){
		if( file_exists($filename) ){
			$rt=$filename;
		}else{
			$rt=$this->_options['template_dir'].DIR_SEP.$filename;
			if(! file_exists($rt)) throw new Exception("tpl filename not found $filename");
		}
		$rt=realpath($rt);
		if(! file_exists($rt)) throw new Exception("genCacheFileName tpl filename not found $rt");

		$mt=filemtime($rt);
		//translate all special path chr to _
		$rt_full=str_replace(array(':','/', '\\', '//', '\\\\'), ",", realpath($rt));
		//get only last 64
		$rt_full=substr($rt_full,-56);
		$rt=$rt_full."_".$mt.".cache";//方便svn忽略..
		//print "genCacheFileName=$rt";die;
		return $rt;
	}

	/**
	 * get the absolute path of cache file for the given template
	 *
	 * @param string $tpl
	 * @access protected
	 * @return void
	 */
	protected function getCachePath($tpl) {
		$cache_file=$this->genCacheFileName($tpl);
		//$rt=$this->trimPath( realpath($this->_options['cache_dir']).DIR_SEP.$cache_file);
		$rt=$this->_options['cache_dir'].DIR_SEP.$cache_file;
		return $rt;
	}

	/**
	 * check cached content for the given template whether to recompile or not
	 *
	 * @param string $tpl
	 * @param string $chk_tag
	 * @param integer $expire_time
	 * @access public
	 * @return void
	 */
	public function checkCache($tpl, $chk_tag, $expire_time) {
		//if($this->_options['compile_check'] && md5_file($this->getTemplatePath($tpl)) != $chk_tag)
		if($this->_options['compile_check'] && filemtime($tpl) != $chk_tag)
		{
			###quicklog_must("TPL","checkCache saveCache $tpl");
			$this->saveCache($tpl);
		}else
			if($this->_options['cache_lifetime'] != 0 && (time() - $expire_time >= $this->_options['cache_lifetime'])) {
				###quicklog_must("TPL","2 checkCache saveCache $tpl");
				$this->saveCache($tpl);
			}
	}

	/**
	 * parse the template & replace the template tags
	 *
	 * @param string $tpl
	 * @access public
	 * @return void
	 */
	public function parseTemplate($tpl) {
		$tpl_path = $this->getTemplatePath($tpl);
		//print "parseTemplate:$tpl_path";die;

		if(!is_readable($tpl_path)) {
			#$this->throwException("parseTemplate: Current template file ".basename($tpl,".htm")." not found or have no access!");
			$this->throwException("parseTemplate: Current template file $tpl ($tpl_path) not found or have no access!");
			//return "<b>[Failed ".basename($tpl,".htm")."]</b>";
		}

		$template = file_get_contents($tpl_path);
		//$template=str_replace('<'.'?xml version="1.0"?'.'>','',$template);//patch to remove this stuff of xml...
		$template = preg_replace("/<\?xml\s+[^\>]*>/is","",$template);//patch for xml template...

		//delete the comments first...
		if(self::$flagSincePHP7){
			$template = preg_replace_callback("/\{\*.*?\*\}/is", function($m){
				return "";
			}, $template);
		}else{
			$template = preg_replace("/\{\*.*?\*\}/ies", "", $template);//TMP should do super parentheses matching?
		}

		$template = preg_replace(
			"/".$this->_options['left_delimiter']."(.+?)".$this->_options['right_delimiter']."/s",
			"{\\1}",
			$template
		);

		//$template = preg_replace("/\{lang\s+(.+?)\}/ies", "languagevar('\\1')", $template);
		if(self::$flagSincePHP7){
			$template = preg_replace_callback("/\{filetime\s+(.+?)\}/is", function($m){
				return _dz_function_filetime($m[1]);
			}, $template);
		}else{
			$template = preg_replace("/\{filetime\s+(.+?)\}/ies", "_dz_function_filetime('\\1')", $template);
		}
		if(self::$flagSincePHP7){
			$template = preg_replace_callback("/\{url\s+(.+?)\}/is", function($m){
				return _dz_function_url($m[1]);
			}, $template);
		}else{
			$template = preg_replace("/\{url\s+(.+?)\}/ies", "_dz_function_url('\\1')", $template);
		}
		$template = str_replace("{LF}", "<?=\"\\n\"?".">", $template);

		$var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"."(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
		$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?php echo \\1; ?".">", $template);
		//$template = preg_replace("/$var_regexp/es", "addquote('<?=\\1?".">')", $template);
		//$template = preg_replace("/\<\?\=\<\?\=$var_regexp\?\>\?\>/es", "addquote('<?=\\1?".">')", $template);

		//$template = preg_replace(
		//	"/[\n\r\t]*\{inc\s+([a-z0-9_]+)\}[\n\r\t]*/is",
		//	"\r\n<? include(DzTemplate::getInstance()->fetchCache('\\1')); ?".">\r\n",
		//	$template
		//);
		if(self::$flagSincePHP7){
			$template = preg_replace_callback(
				"/[\n\r\t]*\{inc2\s+(.+?)\}[\n\r\t]*/is",
				function($m){
					return stripvtags('<?php include(DzTemplate::getInstance()->fetchCache('.$m[1].')); ?'.'>');
				}, $template);
		}else{
			$template = preg_replace(
				"/[\n\r\t]*\{inc2\s+(.+?)\}[\n\r\t]*/ies",
				"stripvtags('<?php include(DzTemplate::getInstance()-".">fetchCache(\\1)); ?".">','')",
				$template
			);
		}
		$template = preg_replace(
			"/[\n\r\t]*\{inc\s+(.+?)\}[\n\r\t]*/is",
			"<?php include(DzTemplate::getInstance()->fetchCache(\"\\1\")); ?".">",
			$template
		);

		if(self::$flagSincePHP7){
			$template = preg_replace_callback(
				"/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/is",
				function($m){
					return stripvtags('<?php '.$m[1].' ?'.'>');
				}, $template);
		}else{
			$template = preg_replace(
				"/[\n\r\t]*\{eval\s+(.+?)\}[\n\r\t]*/ies",
				"stripvtags('<?php \\1 ?".">','')",
				$template
			);
		}
		if(self::$flagSincePHP7){
			$template = preg_replace_callback(
				"/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/is",
				function($m){
					return stripvtags('<?php echo '.$m[1].'; ?'.'>');
				}, $template);
		}else{
			$template = preg_replace(
				"/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies",
				"stripvtags('<?php echo \\1; ?".">','')",
				$template
			);
		}
		if(self::$flagSincePHP7){
			$template = preg_replace_callback(
				"/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/is",
				function($m){
					return stripvtags($m[1].'<?php } elseif('.$m[2].') { ?'.'>'.$m[3]);
				}, $template);
		}else{
			$template = preg_replace(
				"/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/ies",
				"stripvtags('\\1<?php } elseif(\\2) { ?".">\\3','')",
				$template
			);
		}
		$template = preg_replace(
			"/([\n\r\t]*)\{else\}([\n\r\t]*)/is",
			"\\1<?php } else { ?".">\\2",
			$template
		);

		$nest = 5;
		for ($i = 0; $i < $nest; $i++) {
			if(self::$flagSincePHP7){
				$template = preg_replace_callback(
					"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/is",
					function($m){
						return stripvtags('<?php if(is_array('.$m[1].')) { foreach('.$m[1].' as '.$m[2].') { ?'.'>'.$m[3].'<?php } } ?'.'>');
					}, $template);
			}else{
				$template = preg_replace(
					"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r]*(.+?)[\n\r]*\{\/loop\}[\n\r\t]*/ies",
					"stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?".">','\\3<?php } } ?".">')",
					$template
				);
			}
			if(self::$flagSincePHP7){
				$template = preg_replace_callback(
					"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/is",
					function($m){
						return stripvtags('<?php if(is_array('.$m[1].')) { foreach('.$m[1].' as '.$m[2].' => '.$m[3].') { ?'.'>'.$m[4].'<?php } } ?'.'>');
					}, $template);
			}else{
				$template = preg_replace(
					"/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*(.+?)[\n\r\t]*\{\/loop\}[\n\r\t]*/ies",
					"stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?".">','\\4<?php } } ?".">')",
					$template
				);
			}
			if(self::$flagSincePHP7){
				$template = preg_replace_callback(
					"/([\n\r\t]*)\{if\s+(.+?)\}([\n\r]*)(.+?)([\n\r]*)\{\/if\}([\n\r\t]*)/is",
					function($m){
						return stripvtags($m[1].'<?php if('.$m[2].') { ?'.'>'.$m[3].$m[4].$m[5].'<?php } ?'.'>'.$m[6]);
					}, $template);
			}else{
				$template = preg_replace(
					"/([\n\r\t]*)\{if\s+(.+?)\}([\n\r]*)(.+?)([\n\r]*)\{\/if\}([\n\r\t]*)/ies",
					"stripvtags('\\1<?php if(\\2) { ?".">\\3','\\4\\5<?php } ?".">\\6')",
					$template
				);
			}
		}

		$template = preg_replace(
			"/\{I18N_(.+?)\s*\}/is",
			"<?=getLang('\\1')?".">",
			$template
		);

		$template = preg_replace(
			"/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/s",
			"<?=\\1?".">",
			$template
		);

		$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);

		if(self::$flagSincePHP7){
			$template = preg_replace_callback(
					"/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/",
					function($m){
					return transamp($m[0]);
					}, $template);
		}else{
		$template = preg_replace(
			"/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e",
			"transamp('\\0')",
			$template
		);
		}
		if(self::$flagSincePHP7){
			$template = preg_replace_callback(
					"/\<script[^\>]*?src=\"(.+?)\".*?\>\s*\<\/script\>/is",
					function($m){
					return stripscriptamp($m[1]);
					}, $template);
		}else{
		$template = preg_replace(
			"/\<script[^\>]*?src=\"(.+?)\".*?\>\s*\<\/script\>/ise",
			"stripscriptamp('\\1')",
			$template
		);
		}
		if(self::$flagSincePHP7){
			$template = preg_replace_callback(
				"/[\n\r\t]*\{block\s+([a-zA-Z0-9_]+)\}(.+?)\{\/block\}/is",
				function($m){
					return stripblock($m[1], $m[2]);
				}, $template);
		}else{
			$template = preg_replace(
				"/[\n\r\t]*\{block\s+([a-zA-Z0-9_]+)\}(.+?)\{\/block\}/ies",
				"stripblock('\\1', '\\2')",
				$template
			);
		}

		//$chk_tag = md5_file($tpl_path);//效率有点低...
		$chk_tag=filemtime($tpl_path);//用这个吧...
		$expire_time = time();
		$template = "<?php if (!class_exists('DzTemplate')) throw new Exception('404 DzTemplate'); "
			."DzTemplate::getInstance()->checkCache('$tpl', '$chk_tag', $expire_time); "
			//."?".">\r\n$template";
			."?".">".trim($template);
		return $template;
	}

	/**
	 * test to see if valid cache exists for this template
	 *
	 * @param string $tpl
	 * @access public
	 * @return void
	 */
	public function isCached($tpl) {
		$cache_path = $this->getCachePath($tpl);
		if(!file_exists($cache_path)) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * executes & returns the cache path for the given template
	 *
	 * @param string $tpl
	 * @access public
	 * @return void
	 */
	public function fetchCache($tpl){
		if(! file_exists($this->_options['template_dir'].DIR_SEP.$tpl)){
			//dirty: assume related...
			//$tpl=basename($tpl);
		}else{
			$tpl=$this->_options['template_dir'].DIR_SEP.$tpl;
		}
		//$tpl="../".basename(dirname($tpl))."/".basename($tpl);
		$cache_path = $this->getCachePath($tpl);
		$tpl=realpath($tpl);
		if(!$this->isCached($tpl)) {
			$this->saveCache($tpl);
		}
		return $cache_path;
	}

	/**
	 * compile & save cached content for the given template
	 *
	 * @param string $tpl
	 * @access public
	 * @return void
	 */
	public function saveCache($tpl) {
		//print "saveCache:$tpl";die;
		$template = $this->parseTemplate($tpl);
		$cache_path = $this->getCachePath($tpl);
		$rt=file_put_contents($cache_path, $template);
		if($rt>0){//OK
		}else{
			throw new Exception("saveCache_Failed");
		}
	}

	/**
	 * clear cached content for the given template if cache file exists
	 *
	 * @param string $tpl
	 * @access public
	 * @return void
	 */
	public function clearCache($tpl) {
		if($this->isCached($tpl)) {
			@unlink($this->getCachePath($tpl));
		}
	}

	/**
	 * clear the entire contents of cache (all templates)
	 *
	 * @access public
	 * @return void
	 */
	public function clearAllCache() {
		$cache_dir = $this->trimPath($this->_options['cache_dir']);
		$fs = @scandir($cache_dir);
		foreach($fs as $f) {
			$path = $cache_dir.DIR_SEP.$f;
			if(is_file($path)) {
				if(preg_match("/\.php$/", $f)) {
					@unlink($path);
				}
			}
		}
	}
}
?>
