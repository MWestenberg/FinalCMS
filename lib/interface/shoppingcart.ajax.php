<?php 
require_once(APPLICATION . 'php.captcha.inc.php');
require_once(APPLICATION . 'sendmail.class.php');
require_once(APPLICATION . 'creditcard.class.php');

class shoppingcart extends controller {
	
	private $__result,$__translator,$__auth,$_ccError=false;
	
	/* main constructor
	**************/
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
			header("Location: http://".BASEURL);
		}
	}
	

	/* check if cart is filled
	**************/
	private function checkCart() {
		
		if (isset($_SESSION['shoppingcart']) && is_array($_SESSION['shoppingcart']) && count($_SESSION['shoppingcart'])>0)
		{
			$this->__result['func'] = 'showCart';
			$this->__result['valid'] = 'ok';
			echo json_encode($this->__result); 
		}
		else {
			$this->__result['func'] = 'showCart';
			$this->__result['valid'] = 'nok';
			echo json_encode($this->__result);
		}
		
		
	}
	
	
	/* create record in pay_details
	**************/
	private function payDetails($params) {
		
		//store debetnumber and city 
		// credicardnumber, cardholdername, cvc-code, valid-thru, cardtype, fk_user
		
		if ($params['paymentmethod']=='bank')
		{
			return 'bank'; //we do nothing
		}
		else if (empty($params['creditcardnumber']) || empty($params['cardholdername']) || empty($params['cardholderaddress']) || empty($params['cvccode']) || empty($params['validthru']) || empty($params['cardtype'])) 
		{
			//check alle verplichte velden
			$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
			exit;
		}
		
		else 
		{	
			
			$date = explode("/",$params['validthru']);
			if (isset($date[1]) && strlen($date[1])>2)
			{
				$date[1] = substr($date[1], 2);
			}
			$realDate = '20'.$date[1].'-'.$date[0].'-01';
			
			if (strtotime($realDate) < time())
			{			
				$this->_ccError = 'invalid-ccdate';
				return false;
			}
			
			$CCV = new CreditCardValidator();
			
			if ($CCV->Validate($params['creditcardnumber']))
			{
				//insert the paydetails
				//init for storage
	
							
				$cipher = new CreditCardFreezer(array(
				    CreditCardFreezer::NUMBER       => $params['creditcardnumber'],
				    CreditCardFreezer::EXPIRE_MONTH => $date[0],
				    CreditCardFreezer::EXPIRE_YEAR  => $date[1]
				));
				//set pass key
				$cipher->setPassKey(SALT);
				//retrieve for storage
				//$params['creditcardnumber'] = $cipher->get('secure_store');
				
				$params['creditcardnumber'] = '';
				$params['cvccode'] = '';
				
				$queryAdd = $this->buildQuery("pay_details",$params,true);
				$query = sprintf("INSERT INTO pay_details %s",$queryAdd);
							
				if ($payId = $this->insertRow($query))
				{
					return $payId;
				}
				else 
				{
					return false;	
				}
			}
			else 
			{
				$this->_ccError = 'invalid-creditcard';
				return false;
			}
			
		}
				
	}
	
	/* create an order in table order
	**************/
	private function createOrder($params,$payId,$userid=false) {
		
		if (!isset($payId))
		{
			return false;
		}
		else 
		{
			$options = '';
			$regfee = 0;
			if (isset($params['eventID']))
			{
				$amount = $this->calculateEventPrice($params,'totalPrice');
				$cartData = $this->getShoppingCartContent();
				$regfee = $this->calculateEventPrice($params,'regFee');
				foreach ($cartData as $key => $value) 
				{
					if (isset($value['event_title']))
					{
						$options = $value['optionalFee'];
					}
				}
				
			}
			else 
			{
				$amount = $this->getShoppingCartContent(true,$params['currency']);	
				
			}
			
			(empty($amount)) ? ($amount = 0):false;
			$query = sprintf("INSERT INTO shop_order (amount, currency, create_date, mod_date, remark, regfee, options, fk_pay_details, fk_shop_user) VALUES(%s,'%s',NOW(),NOW(),'%s', %s,'%s', %s,%s)",$amount,$params['currency'],$params['remark'],$regfee, $options,$payId,$userid);
			
			
			if ($orderId = $this->insertRow($query))
			{
				return $orderId;
			}
			else 
			{
				return false;	
			}
			
				
		}
		
		
		//store amount, pay_details, fk_user,fk_company
		return true;
		//$this->returnMessage('createOrder: '.$query);
	}
	

	/* create order items in order_item
	**************/
	private function orderItems($orderId,$eventOnly=false) {
		//store id, fk_report, fk_event, fk_order
		
		if ($items = $this->getShoppingCartContent())
		{
			foreach ($items as $value) 
			{
				
				if ($eventOnly)
				{
					//only get events
					if (isset($value['event_title']))
					{
					
						$reportID = 0;
						$eventID= $value['id'];				
						$query = sprintf("INSERT INTO `order_item` SET fk_report=%s,fk_event=%s,fk_order=%s",$reportID,$eventID,$orderId);
						if (!$this->insertRow($query))
						{
							$this->returnMessage($query);
							return false;
						}
						
					}
					
				}
				else if (isset($value['report_title']))
				{
					$reportID = $value['id'];
					$eventID= 0;
					
					$query = sprintf("INSERT INTO `order_item` SET fk_report=%s,fk_event=%s,fk_order=%s",$reportID,$eventID,$orderId);
					if (!$this->insertRow($query))
					{
						$this->returnMessage($query);
						return false;
					}
						
				}
				
			}
			return true;		
		}
		else 
		{
			return false;	
		}
	}
	
	
	/* create shop user
	**************/
	private function createShopUser($params) {
		
		if (empty($params['firstname']) || empty($params['lastname']) || empty($params['jobtitle']) || empty($params['company']) || empty($params['address']) || empty($params['zipcode']) || empty($params['city']) || empty($params['country']) || empty($params['telephone']) || empty($params['email'])) 
		{
			//check alle verplichte velden
			$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
			exit;
		}
		else 
		{
			
			$queryAdd = $this->buildQuery("shop_user",$params,true);
			$query = sprintf("INSERT INTO shop_user %s",$queryAdd);
			if ($userid = $this->insertRow($query))
			{
				return $userid;
			}
			else 
			{
				return false;	
			}
			
			
				
		}
		
		
		
	}
	
	/* order a report
	**************/
	private function orderReport($params) {
	
		//check if authenticated
		//skip for now just create each user again and again for logging
		
		if ($userid = $this->createShopUser($params))
		{
			$params['fk_shop_user'] = $userid;
			//create pay_details
			//when user already exits and pay details are the same leave them
			//for now just create them and leave fk_user empty
			
			
			if ($payId = $this->payDetails($params))
			{
				($payId=='bank') ? ($payId=0) : false;
				//create order
				if ($orderId = $this->createOrder($params,$payId,$userid))
				{
					if ($this->orderItems($orderId))
					{
						//send an email
						$this->sendMail($params,$orderId,$payId,'report-order');
					}
					else 
					{
						//rollback?
						if ($this->rollBack("shop_order",$orderId) && $this->rollBack('pay_details',$payId) && $this->rollBack('shop_user',$userid))
						{
							$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
						}
						
						$this->returnMessage('error');	
					}
				}
				else 
				{
					//rollback pay
					if ($payId)
					{
						if ($this->rollBack('pay_details',$payId)  && $this->rollBack('shop_user',$userid))
						{
							$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
						}
					}	
				}
				
			}
			else 
			{
				
				if ($this->rollBack('shop_user',$userid))
				{
					if ($this->_ccError)
					{
						$this->returnMessage($this->__translator->getTranslation($this->_ccError));
					}
					else 
					{
						$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
					}
				}
			}
			
		}

	}
	
	
	/* send the email to AWA
	**************/
	private function createMailContent($params,$type) {
		
		
		$maildata = array();
		if ($type =='report-order')
		{
		
			
			//shopping data
			$cartData = $this->getShoppingCartContent();
			$cartTable = '';
			
			$totalprice  = 0;
			foreach ($cartData as $key => $value) 
			{
				if (isset($value['report_title']))
				{
					if ($params['currency']=='EUR')
					{
						$cartTable .= '<tr><td>'.$value['report_title'].'</td><td>&euro; '.number_format($value['price_euro'],2,",",".").'</td></tr>';
						$totalprice += $value['price_euro'];
					}
					else 
					{
						$cartTable .= '<tr><td>'.$value['report_title'].'</td><td>$ '.number_format($value['price'],2,".",",").'</td></tr>';
						$totalprice += $value['price'];	
					}
				
						
				}
			
			}
			
			$maildata['cartData'] = $cartTable;
			
			foreach ($params as $key => $value) 
			{
				$maildata[$key] =  $value;
			}
			
			if ($params['currency']=='EUR')
			{
				$maildata['totalprice'] = '&euro; '.number_format($totalprice,2,",",".");
			}
			else 
			{
				$maildata['totalprice'] = '$ '.number_format($totalprice,2,".",",");
			}	
		}
		else 
		{
			//conference & events	
			$cartData = $this->getShoppingCartContent();
			$cartTable = '';
			foreach ($cartData as $key => $value) 
			{
				if (isset($value['event_title']))
				{
					if ($params['currency']=='EUR')
					{
						$_SESSION['shoppingcart'][$key]['currency'] = '&euro;';
					}
					else 
					{
						$_SESSION['shoppingcart'][$key]['currency'] = '$';
					}
					
					//check for fees
					foreach ($params as $key2 => $value2) 
					{
						if (strpos($key2, 'paymentfee')!==false && isset($value['fees']))
						{
							//we have fees selected and we have fees in our database so now match them
							$feesArr = json_decode($value['fees'],true);
							
							if (isset($feesArr) && isset($feesArr[$value2]))
							{
								if (!isset($maildata['paymentfee']))
								{
									if ($params['currency']=='EUR')
									{
										$maildata['paymentfee'] = $value2. ' &euro;'.number_format($feesArr[$value2],2,",",".");
									}
									else 
									{
										$maildata['paymentfee'] = $value2. ' $ '.number_format($feesArr[$value2],2,".",",");
									}
									
								}
								else 
								{
									if ($params['currency']=='EUR')
									{
										$maildata['paymentfee'] .= '; '.$value2. ' &euro;'.number_format($feesArr[$value2],2,",",".");
									}
									else 
									{
										$maildata['paymentfee'] .= '; '.$value2. ' $ '.number_format($feesArr[$value2],2,".",",");
									}	
								}
								
							}
												
						}
						
						if (strpos($key2, 'regfee')!==false && isset($value['regfee']))
						{
							//we have fees selected and we have fees in our database so now match them
							$regFeeArr = json_decode($value['regfee'],true);
							
							if (isset($regFeeArr) && isset($regFeeArr[$value2]))
							{
								(!isset($maildata['regfee'])) ? ($maildata['regfee'] = ''):false;
								
								if ($params['currency']=='EUR')
								{
									$maildata['regfee'] .= $value2. ' &euro;'.number_format($regFeeArr[$value2],2,",",".");
								}
								else 
								{
									$maildata['regfee'] .= $value2. ' $ '.number_format($regFeeArr[$value2],2,".",",");
								}	

								
							}
												
						}
											
					}
	
					$cartTable .= '<strong>'.$value['event_title'].'</strong>';
					
				}
			}
			
			$maildata['cartData'] = $cartTable;
			
			foreach ($params as $key => $value) 
			{
				if (strpos($key, 'paymentfee')===false && strpos($key, 'regfee')===false)
				{
					$maildata[$key] =  $value;	
				}
			}
			
			
			//get total price for event
			$totalprice = $this->calculateEventPrice($params,'totalPrice');
			
			
			if ($params['currency']=='EUR')
			{
				$maildata['totalprice'] = '&euro; '.number_format($totalprice,2,",",".");
			}
			else 
			{
				$maildata['totalprice'] = '$ '.number_format($totalprice,2,".",",");
			}
		}
		
		return $maildata;
		
	
	}
	

	private function calculateEventPrice($params,$response = 'totalPrice') {
	
		$cartData = $this->getShoppingCartContent();
		$additionalFeeTotal  = 0;
		$regFeeTotal = 0;
		$totalPrice = 0;
		$additionalFee = array();
		
		foreach ($cartData as $key => $value) 
		{
			if (isset($value['event_title']))
			{
				
				
				foreach ($params as $key2 => $value2) 
				{
					//registration fee
					if (strpos($key2, 'regfee')!==false && isset($value['regfee']))
					{
						//we have fees selected and we have fees in our database so now match them
						$regFeeArr = json_decode($value['regfee'],true);
						
						if (isset($regFeeArr) && isset($regFeeArr[$value2]))
						{
							//found the fee lets update the total price with its value
							$regFeeTotal += $regFeeArr[$value2];
							
							$totalPrice += $regFeeArr[$value2];
						}
					}
					
					
					
					if (strpos($key2, 'paymentfee')!==false && isset($value['fees']))
					{
						//we have fees selected and we have fees in our database so now match them
						$feesArr = json_decode($value['fees'],true);
						
						if (isset($feesArr) && isset($feesArr[$value2]))
						{
							//found the fee lets update the total price with its value
							$additionalFeeTotal += $feesArr[$value2];
							$totalPrice += $feesArr[$value2];
							
							//add this new fee to the shopping cart for later use
							$additionalFee[$value2] = $feesArr[$value2];
						}
					}
				}
				//add the amount to the shopping cart (only one choice)
				$_SESSION['shoppingcart'][$key]['amount'] = $regFeeTotal; 
				//add the optionalFees array to the shopping cart (multiple choices)
				$_SESSION['shoppingcart'][$key]['optionalFee'] = json_encode($additionalFee);
					
			}
		}
		
		
		
		switch ($response) 
		{
			case 'additionalFee': return $additionalFeeTotal;break;
			case 'regFee': return $regFeeTotal;break;
			default: return $totalPrice;break;
			
		}
	
		
	}
	
	/* send the email to AWA
	**************/
	private function sendMail($params,$orderId,$payId,$type) {
		
		//need to add these to the database
		//reports@awa-bv.com
		//conferences@awa-bv.com
		
		//send password reset mail
		
		if (!$recipient = $this->getRecipient($type))
		{
			$this->returnMessage($this->__translator->getTranslation('mail-error'));
			return false;
		}
		
		
		$mailAWA = new sendMail;
		$mailAWA->setSender(APPMAIL,APPNAME);
		$mailAWA->setRecipient($recipient,'');
		
		
		if ($CCAddress = $this->getRecipient('CCaddress'))
		{
			if (!$CCName = $this->getRecipient('CCname'))
			{
				$CCName = '';
			}
			$mailAWA->addCC($CCAddress,$CCName); 
		}
		
		
		
		if ($type=='report-order')
		{
			$signature = 'Alexander Watson Associates';
		}
		else 
		{
			$signature = 'The AWA Conferences & Events Team';
		}
		
		//id van country naar naam omzetten
		if (isset($params['country']) && !empty($params['country']))
		{
			$query =sprintf("SELECT * FROM country WHERE id = '%s'",$value['country']);
			if ($countries = $this->queryDB($query))
			{
				$params['country'] = $countries[0]['name'];
			}
		}
		
		
		$mailAWA->setSubject($this->__translator->getTranslation('mail-'.$type));
		$mailAWA->setBodyTemplate($type.'.html');
		$paramReplace = $this->createMailContent($params,$type);
		$paramReplace["recipient"] = "AWA employee";
		$paramReplace["site"] = APPNAME;
		$paramReplace["signature"] = $signature;
		$mailAWA->setHTMLBody($paramReplace,false);


		$mailClient = new sendMail;
		$mailClient->setSender(APPMAIL,APPNAME);
		$mailClient->setRecipient($params['email'],$params['firstname'].' '.$params['lastname']);
		$mailClient->setSubject($this->__translator->getTranslation('mail-'.$type));
		$mailClient->setBodyTemplate($type.'-thanks.html');
		$paramReplace = $this->createMailContent($params,$type);
		$paramReplace["recipient"] = $params['firstname'].' '.$params['lastname'];
		$paramReplace["signature"] = $signature;
		$mailClient->setHTMLBody($paramReplace,false);
		
		
		if ($type=='event-registration')
		{
			$attachment = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/event-brochures/'.$_SESSION['shoppingcart'][0]['brochure'];
			if (file_exists($attachment))
			{
				$mailClient->AddAttachment($attachment);
			}
		
		}
		else 
		{
			foreach ($_SESSION['shoppingcart'] as $value) 
			{
				if (isset($value['report_title']))
				{
					$attachment = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/report-previews/'.$value['preview_file'];
					if (file_exists($attachment))
					{
						$mailClient->AddAttachment($attachment);
					}				
				}
			}
		
		}
		
		
		if ($mailAWA->send() && $mailClient->send()) 
		{
			if ($type=='report-order')
			{
				
				$this->__result['location'] = '/reports/thankyou';
				$this->returnMessage($this->__translator->getTranslation('order-success'));
			}
			else 
			{
				$this->__result['location'] = '/events/thankyou';
				$this->returnMessage($this->__translator->getTranslation('registration-success'));	
			}
		}
		else
		{
			$this->returnFalse($this->__translator->getTranslation('mail-error').'<br />'.serialize($mail->getException()));
		}
		

	}
	
	private function rollBack($table,$id) {
	
		$query = sprintf("DELETE FROM %s WHERE id=%s",$table,$id);
		if ($this->deleteRow($query))
		{
			return true;
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
		}
		
	}
	
	/* join event
	**************/
	private function registerEvent($params) {
		
		if ($userid = $this->createShopUser($params))
		{
			$params['fk_shop_user'] = $userid;
			//create pay_details
			//when user already exits and pay details are the same leave them
			//for now just create them and leave fk_user empty
			
			
			if ($payId = $this->payDetails($params))
			{
				($payId=='bank') ? ($payId=0) : false;
				//create order
								
				$query = sprintf("SELECT * FROM event WHERE md5_id='%s'",$params['eventID']);
				
				if ($result = $this->queryDB($query))
				{
					$_SESSION['shoppingcart']  = $result;
					
					if ($orderId = $this->createOrder($params,$payId,$userid))
					{
					
						if ($this->orderItems($orderId,true))
						{
							//send an email
							$this->sendMail($params,$orderId,$payId,'event-registration');
						}
						else 
						{
							//rollback?
							if ($this->rollBack("shop_order",$orderId) && $this->rollBack('pay_details',$payId) && $this->rollBack('shop_user',$userid))
							{
								$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
							}
							
							$this->returnMessage('error');	
						}
					
					}
					else 
					{
						//rollback pay
						if ($payId)
						{
							if ($this->rollBack('pay_details',$payId)  && $this->rollBack('shop_user',$userid))
							{
								$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
							}
						}	
					}
					
					
				}
				else 
				{
					
					$this->returnMessage($this->__translator->getTranslation('database-error'));	
				}	
				
			}
			else 
			{
				
				if ($this->rollBack('shop_user',$userid))
				{
					if ($this->_ccError)
					{
						$this->returnMessage($this->__translator->getTranslation($this->_ccError));
					}
					else 
					{
						$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
					}
				}
			}
			
		}
	}
	
	private function getRecipient($type) {
		
		$query = sprintf("SELECT c.value FROM config c LEFT JOIN site s ON c.fk_site = s.id	WHERE s.domain='%s' AND c.name='%s'",BASEURL,$type);
		if ($r = $this->queryDB($query))
		{
			return $r[0]['value'];		
		}
		else 
		{
			return false;
		}
	}
	
	/* get shoppingcart contents
	******************/
	private function getShoppingCartContent($total=false,$currency='USD') {
		
		if (isset($_SESSION['shoppingcart']))
		{
		
			if (!$total)
			{	
				return $_SESSION['shoppingcart'];		
			}
			else 
			{
				$totalPrice = 0;
				foreach ($_SESSION['shoppingcart'] as $key => $value) 
				{
					if ($currency=='EUR')
					{
						$totalPrice += $value['price_euro'];
					}
					else 
					{
						$totalPrice += $value['price'];	
					}
					
				}
				return $totalPrice;	
			}
		}
		else {
			return false;
		}
	
	}
	
	/* add report to cart
	**************/
	private function addToCart($params) {
		
		if (!isset($_SESSION['shoppingcart']))
		{
			$_SESSION['shoppingcart'] = array();
		}
		
		$query = sprintf("SELECT * FROM report WHERE md5_id='%s'",$params['reportID']);
		
		if ($report = $this->queryDB($query))
		{
			$added = false;
			foreach ($_SESSION['shoppingcart'] as $key => $value) 
			{
				if ($value['md5_id'] == $params['reportID'])
				{
					/* amounts are disabled
					if (!isset($_SESSION['shoppingcart'][$key]['amount'])) 
					{
						$_SESSION['shoppingcart'][$key]['price'] = ($report[0]['price'] *2);
						$_SESSION['shoppingcart'][$key]['amount'] = 2;
					}
					else 
					{
						$_SESSION['shoppingcart'][$key]['amount'] +=1;
						$_SESSION['shoppingcart'][$key]['price'] = ($report[0]['price'] * $_SESSION['shoppingcart'][$key]['amount']);
					}
					*/
					$added = true;
					
				}
			}
			
			(!$added) ? ($_SESSION['shoppingcart'] = array_merge($_SESSION['shoppingcart'], $report)):false;
		}
		
		$this->loadPage('reportDialog',$report);
	
	}
	
	private function emptyShoppingCart() {
	
		foreach ($_SESSION['shoppingcart'] as $key => $value) 
		{
			unset($_SESSION['shoppingcart'][$key]);	
		}
	
	}
	
	
	/* remove report from cart
	**************/
	private function removeFromCart($params) {
		
		if (!isset($_SESSION['shoppingcart']))
		{
			$_SESSION['shoppingcart'] = array();
		}
		
		$totalPrice = 0;
		
		foreach ($_SESSION['shoppingcart'] as $key => $value) 
		{
		
			$totalPrice += $value['price'];
			if ($value['md5_id'] == $params['reportID'])
			{
				unset($_SESSION['shoppingcart'][$key]);
				$totalPrice -= $value['price'];
				
			}
		}
		
		$this->__result['func'] = 'refreshShoppingList';
		$this->__result['formData'] = $params['reportID'];
		$this->__result['totalprice'] = '$ '.number_format($totalPrice,"2",".",",");
		echo json_encode($this->__result);
	
	}
	
	/* update currency
	**************/
	private function updateCurrency($params) {
		
		$totalPrice = 0;
		
		
		$_SESSION['shoppingcart']['currency'] = $params['currency'];
		
		foreach ($_SESSION['shoppingcart'] as $key => $value) 
		{
			
			if ($params['currency']=='EUR')
			{
				$totalPrice += $value['price_euro'];
			}
			else 
			{
				$totalPrice += $value['price'];	
			}
			
		}
		
		
		$this->__result['func'] = 'refreshShoppingList';
		$this->__result['formData'] = $params['reportID'];
		if ($params['currency']=='EUR')
		{
			$this->__result['totalprice'] = '&euro; '.number_format($totalPrice,"2",",",".");
		}
		else 
		{
			$this->__result['totalprice'] = '$ '.number_format($totalPrice,"2",".",",");	
		}
		echo json_encode($this->__result);
	}
	
	
	private function buildQuery($table,$params,$insert=false,$skipMore=false) {
		
		//get columns from table
		$columns = $this->showColumns($table);
		
	
		
		
		$skipFields = array("id","password","fk_customer");
		if ($skipMore)
		{
			foreach ($skipMore as $key => $value) 
			{
				array_push($skipFields,$value);	
			}
			
		}
		
		//create query
		$queryAdd = '';
		$fields = '';
		$values = '';
		
		
		
		
		foreach ($columns as $columnKey => $columnVal) 
		{
			
			if (!in_array($columnVal['Field'], $skipFields))
			{
				//state is a checkbox with enum value
				if ($columnVal['Field']=='state')
				{
					
					if (isset($params[$columnVal['Field']]))
					{
						if ($insert)
						{
							$fields .= $columnVal['Field'].', ';
							$values .= '1, ';
						}
						else 
						{
							$queryAdd .= $columnVal['Field']."=1,";	
						}
						
					}
					else 
					{
						if ($insert)
						{
							$fields .= $columnVal['Field'].', ';
							$values .= '0, ';
						}
						else 
						{
							$queryAdd .= $columnVal['Field']."=0,";	
						}
					}
				}
				else if ($columnVal['Field']=='publish_date' || $columnVal['Field']=='archive_date')
				{
					
					($columnVal['Field']=='publish_date' && empty($params[$columnVal['Field']]))? ($params[$columnVal['Field']]= date("Y-m-d H:i:s")):false;
					
					if ($columnVal['Field']=='archive_date' && empty($params[$columnVal['Field']]))
					{
						if ($insert)
						{
							$fields .= $columnVal['Field'].', ';
							$values .= "'0000-00-00 00:00:00', ";
						}
						else 
						{
							$queryAdd .= $columnVal['Field']."='0000-00-00 00:00:00',";
						}
					}
					else 
					{
						$params[$columnVal['Field']]  = str_replace("/", "-",$params[$columnVal['Field']]);
						if (!empty($params[$columnVal['Field']]))
						{
							
							if ($insert)
							{
								$fields .= $columnVal['Field'].', ';
								$values .= "'".date("Y-m-d H:i:s", strtotime($params[$columnVal['Field']]))."', ";
							}
							else 
							{
								$queryAdd .= $columnVal['Field']."='".date("Y-m-d H:i:s", strtotime($params[$columnVal['Field']]))."',";		
							}
						}	
					}
				}
				else if (isset($params[$columnVal['Field']]) && !in_array($columnVal['Field'], $skipFields))
				{
					if ($insert)
					{
						$fields .= $columnVal['Field'].', ';
						$values .= "'".$params[$columnVal['Field']]."', ";
					}
					else 
					{
						$queryAdd .= $columnVal['Field']."='".$params[$columnVal['Field']]."',";
					}
				}
			}
		}
		if ($insert)
		{
			//need to add customer id
			//$fields .= 'fk_customer'; $values .= $_SESSION['customerID'];
			
			$queryAdd = sprintf("(%s) VALUES(%s)",rtrim($fields,', '),rtrim($values,', '));		
		}
		return $queryAdd;
	
	}
	
	/*
	* general dialog Loader
	* 
	*/
	private function loadPage($page,$formdata=false) {
	
		$this->__result['dialog'] = '/reports/'.$page;
		$this->__result['func'] = 'loadForm';
		$this->__result['target'] = 'content';
			($formdata) ? (	$this->__result['formData'] = $formdata):false;
		echo json_encode($this->__result);
	}
	
	private function returnMessage($message,$action=false) {
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
		(!is_array($value)) ? ($params[$key] = htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8')): ($params[$key] = $value);
	}
}
$ajaxcall = new shoppingcart($action,$params);

?>