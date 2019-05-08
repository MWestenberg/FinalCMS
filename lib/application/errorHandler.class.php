<?php

class errorHandler {
	
	
	private $__error = array(), $__debug="";
	
	public function __construct() {
	
	
	}
	
	
	public function setError($class,$method,$error) {
		
		$this->__error[$class][$method][] = $error;		
	}
	
	public function printError($class=false,$method=false) {
		
		if (!($class))
		{
			//print all errors
			print "<pre>";
			print_r($this->__error);
			print "</pre>";
		}
		else if ($class && !$method)
		{
			//all errors from one class
			print "<pre>";
			print_r($this->__error[$class]);
			print "</pre>";	
		}
		else 
		{
			//all error from one specific class and method	
			print "<pre>";
			print_r($this->__error[$class][$method]);
			print "</pre>";
		}
	}
	

}



?>