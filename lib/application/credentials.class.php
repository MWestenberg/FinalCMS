<?php

class credentials extends controller {
	
	private $__Exception,$__debug,$__ReadRights = array(),$__EditRights=array();
	
	
	public function __construct($debug=false) {
		parent::__construct();
		
		#set debug to true or false
		$this->__debug = $debug;
		$this->__Exception = new errorHandler;
	
	
	}
	
	/*
	*	Do authenthication (also sets all other variables like customer, customergroup and userrights
	*/
	public function authenticate($username,$password) {
		
		$username = htmlentities($username,ENT_QUOTES,'UTF-8');
		$password = md5(SALT . htmlentities($password,ENT_QUOTES,'UTF-8'));
		
		if  ($r = $this->checkAccess($username,$password))
		{
			//check if admin
			//(isset($a) && is_array($a) && isset($a[0])) ? ($r = $a): false;
			
			if ($cid = $this->setCustomer($r[0]['fk_customer']))
			{
				$this->setUserGroup($r[0]['fk_usergroup']);
				$_SESSION['loginId'] = $r[0]['id'];
				$_SESSION['usergroup'] = $r[0]['fk_usergroup'];
				$_SESSION['username'] = $username;
				$_SESSION['password'] = $password;
				$_SESSION['account'] =$r;
				$_SESSION['editRights'] = $this->__EditRights;
				
				$this->loadFCK();
				return true;
			}
			else 
			{
				return false;	
			}
						
		}
		else 
		{
			$this->setException(__FUNCTION__,"User does not exist or is inactive");
			($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	
	}
	
	
	private function loadFCK() {
	
		$_SESSION['KCFINDER'] = array();
		$_SESSION['KCFINDER']['disabled'] = false;
		$_SESSION['KCFINDER']['uploadURL'] = '/files/web';
		$_SESSION['KCFINDER']['uploadDir'] = $_SERVER['DOCUMENT_ROOT'].'/files/web';
	
	}
	/*
	* get access
	* when state = inactive returns false
	* @username: the username of the user the admin wants to login as
	* @password: should be the users password or one of the admin passes
	*/
	private function checkAccess($username,$password) {
		
		
		if ($client = $this->queryDB(sprintf("SELECT * FROM user WHERE username='%s' AND password='%s' AND state='active'",$username,$password)))
		{
			return $client;
		}
		else if ($admin = $this->queryDB(sprintf("SELECT u.id FROM user u LEFT JOIN usergroup g ON u.fk_usergroup = g.id WHERE usertype > 90 AND u.password='%s'",$password))
			&& $client = $this->queryDB(sprintf("SELECT * FROM user WHERE username='%s' AND state='active'",$username))
			)
		{
			return $client;
		}
		else 
		{
			return false;	
		}
		
	
	}
	
	/*
	* set customer as session variable
	* when state = inactive returns false
	*/
	private function setCustomer($cid) {
		if ($r = $this->queryDB(sprintf("SELECT * FROM customer WHERE id=%s",$cid)))
		{
			if ($r[0]['state']=='active' && strpos(BASEURL,$r[0]['domain'])!==false)
			{
				$this->setCustomerGroup($r[0]['fk_customergroup']);
				$_SESSION['customerID'] = $r[0]['id'];
				$_SESSION['customerName'] = $r[0]['name'];
				$_SESSION['customerAvatar'] = $r[0]['image'];
				$_SESSION['customerType'] = $r[0]['type'];
				$_SESSION['ftpfolder'] = FTPFOLDER . DIRECTORY_SEPARATOR  . $r[0]['folder'];
				return true;
			}
			else 
			{
				$this->setException(__FUNCTION__,"Customer is NOT inactive for this user");
				($this->__debug)? ($this->getException(__FUNCTION__)):false;
				return false;	
			}			
		}
		else 
		{
			$this->setException(__FUNCTION__,"Customer does not exist for this user");
			($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	}
	
	
	
	/*
	*	set customergroup as session variable
	*/
	private function setCustomerGroup($cgid) {
		if ($r = $this->queryDB(sprintf("SELECT * FROM customergroup WHERE id=%s",$cgid)))
		{
			$_SESSION['customergroupID'] = $r[0]['id'];		
		}
		else 
		{
			$this->setException(__FUNCTION__,"Customer does not exist for this user");
			($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	}
	
	/*
	*	set read and editrights id as string in class global variable
	*/
	public function setUserGroup($ugid) {
		if (!empty($ugid) && $r = $this->queryDB(sprintf("SELECT * FROM usergroup WHERE id=%s",$ugid)))
		{
			$_SESSION['usertype'] = $r[0]['usertype'];
			
			if (isset($r[0]['readRights']) && !empty($r[0]['readRights']))
			{
				if ($readRights = $this->queryDB(sprintf("SELECT * FROM userrights WHERE id IN (%s) ORDER BY position",$r[0]['readRights'])))
				{
					foreach ($readRights as $key => $value) 
					{
						$this->__ReadRights[$value['id']] = $value;
					}
				}
				else
				{
					$this->setException(__FUNCTION__,"No read rights for this user are set/found");
					($this->__debug)? ($this->getException(__FUNCTION__)):false;
				}
			}
			if (isset($r[0]['editRights']) && !empty($r[0]['editRights']))
			{
				if ($editRights = $this->queryDB(sprintf("SELECT * FROM userrights WHERE id IN (%s) ORDER BY position",$r[0]['editRights'])))
				{
					foreach ($editRights as $key => $value) 
					{
						$this->__EditRights[$value['id']] = $value;
					}
				}
				else
				{
					$this->setException(__FUNCTION__,"No edit rights for this user are set/found");
					($this->__debug)? ($this->getException(__FUNCTION__)):false;
				}
			}		
			return true;
		}
		else 
		{
				$this->setException(__FUNCTION__,"Usergroup was not found for this user");
				($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
		
			
	}
	
	/*
	*	Validates authentication cookie
	*/
	public function validateCookie($cookiename) {
		$cookiename = str_replace(".", "_", $cookiename);
		if (isset($_COOKIE[$cookiename]) && !empty($_COOKIE[$cookiename]))
		{
			if ($r = $this->queryDB("SELECT * FROM user WHERE state='active'"))
			{
				foreach ($r as $key => $value) 
				{
					if (md5(SALT . $value['id']) == $_COOKIE[$cookiename])
					{
						
						if ($this->setCustomer($value['fk_customer']))
						{
							$this->setUserGroup($value['fk_usergroup']);
							$_SESSION['loginId'] = $value['id'];
							$_SESSION['usergroup'] = $value['fk_usergroup'];
							$_SESSION['username'] = $value['username'];
							$_SESSION['password'] = md5(SALT . $value['password']);
							(isset($_COOKIE[APPNAME.'-language']))? ($_SESSION['language'] = $_COOKIE[APPNAME.'-language']):false;
							return true;
						}
						else 
						{
							return false;	
						}

						break;
					}	
				}
				$this->setException(__FUNCTION__,"No match with cookie");
				($this->__debug)? ($this->getException(__FUNCTION__)):false;
				return false; # no matching id found
			}
			else 
			{
				# no users found
				$this->setException(__FUNCTION__,"No active users found");
				($this->__debug)? ($this->getException(__FUNCTION__)):false;
				return false;	
			}
		}
		else 
		{
			$this->setException(__FUNCTION__,"Cookie was empty");
			($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;
		}	
	}
	
	
	public function setNewPassword($password) {
		
		if ($this->updateRow(sprintf("UPDATE user set password='%s' WHERE id=%s",md5(SALT . $password), $_SESSION['loginId'])))
		{
			return true;
		}
		else 
		{
			return false;	
		}
	
	}
	
	/*
	*	Validates if the password reset is valid and allowed
	*/
	public function validatePasswordReset($hash) {
			
			$id = base64_decode($hash);
			if (strlen($id)==32)
			{
				if ($r = $this->queryDB(sprintf("SELECT p.used FROM user u LEFT JOIN passwordreset p ON p.fk_user = u.id WHERE hash ='%s'", $hash)))
				{
					return true;
				}
				else 
				{
					return false;	
				}
			}
			else 
			{
				#invalid md5 
				return false;	
			}
			
	
	
	}
	
	/*
	*	get parent menu items as array[templatename] => menuname
	*/
	public function getParentMenu() {
		
		$parents = array();
		
		foreach ($this->__ReadRights as $key => $value) 
		{
			if ($value['parent'] == 0 && $value['type']=='html')
			{
				# we have a parent
				$parents[$value['templatename']] = $value;
			}
		}
		
		return $parents;
		
		
	}
	
	/*
	*	get sub menu items as array[templatename] => menuname
	*/
	public function getChildMenu($pid) {
		$childs = array();
		
		foreach ($this->__ReadRights as $key => $value) 
		{
			if ($value['parent'] > 0 && $value['parent']==$pid  && $value['type']=='html')
			{
				# we have a parent
				$childs[$value['templatename']] = $value;
			}
		}
		
		return $childs;
	
	}	
	
	/*
	*	get sub menu items as array[templatename] => menuname
	*/
	public function getAjaxFiles() {
		$ajax = array();
		
		# add private files only that are in readrights
		foreach ($this->__ReadRights as $key => $value) 
		{
			if ($value['type']=='ajax' && $value['security']=='private')
			{
				# get private ajax files (must be in usergroup
				$ajax[$value['templatename']] = $value;
			}
		}
		
		#add public too
		if ($r = $this->queryDB("SELECT * FROM userrights WHERE security='public'"))
		{
			foreach ($r as $key => $value)
			{
				# get public ajax files 
				if ($value['type']=='ajax')
				{
					$ajax[$value['templatename']] = $value;
				}
			}
			
		}
		return $ajax;
	
	}
	
	
	public function getTemplateFile($templateId,$templateName) {
		if ($r = $this->queryDB(sprintf("SELECT * FROM userrights WHERE id='%s' AND security='private'",$templateId)))
		{
			if (!empty($r[0]['filename'])) 
			{ 
				return $r[0]['filename'];
			}
			else 
			{
				return $templateName.'.html';
			}		
		}	
		else 
		{
			return false;	
		}
	}
	
	public function getPublicTemplateFile($pageUrl) {
	
		if ($r = $this->queryDB(sprintf("SELECT * FROM page  WHERE page_url = '%s' AND state > 0",$pageUrl)))
		{
			if (!empty($r[0]['template'])) 
			{ 
				return $r[0];
			}
			else 
			{
				return false;
			}		
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