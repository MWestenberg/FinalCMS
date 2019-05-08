<?
define ("_THUMBALIZR",1);

require_once($_SERVER['DOCUMENT_ROOT']."/plugins/thumbalizr/config.inc.php"); // get config and functions
$image=new thumbalizrRequest(); // create object
$image->request("http://dev.finalmedia.nl".$_GET['page']); // send request

if ($image->headers['Status']=="OK" || $image->headers['Status']=="LOCAL") { // if picture is available
	$image->output(); //dump binary image data	
} else {
	print_r($image->headers); // print text result output - you can dump your own "queued" picture here
}

?>