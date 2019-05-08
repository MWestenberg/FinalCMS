<?php



class Template {
	
	private $__Exception, $__debug=false,$__template = false,$__View,$__templateDir,$getVars,$urlarr,$__private=false,$__readRights = array();
	private $__defaultTemplate = 'start',$__counter=0,$__pageTitle,$__metaKeywords,$__metaDescription,$__metaAuthor;
	
	public function __construct($debug=false) {
		#init view class
		$this->__View = new view($debug);
		
		#set debug to true or false
		$this->__debug = $debug;
		$this->__Exception = new errorHandler;
		
		
	}
	
	public function setPrivate() {
		$this->__private = true;
	}
	
	public function setPublic() {
		
		$this->__private = false;	
		
		if (!isset($_GET['page']) || empty($_GET['page']))
		{
			$_GET['page'] = DEFAULT_PAGE;
		}
	}
	
	private function checkReadRights($templateName,$parent=false) {
		
		#get the parentMenu values
		$parentMenu = $this->__View->__credentials->getParentMenu();
		
		$parentId = false; $child = false; #default value is false;
		
		# no parent then the templateName is parent
		(!$parent) ? ( $parent = $templateName) : ($child = true);
		
		# check the parent if allowed
		foreach ($parentMenu as $key => $value) 
		{
			if ($key==$parent) { $parentId = $value['id'];break;}
		}
		
		# also check child when parent is true
		if ($child)
		{
			$childId = false;
			$submenu = $this->__View->__credentials->getChildMenu($parentId);
			foreach ($submenu as $key => $value) 
			{
				if ($key==$templateName) { $childId = $value['id'];break;}
			}
			
			return $childId;
		}
		else 
		{
	
			return $parentId;	
		}
		
	
	}
	
	/*
	* checks if the user is allowed to access ajax files
	* msut be entered within the userrights table and added to the usergroup
	*/
	private function checkAjaxRights($templateName) {
		
		
		# get all private and public ajax files.
		# private are based on usergroup
		$ajax = $this->__View->__credentials->getAjaxFiles();
		
		# check the file if allowed
		foreach ($ajax as $key => $value) 
		{
			
			if ($key==$templateName) { return $value['filename'];}
		}
		//print "<pre>";
		//print_r($ajax[$templateName]);
		//print "</pre>";
		//$this->setException(__FUNCTION__,"Ajax file $templateName was not found. Add it to userrights and usergroup");
		//($this->__debug)? ($this->getException(__FUNCTION__)):false;
		
		return false;
	}
		
	public function loadTemplate($templateName) {
		
		$_SESSION['currentTemplateName'] = $templateName;
		#check if its not ajax (never in second param but always as $_GET['page']
		if ($filename = $this->checkAjaxRights($templateName))
		{
			if (file_exists(CWD . AJAX_TPL . $filename))
			{
				require_once(CWD . AJAX_TPL . $filename);
			}
			else
			{
				
				$translator = new Translator;
				#ajax exceptions must be returned as json
				($this->__debug)? ($message = "Ajax template error<br /> file not found: ". AJAX_TPL . $filename):($message = $translator->getTranslation('ajax-error'));
				
				echo json_encode(array('title'=>$translator->getTranslation('apologies'), 'func' => 'loadMessage', 'message'=>$message) );
				return false;
			}
		}
		else if ($body = $this->loadBody($templateName)) #create body from file
		{
		
			#check in file for direct functions
			# This only works within the main template. Included tpl's cannot execute internal values like within a form.
			preg_match_all("'<php>([^<]*)</php>'si", $body, $matches);
			foreach ($matches as $key => $value) 
			{
				foreach ($value as $key2 => $phpFunction) 
				{
					$body = str_replace('<php>'.$phpFunction.'</php>', $this->execPHP($phpFunction), $body);
					
				}
			}
			
			(!empty($this->__pageTitle)) ? ($body = $this->replaceTags($body,'title',$this->__pageTitle)):false;
			(!empty($this->__metaKeywords)) ? ($body = $this->replaceMetaTags($body,'keywords',$this->__metaKeywords)):false;
			(!empty($this->__metaDescription)) ? ($body = $this->replaceMetaTags($body,'description',$this->__metaDescription)):false;
			(!empty($this->__metaAuthor)) ? ($body = $this->replaceMetaTags($body,'author',$this->__metaAuthor)):false;
			
				
			#check in file for methods and/or sub templates
			echo $this->createDom($body);
		}
		else 
		{
			return false;
		}
		
	}
	
	private function replaceTags($body,$tag,$replace) {
		
		preg_match("/<$tag>(.*)<\/$tag>/siU", $body,$matches);
		if (isset($matches[1]))
		{
			$val = $matches[1];
		}
		else {
			$val ='';
		}
		
		$body = str_replace('<title>'.$val.'</title>', '<title>'.$replace.'</title>', $body);
		
		return $body;
	}
	
	private function replaceMetaTags($body,$tag,$replace) {
		
		preg_match("/<meta name=\"$tag\" content=\"(.*)\">/siU", $body,$matches);
		if (isset($matches[1]))
		{
			$val = $matches[1];
		}
		else {
			$val ='';
		}
		
		$body = str_replace('<meta name="'.$tag.'" content="'.$val.'">', '<meta name="'.$tag.'" content="'.$replace.'">', $body);
		
		return $body;
	}
	
	
	
	/*
		Executes a php function
		@phpFunction: e.g. openView.getContent()
		returns the response of the function
	*/
	private function execPHP($phpFunction) {
	
		//split by ->
		$rp = explode("->", $phpFunction); // -> notation
		$rd = explode(".", $phpFunction); // . notation
		//must be a method within this class (openView)
		if (method_exists(__CLASS__, $rp[0]))
		{
			$replacement = $this->callPHPMethod($phpFunction,$rp);
		}
		else if (method_exists(__CLASS__, $rd[0]))
		{
			$replacement = $this->callPHPMethod($phpFunction,$rd);
		}
		else 
		{
			$replacement = $phpFunction;	
		}
		
		return $replacement;
	
	}
	
	
	private function callPHPMethod($val,$r) {
		
		//do we have arguments?
		if (preg_match("/\((.*?)\)/",$val,$args))
		{
			//get the arguments
			$params = explode(",",str_replace(array("'",'"'), "", $args[1]));
		}
		else 
		{
			$params = false;	
		}
		
		//set the method and remove anything that comes after the first ( symbol						
		$method = preg_replace("/(\(.*)/", "", $r[1]);
		//call the method
		return $this->$r[0]($method,$params);
			
	}
	
	private function GetgetVars() {
		$urlarr=explode("/",strip_tags($_SERVER['REQUEST_URI']));
		
		foreach ($urlarr as $key1 => $val1) {
			if (strpos($val1,"?")!== false) {
				#we have one ore more variable we need to grab
				$val1 = substr($val1,strpos($val1, "?")+1);
				$val1 = explode("&",$val1);
				foreach ($val1 as $key2 => $val2) {
					#postitie = teken
					$keyVar = substr($val2,0,(strpos($val2,"=")));
					$valVar = substr($val2,(strpos($val2,"=")+1));
					$this->getVars[$keyVar] = htmlentities($valVar,ENT_QUOTES,'UTF-8');
				}
			}
		}
	}
	
	/*
	* Loads the body into content and returns it
	* @templateName: the name of the file to include (based on private or public settings
	* @internal: all internally included tpl's are in the root the directory	
	*/
	public function loadBody($templateName,$internal=false) {
		
		$fileHandler = new fileHandler($this->__debug);
		
		# private area means maybe a subpage in getparam1
		if ($this->__private) 
		{	
			if ($internal)
			{
				# no need to check if allowed
				$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PRIVATE_TPL . DIRECTORY_SEPARATOR . PVT_INCLUDES;	
				$templateFile = $templateName.'.html';
			}
			else 
			{
				#set the directory
				$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PRIVATE_TPL . DIRECTORY_SEPARATOR . $_GET['subpage'];	
				
				if (isset($_GET['getparam1']) && !empty($_GET['getparam1']))
				{
					$templateName = $_GET['getparam1'];
					$parent = $_GET['subpage'];
				}
				else 
				{
					#default means no getparam1 so the default page has the same name as the directory
					$parent = false;
				}
				
			
				if (!$tid = $this->checkReadRights($templateName,$parent))
				{
					header("Location: ".SECUREPATH."/".$this->__defaultTemplate);
				}
				else 
				{
					#set page title
					$this->setPageTitle($templateName,$parent);	
					$templateFile = $this->__View->__credentials->getTemplateFile($tid,$templateName);
				}
			}
			
			
			
		}
		else //public templates
		{	
			//special change for forcing desktop or mobile
			if (($pos = strpos($_SERVER['REQUEST_URI'], "?"))!==false)
			{
				$this->GetgetVars();
				
				if (isset($this->getVars['view']) && $this->getVars['view']=='desktop')
				{
					$_SESSION['device']='desktop';
				}
				if (isset($this->getVars['view']) && $this->getVars['view']=='mobile') 
				{
					$_SESSION['device']='mobile';
				}
				//exit($_SESSION['device']);
				$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'],0,$pos);
				
			}
			
			
			if ($internal)
			{
				#internal includes are located in the includes directory
				$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . PUB_INCLUDES;	
				$templateFile = $templateName.'.html';
			}
			else if ($page = $this->__View->__credentials->getPublicTemplateFile($_SERVER['REQUEST_URI']))
			{
			
				$templateFile = $page['template'];
				//include dynamic template from CMS
				if (isset($_SESSION['device']) && ($_SESSION['device']=='mobile')// || $_SESSION['device']=='tablet')
					&& file_exists(CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . MOBILE_DIR . DIRECTORY_SEPARATOR . CMS_PAGES . DIRECTORY_SEPARATOR . $templateFile)
				)
				{
					$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . MOBILE_DIR . DIRECTORY_SEPARATOR . CMS_PAGES;
				}
				else 
				{
					$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . CMS_PAGES;	
				}
				
								
				$this->__pageTitle = $page['page_title'];
				$this->__metaKeywords = $page['meta_keywords'];
				$this->__metaDescription = $page['meta_description'];
				$this->__metaAuthor = $page['meta_author'];
				
			}
			else
			{	
				//include static template from directory
				if (isset($_GET['subpage']) && !empty($_GET['subpage']) && $_GET['page']!=PUB_INCLUDES && $_GET['page']!=CMS_PAGES && !$internal )
				{
				
					$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . $_GET['page'];
					$templateName = $_GET['subpage'];
					
					
					# special templates for mobile are in the same dir but one down
					# note that inclusion dirs below are not being changed
					if (isset($_SESSION['device']) && ($_SESSION['device']=='mobile')// || $_SESSION['device']=='tablet')
						&& is_dir(CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . MOBILE_DIR . DIRECTORY_SEPARATOR . $_GET['page'])
					)
					{
						$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . MOBILE_DIR . DIRECTORY_SEPARATOR . $_GET['page'];
					}
					
				}
				else
				{
					
					#normal templates in the root of the public_tpl
					$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL;
					
					# special templates for mobile are in the same dir but one down (when exists)
					if (isset($_SESSION['device']) && ($_SESSION['device']=='mobile')// || $_SESSION['device']=='tablet')
						&& is_dir(CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . MOBILE_DIR)
					)
					{
						$this->__templateDir = CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . MOBILE_DIR;
					}
					
				}
				$templateFile = $templateName.'.html';
			}
			
		}
		
		//get template file name from database
		
		//exit('template='.$templateName.' file='.$this->__templateDir.'/'.$templateFile);
		
		# load the template and return the content
		if ($fileHandler->ReadaDir($this->__templateDir)
			&& $file = $fileHandler->getFile($templateFile))
		{
			$content = file_get_contents($fileHandler->_currentDir . $file['name']);
			return $content;
			
		}
		else # if the template was not found redirect to homepage in case of public or settings in case of private
		{
			
			$this->__counter++;
			if ($this->__counter>3)
			{
				$this->setException(__FUNCTION__,'Could not find template '.$this->__templateDir .'/'.$_GET['subpage'].'/'. $templateFile);
				if ($this->__debug) 
				{
					die($this->getException(__FUNCTION__));				
				}
				else 
				{
					exit;	
				}
			}
			header("Location: /");
	
		}
		
	}
	
	private function setPageTitle($templateName,$parent=false) {
		
		($parent) ? ($pid = $this->checkReadRights($templateName,$parent)):($pid=false);
		$this->__pageTitle = $this->__View->setPageTitle($templateName,$pid);
		
	}
	
	private function createDom($body) {
		
		$doc = new DOMDocument();
		libxml_use_internal_errors(true);
		$doc->loadHTML($body);
		
		$tpl = $doc->getElementsByTagName('tpl');
		$code = $doc->getElementsByTagName('php');
		$i = ($tpl->length) + ($code->length); 
		while ($i >= 0) 
		{ 
			if ($element = $tpl->item($i))
			{ 
			
				if ($method = $element->nodeValue)
				{
					if (method_exists("view", $method))
					{
									
						#we have a function so execute it;
						$template = $doc->createDocumentFragment();
						$template->appendXML($this->parseEntities($this->openView($method)));
						$element->parentNode->replaceChild($template, $element);
					}
					else if ($body = $this->loadBody($method,true))
					{
						
						# we have sub template include it
						$template = $doc->createDocumentFragment();
						$template->appendXML($this->parseEntities($body));
						$element->parentNode->replaceChild($template, $element);
						
					}
					
					# save it again and load recursive
					$newdoc = $doc->saveHTML();
					$doc->loadHTML($this->createDom($newdoc));
				}
			}
			else if ($element = $code->item($i))
			{
				
				if ($phpFunction = $element->nodeValue)
				{
					
					$template = $doc->createDocumentFragment();
					$template->appendXML($this->parseEntities($this->execPHP($phpFunction)));
					$element->parentNode->replaceChild($template, $element);
					
					# save it again and load recursive
					$newdoc = $doc->saveHTML();
					$doc->loadHTML($this->createDom($newdoc));
				}
			}
			$i--;
		}
		
		libxml_clear_errors();
		return $doc->saveHTML();
	}
	
	
	/* 
	* parsing entities for domdocument
	* @string: the html that needs to be checked
	* returns a parsed and corrected html.
	*/
	public function parseEntities($string) {
	    $entities = array (
	        "auml" => "#228;",
	        "ouml" => "#246;",
	        "uuml" => "#252;",
	        "szlig" => "#223;",
	        "Auml" => "#196;",
	        "Ouml" => "#214;",
	        "Uuml" => "#220;",
	        "nbsp" => "#160;",
	        "Agrave" => "#192;",
	        "Egrave" => "#200;",
	        "Eacute" => "#201;",
	        "Ecirc"    => "#202;",
	        "egrave" => "#232;",
	        "eacute" => "#233;",
	        "ecirc" => "#234;",
	        "agrave" => "#224;",
	        "iuml" => "#239;",
	        "ugrave" => "#249;",
	        "ucirc" => "#251;",
	        "uuml" => "#252;",
	        "ccedil" => "#231;",
	        "AElig" => "#198;",
	        "aelig" => "#330;",
	        "OElig" => "#338;",
	        "oelig" => "#339;",
	        "angst" => "#8491;",
	        "cent" => "#162;",
	        "copy" => "#169;",
	        "Dagger" => "#8225;",
	        "dagger" => "#8224;",
	        "deg" => "#176;",
	        "emsp" => "#8195;",
	        "ensp" => "#8194;",
	        "ETH" => "#208;",
	        "eth" => "#240;",
	        "euro" => "#8364;",
	        "half" => "#189;",
	        "laquo" => "#171;",
	        "ldquo" => "#8220;",
	        "lsquo" => "#8216;",
	        "mdash" => "#8212;",
	        "micro" => "#181;",
	        "middot" => "#183;",
	        "ndash" => "#8211;",
	        "not" => "#172;",
	        "numsp" => "#8199;",
	        "para" => "#182;",
	        "permil" => "#8240;",
	        "puncsp" => "#8200;",
	        "raquo" => "#187;",
	        "rdquo" => "#8221;",
	        "rsquo" => "#8217;",
	        "reg" => "#174;",
	        "sect" => "#167;",
	        "THORN" => "#222;",
	        "thorn" => "#254;",
	        "trade" => "#8482;"
	     );
	 
	    foreach ($entities as $ent=>$repl) {
	        $string = preg_replace('/&'.$ent.';?/m', '&'.$repl, $string);
	    }
	 
	    return $string;
	}
	
	/* 
	* call view class with method
	* @method: method to call
	* returns result from $view->method()
	*/
	private function openView($method,$args=false) {
		if ($args) 
		{
			return $this->__View->$method($args);
		}
		else 
		{
			return $this->__View->$method();
		}
	}
	
	
	private function setException($method,$str) {
		$this->__Exception->setError(__CLASS__,$method,$str);
	}
	
	private function getException($method) {
		$this->__Exception->printError(__CLASS__,$method);
	}
	
}



?>