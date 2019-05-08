<?php
require_once(APPLICATION. 'php.captcha.inc.php');
require_once(APPLICATION . 'sendmail.class.php');


class login extends controller {
	
	private $__result,$__translator;
	
	public function __construct($method=false,$params=false) {
		
		if (method_exists(__CLASS__, $method))
		{
			parent::__construct();
			$this->__translator = new Translator();
			$this->$method($params);
		}
		else
		{
			
			//illegale actie, iemand probeert op een andere manier hierbij te komen
			//redirect naar home
			header("Location: http://".BASEURL);
			//$this->returnFalse("Unknown method $method");
		}
	}
	
	private function signIn($params) {
		
		if (isset($_SESSION['falselogin']) && $_SESSION['falselogin']>=3)
		{
			$this->returnFalse($this->__translator->getTranslation('toomanylogins'));
			exit;
		}
		
		$auth = new credentials();
		if ($auth->authenticate($params['username'],$params['password']))
		{
			
			if (isset($params['remember']) && ($params['remember']=='on' || $params['remember']==1))
			{
				$this->setACookie(APPNAME, md5(SALT . $_SESSION['loginId']));
				$this->setACookie(APPNAME.'-language',$_SESSION['language']);
				
			}
			
			$_SESSION['falselogin']=0;
			$this->__result['func'] = 'refresh';
			$this->__result['lang'] = $_SESSION['language'];
			$this->__result['location'] = SECUREPATH . '/start';
			echo json_encode($this->__result);
		}
		else 
		{
			if (!isset($_SESSION['falselogin']))
			{
				$_SESSION['falselogin']=1;
			}
			else
			{
				$_SESSION['falselogin']++;
			}
			
			if ($_SESSION['falselogin']>=3)
			{
				$this->returnFalse($this->__translator->getTranslation('toomanylogins'));
			}
			else 
			{
				$this->returnFalse($this->__translator->getTranslation('invalid-login'));		
			}
				
		}
		
	}
	
	private function signOut($params) {
				
		if (isset($_SESSION['language']) && $this->__translator->checkLanguage($_SESSION['language']))
		{
			$this->__result['location'] = '/'.$_SESSION['language'].'/login';
		}
		else 
		{
			$this->__result['location'] = '/login';	
		}
		
		//$location = "/".$_SESSION['language']."/login";
				
		foreach ($_SESSION as $key => $value) 
		{
			if ($key!='language')
			{
				$_SESSION[$key] = "";	
				unset($_SESSION[$key]);
			}
		}
				
				
		session_destroy();
		$this->unsetACookie(APPNAME);
		$this->unsetACookie("PHPSESSID");
	
		
		$this->__result['func'] = 'refresh';
		echo json_encode($this->__result);
	}
	
	private function passwordReset($params) {
		
		if (!PhpCaptcha::Validate($params['user_code'])) //check captcha 
		{
			$this->returnFalse($this->__translator->getTranslation('invalid-capthca'),'reload');
		} 
		else
		{
				
			$q1 = sprintf("SELECT u.id,md5(u.id) as hash,u.firstname,u.lastname,u.email1,u.email2 FROM user u LEFT JOIN customer c ON u.fk_customer=c.id WHERE u.email1 = '%s' AND u.username='%s' AND c.domain='%s'", $params['email'],$params['username'],BASEURL);	
			if ($r = $this->queryDB($q1))
			{
				$onedayago = time() - (24 * 60 * 60);
				if (!$this->queryDB(sprintf("SELECT fk_user FROM passwordreset WHERE fk_user='%s' AND date_time > %s ",$r[0]['id'],$onedayago)))
				{ 
					#set complicated hash as md5
					$hash = base64_encode(md5(SALT . date("YmdHis") . $r[0]['hash']));
					
					#insert new record in password reset table
					if ($insertID = $this->insertRow( sprintf("INSERT INTO passwordreset set fk_user='%s',date_time='%s', hash='%s'",$r[0]['id'],time(),$hash)))
					{
						
						//send password reset mail
						$mail = new sendMail;
						
						$mail->setSender(APPMAIL,APPNAME);
						
						$mail->setRecipient($r[0]['email1'], $r[0]['lastname']);
						
						(isset($hash[0]['email2']) && !empty($hash[0]['email2'])) ? ($mail->addCC($hash[0]['email1'])):false; # backup email gets a cc
						
						$mail->setSubject($this->__translator->getTranslation('mail-passreset-subject'));
						
						$mail->setBodyTemplate('password-reset.html');
						
						$paramReplace  = array(
							"param1" => $r[0]['lastname'],
							"param2" => APPNAME,
							"param3" => "https://" . BASEURL . "/".$_SESSION['language']."/reset-password/" . $hash,
							"param4" => "https://" . BASEURL . "/".$_SESSION['language']."/reset-password/" . $hash,
							"param5" => APPNAME
						);
						
						$mail->setHTMLBody($paramReplace);
						
						$mail->setAltBodyTemplate('password-reset.txt');	
						
						$mail->setTextBody($paramReplace);
						
						if ($mail->send()) 
						{
							$this->returnSuccess($this->__translator->getTranslation('passwordreset-success-msg1'),$this->__translator->getTranslation('request-success'),'reload');
						}
						else
						{
							$this->returnFalse($this->__translator->getTranslation('mail-error').'<br />'.serialize($mail->getException()),'reload');
						}
						
					}
					else 
					{
						$this->returnFalse($this->__translator->getTranslation('database-error'),'reload');	
					}
					
					
					
				}
				else 
				{
					$this->returnFalse($this->__translator->getTranslation('passwordreset-error1'),'reload');		
				} 
						
			}
			else 
			{
				$this->returnFalse($this->__translator->getTranslation('invalid-account'),'reload');	
			}
			
		}
	
	}
	
	private function resetpass($params) {
		
		//test1234 = 64d00bf267bbe86eed547f5789b35784
				
		if (!PhpCaptcha::Validate($params['user_code'])) //check captcha 
		{
			$this->returnFalse($this->__translator->getTranslation('invalid-capthca'),'reload');
		} 
		else
		{
		
			if ($r = $this->queryDB(sprintf("SELECT u.id as uid,p.id as pid FROM user u LEFT JOIN passwordreset p ON p.fk_user = u.id WHERE hash ='%s' AND p.used=0", $_SESSION['resetpass'])))
			{
				if (isset($params['password1']) && isset($params['password2']) && $params['password1']==$params['password2'])
				{
					if ($insertID = $this->updateRow(sprintf("UPDATE passwordreset set used=1 WHERE id=%s AND hash='%s'",$r[0]['pid'],$_SESSION['resetpass'])))
					{
						if ($this->updateRow(sprintf("UPDATE user set password='%s' WHERE id=%s",md5(SALT . $params['password2']), $r[0]['uid'])))
						{
							# need to unset it so link will not work anymore and user will be redirected to login page
							unset($_SESSION['resetpass']);
							$this->returnSuccess($this->__translator->getTranslation('passwordreset-success-msg2'),$this->__translator->getTranslation('request-success'),'reload');
						}
						else 
						{
							$this->returnFalse($this->__translator->getTranslation('database-error'),'reload');	
						}
					}
					else 
					{
						$this->returnFalse($this->__translator->getTranslation('database-error'),'reload');	
					}
				}
				else 
				{
					$this->returnFalse($this->__translator->getTranslation('passwordreset-error4'),'reload');	
				}
			}
			else 
			{
				# need to unset it so link will not work anymore
				unset($_SESSION['resetpass']);
				
				#  hash already used
				$this->__result['location'] = '/'. $_SESSION['language'] . '/passwordReset';
				
				$this->returnFalse($this->__translator->getTranslation('passwordreset-error3'),'reload');
			}
		}
	}
	
	/*
	* sets a cookie
	* @cookie: cookie name
	* @value: the value of the cookie
	* @time: optional - when should it expire. when omitted 100 days
	* @path: optional - on which path you want to set it. Default is root path
	*/
	private function setACookie($cookie, $value, $time = false,$path = '/') {
		(!$time) ? ($time = time()+(100 * 24 * 60 * 60)):false;
		setcookie($cookie, $value, $time,$path);
	}
	
	/*
	* Unsets a cookie
	* @cookie: cookie name
	* @path: optional - on which path you want to set it. Default is root path
	*/
	private function unsetACookie($cookie, $path = '/') {
		setcookie($cookie, "", mktime(12,0,0,1, 1, 1971),$path);  
	}
	
	
	private function returnSuccess($message,$title,$action=false) {
		$this->__result['title'] = $title;
		$this->__result['func'] = 'loadMessage';
		$this->__result['action'] = $action;
		$this->__result['message'] = $message;
		echo json_encode($this->__result);	
	}
	
	private function returnFalse($message,$action=false) {
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
$ajaxcall = new login($action,$params);


?>