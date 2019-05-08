<?php

class downloads extends controller {
	
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
			$this->returnMessage($this->__translator->getTranslation('filenotfound'));
		}
	}
	
	private function getSite() {
		$query = sprintf("SELECT fk_customer FROM site WHERE domain='%s'",BASEURL);
		if ($d = $this->queryDB($query))
		{
			return $d[0]['fk_customer'];
		}
		else 
		{
			return false;	
		}
		
	}
	
	private function downloadFile($params) {
		
		
		$baseDir = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/'.$params['subpage'].'/';
		
		if (is_dir($baseDir))
		{
			//select it from the database
			switch($params['subpage'])
			{
				case 'report-previews': $query=sprintf("SELECT report_title as title FROM report WHERE preview_file='%s' AND fk_customer=%s",$params['getparam1'],$this->getSite()); break;
				case 'event-brochures':$query=sprintf("SELECT event_title as title FROM event WHERE brochure='%s' AND fk_customer=%s",$params['getparam1'],$this->getSite()); break;
				default: $this->returnMessage($this->__translator->getTranslation('filenotfound'));break;
			}
			
			if ($r = $this->queryDB($query))
			{
				//ok we found it now display
				if (file_exists($baseDir . $params['getparam1']))
				{
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
					header("Pragma: public");
					header('Content-Type: application/pdf'); 
					header('Content-Disposition: inline; filename='.$r[0]['title']); 
					readfile($baseDir . $params['getparam1']);
					
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('filenotfound'));	
				}
			
			}
			else 
			{
				$this->returnMessage($this->__translator->getTranslation('filenotfound'));	
			}
			
			
		}
		else 
		{
			$this->returnMessage($this->__translator->getTranslation('filenotfound'));	
		}
		
	
	}
	
	
	private function returnMessage($message,$action=false) {
		header("Location: http://".$_SERVER['SERVER_NAME']);
		/*
		$this->__result['func'] = 'loadMessage';
		$this->__result['action'] = $action;
		$this->__result['message'] = $message;
		echo json_encode($this->__result);
		exit;
		*/
	}

}

$params = array();

if (!isset($_GET['subpage']) || !isset($_GET['getparam1']))
{
	header("Location: http://".$_SERVER['SERVER_NAME']);
}
foreach($_GET as $key => $value)
{
	$params[$key] = htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8');
}
$ajaxcall = new downloads('downloadFile',$params);

?>
