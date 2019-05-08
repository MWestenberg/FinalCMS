<?php



if (!isset($_GET['file']) || !isset($_GET['type']) || !isset($_GET['action']))
{
	exit;
}
else 
{
	$filename = htmlentities($_GET['file'],ENT_QUOTES,'UTF-8');	
	$type = htmlentities($_GET['type'],ENT_QUOTES,'UTF-8');	
}

$filename = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/'.$type.'/'.urldecode($filename);



function thumbPdf($source, $width)
{		
	
	header('Content-Type: image/png'); 
	$base = $_SERVER['DOCUMENT_ROOT'];
	
	$filename = end(explode("/",$source));
	$ext = ".png";
	$dest = $base."/files/thumb/pdf/".$filename;
	
    if (file_exists($dest."-0".$ext))
    {
    	readfile($dest."-0".$ext);   
    }
    else if (file_exists($dest.$ext))
    {
    	readfile($dest.$ext);  
    }
    else 
    {
    	readfile($_SERVER['DOCUMENT_ROOT']."/images/frontend/document-icon.png");	
    }
    
}

function downloadPDF($filename) {
	
	if (file_exists($filename))
	{
		
		//echo $filename;
		header('Content-Type: application/pdf'); 
		header('Content-Disposition: inline; '.$filename); 
		readfile($filename);
		
	}	

}


if ($_GET['action']=='thumb')
{
	thumbPdf($filename, 200);
}
else 
{
	downloadPDF($filename);
}

?>