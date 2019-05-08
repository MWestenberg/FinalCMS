<?php 

require_once(APPLICATION . 'php.captcha.inc.php');
require_once(APPLICATION . 'sendmail.class.php');

class accountChange extends controller {
	
	private $__result,$__translator,$__auth;
	
	
	public function __construct($method=false,$params=false) {
		
		$this->__translator = new Translator();
		
		if (method_exists(__CLASS__, $method))
		{
			parent::__construct();
			
			$this->__auth = new credentials();
			$this->$method($params);
			
		}
		else
		{
			
			//illegale actie, iemand probeert op een andere manier hierbij te komen
			//redirect naar home
			header("Location: http://".BASEURL);
			//$this->returnFalse('dirnotfound');
		}
	}
	
	private function accountForm($params) {
		$message = '';
		if ($this->__auth->authenticate($_SESSION['username'],$params['password']))
		{
			//64d00bf267bbe86eed547f5789b35784
			if (!empty($params['password1']) && !empty($params['password2']) && $params['password1']!='d41d8cd98f00b204e9800998ecf8427e')
			{
				if (!$message = $this->resetPassword($params))
				{
					exit();
				}
			}
			
			$q = sprintf("UPDATE user SET firstname='%s',lastname='%s',email1='%s',phone='%s' WHERE id=%s",$params['firstname'],$params['lastname'],$params['email'],$params['phone'],$_SESSION['loginId']);
			if ($this->updateRow($q))
			{
				$this->returnSuccess('store-success','','reload');
			}
			else 
			{
				$this->returnFalse('database-error','reload');	
			}
			
				
			
		}
		else 
		{
			$this->returnFalse('invalid-login');
		}		
		
	}
	
	private function resetPassword($params) {
		
		if ($this->__auth->authenticate($_SESSION['username'],$params['password']))
		{
			if ($params['password1']==$params['password2'])
			{
				if ($this->__auth->setNewPassword($params['password2']))
				{
					return 'Password reset';
				}
				else 
				{
					$this->returnFalse('database-error');	
					return false;	
				}
			}
			else 
			{
				$this->returnFalse('password-mismatch');
			}
		}
		else 
		{
			$this->returnFalse('invalid-login');
			return false;
		}
			
	}
	
	private function contactForm($params) {
				
		if (empty($params['name']) || empty($params['remark']) || (empty($params['phone']) && empty($params['email']))) 
		{
			//check alle verplichte velden
			$this->returnFalse('mandatory-fields');
		}
		else if (!empty($params['phone']) && !$this->checkPhoneNumber($params['phone'])) //validatie email, telefoon en postcode veld
		{
			$this->returnFalse('invalid-phonenumber');
		}
		else if (!empty($params['email']) && !$this->isValidEmail($params['email'])) //validatie email, telefoon en postcode veld
		{
			$this->returnFalse('invalid-emailaddress');
		}
		else 
		{
				
			$paramReplace =  array(
				"param1" => $params['name'],
				"param2" => $params['email'],
				"param3" => $params['phone'],
				"param4" => $params['remark'] 
			);
			//send password reset mail
			$mail = new sendMail;
			$mail->setSender($params['email'], $params['name']);
			$mail->setRecipient('hosting@finalmedia.nl','Final Media');
			$mail->setSubject($this->__translator->getTranslation('contact-form-subject') . APPNAME);
			$mail->setBodyTemplate('contact.html');
			$bodyParams =  array(
				"param1" => $params['name'],
				"param2" => $params['email'],
				"param3" => $params['phone'],
				"param4" => $params['remark'] 
			);
			
			$mail->setHTMLBody($paramReplace);
			$mail->setAltBodyTemplate('contact.txt');	
			$mail->setTextBody($paramReplace);
			
			if ($mail->send()) 
			{
				$this->returnSuccess('thankyou-msg','thankyou-title','reload');
			}
			else 
			{
				$this->returnFalse('mail-error');	
			}	
			
		}
	
	}
	
	public function isValidEmail($email) {
		if  (preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/", $email))
		{
			return true;
		}
		else 
		{
			return false;	
		}
	}
	
	public function checkPhoneNumber($telefoon) {
		if (preg_match("/(^\+[0-9]{2}|^\+[0-9]{2}\(0\)|^\(\+[0-9]{2}\)\(0\)|^00[0-9]{2}|^0)([0-9]{9}$|[0-9\-\s]{10}$)/",$telefoon))
		{
			return true;
		} 
		else 
		{
			return false;
		}
	}
	
	private function returnSuccess($message,$title,$action=false) {
		$this->__result['title'] = $this->__translator->getTranslation($title);
		$this->__result['func'] = 'loadMessage';
		$this->__result['action'] = $action;
		$this->__result['message'] = $this->__translator->getTranslation($message);
		echo json_encode($this->__result);	
	}
		
	private function returnFalse($message,$action=false) {
		$this->__result['title'] = $this->__translator->getTranslation('warning');
		$this->__result['func'] = 'loadMessage';
		$this->__result['action'] = $action;
		$this->__result['message'] = $this->__translator->getTranslation($message);
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
$ajaxcall = new accountChange($action,$params);

?>