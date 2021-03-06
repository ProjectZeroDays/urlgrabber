<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class UrlGrabber
{
	const LOOPING_INDEX = 9;
	
	const T_ASSETS_EXTENSIONS = ['js','css','woff','woff2','eot','ttf','pdf','svg','png','ico','gif','jpg','jpeg','bmp','txt','csv','pdf','xml','mp3','mp4','wav','mpg','mpeg','avi','mov','wmv','doc','xls','zip','tar','7z','rar','tgz','gz','exe','rtp','cbr','flv' ];
	
	const T_USER_AGENT = [
		'Mozilla/5.0 (X11; Linux x86_64; rv:31.0) Gecko/20100101 Firefox/31.0 Iceweasel/31.7.0',
		'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)',
		'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A',
		'Mozilla/5.0 (Windows NT 6.3; rv:36.0) Gecko/20100101 Firefox/36.0',
		'Opera/9.80 (Windows NT 6.0) Presto/2.12.388 Version/12.14',
		'Mozilla/5.0 (X11; Linux 3.5.4-1-ARCH i686; es) KHTML/4.9.1 (like Gecko) Konqueror/4.9',
		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0',
	];
	const N_USER_AGENT = 7;
	const DEFAULT_DORK = 'site:__TARGET__';

	private $target = '';
	private $t_default = [ 3=>1, 9=>1, ];
	
	private $tor = false;
	private $dork = null;
	private $assets = true;
	private $no_params = false;
	private $https = false;
	private $looping = true;
	private $verbose = 0;
	
	private $t_urls = [];

	private $t_run = [];
	private $t_sources = [];
	
	
	public function getTarget() {
		return $this->target;
	}
	public function setTarget( $v ) {
		$this->target = trim( $v );
		if( is_null($this->dork) ) {
			$this->setdork( self::DEFAULT_DORK );
			//$this->_dork = self::DEFAULT_DORK;
			//$this->_dork = Utils::encodeDork( $this->_dork );
			//$this->dork = str_replace( '__TARGET__', $this->target, $this->_dork );
		}
		return true;
	}

	
	public function getDork() {
		return $this->dork;
	}
	public function setDork( $v ) {
		$this->dork = trim( $v );
		$this->_dork = Utils::encodeDork( $this->dork );
		$this->_dork = str_replace( '__TARGET__', $this->target, $this->_dork );
		return true;
	}
	
	
	public function getVerbose() {
		return $this->verbose;
	}
	public function setVerbose( $v ) {
		$this->verbose = (int)$v;
		return true;
	}

	
	public function enableTor() {
		$this->tor = true;
	}
	
	
	public function enableLooping() {
		$this->looping = true;
	}
	public function disableLooping() {
		$this->looping = false;
	}
	
	
	public function enableMaliciousSearch() {
		$this->malicious = true;
	}
	
	
	public function excludeAssets() {
		$this->assets = false;
	}
	
	
	public function excludeNoParams() {
		$this->no_params = true;
	}
	
	
	public function enableHttps() {
		$this->https = true;
	}
	
	
	private function testSource( $s ) {
		/*if( !is_subclass_of($s,'ThirdParty') ) {
			return false;
		}*/
		if( !method_exists($s,'run') ) {
			return false;
		}
		if( property_exists($s,'SOURCE_NAME') ) {
			return false;
		}
		return true;
	}
	public function registerSource( $index, $v ) {
		$v = trim( $v );
		$file = dirname(__FILE__).'/'.$v.'.php';
		if( !is_file($file) || !class_exists($v) ) {
			Utils::help( $v.' class not found' );
		}
		if( !$this->testSource($v) ) {
			Utils::help( $v.' class wrongly configured' );
		}
		$this->t_sources[ $index ] = $v;
		//ksort( $this->t_sources );
		$this->t_run[ $index ] = [ 'index'=>$index, 'class'=>$v, 'params'=>(isset($this->t_default[$index])?$this->t_default[$index]:'') ];
		return true;
	}
	public function setSource( $v ) {
		$tmp = explode( ',', $v );
		$this->t_run = [];
		$this->disableLooping();
		foreach( $tmp as $s ) {
			if( strlen($s) > 1 ) {
				$this->t_default[ $s[0] ] = $s[1];
				$s = $s[0];
			}
			if( !isset($this->t_sources[$s]) ) {
				Utils::help( $s.' source not found' );
			}
			$this->t_run[ $s ] = [ 'index'=>$s, 'class'=>$this->t_sources[$s], 'params'=>(isset($this->t_default[$s])?$this->t_default[$s]:'') ];
		}
	}
	
	
	public function run()
	{
		foreach( $this->t_run as $index=>$source )
		{
			if( $index == self::LOOPING_INDEX ) {
				$this->enableLooping();
				// skipping looping for the moment
				continue;
			}
			
			if( $this->verbose <= 0 ) {
				echo "Testing ".$source['class']::SOURCE_NAME."...\n";
			}
			$t_urls = $source['class']::run( $this->target, $this->tor, $this->_dork, $this->https, $source['params'], $this->verbose );
			$t_urls = array_unique( $t_urls );
			if( $this->no_params ) {
				$t_urls = $this->removeNoParams( $t_urls );
			}
			if( !$this->assets ) {
				$t_urls = $this->removeAssets( $t_urls );
			}
			
			$this->t_urls = array_merge( $this->t_urls, $t_urls );
			if( $this->verbose <= 0 ) {
				echo count( $t_urls )." urls found.\n\n";
			}
			$this->printUrls( $t_urls );
			echo "\n";
		}
		
		if( $this->looping && $this->t_run[self::LOOPING_INDEX]['params'] )
		{
			$t_urls = $this->t_urls;
			
			for( $i=1 ; $i<=$this->t_run[self::LOOPING_INDEX]['params'] && count($t_urls) ; $i++ )
			{
				echo "Looping ".$i."...\n";
				$t_urls = SourceLoop::run( $this->target, $this->tor, $this->_dork, $this->https, $this->t_run[self::LOOPING_INDEX]['params'], $t_urls, $this->verbose );
				$t_urls = array_unique( $t_urls );
				if( $this->no_params ) {
					$t_urls = $this->removeNoParams( $t_urls );
				}
				if( !$this->assets ) {
					$t_urls = $this->removeAssets( $t_urls );
				}
				$t_urls = array_diff( $t_urls, $this->t_urls );
				$this->t_urls = array_merge( $this->t_urls, $t_urls );
				echo count( $t_urls )." new urls found.\n\n";
				$this->printUrls( $t_urls );
				echo "\n";
			}
		}

		return true;
	}
	
	
	public function removeNoParams( $t_urls )
	{
		foreach( $t_urls as $k=>$u ) {
			$parse = parse_url( $u );
			//var_dump($parse);
			if( !isset($parse['query']) ) {
				unset( $t_urls[$k] );
			}
		}
		
		return $t_urls;
	}
	
	
	public function removeAssets( $t_urls )
	{
		foreach( $t_urls as $k=>$u ) {
			$parse = parse_url( $u );
			//var_dump($parse);
			//var_dump( $parse['path'] );
			if( isset($parse['path']) && strstr($parse['path'],'.') ) {
				$ext = strtolower( substr( $parse['path'], strrpos($parse['path'],'.')+1 ) );
				//var_dump($ext);
				if( in_array($ext,self::T_ASSETS_EXTENSIONS) || in_array(strtoupper($ext),self::T_ASSETS_EXTENSIONS) ) {
					unset( $t_urls[$k] );
				}
			}
		}
		
		return $t_urls;
	}
	
	
	public function printResult()
	{
		$this->printUrls( $this->t_url );
	}
	
	
	private function printUrls( $t_urls )
	{
		echo implode( "\n", $t_urls )."\n";
	}
}
