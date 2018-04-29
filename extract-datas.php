#!/usr/bin/php
<?php

function usage( $err=null ) {
	echo 'Usage: php '.$_SERVER['argv'][0]." -d <directory> -s <subdomain>\n\n";
	echo "Options:\n";
	echo "\t-d\tset source directory (required)\n";
	echo "\t-s\tsubdomain\n";
	echo "\n";
	if( $err ) {
		echo 'Error: '.$err."!\n";
	}
	exit();
}


require_once( 'Utils.php' );


$options = '';
$options .= 'd:'; // source directory
$options .= 's:'; // subdomain
$t_options = getopt( $options );
//var_dump($t_options);
if( !count($t_options) ) {
	usage();
}

if( isset($t_options['d']) ) {
	$d = $t_options['d'];
	if( !is_dir($d) ) {
		usage( 'Source directory not found' );
	}
	$_directory = rtrim( $d, '/' );
} else {
	usage( 'Source directory not found' );
}

if( isset($t_options['s']) ) {
	$_subdomain = strtolower( trim($t_options['s']) );
	$_domain = Utils::extractDomain( $_subdomain, $_tld );
} else {
	usage( 'Subdomain not found' );
}


$t_ignore_ext = [
	'ico','png','gif','jpg','jpeg','bmp',
	'avi','mpg','mpeg',
	'tgz','tar','gz','tgz','tar.gz','zip',
	'css','js','woff','woff2','ttf','eot',
];
$t_ignore_ext = implode( ',', $t_ignore_ext );


echo "########### 0: Looking for new domains with same extension: ".$_tld."\n";
$t_regexp = [ '#[^a-z0-9\\.\-]+([a-z0-9\\.\-]+\.'.$_tld.')#' ];
foreach( $t_regexp as $r ) {
	$cmd = 'extract-endpoints -r -d '.$_directory.' -e "*" -v 2 -i "'.$t_ignore_ext.'" --gg "'.$r.'"';
	//echo $cmd."\n";
	exec( $cmd, $output );
	$output = trim( implode( "\n", $output ) );
	if( strlen($output) ) {
		echo trim($output)."\n";
	}
}
echo "######################\n\n";


echo "########### 1: Looking for new subdomains of the same domain: ".$_domain."\n";
$t_regexp = [ '#[^a-z0-9\\.\-]+([a-z0-9\\.\-]+\.'.str_replace('.','\.',$_domain).')#' ];
foreach( $t_regexp as $r ) {
	$cmd = 'extract-endpoints -r -d '.$_directory.' -e "*" -v 2 -i "'.$t_ignore_ext.'" --gg "'.$r.'"';
	//echo $cmd."\n";
	exec( $cmd, $output );
	$output = trim( implode( "\n", $output ) );
	if( strlen($output) ) {
		echo trim($output)."\n";
	}
}
echo "######################\n\n";


echo "########### 2: Looking for absolute urls within the same subdomain: ".$_subdomain."\n";
$t_regexp = [ "#[^a-z0-9\.\-]+(([a-z]{3,15}:[/]{2,3}){0,1}".str_replace('.','\.',$_subdomain)."/[^'\\\"]+)#" ];
foreach( $t_regexp as $r ) {
	$cmd = 'extract-endpoints -s -r -d '.$_directory.' -e "*" -v 2 -i "'.$t_ignore_ext.'" --gg "'.$r.'"';
	//echo $cmd."\n";
	exec( $cmd, $output );
	$output = trim( implode( "\n", $output ) );
	if( strlen($output) ) {
		echo trim($output)."\n";
	}
}
echo "######################\n\n";


echo "########### 3: Looking for relative urls within the same subdomain: ".$_subdomain."\n";
$output = '';
$t_regexp = [ "(href=['\\\\\\\"]+[^\\\\\\\"'>]*['\\\\\\\"])", "(src=['\\\\\\\"]+[^\\\\\\\"'>]*['\\\\\\\"])" ];
foreach( $t_regexp as $r ) {
	$cmd = 'extract-endpoints -k -s -r -d '.$_directory.' -e "*" -v 2 -i "'.$t_ignore_ext.'" --gg "'.$r.'"';
	exec( $cmd, $o );
	$output .= trim( implode( "\n", $o ) );
}

$t_matches = [];
preg_match_all( '#<.*(href|src)=[\'"]+([^"\'>]*)#i', $output, $tmp );
if( $tmp && is_array($tmp) && isset($tmp[2]) && is_array($tmp[2]) && count($tmp[2]) ) {
    $t_matches = array_merge( $tmp[2], $t_matches );
}

foreach( $t_matches as &$m ) {
	if( $m[0] == '/' ) {
		$m = 'https://'.$_subdomain.$m;
	} else {
		if( !preg_match('#^http[s]+://'.$_subdomain.'#',$m) ) {
			$m = '';
		}
	}
    //echo $m."\n";
}
$t_matches = array_unique( $t_matches );
echo implode( "\n", $t_matches );
echo "######################\n\n";


echo "########### 4: Looking for Amazon S3 buckets\n";
$t_regexp = [
	'#[^a-z0-9\\.\-]+([a-z0-9\\.\-]+)\.s3.*\.amazonaws\.com#',
	'#[^a-z0-9\\.\-]+s3.*\.amazonaws\.com/([a-z0-9\\.\-]+)#',
];
foreach( $t_regexp as $r ) {
	$cmd = 'extract-endpoints -r -d '.$_directory.' -e "*" -v 2 -i "'.$t_ignore_ext.'" --gg "'.$r.'"';
	//echo $cmd."\n";
	exec( $cmd, $output );
	$output = trim( implode( "\n", $output ) );
	if( strlen($output) ) {
		echo trim($output)."\n";
	}
}
echo "######################\n\n";


echo "########### 5: Looking for Google Cloud buckets\n";
$t_regexp = [
	'#[^a-z0-9\\.\-]+([a-z0-9\\.\-]+\.storage\.googleapis\.com)#',
	'#[^a-z0-9\\.\-]+(storage\.googleapis\.com/[a-z0-9\\.\-]+)#',
	'#[^a-z0-9\\.\-]+(storage\.cloud\.google\.com/[a-z0-9\\.\-]+)#',
];
foreach( $t_regexp as $r ) {
	$cmd = 'extract-endpoints -r -d '.$_directory.' -e "*" -v 2 -i "'.$t_ignore_ext.'" --gg "'.$r.'"';
	//echo $cmd."\n";
	exec( $cmd, $output );
	$output = trim( implode( "\n", $output ) );
	if( strlen($output) ) {
		echo trim($output)."\n";
	}
}
echo "######################\n\n";


echo "########### 6: Looking for keys\n";
$cmd = 'extract-endpoints -k -r -d '.$_directory.' -v 1 -e "*" -i "'.$t_ignore_ext.'"';
//echo $cmd."\n";
exec( $cmd, $output );
$output = trim( implode( "\n", $output ) );
if( strlen($output) ) {
	echo trim($output)."\n";
}
echo "######################\n\n";


exit();
