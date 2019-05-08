<?php 
session_start();

$rootpath = $_SERVER['DOCUMENT_ROOT'].$_SESSION['ftpfolder']."/";

if (isset($_REQUEST['image'])) {
    // GET
    $url = $_REQUEST['image'];
    $path = $rootpath . $_REQUEST['title'] . "." . $_REQUEST['type'];
    // SAVE IMAGE
    echo $path;
    $fp = fopen($path, 'w');
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    $data = curl_exec($ch);
    curl_close($ch);
    fclose($fp);
} elseif (isset($_FILES['image']['tmp_name'])) {
    // POST
    $type = $_REQUEST['type'];
    $title = $_REQUEST['title'];
    $path = $rootpath . $title . "." . $type;
    move_uploaded_file($_FILES['image']['tmp_name'], $path);
    
} 

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
	<title>Pixlr - development manual</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<meta name="Description" content="Pixlr is a free online photo editor. Edit, adjust and filter your images. No registration jump right in!" />
	<meta name="Keywords" content="photo, photograph, picture, free, opacity, alpha, edit, editing, adjustments, online, flickr, facebook, photobucket, camera, digital, image, red-eye, fix, photoshop, gimp, photoshop" />
	<script type="text/javascript" src="https://dev.finalmedia.nl/plugins/pixlr/pixlr.js"></script>
	<script type="text/javascript">
		//pixlr.settings.target = 'https://dev.finalmedia.nl/ckeditor/filemanager/connectors/php/pixlsave.php';
		pixlr.settings.exit = 'https://dev.finalmedia.nl/plugins/pixlr/pixledit.php';
		pixlr.settings.credentials = true;
		pixlr.settings.method = 'get';
	</script>
</head>
<body>

<script>
<?php
if (isset($_GET['path']))
{
?>
pixlr.edit({image:'http://dev.finalmedia.nl<?php echo $_GET['path'];?>', title:'<?php echo end(explode("/",$_GET['path']));?>', service:'express', target: 'https://dev.finalmedia.nl/plugins/pixlr/pixledit.php?pad=<?php echo $_GET['path'];?>'});

<?php
}
else 
{
?>
window.opener.location.href = window.opener.location.href;
self.close();

<?php	
}
?>
</script>
</body>
</html>
