<?php
// File name: pixlr.php

$rootpath = '/home/finalmedia/domains/dev.finalmedia.nl/public_html/files/';

if (isset($_REQUEST['image'])) {
    // GET
    $url = $_REQUEST['image'];
    $path = $rootpath . $_REQUEST['title'] . "." . $_REQUEST['type'];
    // SAVE IMAGE
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
    
} else {
    // IMAGE LOADING
    $image = 'http://dev.finalmedia.nl'.$_GET['path'];
    //$image = 'http://developer.pixlr.com/_image/example3.jpg';
    $imagePathParts = explode("/", $image);
    $title = array_pop($imagePathParts);
    $title = substr($title, 0, -4);
    $pixlrURL = "http://www.pixlr.com/express/?";
    $pixlrURL.= "target=https://dev.finalmedia.nl/plugins/pixlr/pixlsave.php&";
    $pixlrURL.= "method=GET&";
    $pixlrURL.= "referer=localhost&";
    $pixlrURL.= "image=" . $image . "&";
    $pixlrURL.= "title=" . $title . "&";
    $pixlrURL.= "locktarget=true&locktitle=true";
    header("Location: " . $pixlrURL);
}
?>