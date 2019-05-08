<?php
session_start();

if (isset($_SESSION['loginId']) && isset($_SESSION['ftpfolder']) && isset($_GET['file'])   )
{

	$filename = '../'.$_SESSION['ftpfolder'] . urldecode(base64_decode($_GET['file']));
	
	if (file_exists($filename))
	{
	
		header('Content-Description: File Transfer');
		header('Content-Type: application/'.end(explode(".", $filename )).'');
		header('Content-Disposition: attachment; filename="'.basename($filename).'"');
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: '. filesize($filename));
		readfile($filename);
	}
}
else 
{
	header("Location: /");	
}

?>