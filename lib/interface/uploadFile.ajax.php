<?php


class uploadDocuments extends controller {
	
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
	* general dialog Loader
	*/
	private function loadDialogWindow($path,$params) {
		$this->__result['dialog'] = SECUREPATH .'/'.$path.'/uploadDialog';
		$this->__result['formID'] = '#UploadForm';
		$this->__result['func'] = 'loadForm';
		$this->__result['target'] = '#dialogWindow';
		$this->__result['formData'] = array("0" => array("currentDir" => $params['id'],"page" => $path));
		echo json_encode($this->__result);
	}
	
	
	/*
	* load upload  Dialog
	*/
	private function uploadfile($params) {
		$this->loadDialogWindow(current(explode("/",$params['id'])),$params);
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
$ajaxcall = new uploadDocuments($action,$params);


?>