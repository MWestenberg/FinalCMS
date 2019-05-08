<? if ( !defined("_THUMBALIZR") ) die ("no access");

$thumbalizr_config= array(
	'api_key'			=>	"f0c881ccb228abad41c6e1c6db2a4dc1", //put your api key here
	'service_url'		=>	"http://api.thumbalizr.com/", // don't change, if you didn't have a special service contract
	'use_local_cache'	=>	TRUE, // TRUE or FALSE for local image cache
	'local_cache_dir'	=>	$_SERVER['DOCUMENT_ROOT']."/plugins/thumbalizr/cache", //relative cache directory must exists in install directory and rwx permissions to all (777)
	'local_cache_expire'=>	1 // local chache expiration time in hours
);

$thumbalizr_defaults= array(
	'width'				=>		"250", // image width
	'delay'				=>		"8", // caputre delay useful for flash content 5 - 10 is a good value
	'encoding'			=>		"png", // jpg or png
	'quality'			=>		"90", //image quality 10-90
	'bwidth'			=>		"1280", // browser width
	'mode'				=>		"page", // screen or page
	'bheight'			=>		"1024" // browser height only for mode=screen
);

require_once('class.inc.php');
?>