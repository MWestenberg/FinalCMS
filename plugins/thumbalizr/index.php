<?
define ("_THUMBALIZR",1);

require_once("config.inc.php"); // get config and functions
$image=new thumbalizrRequest(); // create object
$image->request("http://www.ebay.com"); // send request

if ($image->headers['Status']=="OK" || $image->headers['Status']=="LOCAL") { // if picture is available
	$image->output(); //dump binary image data	
} else {
	print_r($image->headers); // print text result output - you can dump your own "queued" picture here
}

?>