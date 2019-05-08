<?php


class EventManager extends controller {
	
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
			//$this->getFeed($params);
			//illegale actie, iemand probeert op een andere manier hierbij te komen
			//redirect naar home
			//header("Location: http://".BASEURL);
			//$this->returnFalse('dirnotfound');
		}
	}
	
	private function eventResize($event) {
		
		$this->dropEvent($event);
	}
	
	private function moveEvent($event) {
		
		$this->dropEvent($event);
		
		//$result = array("result"=> 'valid',"event" => $event['event']['title']);
		//echo json_encode($result);
	}
	
	private function dropEvent($event) {
		
		if ($event['event']['allDay']=='true')
		{
			//geen tijdstip
			//$result = array("result"=> 'valid',"allDay" => $event['event']['start']);
			$start = date("Y-m-d H:i:s", strtotime($event['event']['start']));
			(isset($event['event']['end'])) ? ( $end = date("Y-m-d H:i:s",strtotime($event['event']['end']))) : ($end = '0000-00-00');
			
			$allDay = 1;
		}
		else 
		{
			//wel tijdstip	is default + 2 uur
			$start = date("Y-m-d H:i:s", strtotime($event['event']['start']));
			(isset($event['event']['end'])) ? ( $end = date("Y-m-d H:i:s",strtotime($event['event']['end']))) : ($end = date("Y-m-d H:i:s",(strtotime($event['event']['start']) + (60*60*2))));
			$allDay = 0;
		}
		
		
		//update event table
		$query = sprintf("UPDATE event SET start_datetime='%s', allDay=%s, stop_datetime='%s',planned=1 WHERE md5_id='%s'",$start,$allDay,$end,$event['event']['id']);
		if ($this->updateRow($query))
		{
			$result = array("result"=> 'valid',"event" => $query);
			echo json_encode($result);
		}
		
	
	}
	
	
	private function getFeed($params) {
		
		
		$query = sprintf("SELECT * FROM event WHERE fk_customer=%s AND planned=1",$_SESSION['customerID']);
		
		$feed = array();
		
		if ($r = $this->queryDB($query))
		{
			
			foreach ($r as $value)
			{
				$tArr = array(
					"id" => $value['md5_id'],
					"title" => html_entity_decode($value['event_title'],ENT_QUOTES,'UTF-8'),
					"start" => $value['start_datetime'],
					"end" => $value['stop_datetime']
					
				);
				
				if ($value['allDay']) 
				{ 
					$tArr['allDay'] = true;
				}
				else 
				{
					$tArr['allDay'] = false;
					
				}	
					 
				$feed[] = $tArr;
			}
		}
		
		echo json_encode($feed);
		
		
	
	}
	
	private function getEvent($params) {
		
		if (empty($params['id']))
		{
			$this->returnMessage($this->__translator->getTranslation('empty-event'));
			exit;
		}
		
	
		$query = sprintf("SELECT * FROM event WHERE md5_id='%s' AND fk_customer=%s",$params['id'],$_SESSION['customerID']);
		if ($r = $this->queryDB($query))
		{
						//needed to show into fields
			foreach ($r[0] as $key => $value) 
			{
				($key == 'md5_id') ? ($r[0]['eventid'] = $value):false;
				
				if ($key == 'fees')
				{
					$r[0][$key] = json_decode(html_entity_decode($value,ENT_QUOTES,'UTF-8'));				
				}
				else if ($key == 'regfee')
				{
					$r[0][$key] = json_decode(html_entity_decode($value,ENT_QUOTES,'UTF-8'));				
				}
				else 
				{
					$r[0][$key] = html_entity_decode($value,ENT_QUOTES,'UTF-8');	
				}
				
					
			}
			
			(isset($params['view'])) ? ($r[0]['view'] = $params['view']):false;
			
			//set formData
			$this->__result['formData'] = $r; 
		}
		else
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
			exit;
		}
		
		$this->__result['dialog'] = SECUREPATH .'/events/edit-event?eventID='.$r[0]['md5_id'];
		$this->__result['func'] = 'loadPage';
		$this->__result['target'] = '#main';
		$this->__result['title'] = 'Edit event';
		
		//$this->__result['action'] = $action;
		//($formdata) ? (	$this->__result['formData'] = $formdata):false;
		echo json_encode($this->__result);
	
	}
	
	/* generates a thumbnail file 
	*********/
	private function generatePDFThumb($filename) {
		
		
		$source = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/event-brochures/'.$filename;
		$width = 200;
		$ext = ".png";
		$dest = $_SERVER['DOCUMENT_ROOT']."/files/thumb/pdf/".$filename;
		
		if (!file_exists($dest."-0".$ext) && file_exists($source) && !file_exists($dest.$ext))
		{
			{
				$exec = "convert -scale $width ".$source."[0] $dest".$ext." > /dev/null 2>/dev/null &";
				$output = shell_exec($exec);
			}
		} 
	
	}
	
	/* generates a thumbnail file from pdf
	*********/
	private function imageconverter($filename) {
		
		$source = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/event-brochures/'.$filename;
		
		$dest = $_SERVER['DOCUMENT_ROOT'].'/files/thumb/pdf/'.$filename.'.png';
		
		if (!file_exists($dest) && file_exists($source))
		{
			
			$im = new Imagick();
			$im->setResolution(100,100);
			$im->readimage($source.'[0]'); 
			$im->setImageFormat('png');  
			$type=$im->getFormat();  
			$im->writeImage($dest); 
			$im->clear(); 
			$im->destroy();
			
		}
		return $dest;
		
	}
	
	
	private function refreshThumb($params) {
				
		if (isset($params['file']) && !empty($params['file']))
		{
			$source = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/event-brochures/'.$params['file'];
			
			$dest = $_SERVER['DOCUMENT_ROOT'].'/files/thumb/pdf/'.$params['file'].'.png';
			
			if (file_exists($source) && file_exists($dest))
			{
				unlink($dest);
			}
			else if (!file_exists($source))
			{
				$this->returnMessage($this->__translator->getTranslation('filenotfound'));
				exit;
			}
			//refresh the file
			$this->imageconverter($params['file']);
			
			
			$this->__result['result'] = 'ok';
			$this->__result['func'] = 'refreshThumbnail';
			$this->__result['thumb'] = $params['file'];
			$this->__result['type'] = 'event-brochures';
			echo json_encode($this->__result);
			
			
		}
	}
	
	private function eventForm($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
				
		if (isset($params['button']) && $params['button']=='create-event')
		{
			//create event
			
			if (empty($params['event_title']) || empty($params['description']) || empty($params['keywords']) || empty($params['brochure'])) 
			{
				//check alle verplichte velden
				$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
			}
			{
				//check reg fees and create json for storage
				$params['regfee'] = $this->createFeesAsJson($params,'reg');
				
				//check special fees and create json for storage
				$params['fees'] = $this->createFeesAsJson($params,'fee');
				
				//insert
				$queryAdd = $this->buildQuery("event",$params,true);
				$query = sprintf("INSERT INTO event %s",$queryAdd);
				if ($insertID = $this->insertRow($query))
				{
					
					$query2 = sprintf("UPDATE event set md5_id='%s',planned=0 WHERE id=%s AND fk_customer=%s",md5($insertID),$insertID,$_SESSION['customerID']);
					$this->updateRow($query2);
					
					$this->imageconverter($params['brochure']);
					
					$this->__result['location'] = SECUREPATH .'/events/event-calendar';
					$this->returnMessage($this->__translator->getTranslation('store-success'));
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}
			}
		}
		else if (isset($params['button']) && $params['button']=='remove-event')
		{
			//delete event 
			
			if ($this->deleteRow(sprintf("DELETE FROM event WHERE md5_id='%s' AND fk_customer=%s",$params['eventid'],$_SESSION['customerID'])))
			{	
				$this->__result['location'] = SECUREPATH .'/events/event-calendar';
				$this->returnMessage(sprintf($this->__translator->getTranslation('delete-event'),'<b>'.$params['event_title'].'</b>'));
			}
			else 
			{
				$this->returnMessage($this->__translator->getTranslation('database-error'));
			}
		}
		else 
		{
			//update event
			
			if (empty($params['event_title']) || empty($params['description']) || empty($params['keywords']) || empty($params['brochure'])) 
			{
				//check alle verplichte velden
				$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
			}
			{
				
				
				//check reg fees and create json for storage
				$params['regfee'] = $this->createFeesAsJson($params,'reg');
				
				//check special fees and create json for storage
				$params['fees'] = $this->createFeesAsJson($params,'fee');
				
				$queryAdd = $this->buildQuery("event",$params);
				$query = sprintf("UPDATE event SET %s WHERE md5_id='%s' AND fk_customer=%s",rtrim($queryAdd,","),$params['eventid'],$_SESSION['customerID']);
				if ($this->updateRow($query))
				{
					
					$query2 = sprintf("SELECT start_datetime FROM event WHERE md5_id='%s'",$params['eventid']);
					if ($e  = $this->queryDB($query2))
					{
						
						
						$this->imageconverter($params['brochure']);
						
						$this->__result['location'] = SECUREPATH .'/events/event-calendar?gotoDate='.date("Y",strtotime($e[0]['start_datetime'])).'-'.date("m",strtotime('first day of previous month',strtotime($e[0]['start_datetime']))).'-'.date("d",strtotime($e[0]['start_datetime'])).'&view='.$params['view'];
					}
					else 
					{
						$this->__result['location'] = SECUREPATH .'/events/event-calendar';	
					}	
					
					$this->returnMessage($this->__translator->getTranslation('store-success'));
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}
			}
		}
			
			
		
	
	}
	
	
	private function createFeesAsJson($params,$type) {
	
		$arr = array();
		
		//get keynames first
		foreach ($params as $key => $value) 
		{
			//loop through keys first
			if (strpos($key, $type.'key')!==false && !empty($value))
			{
				$valueKey = $type.'value'.substr($key, 6);
				$arr[$value] = $valueKey;
			}
				
		}
		
		//now get the values
		foreach ($arr as $key2 => $value2) 
		{
			//loop through keys first
			if (isset($params[$value2]))
			{
				$arr[$key2] = $params[$value2];			
			}
			else 
			{
				unset($arr[$key2]);	
			}
				
		}
		
		
		return json_encode($arr);

	
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
			$fields .= 'fk_customer'; $values .= $_SESSION['customerID'];
			$queryAdd = sprintf("(%s) VALUES(%s)",$fields,$values);		
		}
		return $queryAdd;
	
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
	//header("Location: http://".$_SERVER['HTTP_HOST']);
}

foreach($_POST as $key => $value)
{
	if ($key == 'action')
	{
		$action = htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8');
	}
	else if (is_array($value))
	{
		$params[$key]  = $value;
	}
	else
	{
		$params[$key] = htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8');
	}
}
$ajaxcall = new EventManager($action,$params);


?>