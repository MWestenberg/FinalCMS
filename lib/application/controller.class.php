<?php
/*
	main controller class makes use of db.query.class.php
	connections to the database should go through this class
	and not directly to db.query.class.php allthough it is possible

*/
class controller {
	
	private $__Exception;
	protected $_db, $debug =false;
	
	public function __construct() {
		$this->__Exception = new errorHandler;
		$this->_db = new db_query;
	}
	
	
	protected function queryDB($query=string) {
		if (preg_match("/\b(select)\b/i",$query))
		{
			return $this->returnResult($this->_db->getResult($query));
		}
		else 
		{
			$this->setException(__FUNCTION__,"INVALID SELECT QUERY: $query");
			($this->debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	}
	
	
	protected function enumValues($table=string, $field=string) {
		return $this->returnResult($this->_db->enumValues($table, $field));
	}
	
	protected function showColumns($table=string,$name=false) {
		if (!$name)
		{
			return $this->returnResult($this->_db->showColumns($table));
		}
		else 
		{
			if ($columns = $this->returnResult($this->_db->showColumns($table)))
			{
				$fields = array();
				foreach ($columns as $key => $value) 
				{
					$fields[] = $value[$name];
				}
				return $fields;
			}
		}
	}
	
	protected function insertRow($query=string) {
		
		if (preg_match("/insert/i",$query))
		{
			$this->_db->getResult($query);
			if ($this->_db->checkResult())
			{
				return $this->_db->getInsertId();
			}
			else
			{
				$this->setException(__FUNCTION__,"INVALID INSERT QUERY: $query");
				($this->debug)? ($this->getException(__FUNCTION__)):false;
				return false;
			}
		}
		else 
		{
			$this->setException(__FUNCTION__,"INVALID INSERT QUERY: $query");
			($this->debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
			
	}
	
	protected function updateRow($query=string) {
		if (preg_match("/update/i",$query))
		{
			return $this->returnResult($this->_db->getResult($query));
		}
		else 
		{
			$this->setException(__FUNCTION__,"INVALID UPDATE QUERY: $query");
			($this->debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	}
	
	protected function deleteRow($query=string) {
		if (preg_match("/delete/i",$query))
		{
			return $this->returnResult($this->_db->getResult($query));
		}
		else 
		{
			$this->setException(__FUNCTION__,"INVALID DELETE QUERY: $query");
			($this->debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	}
	
	protected function checkEditRights() {
		
		foreach ($_SESSION['editRights'] as $key => $value) 
		{
			if ($value['templatename']	== $_SESSION['currentTemplateName'])
			{
				return true;
			}
		}
		
		return false;
	
	}
	
	protected function returnResult($result) {
		
		if ($this->_db->checkResult())
		{
			return $result;
		}
		else 
		{
			return false;	
		}	
	}
	
	private function setException($method,$str) {
		$this->__Exception->setError(__CLASS__,$method,$str);
		
	
	}
	
	private function getException($method) {
		$this->__Exception->printError(__CLASS__,$method);
	}
	
	
}


?>