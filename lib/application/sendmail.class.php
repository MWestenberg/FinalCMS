<?php

# name: sendMail Class
# desc: Send emails using phpMailer class
# create date: 2010-10-14	
# author: Mark Westenberg	
# company: Final Media
# public functions:
#	public function setSender($email,$name="noname");
#	public function setRecipient($email, $name="noname");
#	public function addCC ($email,$name="nonename");
#	public function addBCC ($email,$name="nonename");
#	public function setSubject($subject);
#	public function setBodyTemplate($file);
#	public function setAltBodyTemplate($file);
#	public function setHTMLBody($body);
#	public function setTextBody($body);
#	public function send();

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpmailer.class.php');

class sendMail {
	
	protected $mail;
	protected $subject = "";
	protected $HTMLbody = false;
	protected $TextBody = false;
	protected $bodyTpl = false;
	protected $bodyAltTpl = false;
	private $__Exception;
	protected $_mailtpl = false;
	
	# initiates the phpMailer class and sets the host,username, pass and port
	public function __construct() {
		$this->mail = new PHPMailer();
		//$this->mail->SetLanguage("nl");
		$this->mail->IsSMTP();  // telling the class to use SMTP
		$this->mail->Mailer = "smtp";
		$this->mail->Host = "localhost";
		$this->mail->Port = 25;
		$this->mail->SMTPAuth = false; // turn on SMTP authentication		
		//$this->mail->Username = "ahgn";
		//$this->mail->Password = "V06bM03K";
		$this->mail->SMTPDebug  = false; // enables SMTP debug information (for testing)
		$this->_mailtpl = CWD . TPL_DIR . DIRECTORY_SEPARATOR . MAIL_TPL . DIRECTORY_SEPARATOR;
	}
	
	# sets the sender optional name
	public function setSender($email,$name="Enforcement BV") {
		$this->mail->From = $email;
		$this->mail->FromName = $name;
		$this->mail->AddReplyTo($email,$name);
	}
	
	
	# sets the recipient optional name
	public function setRecipient($email, $name="noname") {
		$this->mail->AddAddress($email,$name);
	}
	
	# sets CC recipient optional name
	public function addCC ($email,$name="nonename") {
		$this->mail->AddCC($email,$name);
	}
		
	# sets BCC recipient optional name
	public function addBCC ($email,$name="nonename") {
		$this->mail->AddBCC($email,$name);
	}
	
	# set the mail subject
	public function setSubject($subject) {
		$this->subject = $subject;
	}
	
	# set a HTML body template
	public function setBodyTemplate($file) {
		if (file_exists($this->_mailtpl . $file))
		{
			$this->bodyTpl = file_get_contents($this->_mailtpl . $file);
			$this->setImages();
			return true;
		}
		else
		{
			return false;
		}
	}
	
	# set altnerative text only template
	public function setAltBodyTemplate($file) {
		if (file_exists($this->_mailtpl . $file))
		{
			$this->bodyAltTpl = file_get_contents($this->_mailtpl . $file);
			return true;
		}
		else
		{
			return false;
		}
	}
	
	# sets HTML body. 
	# $body should be an array. each value in body is a param in the template (param1, param2 etc).
	public function setHTMLBody($body,$stripEntities=true) {
		if ($this->bodyTpl && is_array($body) && count($body) >0 )
		{
			$this->HTMLbody = $this->bodyTpl;
			foreach ($body as $key => $value)
			{	
				if ($stripEntities)
				{
					$this->HTMLbody = str_ireplace("<".$key.">", htmlentities($value, ENT_QUOTES,'UTF-8'), $this->HTMLbody);			
				}
				else 
				{
					$this->HTMLbody = str_ireplace("<".$key.">", $value, $this->HTMLbody);
				}
			}
		}
		else
		{
			$this->HTMLbody = htmlentities($body, ENT_QUOTES,'UTF-8'); # no template so ignore it.
		}
		
	}
	
	# sets a text only body. 
	# $body should be an array. each value in body is a param in the template (param1, param2 etc).
	public function setTextBody($body) {
		if ($this->bodyAltTpl && is_array($body) && count($body) >0 )
		{
			$this->TextBody =  $this->bodyAltTpl;
			
			foreach ($body as $key => $value)
			{
				$this->TextBody = str_ireplace("<".$key.">", strip_tags($value), $this->TextBody);
			}
		}
		else
		{
			$this->TextBody = strip_tags($body); # no template so ignore it
		}
		
	}
	
	#sets images (must be located in the subdir images in the mail templater dir
	private function setImages() {
		
		if ($this->bodyTpl)
		{
			preg_match_all("/<img(.*)src=\"(.*?)\"(.*)>/i", $this->bodyTpl, $imagetag, PREG_SET_ORDER);
			foreach ($imagetag as $tag)
			{
				$src = strip_tags($tag[2]); # get the image file
				$src = end(explode(":", $src)); # remove everything but the file
				$cid = rand(1,10);
				//$this->mail->AddAttachment('images/'.$src);
				$this->mail->AddEmbeddedImage($this->_mailtpl . '/images/' .$src, $cid, $src);
				$newimgtag = str_ireplace($tag[2],'cid:'.$cid, $tag[0]);
				$this->bodyTpl  = str_ireplace($tag[0], $newimgtag, $this->bodyTpl);	
			}
			
		}

	}
	
	
	#add some attachements
	public function AddAttachment($path, $name = '')
	{
		$this->mail->AddAttachment($path, $name);
	
	}
	
	# send the mail
	public function send() {
		try {
			
			if ($this->HTMLbody) # we have an html body
			{
				$this->mail->IsHTML(true);
				$this->mail->Body = $this->HTMLbody;
				if ($this->TextBody) # we also have a textbody
				{
					$this->mail->AltBody = $this->TextBody; 
				}
			}
			else if ($this->TextBody) # we only have a textbody
			{
				$this->mail->IsHTML(false);
				$this->mail->Body = $this->TextBody;
			}
			else
			{
				$this->__Exception = "Please set a body first using setHTMLBody() or setTextBody()";
				return false; # we have no body
			}
				
			(empty($this->mail->From)) ? ($this->mail->From = APPMAIL):false;
			(empty($this->mail->From)) ? ($this->mail->FromName = APPNAME):false;
			
			$this->mail->Subject = $this->subject; 
			
			
			//mailboxinstellingen
			//$CCname = $this->getConfig("CCname");
			//$CCaddress = $this->getConfig("CCaddress");
			
			if (!empty($CCaddress))
			{				
				$this->mail->AddCC($CCaddress,$CCname);
			}
			
			//$BCCname = $this->getConfig("BCCname");
			//$BCCaddress = $this->getConfig("BCCaddress");
			
			if (!empty($BCCaddress))
			{	
				$this->mail->AddBCC($BCCaddress,$BCCname);
			}
			
			if ($this->mail->Send())
			{
				return true;
			} 
			else 
			{
				$this->__Exception = $this->mail->ErrorInfo;
				return false;
			}
			
		} 
		catch (phpmailerException $e) 
		{
			$this->__Exception =  $e->errorMessage(); //Pretty error messages from PHPMailer
			return false;
		} 
		catch (Exception $e) 
		{
			$this->__Exception = $e->getMessage(); //Boring error messages from anything else!
			return false;
		}
	}
	
	#gets a specific config parameter from the database
	private function getConfig($configname) {
		$j = new db_query;
		$query = sprintf("SELECT value FROM config WHERE name='%s'",$configname);
		$result = $j->getResult($query);
		if ($j->checkResult()) {
			return $result[0]['value'];
		}
		else 
		{
			return "";
		}
	}
	
	
	public function getException() {
		return $this->__Exception;
	}
}


/*
$mail = new sendMail;
$mail->setSender($email,$name="noname");
$mail->setRecipient($email, $name="noname");
$mail->setSubject($subject);
$mail->setBody($body);
if ($mail->send()) 
{
	print "verstuurd!";
}
else
{
	$mail->getException();
}
*/