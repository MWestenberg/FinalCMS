<?php 

class reportManager extends controller {
	
	private $__result,$__translator,$__auth;
	
	
	public function __construct($method=false,$params=false) {
		
		$this->__translator = new Translator();
		
		if (method_exists(__CLASS__, $method))
		{
			parent::__construct();
			$this->$method($params);
			
		}
		else
		{
			header("Location: http://".BASEURL);
			//$this->returnMessage('dirnotfound');
		}
	}
	
	
	private function getReport($params) {
		
		if (empty($params['id']))
		{
			$this->returnMessage($this->__translator->getTranslation('empty-reportlist'));
			exit;
		}
		
		$query = sprintf("SELECT * FROM report WHERE md5_id='%s' AND fk_customer=%s",$params['id'],$_SESSION['customerID']);
		if ($r = $this->queryDB($query))
		{
			($r[0]['publish_date']!='0000-00-00 00:00:00') ? ($r[0]['publish_date'] = strftime("%Y-%m-%d",strtotime($r[0]['publish_date']))):($r[0]['publish_date'] = '');
			
			//needed to show into fields
			foreach ($r[0] as $key => $value) 
			{
				($key == 'md5_id') ? ($r[0]['reportid'] = $value):false;
				$r[0][$key] = html_entity_decode($value,ENT_QUOTES,'UTF-8');	
			}
			
			//set formData
			$this->__result['formData'] = $r; 
		}
		else
		{
			$this->returnMessage($this->__translator->getTranslation('database-error'));
			exit;
		}
		
		$this->__result['dialog'] = SECUREPATH .'/reports/edit-report?reportID='.$r[0]['md5_id'];
		$this->__result['func'] = 'loadPage';
		$this->__result['target'] = '#main';
		$this->__result['title'] = 'Edit report';
		
		//$this->__result['action'] = $action;
		//($formdata) ? (	$this->__result['formData'] = $formdata):false;
		echo json_encode($this->__result);
	
	}
	
	
	/* generates a thumbnail file 
	*********/
	private function generatePDFThumb($filename) {
		
		
		$source = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/report-previews/'.$filename;
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
		
		$source = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/report-previews/'.$filename;
		
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
			$source = $_SERVER['DOCUMENT_ROOT'].'/files/web/files/report-previews/'.$params['file'];
			
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
			$this->__result['type'] = 'report-previews§';
			echo json_encode($this->__result);
			
			
		}
	}
	
	
	private function reportForm($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
				
		if (empty($params['report_title']) || empty($params['description']) || empty($params['keywords']) || empty($params['preview_file']) || empty($params['publish_date'])) 
		{
			//check alle verplichte velden
			$this->returnMessage($this->__translator->getTranslation('mandatory-fields'));
		}
		else 
		{
			
			
				
			if (isset($params['button']) && $params['button']=='create-report')
			{
				
				//insert
				$queryAdd = $this->buildQuery("report",$params,true);
				$query = sprintf("INSERT INTO report %s",$queryAdd);
				if ($insertID = $this->insertRow($query))
				{
					
					$query2 = sprintf("UPDATE report set md5_id='%s' WHERE id=%s AND fk_customer=%s",md5($insertID),$insertID,$_SESSION['customerID']);
					$this->updateRow($query2);
					
					$this->imageconverter($params['preview_file']);
					
					
					$this->__result['location'] = SECUREPATH .'/reports/report-management';
					$this->returnMessage($this->__translator->getTranslation('store-success'));
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}
			}
			else if (isset($params['button']) && $params['button']=='remove-report')
			{
				if ($this->deleteRow(sprintf("DELETE FROM report WHERE md5_id='%s' AND fk_customer=%s",$params['reportid'],$_SESSION['customerID'])))
				{	
					$this->__result['location'] = SECUREPATH .'/reports/report-management';
					$this->returnMessage(sprintf($this->__translator->getTranslation('delete-report'),'<b>'.$params['report_title'].'</b>'));
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}
			}
			else 
			{
				//update	
				$queryAdd = $this->buildQuery("report",$params);
				$query = sprintf("UPDATE report SET %s WHERE md5_id='%s' AND fk_customer=%s",rtrim($queryAdd,","),$params['reportid'],$_SESSION['customerID']);
				if ($this->updateRow($query))
				{
					$this->imageconverter($params['preview_file']);
					
					$this->__result['location'] = SECUREPATH .'/reports/report-management';
					$this->returnMessage($this->__translator->getTranslation('store-success'));
				}
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}
			}
			
			
		}
	
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
$ajaxcall = new reportManager($action,$params);

?>