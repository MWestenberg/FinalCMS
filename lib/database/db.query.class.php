<?php

/*
	class used for connecting to the database

*/
class db_query extends databaseConnection {
	
	protected $query;
	protected $query_result;
	
	//check if the connection is alive
	public function __construct() {
		if (!parent::getLink()->check()) {
			die (parent::getLink()->handle());
		}
	}
	
	//raw querying of the database
	private function query_db($query) {
		//store the query
		$this->query = $query;
		if ( !$r = mysql_query($query) ) {
			return  "Database error " . mysql_errno() .": ". mysql_error()."<br />$r\n\<br />\n$query<br />\n";
		} else {
			return $r;
		}
	}
	
	// method to get the result and create a nice array of it
	public function getResult($query) {
		$qresult = $this->query_db($query);
		$qarray = array();
		if ( !is_resource($qresult) ) {
				$qarray['error'] = $qresult;
		} else {
			for ($i=0; $i<mysql_num_rows ($qresult);$i++) {
				$qarray[$i] = mysql_fetch_array ($qresult);
			}
		}
		$this->query_result = $qarray;
		return $qarray;
	}
	
	public function checkResult() {
		if (isset($this->query_result) && is_array($this->query_result) 
			&& (isset($this->query_result[0]) || ( isset($this->query_result['error']) && $this->query_result['error']==1))
		) {
			#there is at lease one record so return true
			return true;
		} else {
			return false;
		}
	}
	
	#returns the columns of a certain table
	public function showColumns($table){
		
		$velden = $this->getResult(sprintf("SHOW columns FROM %s",$table));
		return $velden;
	}
	
	public function enumValues($table, $field) {
		$result = mysql_query($query = sprintf("SHOW COLUMNS FROM %s LIKE '%s'",htmlentities($table, ENT_QUOTES,'UTF-8'),htmlentities($field, ENT_QUOTES,'UTF-8')))
			or die("Error getting ENUM field '$field' from '$table'");
		$row = mysql_fetch_array($result, MYSQL_NUM);
		preg_match_all("/'(.*?)'/", $row[1], $enumArr);
		return $enumArr[1];
	}
	
	public function showResult() {
		return $this->query_result;
	}
	
	public function deleteResult() {
		$this->query_result = false;
	}
	
	public static function callme() {
		static $db = null;
		if ($db == null) {
			$db = new db_query();
		}
		return $db;
	}
	
	//for debugging purposes
	public function debug() {
		print "<div class='debug'>".$this->query."</div>";
	}
	
	public function getInsertId() {
		return mysql_insert_id(parent::getLink()->handle());
	}
	
	public function db_close() {
		return mysql_close(parent::getLink()->handle());
	}
}


?>