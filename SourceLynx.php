<?php

/**
 * I don't believe in license
 * You can do want you want with this program
 * - gwen -
 */

class SourceLynx
{
	const SOURCE_NAME = 'Lynx';


	public static function run( $target, $tor, $dork, $https, $params, $verbose )
	{
		$t_urls = [];
		$cmd = 'lynx -useragent="'.UrlGrabber::T_USER_AGENT[rand(0,UrlGrabber::N_USER_AGENT)].'" -listonly -dump "http://www.google.com/search?q='.$dork.'&start=0&num=1000" 2>/dev/null';
		if( $tor ) {
			$cmd = 'torsocks '.$cmd;
		}
		if( $verbose <= 0 ) {
			echo $cmd."\n";
		}
		exec( $cmd, $output );
		$output = implode( "\n", $output );
		//file_put_contents( 'lynx.txt', $output );
		//$output = file_get_contents( 'lynx.txt' );
		//var_dump( $output );
		
		$r = '#\s*[0-9]+\.\s*(http[s]?://'.$target.'.*)#i';
		$m = preg_match_all( $r, $output, $tmp );
		//var_dump( $tmp );
		if( $m ) {
			$t_urls = array_merge( $t_urls, $tmp[1] );
		}
		
		$r = '#/url\?q=(http[s]?://'.$target.'.*)&sa=.*#i';
		$m = preg_match_all( $r, $output, $tmp );
		//var_dump( $tmp );
		if( $m ) {
			$t_urls = array_merge( $t_urls, $tmp[1] );
		}
		
		$r = '#/translate\?hl=[a-z]+&sl=[a-z]+&u=(http[s]?://'.$target.'.*)&prev=#i';
		$m = preg_match_all( $r, $output, $tmp );
		//var_dump( $tmp );
		if( $m ) {
			$t_urls = array_merge( $t_urls, $tmp[1] );
		}
		
		$r = '#/search\?q=related:(http[s]?://'.$target.'.*)&hl=#i';
	    $m = preg_match_all( $r, $output, $tmp );
		//var_dump( $tmp );
		if( $m ) {
			$t_urls = array_merge( $t_urls, $tmp[1] );
		}
		
		$r = '#/search\?q=cache:.*:(http[s]?://'.$target.'.*)\+#i';
	    $m = preg_match_all( $r, $output, $tmp );
		//var_dump( $tmp );
		if( $m ) {
			$t_urls = array_merge( $t_urls, $tmp[1] );
		}

		$t_urls = array_unique( $t_urls );
		//var_dump( $t_urls );
		
		return $t_urls;
	}
}
