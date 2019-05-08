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
	
	/*
	* Initializes dynamic content
	*/
	public function getContent($params) {
		
		$templateFile = TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . $params[0]; //includes/item.html
		$body = '';
		if (file_exists($templateFile))
		{
			$tpl = file_get_contents($templateFile);
		
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
		$dom1->loadHTML($tpl);
		$dom2->loadHTML($body);
		
		$dit = new RecursiveIteratorIterator(
		    new RecursiveDOMIterator($dom1),
		    RecursiveIteratorIterator::SELF_FIRST
		);
		
		
		$dat = new RecursiveIteratorIterator(
		    new RecursiveDOMIterator($dom2),
		    RecursiveIteratorIterator::SELF_FIRST
		);
		
		
		foreach($dit as $tplNode) {
		    if($tplNode->nodeType === XML_ELEMENT_NODE && $tplNode->hasAttribute('contenteditable')) {
		       
		        
		        if ($tplNode->getAttribute('contenteditable')=='true')
		        {
					
					foreach($dat as $contentNode) {
					    if($contentNode->nodeType === XML_ELEMENT_NODE) {
					        
					        //replace elements dat are obscured by cke
					        if ($contentNode->hasAttribute('data-cke-realelement'))
					        {
					        	$fragment = $dom2->createDocumentFragment();
					        	$fragment->appendXML(urldecode($contentNode->getAttribute('data-cke-realelement')));
					        	$contentNode->parentNode->replaceChild($fragment,$contentNode);
					        }
					        
					        $contentId = $contentNode->getAttribute('id');
					        
					        
					        if (!empty($contentId) && $contentId == $tplNode->getAttribute('id'))
					        {
								//$tplNode->nodeValue = $contentNode->getAttribute('id'); //empty the nodevalue
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
	
	
	public function getCustomerName() {
		return $_SESSION['customerName'];	
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
				
				console.debug($(this).css("color"));
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
						
						(isset($params[1]))? ($value['page_url'] = '#'):false;
						
						$body .= sprintf($tpl, $value['page_url'],$classAdd,$value['page_title']);
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
					$cell .= sprintf($tcell, '','',$value['preview_file']);
					$cell .= sprintf($tcell, '','',$value['keywords']);
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
			
			if ($r = $this->queryDB(sprintf("SELECT * FROM event WHERE fk_customer=%s AND YEAR(start_datetime)=%s",$_SESSION['customerID'],$selectedYear)))
			{
				
								
				foreach ($r as $value) 
				{
					
					$cell = sprintf($tcell, '','',$value['event_title']);
					$cell .= sprintf($tcell, '','',$value['brochure']);
					$cell .= sprintf($tcell, '','',$value['keywords']);
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
		
		$query = sprintf("SELECT report_title,preview_file FROM report WHERE fk_customer=%s",$_SESSION['customerID']);
		$options = '';
		if ($r = $this->queryDB($query))
		{
			
			
			foreach ($files as $file) {
				
				$fileName = $file['name'];
				foreach ($r as $value) 
				{
					if ($file['name'] == $value['preview_file'])
					{
						$fileName .= ' ('.$value['report_title'].')';
					}
				}
		
				$options .= '<option value="'.$file['name'].'">'.html_entity_decode($fileName,ENT_QUOTES,'UTF-8').'</option>';
				
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
		
		$query = sprintf("SELECT event_title,preview_file FROM event WHERE fk_customer=%s",$_SESSION['customerID']);
		$options = '';
		if ($r = $this->queryDB($query))
		{
			
			
			foreach ($files as $file) {
				
				$fileName = $file['name'];
				foreach ($r as $value) 
				{
					if ($file['name'] == $value['brochure'])
					{
						$fileName .= ' ('.$value['event_title'].')';
					}
				}
		
				$options .= '<option value="'.$file['name'].'">'.html_entity_decode($fileName,ENT_QUOTES,'UTF-8').'</option>';
				
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
	
		$output = '<p>%s</p>';
		$events ='';
		
		if (isset($params[0]))
		{
			if (is_numeric($params[0]))
			{
				$queryLimit = 'LIMIT 0, '.$params[0];
			}
			else 
			{
				$queryLimit = 'LIMIT 0, 10';	
			}
			
		}
		
		
		if (isset($params[1]))
		{
			$queryCond = 'AND start_datetime < NOW()';				
		}
		else 
		{
			$queryCond = 'AND start_datetime >= NOW()';	
		}
		
		if ($r = $this->queryDB(sprintf("SELECT * FROM event WHERE fk_customer=%s %s AND planned=1 %s",$_SESSION['customerID'],$queryCond,$queryLimit)))
		{
			
							
			foreach ($r as $value) 
			{
				$events .= ''.$value['event_title'].' ('.date("Y/m/d",strtotime($value['start_datetime'])).')<br />';							
			}
		}
				
		return sprintf($output,$events);
	
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
			
			if (isset($this->getVars['search']) && !empty($this->getVars['search']))
			{
				$searchQuery = ' AND (report_title like "%'.htmlentities($this->getVars['search'],ENT_QUOTES,'UTF-8').'%"  OR keywords like "%'.htmlentities($this->getVars['search'],ENT_QUOTES,'UTF-8').'%" OR description like "%'.htmlentities($this->getVars['search'],ENT_QUOTES,'UTF-8').'%")';
			}
			else 
			{
				$searchQuery = '';	
			}
			
			if (isset($_GET['getparam1']) && !empty($_GET['getparam1']))
			{
				$searchQuery = ' AND report_category="'.htmlentities($_GET['getparam1'],ENT_QUOTES,'UTF-8').'"';				
			}
			
			
			
			$query = sprintf("SELECT * FROM report WHERE fk_customer=%s %s LIMIT 0,20",$_SESSION['customerID'],$searchQuery);
			
			if ($r = $this->queryDB($query))
			{
				$counter =1;
				
				foreach ($r as $value) 
				{
					
					// reportItem
					$searchItem = array('{blockId}', '{reportFile}', '{reportId}', '{report_title}','{report_category}');
					$replaceItem = array('b_'.$value['md5_id'], $value['preview_file'], $value['md5_id'], $value['report_title'],$value['report_category']);
					$newItem .= str_ireplace($searchItem , $replaceItem, $rItem);
					
					// reportDetail
					$searchDetail = array('{blockId}', '{reportId}', '{reportFile}', '{report_title}', '{report_description}');
					$replaceDetail = array('b_'.$value['md5_id'], $value['md5_id'], $value['preview_file'], $value['report_title'], $value['description']);
					$newDetail .= str_ireplace($searchDetail , $replaceDetail, $rDetail);
					
					
					if ($counter % 4 == 0 || count($r) < 4)
					{
						
						$body .= sprintf($rRow, $newItem,$newDetail);
						
						
						
						//new row begins
						$newRow = '';
						$newItem = '';
						$newDetail = '';
					
					}
					
					
					$counter++;
				}
			}
			else 
			{
				$body = sprintf($rRow, "<p>No results with your search query ".htmlentities($this->getVars['search'],ENT_QUOTES,'UTF-8').'</p>',$newDetail);
		
			}
				
		}
		else 
		{
			$body .= 'Could not find templates : '.$reportRow.'<br />'.$reportItem.'<br />'.$reportDetail;	
		}
		
		return $body;
		
	}
	
	

	public function getFooter()  {
		
		$footer = 'FinalCMS is een product van Final Media Copyright '.$this->getCurrentYear();
		
		return $footer;
	}
	
	public function tester() {
	
		return  $_SESSION['ftpfolder'];
	}

}



?>