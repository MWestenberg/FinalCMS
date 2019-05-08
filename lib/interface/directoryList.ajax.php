<?php 
class directoryList extends controller {
	
	private $__result,$__translator,$__fileHandler,$_subdir = false;
	
	
	public function __construct($method=false,$params=false) {
		
		$this->__translator = new Translator();
		
		if (method_exists(__CLASS__, $method))
		{
			parent::__construct();
			
			$this->__fileHandler = new fileHandler(true);
			if ($this->setSubDir($params))
			{
				$this->$method($params);
			}
			else 
			{
				$this->returnFalse($this->__translator->getTranslation('dirnotfound'));
			}
		}
		else
		{
			
			//illegale actie, iemand probeert op een andere manier hierbij te komen
			//redirect naar home
			header("Location: http://".BASEURL);
			//$this->returnFalse($this->__translator->getTranslation('dirnotfound'));
		}
	}
	
	private function setSubDir($params) {
		
		if (is_dir($_SESSION['ftpfolder'] . $params['rel'] .DIRECTORY_SEPARATOR))
		{
			$this->_subdir = $params['rel']  .DIRECTORY_SEPARATOR;
			return true;
		}
		else 
		{
			return false;
		}
		
	
	}
	
	private function fixURL($uri) {
	
		//explorer fix
		$url = parse_url($uri);
		$path = str_ireplace(SECUREPATH, "", $url['path']);
		
		return $path;
	}
	
	
	private function getDirList($params) {
		
		$params['dir'] = $this->fixURL($params['dir']);
		
		if (is_dir($_SESSION['ftpfolder'] . $this->_subdir . urldecode($params['dir'])))
		{
		
			$obj = '';
		
			if ($dirlist = $this->__fileHandler->ReadaDir($_SESSION['ftpfolder'] . $this->_subdir . urldecode($params['dir'])))
			{
				
				
				
				foreach ($dirlist as $key => $value) 
				{
				
					(empty($params['dir'])) ?( $box=true):($box=false);
					$icon = $this->__translator->getIcon($value,$box);
					$obj .=	'<tr><td><a href="'.$params['dir'].'/'.$value['name'].'" class="'.$value['type'].'" rel="'.$params['rel'].'"><img src="/images/icons/'.$icon.'" width="40" height="40" class="textmiddle" border="0" /></a> <a href="'.$params['dir'].'/'.$value['name'].'" class="'.$value['type'].'" rel="'.$params['rel'].'">'.$value['name'].'</a></td><td>'.$this->__translator->getTranslation($value['type']).'</td><td>'.$value['size'].'</td><td>'.$value['time'].'</td></tr>';
								
				}
			}
			else 
			{
				$obj .=	'<tr><td colspan="4">'.$this->__translator->getTranslation('emptydir').'</td></tr>';
			}
			
			$arr = explode("/", $params['dir']);
			
			$root = '';
			$breadcrumbs = '';
			for($i=0;$i<count($arr);$i++)
			{
				$root .= $arr[$i].'/';
				if (!empty($arr[$i]))
				{
					if (($i+1)>=count($arr))
					{
						if (isset($arr[$i]))
						{
							$breadcrumbs .= ' <span class="arrows">&gt;&gt;</span> '.urldecode($arr[$i]);
						}
					}
					else
					{
						$breadcrumbs .= ' <span class="arrows">&gt;&gt;</span> <a class="dir" href="'.rtrim($root,'/').'" rel="'.$params['rel'].'">'.urldecode($arr[$i]).'</a>';	
					}
				}
			}
			
			
			//enable upload button only when in subdir
			(count($arr)>1) ?($uploadBut = true):($uploadBut = false);
			
			
			
			if (!empty($breadcrumbs))
			{
				$icon = $this->__translator->getIcon('crumbpath');
				$breadcrumbs = '<a href="" class="dir" rel="'.$params['rel'].'"><img src="/images/icons/'.$this->__translator->getIcon('crumbpath').'" width="117" height="124" class="textmiddle" border="0" /></a>' . $breadcrumbs;
			}
			
								
			$this->__result['breadcrumbs'] = $breadcrumbs;
			$this->__result['func'] = 'updateElement';
			$this->__result['uploadBut'] = $uploadBut;
			$this->__result['dir'] = $this->_subdir . $params['dir'];
			$this->__result['element'] = "#dirlist";
			$this->__result['html'] = $obj;
			echo json_encode($this->__result);
			
		}
		else 
		{
			$this->returnFalse($this->__translator->getTranslation('dirnotfound'));	
		}
	
	}
	
	
	private function downloadFile($params) {
			
			$params['file'] = $this->fixURL($params['file']);
			
			if (file_exists($_SESSION['ftpfolder'] . $this->_subdir . urldecode($params['file'])))
			{
				$this->__result['title'] = '';//$this->__translator->getTranslation('download-file-title');
				$this->__result['location'] = '/stream/download.php?file='.base64_encode($this->_subdir .  $params['file']);
				$this->__result['func'] = 'loadMessage';
				$this->__result['action'] = 'download';
				$this->__result['message'] = $this->__translator->getTranslation('download-file-msg');
				echo json_encode($this->__result);
			}
			else 
			{
				$this->returnFalse($this->__translator->getTranslation('filenotfound'));
			}
			
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
$ajaxcall = new directoryList($action,$params);

?>