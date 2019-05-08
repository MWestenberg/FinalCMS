<?php
/********************************************************************
*   Author: Mark Westenberg											*
*   Company: Final Media								*
*   website: www.finalmedia.nl										*
*  	Date: 2009-08-26												*
*  	Last modified: 2010-03-11							 			*
********************************************************************/


class databaseConnection {

/********************************************************************
*   This is the main database connection class						*
*   It cannot instantiate a connection more than once				*
*   by use of a static function and a private __construct )			*
*  	This is to prevent multiple connections				 			*
*   call : databaseConnection::getLink()->handle();					*
********************************************************************/

	// list of protected vars for the connection
	protected static $sys_dbhost = SYS_DBHOST; 		// database host (usually localhost)
	protected static $sys_dbport = SYS_DBPORT; 					// database port (optional)
	protected static $sys_dbname = SYS_DBNAME; 		// database name (change this to your database name)
	protected static $sys_dbuser = SYS_DBUSER; 				// database user (the username for your database)
	protected static $sys_dbpassword = SYS_DBPASSWORD; 			// database password (the password for your database)

	private $_handle;									// the handle for storing the errors and resource id
	private $_error;									// error placeholder
	
	
	protected function db_connect($host, $user, $password, $database) {
		if ( !$link = mysql_connect ($host, $user, $password) ) {
			$this->_error = "Databasefout: DB Server &quot;$host&quot; niet bereikbaar.";
		} else {
			if ( !$tmp = mysql_select_db ($database, $link) ) {
				$this->_error = "Databasefout: Database &quot;$database&quot; niet beschikbaar.";
			} else {
				$this->_handle = $link;
			}
		}
	}

	// actual initialization of the database connection
	private function __construct() {
		self::db_connect(self::$sys_dbhost,self::$sys_dbuser,self::$sys_dbpassword,self::$sys_dbname);
	}
	
	// public function to get the connection from outside the class
	public static function getLink() {
		static $db = null;
		if ($db == null) {
			$db = new databaseConnection();
		}
		return $db;
	}
	
	// use this method to get the error or resource id when connecting
	public function handle(){
		if (isset($this->_error)) {
			return $this->_error;
		} else {
			return $this->_handle;
		}
	}
	
	// use this method if you just want to know if the database connection is up or not;
	public function check() {
		if (isset($this->_error)) {
			return false;
		} else {
			return true;
		}
	}
}



?>
