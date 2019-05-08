<?php 
require_once(APPLICATION . 'sendmail.class.php');

class customerList extends controller {
	
	private $__result,$__translator,$__fileHandler,$_subdir = false;
	
	
	public function __construct($method=false,$params=false) {
		
		$this->__translator = new Translator();
		
		if (method_exists(__CLASS__, $method))
		{
			parent::__construct();
			$this->$method($params);

		}
		else
		{
			
			//illegale actie, iemand probeert op een andere manier hierbij te komen
			//redirect naar home
			header("Location: http://".BASEURL);
			//$this->returnMessage($this->__translator->getTranslation('dirnotfound'));
		}
	}
	
	/*
	* initiates all buttons on the dialog with id customerDialog
	*/
	private function customerDialog($params) {
	
		if (isset($params['button']) && method_exists(__CLASS__, $params['button']))
		{
			
			$method = $params['button'];
			$this->$method($params);
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}
		
	}
	
	
	/*
	* initiates all buttons on the dialog with id userDialog
	*/
	private function userDialog($params) {
	
		if (isset($params['button']) && method_exists(__CLASS__, $params['button']))
		{
			
			$method = $params['button'];
			$this->$method($params);
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}
		
	}
	
	/*
	* stores a customer initiated from customerDialog()
	*/
	private function storeCustomer($params) {
		
		//check required fields
		if (!$this->requiredFields(array("name","streetnumber","zipcode","city","email"),$params))
		{
			$this->returnMessage($this->__translator->getTranslation('required-fields'));	
			exit();
		}
		else if(empty($params['id']) && $r = $this->queryDB(sprintf("SELECT id,name FROM customer WHERE folder='%s'",$params['name'].'-'.$params['zipcode'].'-'.$params['streetnumber'])))
		{
			$this->returnMessage($this->__translator->getTranslation('customer-exists'));	
			exit();
		}
		else {
			
			$folder = $params['name'].'-'.$params['zipcode'].'-'.$params['streetnumber'];
			
			$queryAdd = $this->buildQuery('customer',$params);
			
			if (isset($params['id']) && !empty($params['id']) && $r = $this->queryDB(sprintf("SELECT * FROM customer WHERE id=%s",$params['id'])))
			{	
				//update record
				if ($this->updateRow(sprintf("UPDATE customer SET %s folder='%s',mod_date=NOW() WHERE id=%s", $queryAdd,$folder,$r[0]['id'])))
				{
					if (isset($r[0]['folder']) && !empty($r[0]['folder'])) 
					{
						$this->moveCustomerDir($r[0]['folder'],$folder);
						$this->returnMessage($this->__translator->getTranslation('customer-changed'),'reload');
					}
					else 
					{
						$this->createCustomerDir($folder); //just create it then
						$this->returnMessage($this->__translator->getTranslation('customer-changed'),'reload');		
					}
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}				
			} 
			else if ($cid = $this->insertRow(sprintf("INSERT INTO customer SET %s folder='%s',type='%s',domain='%s',create_date=NOW(),mod_date=NOW(),fk_customergroup=2", $queryAdd,$folder,$_SESSION['customerType'],$_SERVER['SERVER_NAME'])))
			{	
				//add record to database
				if ($this->createCustomerDir($folder,$cid)) 
				{
					$this->returnMessage($this->__translator->getTranslation('customer-created'),'reload');
				}
			}
			else 
			{
				$this->returnMessage($this->__translator->getTranslation('database-error'));
			}
		}
	}
	
	/*
	* stores a customer initiated from userDialog()
	*/
	private function storeUser($params) {
		
		$usergroup = $this->queryDB(sprintf("SELECT id FROM usergroup WHERE name='%s'",$_SERVER['SERVER_NAME']));
		
		//check required fields
		if (!$this->requiredFields(array("firstname","lastname","email1","phone"),$params))
		{
			$this->returnMessage($this->__translator->getTranslation('required-fields'));	
			exit();
		}
		else if(empty($params['id']) && $r = $this->queryDB(sprintf("SELECT id,name,username FROM user WHERE username='%s'",$params['email1'])))
		{
			$this->returnMessage(sprintf($this->__translator->getTranslation('user-exists'),$r[0]['username']));	
			exit();
		}
		else 
		{	
			
			//set username to email when empty
			if (empty($params['username']))
			{
				$params['username'] = $params['email1'];
			}
			
			$queryAdd = $this->buildQuery('user',$params);
			
			if (isset($params['id']) && !empty($params['id']) && $r = $this->queryDB(sprintf("SELECT u.*,c.name as custname FROM user u LEFT JOIN customer c ON u.fk_customer=c.id WHERE u.id=%s",$params['id'])))
			{	
				
				//check username 
				if ($this->queryDB(sprintf("SELECT id FROM user WHERE username='%s' AND deleted=0 AND fk_usergroup=%s AND id!=%s",$params['username'],$usergroup[0]['id'],$params['id']))) 
				{
					$this->returnMessage(sprintf($this->__translator->getTranslation('user-exists'),$params['username']));				
				}
				else 
				{
					//update record	
					if ($this->updateRow(sprintf("UPDATE user SET %s mod_date=NOW() WHERE id=%s", $queryAdd,$r[0]['id'])))
					{
						$this->__result['redirect'] = 'customerList';
						$this->__result['rel'] = 'customer';
						$this->__result['id'] = $r[0]['fk_customer'];
						$this->__result['href'] = $r[0]['custname'];
										
						$this->returnMessage($this->__translator->getTranslation('user-changed'),'redirect');
					}
					else 
					{
						$this->returnMessage($this->__translator->getTranslation('database-error'));
					}	
				}
				
				
				
			}
			else if ($this->queryDB(sprintf("SELECT id FROM user WHERE username='%s' AND deleted=0 AND fk_usergroup=%s",$params['username'],$usergroup[0]['id']))) 
			{
				//check username
				$this->returnMessage(sprintf($this->__translator->getTranslation('user-exists'),$params['username']));	
			}
			else if ($cid = $this->insertRow(sprintf("INSERT INTO user SET %s create_date=NOW(),mod_date=NOW(),password='%s',fk_customer=%s,fk_usergroup=%s", $queryAdd,md5(SALT.uniqid()),$params['cid'],$usergroup[0]['id'])) && $r = $this->queryDB(sprintf("SELECT c.id,c.name as custname FROM customer c WHERE c.id=%s",$params['cid'])))
			{
				
				$this->__result['redirect'] = 'customerList';
				$this->__result['rel'] = 'customer';
				$this->__result['id'] = $r[0]['id'];
				$this->__result['href'] = $r[0]['custname'];
				//add record to database
				$this->returnMessage($this->__translator->getTranslation('user-created'),'redirect');
			}	
			else 
			{
				$this->returnMessage($this->__translator->getTranslation('database-error'));
			}
			
		}
	}
	
	private function buildQuery($table,$params) {
		
		//get columns from table
		$columns = $this->showColumns($table);
		
		 $skipFields = array("id","password","fk_customer");
		
		//create query
		$queryAdd = '';
		foreach ($columns as $columnKey => $columnVal) 
		{
			//state is a checkbox with enum value
			if ($columnVal['Field']=='state')
			{
				if (isset($params[$columnVal['Field']]))
				{
					$queryAdd .= $columnVal['Field']."='active',";
				}
				else 
				{
					$queryAdd .= $columnVal['Field']."='inactive',";	
				}
			}
			else if (isset($params[$columnVal['Field']]) && !in_array($columnVal['Field'], $skipFields))
			{
				$queryAdd .= $columnVal['Field']."='".$params[$columnVal['Field']]."',";
			}
			
		}
		
		return $queryAdd;
	
	}
	
	
	/*
	* delete a customer
	*/
	private function delCustomer($params) {
			
		if ($r = $this->queryDB(sprintf("SELECT * FROM customer WHERE id=%s",$params['id'])))
		{
			if (isset($params['confirm']))
			{
				
				//set customer to deleted and deactivate it so users are not able to login anymore
				if ($this->updateRow(sprintf("UPDATE customer set deleted='1', state='inactive' WHERE id=%s",$params['id'])))
				{
					//remove dir
					/* doesnt work when permissions are not set. Let user do it himself
					$root = rtrim($_SESSION['ftpfolder'],"default/");
					$folder = $root . DIRECTORY_SEPARATOR . $r[0]['folder'];
					$fileHandler = new fileHandler;
					$fileHandler->rrmdir($folder);
					*/					
					$this->returnMessage(sprintf($this->__translator->getTranslation("customer-deleted"),$r[0]['name']),'reload');
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}

			}
			else 
			{
				$message = sprintf($this->__translator->getTranslation('confirm-delcustomer'),$r[0]['name']);
				$this->__result['formID'] = '#customerdialog';
				$this->__result['page'] = "detaileditor";
				$this->__result['params'] = array("action" => __FUNCTION__,"id" => $r[0]['id'],"confirm" => true);
				$this->__result['func'] = 'loadMessage';
				$this->__result['action'] = 'confirm';
				$this->__result['message'] = $message;
				echo json_encode($this->__result);	
			}
				
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}
		
	}
	
	
	
	private function moveCustomerDir($oldFolder,$newFolder) {
		
		//create directory	
		$root = rtrim($_SESSION['ftpfolder'],"default/");	
		
		$oldDir = $root . DIRECTORY_SEPARATOR . $oldFolder;
		$newDir = 	$root . DIRECTORY_SEPARATOR . $newFolder;
		$fileHandler = new fileHandler;
		if ($fileHandler->renameDirectory($oldDir,$newDir))
		{
			return true;
		}
		else 
		{
			$this->returnMessage('Unable to move folder from '.$oldFolder .' to '. $newFolder);
		}
		
	}	
	
	
	private function createCustomerDir($folder,$cid) {
		
		//create directory	
		$root = rtrim($_SESSION['ftpfolder'],"default/");	
		$dirName = 	$root . DIRECTORY_SEPARATOR . $folder;
		$fileHandler = new fileHandler;
		if ($fileHandler->createDirectory($dirName))
		{
			
			if ($_SESSION['customerType']=='gemeenten')
			{
				$fileHandler->createDirectory($dirName . '/particulier');	
			}
			
			$fileHandler->createDirectory($dirName . '/sc530');
			
			return true;
		}
		else 
		{
			$this->rollBack("customer",$cid); //rolback	
			$this->returnMessage($this->__translator->getTranslation('emptydir') .$dirName );
		}
		
	}	
	
		
	private function requiredFields($requiredFields,$params) {
	
		//check required fields
		
		
		foreach ($requiredFields as $key => $value) 
		{
			if (isset($params[$value]) && !empty($params[$value]))
			{
				unset($requiredFields[$key]);
			}
		}
		
		if (count($requiredFields) > 0)
		{
			return false;	
		}
		else 
		{
			return true;
		}
	}
	
	
		
	
	private function delUser($params) {
		
		if ($r = $this->queryDB(sprintf("SELECT u.*,c.name as custname FROM user u LEFT JOIN customer c ON u.fk_customer=c.id WHERE u.id=%s",$params['id'])))
		{
			if (isset($params['confirm']))
			{
				
				//set customer to deleted and deactivate it so users are not able to login anymore
				if ($this->updateRow(sprintf("UPDATE user set deleted='1', state='inactive' WHERE id=%s",$params['id'])))
				{
					$this->__result['redirect'] = 'customerList';
					$this->__result['rel'] = 'customer';
					$this->__result['id'] = $r[0]['fk_customer'];
					$this->__result['href'] = $r[0]['custname'];
					
					
					$this->returnMessage(sprintf($this->__translator->getTranslation("user-deleted"),$r[0]['firstname'].' '.$r[0]['lastname']),'redirect');
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}
				
				
				
			}
			else 
			{
				$message = sprintf($this->__translator->getTranslation('confirm-deluser'),$r[0]['firstname'].' '.$r[0]['lastname']);
				$this->__result['formID'] = '#customerdialog';
				$this->__result['page'] = "detaileditor";
				$this->__result['params'] = array("action" => __FUNCTION__,"id" => $r[0]['id'],"confirm" => true);
				$this->__result['func'] = 'loadMessage';
				$this->__result['action'] = 'confirm';
				$this->__result['message'] = $message;
				echo json_encode($this->__result);	
			}
				
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}		
	}
	
	
	/*
	* general dialog Loader
	*/
	private function loadDialogWindow($page,$dialog,$title,$action,$formdata=false) {
	
		$this->__result['dialog'] = SECUREPATH .'/clientmanagement/'.$page;
		$this->__result['formID'] = '#'.$dialog;
		$this->__result['func'] = 'loadForm';
		$this->__result['target'] = '#dialogWindow';
		$this->__result['title'] = $this->__translator->getTranslation($title);
		$this->__result['action'] = $action;
		($formdata) ? (	$this->__result['formData'] = $formdata):false;
		echo json_encode($this->__result);
	}
	
	
	/*
	* load create customer Dialog
	*/
	private function createCustomer($params,$action=false) {
		$this->loadDialogWindow('clientDialog','customerdialog','create-customer',$action);
	}
	
	
	/*
	* load edit customer Dialog
	*/
	private function editCustomer($params,$action=false) {
		
		if ($r = $this->queryDB(sprintf("SELECT * FROM customer WHERE id=%s",$params['id'])))
		{
			(isset($r[0]['state']) && $r[0]['state'] =='active') ?($r[0]['state']=1):($r[0]['state']=0);
			$this->loadDialogWindow('clientDialog','customerdialog','edit-customer',$action,$r);
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}
		
	}
		
	/*
	* load create user Dialog
	*/
	private function createUser($params,$action=false) {
		$formdata = array("0" => array("cid" => $params['id']));
		$this->loadDialogWindow('userDialog','userdialog','create-user',$action,$formdata);
	}
		
	/*
	* load edit user Dialog
	*/
	private function editUser($params,$action=false) {
		
		if ($r = $this->queryDB(sprintf("SELECT * FROM user WHERE id=%s",$params['id'])))
		{
			(isset($r[0]['state']) && $r[0]['state'] =='active') ?($r[0]['state']=1):($r[0]['state']=0);
			$this->loadDialogWindow('userDialog','userdialog','edit-user',$action,$r);		
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}
	
	}
	
	private function sendNewPass($params) {
		
		//$this->returnMessage('in progress');
		
		
		if ($r = $this->queryDB(sprintf("SELECT id,md5(id) as hash,firstname,lastname,email1,email2,username FROM user WHERE id=%s",$params['id'])))
		{
			
			#set complicated hash as md5
			$hash = base64_encode(md5(SALT . date("YmdHis") . $r[0]['hash']));
			
			if ($insertID = $this->insertRow( sprintf("INSERT INTO passwordreset set fk_user='%s',date_time='%s', hash='%s'",$params['id'],time(),$hash)))
			{
				
				//send password reset mail
				$mail = new sendMail;
				
				$mail->setSender(APPMAIL,APPNAME);
				
				$mail->setRecipient($r[0]['email1'], $r[0]['lastname']);
				
				(isset($hash[0]['email2']) && !empty($hash[0]['email2'])) ? ($mail->addCC($hash[0]['email1'])):false; # backup email gets a cc
				
				$mail->setSubject($this->__translator->getTranslation('mail-passreset-subject'));
				
				$mail->setBodyTemplate('account-init.html');
								
				$paramReplace  = array(
					"param1" => $r[0]['username'],
					"param2" => APPNAME,
					"param3" => "https://" . BASEURL . "/".$_SESSION['language']."/reset-password/" . $hash,
					"param4" => "https://" . BASEURL . "/".$_SESSION['language']."/reset-password/" . $hash,
					"param5" => APPNAME
				);
				
				$mail->setHTMLBody($paramReplace);
				
				$mail->setAltBodyTemplate('account-init.txt');	
				
				$mail->setTextBody($paramReplace);
				
				if ($mail->send()) 
				{
					$this->returnMessage($this->__translator->getTranslation('passwordreset-success-msg3'));
				}
				else
				{
					$this->returnMessage($this->__translator->getTranslation('mail-error').'<br />'.serialize($mail->getException()));
				}
				
			}
			else 
			{
				$this->returnMessage($this->__translator->getTranslation('database-error'));	
			}
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}
		
		#insert new record in password reset table
		
		
		
		
		
	}
	
	private function getList($params) {
		
		/*
			params['type'] = user => show user list, customer => show customerlist
		
		*/
		
		if (isset($params['type']) && $params['type']=='customer')
		{
			$obj = '';
			if ($r = $this->queryDB(sprintf("SELECT id,username,firstname,lastname,email1,avatar,phone,mod_date FROM user WHERE fk_customer=%s AND deleted=0",$params['id'])))
			{
				
				$counter = 0;
				
				foreach ($r as $value)
				{
					$value['type'] = 'user';
					$counter++;
					$icon = $this->__translator->getIcon($value);
					$obj .= 	'<tr>
						<td><a href="editUser"  id="'.$value['id'].'" class="editDialog" rel="editUser"><img src="/images/icons/'.$icon.'" width="40" height="40" class="textmiddle" borde="0" /></a> <a href="editUser"  id="'.$value['id'].'" class="editDialog" rel="editUser">'.$value['firstname'].' '.$value['lastname'].'</a></td>
						<td>'.strftime("%e %B %Y %H:%I:%M:%S", strtotime($value['mod_date'])).'</td>
						<td><a href="editUser"  id="'.$value['id'].'" class="editDialog" rel="editUser"><img src="/images/icons/icon-pencil.png" width="40" height="40" alt="'.$this->__translator->getTranslation('change').'" title="'.$this->__translator->getTranslation('change').'" /></a> <a href="delUser" id="'.$value['id'].'" class="editDialog" rel="delUser"><img src="/images/icons/icon-trash.png" width="40" height="40" alt="'.$this->__translator->getTranslation('delete').'" title="'.$this->__translator->getTranslation('delete').'" /></a></td>
					</tr>';
					
				}				
			}
			else 
			{
				$obj .= 	'<tr><td colspan="4">'.$this->__translator->getTranslation('empty-userlist').'</td></tr>';
			}
			
			$breadcrumbs = '<a class="" href="/secure/clientmanagement"><img src="/images/icons/'.$this->__translator->getIcon('user-crumbpath').'" width="117" height="124" class="textmiddle" border="0" /></a> <span class="arrows">&gt;&gt;</span> '.urldecode($params['href']).'';	
			
		}
		else 
		{
			$this->returnMessage('id: '.$params['id'].' bc='.$params['breadcrumbs'].' t='.$params['type']);
			exit();
		}
		
		
		$this->__result['breadcrumbs'] = $breadcrumbs;
		$this->__result['func'] = 'updateElement';
		$this->__result['element'] = "#dirlist";
		$this->__result['editUser'] = $params['id'];
		$this->__result['html'] = $obj;
		echo json_encode($this->__result);
		
			
	}
	
	private function rollBack($table,$id) {
	
		if ($_SESSION['usergroup']>=90)
		{
			if ($this->deleteRow(sprintf("DELETE FROM %s WHERE id=%s",$table,$id)))
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
			return false;	
		}
	
	}
		
	private function returnMessage($message,$action=false) {
		$this->__result['title'] = $this->__translator->getTranslation('warning');
		$this->__result['func'] = 'loadMessage';
		$this->__result['action'] = $action;
		$this->__result['message'] = $message;
		echo json_encode($this->__result);
	}


}



$params = array();

if (!isset($_POST) || !isset($_POST['action']))
{
	header("Location: http://".$_SERVER['HTTP_HOST']);
}

foreach($_POST as $key => $value)
{
	if ($key == 'action')
	{
		$action = htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8');
	}
	else
	{
		$params[$key] = htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8');
	}
}
$ajaxcall = new customerList($action,$params);

?>