<?php


class Translator extends controller {
	
	private $__language=false,$__languages=array(),$__debug;
	
	public function __construct($lang=false,$debug=false) {
		parent::__construct();
		
		(!$lang && isset($_SESSION['language'])) ? ($lang = $_SESSION['language']):false;
		
		#set debug to true or false
		$this->__debug = $debug;
		$this->__Exception = new errorHandler;
		$this->setLanguages();
		$this->setLanguage($lang);
		
	}
	
	private function setLanguages() {
	
		
		if ($r = $this->showColumns("translation"))
		{
			
			foreach ($r as $key => $value) 
			{
				#ignore the first two fields id and identify
				if ($value['Field']!='id' && $value['Field']!='identify')
				{
					$this->__languages[] = $value['Field'];
				}
				
			}
			return true;
		}
		else 
		{
			$this->setException(__FUNCTION__,"No languages found in table translation");
			($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	}
	
	public function checkLanguage($lang) {
		if (in_array($lang, $this->__languages) && is_dir(CWD . TPL_ROOT . DIRECTORY_SEPARATOR . $lang))
		{
			return true;
		}
		else 
		{
			$this->setException(__FUNCTION__,"Language $lang was not found in translation table or directory does not exist in ".CWD . TPL_ROOT );
			($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}	
	}
	
	private function setLanguage($lang) {
		
		if (in_array($lang,$this->__languages))
		{
			$this->__language = $lang;
		}
		else
		{
			if ($lang=='en')
			{
				$this->setException(__FUNCTION__,"Language $lang was not found in translation");
				($this->__debug)? ($this->getException(__FUNCTION__)):false;
				return false;
			}
			else 
			{
			
				$this->setException(__FUNCTION__,"Language $lang was not found in translation table defaulting to english");
				($this->__debug)? ($this->getException(__FUNCTION__)):false;
				if (in_array("en",$this->__languages))
				{
					$this->__language = $lang;
				}
				else 
				{
					$this->setException(__FUNCTION__,"Language english was not found either");
					($this->__debug)? ($this->getException(__FUNCTION__)):false;	
					return false;	
				}	
			}
		}
	}
	
	
	public function getTranslation($identifier=false) {
		
		if (!$identifier) {return false;}
		
		if ($r = $this->queryDB(sprintf("SELECT * from translation WHERE identify='%s'", $identifier)))
		{
			return $r[0][$this->__language];		
		}
		else 
		{
			$this->setException(__FUNCTION__,"Message $identifier was not found in table translation with language ".$this->__language);
			($this->__debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
	}
	
	/*
	*	returns the correct icon for file or directory
	* 	@value: array with information about file/dir like type and name array("type" => "dir", "name" => "testdir")
	*/
	public function getIcon($value,$box=false) {
		
		# if cookie is set we have retina
		(isset($_COOKIE["pixel_ratio"]) && $_COOKIE["pixel_ratio"]>=2)? ($hires = '@2x'):($hires = '');	
		if ($value=='crumbpath')
		{
			$icon = 'icon-box-crumbpath'.$hires.'.png';
		}
		else if ($value == 'user-crumbpath')
		{
			$icon = 'icon-user-crumbpath'.$hires.'.png';
		}
		else if ($value['type']=='dir')
		{
			($box) ? ($icon = 'icon-project'.$hires.'.png'): ($icon = 'icon-folder'.$hires.'.png');
		}
		else if ($value['type'] == 'customer' || $value['type'] == 'user')
		{
			switch($value['type']) 
			{
				case 'customer': $icon = 'icon-customer'.$hires.'.png';
					break;
				case 'user': $icon = 'icon-user'.$hires.'.png';
					break;
			}
			
		}
		else 
		{
			$icon = 'icon-file'.$hires.'.png';
			$ext = end(explode(".",$value['name']));
			switch(strtolower($ext))
			{
				case 'doc' : 
				case 'docx': $icon = 'icon-doc'.$hires.'.png';break;
				case 'pdf' : $icon = 'icon-pdf'.$hires.'.png';break;
				case 'ppt' : $icon = 'icon-ppt'.$hires.'.png';break;
				case 'rtf' : $icon = 'icon-rtf'.$hires.'.png';break;
				case 'text' : 
				case 'pages' :
				case 'odt': $icon = 'icon-text'.$hires.'.png';break;
				case 'txt' : $icon = 'icon-txt'.$hires.'.png';break;
				case 'xls' : $icon = 'icon-xls'.$hires.'.png';break;
				case 'zip' : $icon = 'icon-zip'.$hires.'.png';break;
				case 'png' :
				case 'jpg' :
				case 'bmp' :
				case 'jpeg' : 
				case 'tif' :
				case 'gif' : $icon = 'icon-image'.$hires.'.png';break;
				case 'mp4' :
				case 'mpg' :
				case 'avi' : 
				case 'm4v' :
				case 'mov' :
				case 'mkv' : $icon = 'icon-movie'.$hires.'.png';break;
				default :  $icon = 'icon-file'.$hires.'.png';break;
			}	
		}
		
		return $icon;
	
	}
	
	private function setException($method,$str) {
		$this->__Exception->setError(__CLASS__,$method,$str);
	}
	
	private function getException($method) {
		$this->__Exception->printError(__CLASS__,$method);
	}

}

?>