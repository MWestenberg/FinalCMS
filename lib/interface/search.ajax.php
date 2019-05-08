<?php 
class search extends controller {
	
	private $__result,$__translator;
	
	
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
			//$this->returnFalse($this->__translator->getTranslation('dirnotfound'));
		}
	}
	
	private function searchForm($params) {
		
		
		$this->__result['func'] = 'refresh';
		$this->__result['location'] = SECUREPATH . DIRECTORY_SEPARATOR .$params['rel'].'/?search='.$params['search-key'];
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
$ajaxcall = new search($action,$params);

?>