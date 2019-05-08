<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

if(isset($_POST))
{

	$translator = new Translator;
	$DestinationDirectory	= $_SESSION['ftpfolder'].urldecode($_POST['currentDir']).DIRECTORY_SEPARATOR; //Upload Directory ends with / (slash)
	if(!isset($_FILES['pdfFile']) || !is_uploaded_file($_FILES['pdfFile']['tmp_name']))
	{
			echo $translator->getTranslation('upload-error1');
			exit;
	}
	else 
	{
		$fileName 		= str_replace(' ','-',strtolower($_FILES['pdfFile']['name'])); 
		$fileSize 		= $_FILES['pdfFile']['size']; // Obtain original image size
		$TempSrc	 	= $_FILES['pdfFile']['tmp_name']; // Tmp name of image file stored in PHP tmp folder
		$fileType	 	= $_FILES['pdfFile']['type']; //Obtain file type, returns "image/png", image/jpeg, text/plain etc.
	
		if (!move_uploaded_file($_FILES['pdfFile']['tmp_name'], $DestinationDirectory.$fileName))
		{
			echo $translator->getTranslation('upload-error1');
			exit;
		}
		else 
		{
			chmod($DestinationDirectory.$fileName, 0777);
			echo $translator->getTranslation('upload-success');
		}
		
	}
	
}
else
{
	exit;
}