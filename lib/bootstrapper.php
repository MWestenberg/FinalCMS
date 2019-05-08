<?php
	/********************************************
	*	Author: Mark Westenberg					*
	*	Date: 2012-10-05						*
	*	Company: Final Media					*
	*	License: General Public License	(GPL)	*
	********************************************/
	
	ini_set('auto_detect_line_endings', true); #setting for phpini
	error_reporting(E_ALL);

	setlocale(LC_ALL,'en_EN');
	date_default_timezone_set('Europe/Amsterdam');
	session_start(); 

	# set the languag (from translation table)
	DEFINE("SALT","SDFG$51%&IUER456#$#^&*$%WFHNDF454643");
	
	#default BASEURL
	DEFINE('BASEURL', $_SERVER['SERVER_NAME']);
	
	#default language
	DEFINE("DEFAULT_LANGUAGE","en");
	
	# APP name (used for cookie variables and titles)
	DEFINE("APPNAME", "AWA-bv.com");
	# default mail address as sender for mails that do not have a sender specified
	DEFINE("APPMAIL", "no-reply@awa-bv.com");
	
	#set subpath for secure login.
	# this is needed to know the difference between public and private and is shown in the url
	DEFINE("SECUREPATH","/secure");
	
	DEFINE("CWD",getcwd()."/"); # defined based on directory of index.php where this file is included
	DEFINE("APPLICATION",'lib/application/'); # where application files are located
	DEFINE("AJAX_TPL",'lib/interface/'); # where ajax php files are located 
	DEFINE("TPL_ROOT","templates/"); # Root template directory
	
	DEFINE("SYS_DBHOST","localhost"); 		// database host (usually localhost)
	DEFINE("SYS_DBPORT","");  				// database port (optional)
	DEFINE("SYS_DBNAME","finalmedia_dev"); // database name (change this to your database name)
	DEFINE("SYS_DBUSER","finalmedia_dev");	// database user (the username for your database)
	DEFINE("SYS_DBPASSWORD","ZXRUjUR9"); // database password (the password for your database)	
	
	
	DEFINE("FTPFOLDER", "/files");
		
	#includes database classes
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/database/databaseConnection.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/database/databaseConnection.php');
	}
			
	#includes controller classes
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/database/db.query.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/database/db.query.class.php');
	}
	
	# general controller class that is used to execute database queries
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/controller.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/controller.class.php');
	}
	
	# translation class
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/translate.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/translate.class.php');
	}
	
	# class for authentication and private templates
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/credentials.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/credentials.class.php');
	}
	
	# includes file handler class (including templates and such)
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/fileHandler.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/fileHandler.class.php');
	}
	
	# includes ciphering class
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/CreditCardFreezer.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/CreditCardFreezer.php');
	}
	
	# includes error handler class
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/errorHandler.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/errorHandler.class.php');
	}
	
	# includes RecursiveDOMIterator class
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/RecursiveDOMIterator.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/RecursiveDOMIterator.php');
	}
	
	
	#includes device class
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/application/browser.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/application/browser.class.php');
	}
	
	#includes view classes
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/interface/view.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface/view.class.php');
	}
	
	#includes the template class
	if (file_exists($_SERVER['DOCUMENT_ROOT'].'/lib/interface/template.class.php')) {
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface/template.class.php');
	}
	
	
	
	
?>