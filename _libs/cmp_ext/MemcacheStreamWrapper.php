<?php
//purpose: make the memcache:// protocal reflects the memcache, to make coding easier to understand.
//refer concepts from SAE ( http://stdlib.sinaapp.com/?f=sae_include.function.php )
if ( ! in_array("memcache", stream_get_wrappers()) )
	stream_wrapper_register("memcache", "MemcacheStreamWrapper");

class MemcacheStreamWrapper // implements WrapperInterface
{
	public $dir_mode = 16895 ; //040000 + 0222;
	public $file_mode = 33279 ; //0100000 + 0777;

	//public function __construct()
	//{
	//	$this->_mc = memcache_init();
	//}

	public function mc() {
		if ( !isset( $this->_mc ) ) $this->_mc = memcache_init();
		return $this->_mc ;
	}

	public function stream_open( $path , $mode , $options , &$opened_path)
	{
		$this->position = 0;
		$this->mckey = trim(substr($path, 8));
		$this->mode = $mode;
		$this->options = $options;

		if ( in_array( $this->mode, array( 'r', 'r+', 'rb' ) ) ) {
			if ( $this->mccontent = memcache_get( $this->mc(), $this->mckey ) ) {
				$this->get_file_info( $this->mckey );
				$this->stat['mode'] = $this->stat[2] = $this->file_mode;
			} else {
				trigger_error("fopen({$path}): failed to read from Memcached: No such key.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $this->mode, array( 'a', 'a+', 'ab' ) ) ) {
			if ( $this->mccontent = memcache_get( $this->mc() , $this->mckey ) ) {
				$this->get_file_info( $this->mckey );
				$this->stat['mode'] = $this->stat[2] = $this->file_mode;
				$this->position = strlen($this->mccontent);
			} else {
				$this->mccontent = '';
				$this->stat['ctime'] = $this->stat[10] = time();
			}
		} elseif ( in_array( $this->mode, array( 'x', 'x+', 'xb' ) ) ) {
			if ( !memcache_get( $this->mc() , $this->mckey ) ) {
				$this->mccontent = '';
				$this->statinfo_init();
				$this->stat['ctime'] = $this->stat[10] = time();
			} else {
				trigger_error("fopen({$path}): failed to create at Memcached: Key exists.", E_USER_WARNING);
				return false;
			}
		} elseif ( in_array( $this->mode, array( 'w', 'w+', 'wb' ) ) ) {
			$this->mccontent = '';
			$this->statinfo_init();
			$this->stat['ctime'] = $this->stat[10] = time();
		} else {
			$this->mccontent = memcache_get( $this->mc() , $this->mckey );
		}

		return true;
	}

	public function stream_read($count)
	{
		if (in_array($this->mode, array('w', 'x', 'a', 'wb', 'xb', 'ab') ) ) {
			return false;
		}


		$ret = substr( $this->mccontent , $this->position, $count);
		$this->position += strlen($ret);

		$this->stat['atime'] = $this->stat[8] = time();
		$this->stat['uid'] = $this->stat[4] = 0;
		$this->stat['gid'] = $this->stat[5] = 0;

		return $ret;
	}

	public function stream_write($data)
	{
		if ( in_array( $this->mode, array( 'r', 'rb' ) ) ) {
			return false;
		}

		$left = substr($this->mccontent, 0, $this->position);
		$right = substr($this->mccontent, $this->position + strlen($data));
		$this->mccontent = $left . $data . $right;

		if ( memcache_set( $this->mc() , $this->mckey , $this->mccontent ) ) {
			$this->stat['mtime'] = $this->stat[9] = time();
			$datalen = strlen($data);
			$this->position += $datalen;
			$this->stat['size'] = $this->stat[7] += $datalen;
			return strlen( $data );
		}
		else return false;
	}

	public function stream_close()
	{

		memcache_set( $this->mc() , $this->mckey.'.meta' ,  serialize($this->stat)  );
		//memcache_close( $this->mc() );//Why close?
	}


	public function stream_eof()
	{
		return $this->position >= strlen( $this->mccontent  );
	}

	public function stream_tell()
	{

		return $this->position;
	}

	public function stream_seek($offset , $whence = SEEK_SET)
	{

		switch ($whence) {
		case SEEK_SET:

			if ($offset < strlen( $this->mccontent ) && $offset >= 0) {
				$this->position = $offset;
				return true;
			}
			else
				return false;

			break;

		case SEEK_CUR:

			if ($offset >= 0) {
				$this->position += $offset;
				return true;
			}
			else
				return false;

			break;

		case SEEK_END:

			if (strlen( $this->mccontent ) + $offset >= 0) {
				$this->position = strlen( $this->mccontent ) + $offset;
				return true;
			}
			else
				return false;

			break;

		default:

			return false;
		}
	}

	public function stream_stat()
	{
		return $this->stat;
	}

	// ============================================
	public function mkdir($path , $mode , $options)
	{
		$path = trim(substr($path, 8));


		//echo "回调mkdir\n";
		$path  = rtrim( $path  , '/' );

		$this->stat = $this->get_file_info( $path );
		$this->stat['ctime'] = $this->stat[10] = time();
		$this->stat['mode'] = $this->stat[2] = $this->dir_mode;

		//echo "生成新的stat数据" . print_r( $this->stat , 1 );

		memcache_set( $this->mc() , $path.'.meta' ,  serialize($this->stat)  );

		//echo "写入MC. key= " . $path.'.meta ' .  memcache_get( $this->mc() , $path.'.meta'  );
		memcache_close( $this->mc() );


		return true;
	}

	public function rename($path_from , $path_to)
	{
		$path_from = trim(substr($path_from, 8));
		$path_to = trim(substr($path_to, 8));


		memcache_set( $this->mc() , $path_to , memcache_get( $this->mc() , $path_from ) );
		memcache_set( $this->mc() , $path_to . '.meta' , memcache_get( $this->mc() , $path_from . '.meta' ) );
		memcache_delete( $this->mc() , $path_from );
		memcache_delete( $this->mc() , $path_from.'.meta' );
		clearstatcache( true );
		return true;
	}

	public function rmdir($path , $options)
	{
		$path = trim(substr($path, 8));


		$path  = rtrim( $path  , '/' );

		memcache_delete( $this->mc() , $path .'.meta'  );
		clearstatcache( true );
		return true;
	}

	public function unlink($path)
	{
		$path = trim(substr($path, 8));
		$path  = rtrim( $path  , '/' );

		memcache_delete( $this->mc() , $path );
		memcache_delete( $this->mc() , $path . '.meta' );
		clearstatcache( true );
		return true;
	}

	public function url_stat($path , $flags)
	{
		$path = trim(substr($path, 8));
		$path  = rtrim( $path  , '/' );

		if ( !$this->is_file_info_exists( $path ) ) {
			return false;
		} else {
			if ( $stat = memcache_get( $this->mc() , $path . '.meta' ) ) {
				$this->stat = unserialize($stat);
				if ( is_array($this->stat) ) {
					if ( $this->stat['mode'] == $this->dir_mode || $c = memcache_get( $this->mc(), $path ) ) {
						return $this->stat;
					} else {
						memcache_delete( $this->mc() , $path . '.meta' );
					}
				}
			}
			return false;
		}
	}




	// ============================================

	public function is_file_info_exists( $path )
	{
		//echo "获取MC数据 key= " .  $path.'.meta' ;
		$d = memcache_get( $this->mc() , $path . '.meta' );
		//echo "\n返回数据为" . $d . "\n";
		return $d;
	}

	public function get_file_info( $path )
	{
		if ( $stat = memcache_get( $this->mc() , $path . '.meta' ) )
			return $this->stat =  unserialize($stat);
		else $this->statinfo_init();
	}

	public function statinfo_init( $is_file = true )
	{
		$this->stat['dev'] = $this->stat[0] = 0x8002;
		$this->stat['ino'] = $this->stat[1] = mt_rand(10000, PHP_INT_MAX);

		if( $is_file )
			$this->stat['mode'] = $this->stat[2] = $this->file_mode;
		else
			$this->stat['mode'] = $this->stat[2] = $this->dir_mode;

		$this->stat['nlink'] = $this->stat[3] = 0;
		$this->stat['uid'] = $this->stat[4] = 0;
		$this->stat['gid'] = $this->stat[5] = 0;
		$this->stat['rdev'] = $this->stat[6] = 0;
		$this->stat['size'] = $this->stat[7] = 0;
		$this->stat['atime'] = $this->stat[8] = 0;
		$this->stat['mtime'] = $this->stat[9] = 0;
		$this->stat['ctime'] = $this->stat[10] = 0;
		$this->stat['blksize'] = $this->stat[11] = 0;
		$this->stat['blocks'] = $this->stat[12] = 0;

	}

	public function dir_closedir() {
		return false;
	}

	public function dir_opendir($path, $options) {
		return false;
	}

	public function dir_readdir() {
		return false;
	}

	public function dir_rewinddir() {
		return false;
	}

	public function stream_cast($cast_as) {
		return false;
	}

	public function stream_flush() {
		return false;
	}

	public function stream_lock($operation) {
		return false;
	}

	public function stream_set_option($option, $arg1, $arg2) {
		return false;
	}

}
