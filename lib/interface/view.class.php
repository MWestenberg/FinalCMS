<?php



class view extends controller {
	
	private $getVars,$urlarr,$__translator,$__account;
	public $__credentials;
		
	public function __construct($debug=false) {
		parent::__construct();
		
		#set debug to true or false
		$this->debug = $debug;
		
		$urlarr=explode("/",strip_tags($_SERVER['REQUEST_URI']));
		array_shift($urlarr); //the first one is empty anyway
		$this->urlarr = $urlarr;
		$this->GetgetVars();
		
		$this->__credentials = new credentials();
		if (isset($_SESSION['usergroup']))
		{
			$this->__credentials->setUserGroup($_SESSION['usergroup']);
		}
		$this->__translator = new Translator;
		
		
	}
	
	/*
		GENERAL FUNCTIONS
	*/
	public function getSession() {
			
		$output = "";
		foreach ($_SESSION as $key => $value) {
			$output .= "$key : $value<br />";	
		}
		
		$output .= 'register globals:'.ini_get('register_globals ').'<br />';
		
		$ajax = $this->__credentials->getAjaxFiles();
		foreach ($ajax as $key => $value)
		{
			$output .= $key .' = '.$value['templatename'].' ('.$value['security'].')<br />'; 	
		}

		return $output;
	}
		
	public function currentLink() {
		$vars ="";
		foreach($this->urlarr as $key => $value) {
			
			if (strpos($value, "?")!== false) {
				$value = substr($value, 0, strpos($value, "?"));
				$this->urlarr[$key] = $value;
			}
			
			if (constant('SECUREPATH')) 
			{
				$noOfPaths = substr_count(SECUREPATH,"/")-1;
				
				if ($key>$noOfPaths && $key<(count($this->urlarr)-1)) {
					$vars .= $value."/";
				} else if ($key!=0 && $key==(count($this->urlarr)-1)) {
					$vars .= $value;
				}
			} 
			else 
			{
				if ($key<(count($this->urlarr)-1)) {
					$vars .= $value."/";
				} else if ($key==(count($this->urlarr)-1)) {
					$vars .= $value;
				}
			}
		}
		return $vars;
	}
	
	#Method searches the url for get variables and puts them in the array $this->getVars
	private function GetgetVars() {
		foreach ($this->urlarr as $key1 => $val1) {
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
	
	
	public function getParam($params) {
	
		return $_GET['getparam'.$params[0]];
	}
	
	
	public function setPageTitle($templateName,$parent=false) {
		
		if ($title = $this->__translator->getTranslation('title-'.$templateName))
		{
			return $title;
		}
		else 
		{
			//return the description of the template from userrights
			if ($parent)
			{
				//child
				$value = $this->__credentials->getChildMenu($parent);
			}
			else 
			{
				//parent	
				$value = $this->__credentials->getParentMenu();
			}
			
			if (isset($value[$templateName]['description'])) {return $value[$templateName]['description'];}
			 		
		}
		
	
	}
	
	/*
	* Initializes dynamic content
	*/
	public function getContent($params) {
		
		$templateFile = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . $params[0]; //includes/item.html
		$body = '';
		if (file_exists($templateFile))
		{
			$tpl = file_get_contents($templateFile);
		 	
		 	//capture any parameters and ignore them
		 	if (($pos = strpos($_SERVER['REQUEST_URI'], "?"))!==false)
		 	{
		 		$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'],0,$pos);
		 	}
		 			 	
			if ($r = $this->queryDB(sprintf("SELECT p.*,c.text FROM page p  LEFT JOIN content c ON p.id=c.fk_page WHERE p.page_url = '%s' AND p.state > 0 ORDER BY c.position ASC",$_SERVER['REQUEST_URI'])))
			{
			
				foreach ($r as $value) 
				{
										
					if (strtotime($value['publish_date']) <= time() && $value['state'] && (strtotime($value['archive_date']) > time() || strtotime($value['archive_date']) < 0))
					{
						//remove junk
						(trim($value['text'])!='') ? ($content  = $this->replaceContent($value['text'],$value['cms_template'])): ($content = '<div id="contentwrapper" >'.file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/files/templates/'.$r[0]['cms_template']).'</div>');
						$body .= sprintf($tpl, str_ireplace('editable',"", $content));
					}
				}
			}
			else 
			{
				$body .= sprintf("SELECT p.*,c.text FROM page p  LEFT JOIN content c ON p.id=c.fk_page WHERE p.page_url = '%s' AND p.state > 0 AND p.published >0 ORDER BY c.position ASC",$_SERVER['REQUEST_URI']);	
			}
		}
		else 
		{
			$body = 'Template not found: '.TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . $params[0];
		}
		
		
		return $body;
	}
	
	
	private function replaceContent($body,$template) {
	
		$dom1 = new DOMDocument();
		$dom2 = new DOMDocument();
		libxml_use_internal_errors(true);
		$tpl = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/files/templates/'.$template);
		$dom1->loadHTML($tpl); //template
		$dom2->loadHTML($body); //stored content
		
		$dit = new RecursiveIteratorIterator(
		    new RecursiveDOMIterator($dom1),
		    RecursiveIteratorIterator::SELF_FIRST
		);
		
		
		$dat = new RecursiveIteratorIterator(
		    new RecursiveDOMIterator($dom2),
		    RecursiveIteratorIterator::SELF_FIRST
		);
		
		foreach($dat as $contentNode) {
		    if($contentNode->nodeType === XML_ELEMENT_NODE) {
		        
		        //replace elements dat are obscured by cke
		        if ($contentNode->hasAttribute('data-cke-realelement'))
		        {
		        	
		        	$contentNode->parentNode->setAttribute('class', 'iframe');
		        	$contentToReplace =urldecode($contentNode->getAttribute('data-cke-realelement'));
		        	$fragment = $dom2->createDocumentFragment();
		        	$fragment->appendXML($contentToReplace);
		        	$contentNode->parentNode->replaceChild($fragment,$contentNode);

		        }
		    }
		}
		
		
		//save it
		$dom2->saveHTML();
		
		
		foreach($dit as $tplNode) {
		    if($tplNode->nodeType === XML_ELEMENT_NODE && $tplNode->hasAttribute('contenteditable')) {
		       
		        
		        if ($tplNode->getAttribute('contenteditable')=='true')
		        {
					
					foreach($dat as $contentNode) {
					    if($contentNode->nodeType === XML_ELEMENT_NODE) {
		        
					        $contentId = $contentNode->getAttribute('id');

					        if (!empty($contentId) && $contentId == $tplNode->getAttribute('id'))
					        {
								$tplNode->nodeValue = $this->get_inner_html($contentNode); //empty the nodevalue
					        }
					
					    }
					}
					
					$tplNode->removeAttribute('contenteditable');
					$tplId = $tplNode->getAttribute('id');
					
					(!empty($tplId) && strlen($tplId) <=3) ?($tplNode->removeAttribute('id')):false;

		        }
			
		    }	    
		}
		
		
		
		libxml_clear_errors();
		$result =  $dom1->saveHTML();
		
		return htmlspecialchars_decode($result);	
	
	}
	
	
	
		
	private function get_inner_html( $node ) { 
	    $doc = $node->ownerDocument; 
	    $frag = $doc->createDocumentFragment(); 
	    foreach ($node->childNodes as $child) { 
	    	$frag->appendChild($child->cloneNode(TRUE)); 
	    } 
	    return $doc->saveXML($frag); 
	}
	
	
	
	
	public function getCMSTemplates() {
		
		
		$query = sprintf("SELECT * FROM template WHERE fk_customer=%s",$_SESSION['customerID']);
		
		if ($r = $this->queryDB($query))
		{
			$options = '';
			foreach ($r as $key => $value) {
				$options .= '<option value="'.$value['cms_template'].'">'.$value['template_name'].'</option>';
				
			}
			return $options;
		}
		else 
		{
			return "";	
		}
	}
	
	
	/*
		CMS FUNCTIONS
	*/
	public function getParentMenu($start=false) {
		$ParentMenu = $this->__credentials->getParentMenu();
		
		$output = "";
		foreach ($ParentMenu  as $key => $value) {
			
			if ($value['menu-item'] && $value['templatename']!='account')
			{
				(!$menuName = $this->__translator->getTranslation($value['templatename'])) ? ($menuName = $value['templatename']): false;
				
				
				if (!$start)
				{
					($_GET['subpage'] == $value['templatename']) ? ($addClass = ' selected'):($addClass='');
				
					$output .= '<div class="menuItem'.$addClass.'"><a href="'.SECUREPATH .'/'.$value['templatename'].'" >'.$menuName.'</a></div>';	
				}
				else 
				{
					$output .= '<div class="cmsstart finalCMSMenu"><a  href="'.SECUREPATH .'/'.$value['templatename'].'" >'.$menuName.'</a></div>';		
				}
			}
		}
		
		return $output;
		
	}	
	
	public function getChildMenu() {
		
		$ParentMenu = $this->__credentials->getParentMenu();
		
		foreach ($ParentMenu as $parentkey => $parentvalue) 
		{
			if ($parentkey == $_GET['subpage'])
			{
				$submenu = $this->__credentials->getChildMenu($parentvalue['id']);
				$output = "";
				foreach ($submenu as $childkey => $childvalue) {
					if ($childvalue['menu-item'])
					{
						(!$menuName = $this->__translator->getTranslation($childvalue['templatename'])) ? ($menuName = '['.$childvalue['templatename'].']'): false;
						$output .= '<li><a href="'.SECUREPATH .'/'.$parentvalue['templatename'].'/'.$childvalue['templatename'].'" >'.$menuName.'</a></li>';	
					}
				}
				return $output;
				
			}
			
			
		}			

	}
	public function getAccountInfo() {
		
		if ($r = $this->queryDB(sprintf("SELECT firstname,lastname,email1,phone FROM user WHERE id=%s",$_SESSION['loginId'])))
		{
			$this->__account  = $r;
		}
	
	}
	
	public function getFirstName() { $this->getAccountInfo(); return $this->__account[0]['firstname']; }
	public function getlastName() { return $this->__account[0]['lastname']; }
	public function getEmail() { return $this->__account[0]['email1']; }
	public function getPhone() { return $this->__account[0]['phone']; }
	public function getCurrentYear() { return date("Y"); }
	public function debugInfo() {return '<pre>'.var_export($_GET).'</pre>';}
	
	
	public function liveSearch() {
		
		if (isset($this->getVars['search']) && !empty($this->getVars['search']))
		{
			$search = $this->getVars['search'];
		}
		else 
		{
			$search = '';	
		}
		return '<input type="text" id="search" placeholder="  live search" value="'.$search.'" />';
	}
	
	public function getAPPName() {
		return APPNAME;
	}
	
	public function getLanguage() {
		if (isset($_SESSION['language']))
		{
			return $_SESSION['language'];
		}
		else 
		{
			return "";
		}
	}
	
	public function getCustomerName() {
		return $_SESSION['customerName'];	
	}
	
	public function getAvatar() {
		
		
		
		if (isset($_SESSION['customerAvatar']) && file_exists($_SERVER['DOCUMENT_ROOT'] . "/images/finalcms/".$_SESSION['customerAvatar']))
		{
			return '<img src="/images/finalcms/'.$_SESSION['customerAvatar'].'" />';
		}
		else 
		{
			return '<img src="/images/finalcms/avatar.png" />';	
		}
	}
	
	/*
	* initializes the CKEditor
	*/
	public function initEditor($params) {
		
		(!isset($params[0]) || empty($params[0])) ? ($lang = $_SESSION['language']): ($lang = $params[0]);
		
		
		$javascript = '<script>$(document).ready( function() {
			
			
			
			
			CKEDITOR.disableAutoInline = true;
			
		
				
				$(".editable").each(function(){
				    
				  createEditor($(this).attr("id"),"'.$lang .'");
				    
				});
		
			
			
					
			CKEDITOR.on( "dialogDefinition", function( ev )
				{
					
					var dialogName = ev.data.name;
					var dialogDefinition = ev.data.definition;
			
					dialogDefinition.removeContents( "advanced" )
					dialogDefinition.removeContents( "upload" );
					dialogDefinition.removeContents( "Upload" );
					
					
				});
			
			
			$("*").each(function (key, value) {
				if ($(this).attr("contenteditable"))
				{
					$(this).addClass("ck_selectable");
					
				}
			});
			
			$(".ck_selectable").focus(function() {
				
				//console.debug($(this).css("color"));
				if ($(this).css("color") == "rgb(242, 242, 242)" || $(this).css("color") == "rgb(255, 255, 255)")
				{
					$(this).addClass("cke_focusdark");	
				}
											
				$(this).addClass("cke_focus");	
			}).blur(function() {
				$(this).removeClass("cke_focus");			
				$(this).removeClass("cke_focusdark");
			});
			
			
		});</script>';
		
	//	$javascript = '';
		
		
		$query1 = sprintf("SELECT id,text FROM content WHERE id=%s AND fk_page=%s AND fk_customer=%s",$this->getVars['i'],$this->getVars['p'],$_SESSION['customerID']);
		
		$query2 = sprintf("SELECT cms_template FROM page WHERE id=%s AND fk_customer=%s",$this->getVars['p'],$_SESSION['customerID']);
		
		
		$frame = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/files/templates/frame.html');
		
		$html = '<div id="contentwrapper" >'.file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/files/templates/default.html').'</div>';
		
		
		if ($page = $this->queryDB($query2))
		{
		
			if (!empty($page[0]['cms_template']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/files/templates/'.$page[0]['cms_template']))
			{
				$html = '<div id="contentwrapper" >'.file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/files/templates/'.$page[0]['cms_template']).'</div>';
			}
			
			if ($content = $this->queryDB($query1))
			{
				
				if (trim($content[0]['text'])!='')
				{
					$html = '<div id="contentwrapper" >'.html_entity_decode($content[0]['text'],ENT_QUOTES,'ISO-8859-1').'</div>';
				}
				
			}
					
		}		
				
		return $javascript . sprintf($frame,$html);
		//return $content[0]['text'];
		
	}
	
	public function getReports($params) {
		$tbody = '';
		$trowTpl = TPL_DIR . DIRECTORY_SEPARATOR . PRIVATE_TPL .  DIRECTORY_SEPARATOR . $params[0];//includes/tbody.html
		$tcellTpl = TPL_DIR . DIRECTORY_SEPARATOR . PRIVATE_TPL .  DIRECTORY_SEPARATOR . $params[1];//includes/tcell.html
		//<tr id="%s">%s</tr>
		
		//<td class="%s" colspan="%s">%s</td>
		if (file_exists($trowTpl) && file_exists($tcellTpl))
		{
			
			$trow = file_get_contents($trowTpl);
			$tcell = file_get_contents($tcellTpl);
			
			if (isset($this->getVars['year']) && is_numeric($this->getVars['year']))
			{
				$selectedYear  =$this->getVars['year'];
			}
			else 
			{
				$selectedYear  =date("Y");
			}
			
			if ($r = $this->queryDB(sprintf("SELECT * FROM report WHERE fk_customer=%s AND YEAR(publish_date)=%s",$_SESSION['customerID'],$selectedYear)))
			{
				
								
				foreach ($r as $value) 
				{
					
					$cell = sprintf($tcell, '','',$value['report_title']);
					$cell .= sprintf($tcell, 'break-words','',$value['preview_file']);
					$cell .= sprintf($tcell, 'break-words','',$value['keywords']);
					$cell .= sprintf($tcell, '','',$value['publish_date']);
					($value['state']) ? ($cell .= sprintf($tcell, '','','<span class="glyphicons-black icon-ok">&nbsp;</span>')): ($cell .= sprintf($tcell, '','','<span class="glyphicons-black icon-nok">&nbsp;</span>'));
					
					$tbody .= sprintf($trow, $value['md5_id'],'', $cell);
					
				}
			}
			else 
			{
				$cell = sprintf($tcell, '',$params[2],$this->__translator->getTranslation('empty-reportlist'));
				$tbody .= sprintf($trow, '',$cell);
				
			}
			
				
		}
		else 
		{
			$tbody = 'Templates not found: '.TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . $params[0].' | '.$params[1];
		}
		
		return $tbody;
		
		
		//$params[1] = rows
	
	}
	
	
	public function getEvents($params) {
		$tbody = '';
		$trowTpl = TPL_DIR . DIRECTORY_SEPARATOR . PRIVATE_TPL .  DIRECTORY_SEPARATOR . $params[0];//includes/tbody.html
		$tcellTpl = TPL_DIR . DIRECTORY_SEPARATOR . PRIVATE_TPL .  DIRECTORY_SEPARATOR . $params[1];//includes/tcell.html
		//<tr id="%s">%s</tr>
		
		//<td class="%s" colspan="%s">%s</td>
		if (file_exists($trowTpl) && file_exists($tcellTpl))
		{
			
			$trow = file_get_contents($trowTpl);
			$tcell = file_get_contents($tcellTpl);
			
			if (isset($this->getVars['year']) && is_numeric($this->getVars['year']))
			{
				$selectedYear  =$this->getVars['year'];
			}
			else 
			{
				$selectedYear  =date("Y");
			}
			
			if ($r = $this->queryDB(sprintf("SELECT * FROM event WHERE fk_customer=%s AND YEAR(start_datetime)=%s ORDER BY start_datetime DESC",$_SESSION['customerID'],$selectedYear)))
			{
				
								
				foreach ($r as $value) 
				{
					
					$cell = sprintf($tcell, '','',$value['event_title']);
					$cell .= sprintf($tcell, 'break-words','',$value['brochure']);
					$cell .= sprintf($tcell, 'break-words','',$value['keywords']);
					$cell .= sprintf($tcell, '','',$value['start_datetime']);
										
					$tbody .= sprintf($trow, $value['md5_id'],'', $cell);
					
				}
			}
			else 
			{
				$cell = sprintf($tcell, '',$params[2],$this->__translator->getTranslation('empty-reportlist'));
				$tbody .= sprintf($trow, '',$cell);
				
			}
			
				
		}
		else 
		{
			$tbody = 'Templates not found: '.TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . $params[0].' | '.$params[1];
		}
		
		return $tbody;
		
		
		//$params[1] = rows
	
	}
	
	
	public function getReportYears() {
		
		$years = '';
		if (isset($this->getVars['year']) && is_numeric($this->getVars['year']))
		{
			$selectedYear  =$this->getVars['year'];
		}
		else 
		{
			$selectedYear  =date("Y");
		}
		
		
		if ($r = $this->queryDB(sprintf("SELECT YEAR(publish_date) as year FROM report WHERE fk_customer=%s GROUP BY year",$_SESSION['customerID'])))
		{
			foreach ($r as $value) {
				($value['year'] == $selectedYear) ? ($current = 'selected'):($current='');
				
				$years .= '<option value="'.$value['year'].'" '.$current.'>'.$value['year'].'</option>';
			}
			
		}
		
		return $years;
	}
	
	public function getPreviewReport() {
		
		$rootpath = $_SERVER['DOCUMENT_ROOT']."/files/web/files/report-previews";
		
		$fileHandler = new fileHandler;
		$files = $fileHandler->ReadaDir($rootpath);
		
		//$query = sprintf("SELECT report_title,preview_file FROM report WHERE fk_customer=%s",$_SESSION['customerID']);
		
		(isset($this->getVars['reportID'])) ? ( $queryAdd = "AND md5_id!='".$this->getVars['reportID']."'"): ($queryAdd = "");
		
		$query = sprintf("SELECT preview_file FROM report WHERE fk_customer=%s AND preview_file!='' %s",$_SESSION['customerID'],$queryAdd);
		
		$options = '';
		if ($r = $this->queryDB($query))
		{
			
			
			foreach ($files as $file) {
				
				$fileName = $file['name'];
				
				$add = true;
				foreach($r as $value)
				{
					if ($fileName==$value['preview_file'])
					{
						$add =false;
					}
				
				}
				
				if ($add)
				{
					$options .= '<option value="'.$file['name'].'">'.html_entity_decode($fileName,ENT_QUOTES,'UTF-8').'</option>';
				}
			}
		
		}
		else 
		{
			foreach ($files as $key => $value) {
				$options .= '<option value="'.$value['name'].'">'.html_entity_decode($value['name'],ENT_QUOTES,'UTF-8').'</option>';
				
			}	
		}
		
		
		
		return $options;
		
	
	}
	
	
	public function getEventBrochures() {
		$rootpath = $_SERVER['DOCUMENT_ROOT']."/files/web/files/event-brochures";
		
		$fileHandler = new fileHandler;
		$files = $fileHandler->ReadaDir($rootpath);
		
		//$query = sprintf("SELECT event_title,preview_file FROM event WHERE fk_customer=%s",$_SESSION['customerID']);
		
		(isset($this->getVars['eventID'])) ? ( $queryAdd = "AND md5_id!='".$this->getVars['eventID']."'"): ($queryAdd = "");
		
		$query = sprintf("SELECT brochure FROM event WHERE fk_customer=%s AND brochure!='' %s",$_SESSION['customerID'],$queryAdd);
		
		$options = '';
		if ($r = $this->queryDB($query))
		{
			
			
			foreach ($files as $file) {
				
				$fileName = $file['name'];
				
				$add = true;
				foreach($r as $value)
				{
					if ($fileName==$value['brochure'])
					{
						$add =false;
					}
				
				}
				
				if ($add)
				{
					$options .= '<option value="'.$file['name'].'">'.html_entity_decode($fileName,ENT_QUOTES,'UTF-8').'</option>';
				}
			}
		
		}
		else 
		{
			foreach ($files as $key => $value) {
				$options .= '<option value="'.$value['name'].'">'.html_entity_decode($value['name'],ENT_QUOTES,'UTF-8').'</option>';
				
			}	
		}
		
		
		
		return $options;
	
	}
	
	
	public function getEventYears() {
		
		$years = '';
		if (isset($this->getVars['year']) && is_numeric($this->getVars['year']))
		{
			$selectedYear  =$this->getVars['year'];
		}
		else 
		{
			$selectedYear  =date("Y");
		}
		
		
		if ($r = $this->queryDB(sprintf("SELECT YEAR(start_datetime) as year FROM event WHERE fk_customer=%s GROUP BY year",$_SESSION['customerID'])))
		{
			foreach ($r as $value) {
				($value['year'] == $selectedYear) ? ($current = 'selected'):($current='');
				
				$years .= '<option value="'.$value['year'].'" '.$current.'>'.$value['year'].'</option>';
			}
			
		}
		
		return $years;
	}
	
	public function getUnsignedEvents() {
		$events = '';
		
		if ($r = $this->queryDB(sprintf("SELECT * FROM event WHERE fk_customer=%s AND planned=0",$_SESSION['customerID'])))
		{
			
							
			foreach ($r as $value) 
			{
				$events .= '<div class="external-event" id="'.$value['md5_id'].'">'.$value['event_title'].'</div>';							
			}
		}
		else 
		{
			$events = '<div>No unplanned events</div>';	
		}
		
		return $events;
	
		
	
	}	
	
	
	
	/*
		FRONTEND FUNCTIONS		
	*/
	
	
	
	
	
	
	
	
	public function frontMenu($params) {
	
		$templateFile = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . $params[0]; //includes/item.html
		$body = '';
		if (file_exists($templateFile))
		{
			$tpl = file_get_contents($templateFile);
		
			if ($r = $this->queryDB(sprintf("SELECT * FROM page WHERE state > 0 AND is_menu > 0 ORDER BY position ASC")))
			{
			
				foreach ($r as $value) 
				{
										
					if (strtotime($value['publish_date']) <= time() && (strtotime($value['archive_date']) > time() || strtotime($value['archive_date']) < 0))
					{
						
						
						if ($value['page_url']==$_SERVER['REQUEST_URI'])
						{
							$classAdd = 'selected';
							
						}
						else 
						{
							$classAdd = '';
						}
						//disable the menu for the cms
						(isset($params[1]))? ($value['page_url'] = '#'):false;
						
						if (!empty($value['page_title']))
						{
							$body .= sprintf($tpl, $value['page_url'],$classAdd,$value['page_title']);
						}
					}
				}
			}
		}
		else 
		{
			$body = 'Template not found: '.TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . $params[0];
		}
		
		
		return $body;
	
	}
	
	
	


	public function goToDateCalendar() {
	
		$javascript = '';
		
		if (isset($this->getVars['gotoDate']) && !empty($this->getVars['gotoDate']) && isset($this->getVars['view']) && !empty($this->getVars['view']))
		{
			$arr = explode("-", $this->getVars['gotoDate']);
		
			$javascript = '<script>$(document).ready( function() {
			
						$("#calendar").fullCalendar( "gotoDate", '.$arr[0].','.$arr[1].','.$arr[2].');	
						$("#calendar").fullCalendar( "changeView", "'.$this->getVars['view'].'");	
				
			});</script>';
		
		}
		
		
		
		
		return $javascript;
	
	}
	
	
	public function getLastTenEvents($params) {
		
		
		$tplFile = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . PUB_INCLUDES .  DIRECTORY_SEPARATOR . $params[0];
		
		if (file_exists($tplFile))
		{
			
			$eventTpl = file_get_contents($tplFile);
			
			$body  = '';
			
			if (isset($params[1]))
			{
				if (is_numeric($params[1]))
				{
					$queryLimit = 'LIMIT 0, '.$params[1];
				}
				else 
				{
					$queryLimit = 'LIMIT 0, 10';	
				}
				
			}
			
			
			if (isset($params[2]))
			{
				$queryCond = 'AND start_datetime < (NOW() - INTERVAL 3 WEEK)';
				$direction = 'DESC';			
			}
			else 
			{
				$queryCond = 'AND start_datetime >= (NOW() - INTERVAL 3 WEEK)';	
				$direction = 'ASC';
			}
			
			if ($r = $this->queryDB(sprintf("SELECT * FROM event WHERE fk_customer=%s %s AND planned=1 ORDER BY start_datetime %s %s",$this->getSite(),$queryCond,$direction,$queryLimit)))
			{
				 
								
				foreach ($r as $value) 
				{	
					//set variables to entire days and leave out time for calculation
					$startDate = strftime("%Y-%m-%d",strtotime($value['start_datetime']));
					$endDate = strftime("%Y-%m-%d",strtotime($value['stop_datetime']));
					
					if (strtotime($endDate) > strtotime($startDate))
					{
						$eventDate = strftime("%B %e,  %Y",strtotime($value['start_datetime'])).' - '.strftime("%B %e,  %Y",strtotime($value['stop_datetime']));
						
						if (strtotime($endDate) > (strtotime($startDate) + 60 * 60 *24))
						{
							//3 days
							$day3 = strftime("%d",(strtotime($value['start_datetime']) + 60 * 60 * 24 *2));
							$month3 = strftime("%h",(strtotime($value['start_datetime']) + 60 * 60 * 24 *2));
						}
						else 
						{
							//only 2 days
							$day3 = '';	
							$month3 = '';			
						}
						$day2 = strftime("%d",(strtotime($value['start_datetime']) + 60 * 60 *24));	
						$month2 = strftime("%h",(strtotime($value['start_datetime']) + 60 * 60 *24));	
					}
					else 
					{
						$eventDate = strftime("%B %e,  %Y",strtotime($value['start_datetime']));
						$day2='';$day3='';$month2='';$month3='';	
					}
					
					
					$regfee = '';
					
					$regFees = json_decode($value['regfee'],true);
					
					
					$proceedings = '';
					
					if (isset($regFees))
					{
						foreach ($regFees as $name => $price) 
						{
							
							if (isset($params[2]))
							{
								if (strpos($name, 'proceedings')!==false)
								{
									$proceedings ='<a href="/events/register/'.$value['md5_id'].'" id="'.$value['md5_id'].'" class="btn btn-small">Buy the proceedings</a>';
									
									if ($value['currency']=='EUR')
									{
										$regfee = '<span class="gray-light">'.$name.':</span> € '.number_format($price,2,",",".").'<br />';
									}
									else
									{
										$regfee = '<span class="gray-light">'.$name.':</span> $ '.number_format($price,2,".",",").'<br />';
									}
									break;
									
									
								}
							}
							else 
							{
								if ($value['currency']=='EUR')
								{
									$regfee .= '<span class="gray-light">'.$name.':</span> € '.number_format($price,2,",",".").'<br />';
								}
								else
								{
									$regfee .= '<span class="gray-light">'.$name.':</span> $ '.number_format($price,2,".",",").'<br />';
								}	
							}
							
							
							
						}
					}
					/*
					if (isset($params[2]) && empty($regfee))
					{
						if ($value['currency']=='EUR')
						{
							$regfee .= '<span class="gray-light">Proceedings:</span> € '.number_format(0,2,",",".").'<br />';
						}
						else
						{
							$regfee .= '<span class="gray-light">Proceedings:</span> $ '.number_format(0,2,".",",").'<br />';
						}
					}*/
					
					if ($c = $this->queryDB(sprintf("SELECT * FROM country WHERE id='%s'",$value['country'])))
					{
						$country = $c[0]['name'];
					}
					else 
					{
						$country = 	$value['country'];
					}
					
					$search = array('{eventId}','{blockId}','{month}','{day}','{year}','{event_title}','{location}','{eventFile}', '{event_description}','{start_date}','{regfee}','{date}','{day2}','{day3}','{month2}','{month3}','{proceedings}');
					$replace = array($value['md5_id'],'b_'.$value['md5_id'],strftime("%B",strtotime($value['start_datetime'])), date("d",strtotime($value['start_datetime'])),strftime("%Y",strtotime($value['start_datetime'])), $value['event_title'],$value['city'].', '.$country,urlencode($value['brochure']),$value['description'],strftime("%B",strtotime($value['start_datetime'])),$regfee,$eventDate,$day2,$day3,$month2,$month3,$proceedings);
					$body .= str_ireplace($search, $replace, $eventTpl);		
										
				}
			}
					
			return $body;
			
		}
		else 
		{
			$body .= 'Could not find templates : '.$tplFile;
		}
	}
	
	
	public function getCountries() {
		
		$body = '';
		
		$query =sprintf("SELECT * FROM country ORDER BY name ASC");
		if ($countries = $this->queryDB($query))
		{
			foreach ($countries as $value) 
			{
				$body .= '<option value="'.$value['id'].'">'.$value['name'].'</option>';
			}
			
		}
		
		return $body;
	
	}
	
	public function reportOverview($params) {
	
		
		
		$reportRow = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . PUB_INCLUDES .  DIRECTORY_SEPARATOR . $params[0];//reportrow.html
		$reportItem = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . PUB_INCLUDES .  DIRECTORY_SEPARATOR . $params[1];//reportitem
		$reportDetail = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . PUB_INCLUDES .  DIRECTORY_SEPARATOR . $params[2];//reportdetail.html
		
		$body = ''; //set to nothingd
		
		if (file_exists($reportRow) && file_exists($reportItem) && file_exists($reportDetail))
		{
			
			$rRow = file_get_contents($reportRow);
			$rItem = file_get_contents($reportItem);
			$rDetail = file_get_contents($reportDetail);
			
		
			//$newRow = '';
			$newItem = '';
			$newDetail = '';
			$archive = $this->getPublicReportYears();
			$year  =false;
			
			if (isset($this->getVars['search']) && !empty($this->getVars['search']))
			{
				$this->getVars['search'] =  htmlentities(strip_tags($this->getVars['search']),ENT_QUOTES,'UTF-8'); //clean it up first
				$searchQuery = ' AND (keywords like "%'.urldecode($this->getVars['search']).'%")';
			}
			else 
			{
				$searchQuery = '';	
			}
			
			if (isset($_GET['getparam1']) && !empty($_GET['getparam1']))
			{
				if (!is_numeric($_GET['getparam1']))
				{
					$searchQuery = ' AND report_category="'.htmlentities($_GET['getparam1'],ENT_QUOTES,'UTF-8').'"';	
					$archive = $this->getPublicReportYears('/'.$_GET['getparam1']);			
				}
				else 
				{
					$year = true;
					$searchQuery = " AND YEAR(publish_date) = '".$_GET['getparam1']."'";
					$archive = $this->getPublicReportYears();
					
				}
			}
			if (isset($_GET['getparam2']) && !empty($_GET['getparam2']) && is_numeric($_GET['getparam2']))
			{
				$year = true;
				$searchQuery .= " AND YEAR(publish_date) = '".$_GET['getparam2']."'";
				$archive = $this->getPublicReportYears('/'.$_GET['getparam1']);				
			}
			
			if ($year)
			{
				$query = sprintf("SELECT *,YEAR(publish_date) as year FROM report WHERE fk_customer=%s AND state=1 %s ORDER BY publish_date DESC",$this->getSite(),$searchQuery);
			}
			else 
			{
				$query = sprintf("SELECT *,YEAR(publish_date) as year FROM report WHERE fk_customer=%s AND state=1 %s ORDER BY publish_date DESC LIMIT 0,40 ",$this->getSite(),$searchQuery);
			}
						
			if ($r = $this->queryDB($query))
			{
				$counter =1;
				
				
				foreach ($r as $value) 
				{
					(!isset($currentYear)) ? ($currentYear = $value['year']):false;
					
					/*
					//adds a line for each year..was removed on request
					if ($currentYear != $value['year'])
					{
						$body .= sprintf($rRow, $newItem,$newDetail);
						$body .= '<div class="basicContent"><h3>'.$value['year'].'</h3><hr /></div>';	
						//new row begins
						
						$newRow = '';
						$newItem = '';
						$newDetail = '';
					}
					*/
						
					// reportItem
					$searchItem = array('{blockId}', '{reportFile}', '{reportId}', '{report_title}','{report_category}');
					$replaceItem = array('b_'.$value['md5_id'], $value['preview_file'], $value['md5_id'], $value['report_title'],$value['report_category']);
					$newItem .= str_ireplace($searchItem , $replaceItem, $rItem);
					
					// reportDetail
					$searchDetail = array('{blockId}', '{reportId}', '{reportFile}', '{report_title}', '{report_description}','{publish_date}','{price}','{price_euro}');
					$replaceDetail = array('b_'.$value['md5_id'], $value['md5_id'], urlencode($value['preview_file']), $value['report_title'], $value['description'], $value['publish_date'], number_format($value['price'],2,".",","), number_format($value['price_euro'],2,",","."));
					$newDetail .= str_ireplace($searchDetail , $replaceDetail, $rDetail);
					
					//row per 4 or when counter equals the amount of reports found
					if ($counter % 4 == 0 || $counter==count($r))
					{
						
						$body .= sprintf($rRow, $newItem,$newDetail);
							
						//new row begins
						$newRow = '';
						$newItem = '';
						$newDetail = '';
					
					}
					$currentYear = $value['year'];
					
					$counter++;
				}
			}
			else 
			{
				$body = sprintf($rRow, "<p>No results with your search query ".$this->getVars['search'].'</p>',$newDetail);
		
			}
				
		}
		else 
		{
			$body .= 'Could not find templates : '.$reportRow.'<br />'.$reportItem.'<br />'.$reportDetail;	
		}
		
		
		
		
		
		return $body .$archive;
		
	}
	
	private function getPublicReportYears($link='') {
		
		$years = '';
		
		if ($r = $this->queryDB(sprintf("SELECT YEAR(publish_date) as year FROM report WHERE fk_customer=%s GROUP BY year ORDER BY year DESC",$this->getSite())))
		{
			foreach ($r as $value) {
			
				
				$years .= '<a href="/reports/report-shop'.$link.'/'.$value['year'].'" class="archive">'.$value['year'].'</a> / ';
			}
			
		}
		
		return sprintf('<div class="basicContent"><div class=row width-100 text-centered"><p>View archive: %s</p></div></div>',trim($years,"/ "));
	
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

	public function getFooter()  {
		
		$footer = 'FinalCMS is een product van Final Media Copyright '.$this->getCurrentYear();
		
		return $footer;
	}
	
	
	/* with amount
	public function viewShoppingCart() {
		//$_SESSION['shoppingcart'] = array();
		$cart = '';
		if (isset($_SESSION['shoppingcart']))
		{
		
			foreach ($_SESSION['shoppingcart'] as $key => $value) 
			{
			
				(!isset($value['amount'])) ? ($value['amount'] =1):false;
				$cart .= '<tr><td>'.$value['report_title'].'</td><td><input type="text" id="amount" name="amount" value="'.$value['amount'].'" /></td><td>$ '.number_format($value['price'],"2",".",",").'</td>';
			}
		}
		return $cart;
	
	}
	*/
	public function viewShoppingCart($params) {
		//$_SESSION['shoppingcart'] = array();
		$cart = '';
		
		if (isset($_GET['getparam1']))
		{
			$query = sprintf("SELECT * FROM report WHERE md5(id)='%s'",$_GET['getparam1']);
			if ($r = $this->queryDB($query))
			{
				foreach ($_SESSION['shoppingcart'] as $k => $v) {
				    unset($_SESSION['shoppingcart'][$k]);
				}
				
				$_SESSION['shoppingcart'] = $r;
				
			}
		}
		
		
		if (isset($_SESSION['shoppingcart']))
		{
			
			$totalPrice = 0;
			$totalPriceEuro = 0;
			
			//test
			//$_SESSION['shoppingcart'][0]['optionalFee'] = json_encode(array('Standard Fee' => '100.50'));
			
			foreach ($_SESSION['shoppingcart'] as $key => $value) 
			{
				
				
				
				(!isset($value['amount'])) ? ($value['amount'] =1):false;
				(!isset($value['currency'])) ? ($value['currency'] ='$;'):false;
								
				
				
				
				if ($params[0] == 'reports' && isset($value['report_title']))
				{
					
					$report =true;
					$totalPrice += $value['price'];
					$totalPriceEuro += $value['price_euro'];
					if ($value['currency']=='$')
					{
						//dollar
						$amount = '$ '.number_format($value['price'],"2",".",",");
					}
					else 
					{
						//euro	
						$amount = '&euro; '.number_format($value['price_euro'],"2",",",".");
					}
					
					if (isset($params[1]) && $params[1])
					{
						$trashcan = '';
					}
					else 
					{
						$trashcan = '<a href="#"  class="deleteFromShoppingList"><img src="/images/frontend/trash-icon.png" width="11" height="13" alt="x" align="right" /></a>';	
					}
										
					$cart .= '<tr id="'.$value['md5_id'].'"><td>'.$value['report_title'].'</td><td valign="middle" style="white-space: nowrap">$ '.number_format($value['price'],"2",".",",").' / &euro; '.number_format($value['price_euro'],"2",",",".").' '.$trashcan.'</td>';
				}
				else if (isset($value['event_title']))
				{	
					$report =false;;
					
					
					
					if ($value['currency']=='$')
					{
						//dollar
						$amount = '$ '.number_format($value['amount'],"2",".",",");
						$totalPrice += $value['amount'];
						
					}
					else 
					{
						//euro	
						$amount = '&euro; '.number_format($value['amount'],"2",",",".");
						$totalPriceEuro += $value['amount'];
					}
									
					$cart .= '<tr id="'.$value['md5_id'].'"><td>'.$value['event_title'].'</td><td valign="middle" style="white-space: nowrap">'.$amount.'</td>';
					
					if (isset($value['optionalFee']))
					{
						$assocFee = json_decode($value['optionalFee'],true);
						foreach ($assocFee as $key2 => $value2) 
						{
							if ($value['currency']=='$')
							{
								$cart .= '<tr><td>'.$key2.'</td><td valign="middle" style="white-space: nowrap">$ '.number_format($value2,"2",".",",").'</td>';
								$totalPrice +=$value2;
							}
							else 
							{
								$cart .= '<tr><td>'.$key2.'</td><td valign="middle" style="white-space: nowrap">&euro; '.number_format($value2,"2",",",".").'</td>';	
								
								$totalPriceEuro +=$value2;
							}
							
							
						}					
					}
					
				}
			}
			
			
			if ($report)
			{
				$cart .= '<tfoot><tr class="bold" id="totalprice"><td>Total price</td><td >$ '.number_format($totalPrice,"2",".",",").' / &euro; '.number_format($totalPriceEuro,"2",",",".").'</td></tr></tfoot>';
			}
			else
			{
				if ($value['currency']=='$' && $totalPrice >0)
				{
					$cart .= '<tfoot><tr class="bold" id="totalprice"><td>Total price</td><td >$ '.number_format($totalPrice,"2",".",",").'</td></tr></tfoot>';
				}
				else if ($totalPriceEuro > 0) 
				{
					$cart .= '<tfoot><tr class="bold" id="totalprice"><td>Total price</td><td >&euro; '.number_format($totalPriceEuro,"2",",",".").'</td></tr></tfoot>';
				}
							
			}
			
		}
		
		if (isset($params[1]) && $params[1])
		{
			$this->emptyShoppingCart($params[0]);
		}
		
		
		
		return $cart;
	
	}
	
	private function emptyShoppingCart($type) {
	
		foreach ($_SESSION['shoppingcart'] as $key => $value) 
		{
			unset($_SESSION['shoppingcart'][$key]);	
		}
	
	}
	
	
	public function getChosenEvent() {
	
		$body = '';
		
		if (isset($_GET['getparam1']))
		{
			$query = sprintf("SELECT * FROM event WHERE fk_customer=%s AND md5_id='%s'",$this->getSite(),$_GET['getparam1']);
			if ($r = $this->queryDB($query))
			{
				$body ='<h2>'.$r[0]['event_title'].'</h2>';
			}
			else 
			{
				$body = 'You need to select an event first to register to it';	
			}
		}
		else 
		{
			$body = 'You need to select an event first to register to it';	
		}
		
		
		return $body;
		
	
	}
	
	
	public function getEventCurrency() {
	
		$body = '';
		
		if (isset($_GET['getparam1']))
		{
			$query = sprintf("SELECT * FROM event WHERE fk_customer=%s AND md5_id='%s'",$this->getSite(),$_GET['getparam1']);
			if ($r = $this->queryDB($query))
			{
			
				$body = $r[0]['currency'];
			}
			else 
			{
				$body = 'USD';	
			}
		}
				
		
		return $body;
		
	
	}
	
	
	public function getReportBanners($params) {
	
		$baseDir = $_SERVER['DOCUMENT_ROOT'].'/files/web/images/report-banners/';
		$fileHandler = new fileHandler(true);
		$files  = $fileHandler->ReadaDir($baseDir);
		
		$body = '';
		
		foreach ($files as $file) 
		{
			if (in_array($file['ext'], array('png','jpg','jpeg','gif')))
			{
				$body .='<img src="/files/web/images/report-banners/'.$file['name'].'" alt="" height="346" />';
			}
		}
		
		return $body;
		
	
	}
	
	public function getFees($params) {
	
		$tplFile = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . PUB_INCLUDES .  DIRECTORY_SEPARATOR . $params[0];//paymentfees.html
		$body = '<input type="hidden" name="paymentfee" id="paymentfee" value="" />';
		if (file_exists($tplFile))
		{
			if (isset($_GET['getparam1']))
			{
				$query = sprintf("SELECT * FROM event WHERE fk_customer=%s AND md5_id='%s'",$this->getSite(),$_GET['getparam1']);
				if ($r = $this->queryDB($query))
				{
					if (!empty($r[0]['currency']))
					{
						$tpl = file_get_contents($tplFile);
						
						$fees = '';
						
						($r[0]['currency']=='EUR') ? ($currency = '€'):($currency = '$');
						
						if (isset($r[0]['fees']) && !empty($r[0]['fees']))
						{
							$specialFees = json_decode($r[0]['fees']);
							
							
							if (isset($specialFees))
							{
								$c=0;
								foreach ($specialFees as $key => $value) 
								{
									//(!$c) ? ($checked = 'checked'):($checked = '');
									
									if (empty($value)) {  $currency = '';$value = '';}
									
									$fees .= '<li><input name="paymentfee_'.$c.'" id="paymentfee_'.$c.'" type="checkbox" value="'.$key.'" class="radio" data-prompt-position="bottomLeft" /> <label>'.$key.' '.$currency.' '.$value.'</label></li><br />';	
									$c++;
								}
								
							}
						
						}
				
												
						if (!empty($fees))
						{
							$body = sprintf($tpl,$fees);
						}
							
					}
					
				}
				
			}

		}
		
		return $body;
	}
	
	public function getRegFees($params) {
		
		$tplFile = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . PUB_INCLUDES .  DIRECTORY_SEPARATOR . $params[0];//paymentfees.html
		$body = '';
		if (file_exists($tplFile))
		{
			if (isset($_GET['getparam1']))
			{
				$query = sprintf("SELECT * FROM event WHERE fk_customer=%s AND md5_id='%s'",$this->getSite(),$_GET['getparam1']);
				if ($r = $this->queryDB($query))
				{
					if (!empty($r[0]['currency']))
					{
						$tpl = file_get_contents($tplFile);
						
						$fees = '';
						
						($r[0]['currency']=='EUR') ? ($currency = '€'):($currency = '$');
						
						if (isset($r[0]['regfee']) && !empty($r[0]['regfee']))
						{
							$regFees = json_decode($r[0]['regfee']);
							
							
							
							
							if (isset($regFees))
							{
								$c=0;
								foreach ($regFees as $key => $value) 
								{
									(!$c) ? ($checked = 'checked'):($checked = '');
									if (empty($value)) {  $currency = '';$value = '';}
									
									
									
									$threeweeksago = date("Y-m-d H:i:s").' - 3 week';
									$interval = strtotime($threeweeksago);
									
									//echo '<!--'. $r[0]['start_datetime'].' < '.date("Y-m-d H:i:s",$interval). '//-->';
									if ($r[0]['start_datetime'] < date("Y-m-d H:i:s",$interval))
									{
										
										if (strpos($key, "proceedings")!==false)
										{
											
											$fees .= '<li><input name="regfee" id="regfee" type="radio" value="'.$key.'" class="radio" data-prompt-position="bottomLeft" '.$checked.' /> <label>'.$key.' '.$currency.' '.$value.'</label></li><br />';
											$c++;
										}
																				
									}
									else 
									{
										
										$fees .= '<li><input name="regfee" id="regfee" type="radio" value="'.$key.'" class="radio" data-prompt-position="bottomLeft" '.$checked.' /> <label>'.$key.' '.$currency.' '.$value.'</label></li><br />';	
										$c++;										
									}
									
									
								}
								
							}
							
						
						}
				
												
						if (!empty($fees))
						{
							$body = sprintf($tpl,$fees);
						}
							
					}
					
				}
				
			}

		}
		
		return $body;
	}
	
	
}



?>