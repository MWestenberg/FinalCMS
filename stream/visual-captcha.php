<?php
session_start();

//ini_set('error_reporting', E_ALL);
//ini_set('display_errors','On');
// include captcha class
require($_SERVER['DOCUMENT_ROOT'].'/lib/application/php.captcha.inc.php');
// define fonts
$aFonts = array('fonts/VeraBd.ttf', 'fonts/VeraIt.ttf', 'fonts/Vera.ttf');
// create new image
$oPhpCaptcha = new PhpCaptcha($aFonts, 200, 50);
//$oPhpCaptcha->SetBackgroundImages('../images/bg.jpg');
$oPhpCaptcha->Create();





?>