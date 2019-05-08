<?php

class pageTree extends controller {

	private $__translator,$__result=array();
	
	public function __construct($method=false,$params=false) {
		
		parent::__construct();
		$this->__translator = new Translator();
		
		if (method_exists(__CLASS__, $method))
		{
			
			$this->$method($params);
		}
		else if (isset($params['button']) && method_exists(__CLASS__, $params['button']))
		{
			$method = $params['button'];
			$this->$method($params);
		}
		else
		{
			$this->returnFalse("Unknown method $method");
		}
	}
	
	
	private function getPageTree($params) {
				
		
		//get all sites
		if ($site = $this->queryDB(sprintf("SELECT * FROM site WHERE fk_customer=%s",$_SESSION['customerID'])))
		{
			$result = array();
			
			
			
			foreach ($site as $skey => $svalue) 
			{
				//add the site to the array
				$result[] =  array(
					"id" => 'S'.$svalue['id'],
					"text" => $svalue['name'],
					"parentid" => "-1",
					"icon" => "",
					"level" => 0,
					"value" => base64_encode(json_encode(array("t"=> "site","i"=>$svalue['id'])) )
				);
				
				
				//get all pages on each site
				if ($page = $this->queryDB(sprintf("SELECT * FROM page WHERE fk_site=%s AND language='%s' ORDER BY position ASC",$svalue['id'],$svalue['default_lang'])))
				{
					foreach ($page as $pkey => $pvalue) 
					{
						
						//its a submenu
						if ($pvalue['fk_page']>0)
						{
							$parentID = 'P'.$pvalue['fk_page'];
							$lvl = 2;
						}
						else 
						{
							$parentID = 'S'.$svalue['id'];
							$lvl = 1;
						}
						//add the page to the array
						$result[] =  array(
							"id" => 'P'.$pvalue['id'],
							"text" => $pvalue['page_name'],
							"parentid" => $parentID,
							"icon" => "",
							"level" => $lvl,
							"value" => base64_encode(json_encode(array("t"=> "page","p"=>$pvalue['fk_page'],"i"=>$pvalue['id'],"pos"=>$pvalue['position'],"site"=>$svalue['id'])) )
						);
						
						
						
					}
					
					//add the item per page
					if ($item = $this->queryDB(sprintf("SELECT * FROM content ORDER BY fk_page,position ASC",$pvalue['id'])))
					{
						foreach ($item as $ikey => $ivalue) 
						{
							$result[] =  array(
								"id" => 'C'.$svalue['id'].'.'.$pvalue['id'].'.'.$ivalue['id'],
								"text" => "Page content",
								"parentid" => 'P'.$ivalue['fk_page'],
								"icon" => "",
								"level" => ($lvl+1),
								"value" => base64_encode(json_encode(array("t"=> "content","p"=>$ivalue['fk_page'],"i"=>$ivalue['id'],"pos"=>$ivalue['position'],"site"=>$svalue['id'])) )
							);
						}
						
					}
					
					

				}
	
			}
			
			$this->__result['result'] = 'ok';
			$this->__result['json'] = $result;
			echo json_encode($this->__result);
			
		}
		else 
		{
			$this->returnFalse($this->__translator->getTranslation('ajax-error'));
		}
		
		
	}
	
	private function getThumbNail($page_url,$pageId,$mod_date,$size='F',$view='desktop') {
	
		
		$cache = 0;
		$url = "https://dev.finalmedia.nl".$page_url."?view=".$view;
		$hash = md5($url.'sheeponfire');
		$accountkey='cfd84f';
		$ext = "jpg";
		
		$fileName = "/files/thumb/".$view."-page_".$pageId.".".strtolower($ext);
		
		if (file_exists($_SERVER['DOCUMENT_ROOT'].$fileName))
		{
			if (strtotime($mod_date) > filemtime($_SERVER['DOCUMENT_ROOT'].$fileName))
			{
				//new mod_date so lets try to get a new thumb
				
				if ($data = file_get_contents("http://api.screenshotmachine.com/?key=01f0d0&url=".$url."&size=".$size."&format=".$ext."&hash=".$hash."&cacheLimit=".$cache."&timeout=400"))
				{
					//unlink the old one and put the new one
					unlink($_SERVER['DOCUMENT_ROOT'].$fileName);
					file_put_contents($_SERVER['DOCUMENT_ROOT'].$fileName, $data);
				}
			
			}
			else 
			{
				//get the cached file	
			}
		}
		else 
		{
			//no file try to get it
			if ($data = file_get_contents("http://api.screenshotmachine.com/?key=".$accountkey."&url=".$url."&size=".$size."&format=".$ext."&hash=".$hash."&cacheLimit=".$cache."&timeout=0"))
			{
				file_put_contents($_SERVER['DOCUMENT_ROOT'].$fileName, $data);
			}
			
				
		}
	
		return $fileName;
		
	}
	
	
	
	private function getItem($params) {
		
		
		$item = json_decode(base64_decode($params['item']));
		//$this->returnMessage($item->t);
		
		if (isset($item->t) && $item->t == 'site')
		{
			$this->__result['dialog'] = SECUREPATH .'/pages/editsite';
		}
		else if (isset($item->t) && $item->t == 'page')
		{
			
			$query = sprintf("SELECT * FROM page WHERE id=%s AND fk_customer=%s",$item->i,$_SESSION['customerID']);
			if ($r = $this->queryDB($query))
			{
				(isset($r[0]['id'])) ? ($r[0]['itemID'] =  $r[0]['id']):false;
				(empty($r[0]['meta_author'])) ?($r[0]['meta_author'] = $_SESSION['account'][0]['firstname'].' '.$_SESSION['account'][0]['lastname']):false;
				($r[0]['publish_date']!='0000-00-00 00:00:00') ? ($r[0]['publish_date'] = strftime("%d/%m/%Y %H:%M",strtotime($r[0]['publish_date']))):($r[0]['publish_date'] = '');
				($r[0]['archive_date']!='0000-00-00 00:00:00') ? ($r[0]['archive_date'] = strftime("%d/%m/%Y %H:%M",strtotime($r[0]['archive_date']))):($r[0]['archive_date'] ='');
				
				
				//($r[0]['page_url']=='/' || empty($r[0]['page_url'])) ? ($r[0]['page_url']='/home'):false;
				
				//thumbnails			
				$query2 = sprintf("SELECT MAX(mod_date) as mod_date FROM content WHERE fk_page=%s AND fk_customer=%s",$r[0]['id'],$_SESSION['customerID']);
				if ($c = $this->queryDB($query2))
				{
					
					$r[0]['thumbnailDesktop'] = $this->getThumbNail($r[0]['page_url'],$r[0]['id'],$c[0]['mod_date'],'F','desktop');
					$r[0]['thumbnailPhone'] = $this->getThumbNail($r[0]['page_url'],$r[0]['id'],$c[0]['mod_date'],'Fmob','mobile');
					//$r[0]['thumbnailDesktop']  = $url = "https://dev.finalmedia.nl".$r[0]['page_url']."?view=desktop";
				
				}
				else 
				{
					//default Image	
				}
				
				
				foreach ($r[0] as $key => $value) 
				{
					$r[0][$key] = html_entity_decode($value,ENT_QUOTES,'UTF-8');	
				}
				
				if (isset($r[0]['is_home']) && $r[0]['is_home'])
				{
					$this->__result['disabledFields'] = array("publish_date","archive_date","state","page_url","cms_template");
				}
				$this->__result['formData'] = $r; 
			}
			else
			{
				$this->returnMessage($this->__translator->getTranslation('database-error'));
				exit;
			}
			
			$this->__result['dialog'] = SECUREPATH .'/pages/editpage?p='.$item->p;
			
			
		}
		else if (isset($item->t) && $item->t == 'content') 
		{
			
			$query = sprintf("SELECT * FROM content WHERE id=%s AND fk_customer=%s",$item->i,$_SESSION['customerID']);
			if ($r = $this->queryDB($query))
			{
				(isset($r[0]['id'])) ? ($r[0]['itemID'] =  $r[0]['id']):false;
				$this->__result['formData'] = $r;
			}
			$this->__result['dialog'] = SECUREPATH .'/pages/edititem?p='.$item->p.'&i='.$item->i;
		}
		
		
		$this->__result['func'] = 'loadPage';
		$this->__result['target'] = '#main';
		$this->__result['title'] = 'Edit page';
		
		//$this->__result['action'] = $action;
		//($formdata) ? (	$this->__result['formData'] = $formdata):false;
		echo json_encode($this->__result);
		
	}
	
	private function removePage($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
		
		if (isset($params['parentid']) && isset($params['value']))
		{
			$selectedItem = json_decode(base64_decode($params['value']));
			
			if ($selectedItem->t == 'page')
			{
				//lets remove this page and its content
				if ($this->deleteRow(sprintf("DELETE FROM page WHERE id=%s AND fk_customer=%s",$selectedItem->i,$_SESSION['customerID'])))
				{
					$this->deleteRow(sprintf("DELETE FROM content WHERE fk_page=%s AND fk_customer=%s",$selectedItem->i,$_SESSION['customerID']));
					
					$this->__result['result'] = 'nok';
					$this->__result['json'] = array();
					echo json_encode($this->__result);
				}
				else 
				{
					$this->returnFalse($this->__translator->getTranslation('page-not-exist'));	
				}
				
			}
		
		}
		
	
	}
	
	private function addPage($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
		
		if (isset($params['parentid']) && isset($params['value']))
		{
			
			$selectedItem = json_decode(base64_decode($params['value']));
			
			
			//$selectedItem->t = parent table
			
			if ($selectedItem->t == 'site')
			{
				
				if ($site = $this->queryDB(sprintf("SELECT id,default_lang from site WHERE id=%s AND fk_customer=%s",$params['parentid'],$_SESSION['customerID'])))
				{
					$parentId = 0;
					$siteId = $site[0]['id'];
					$lang = $site[0]['default_lang'];
					$lvl = 1;
				}
				else 
				{
					
					$this->returnFalse($this->__translator->getTranslation('page-not-exist'));	
				}
							
			}
			else if ($selectedItem->t == 'page')
			{
				if ($page = $this->queryDB(sprintf("SELECT fk_site,default_lang from page WHERE id=%s AND fk_customer=%s",$params['parentid'],$_SESSION['customerID'])))
				{
					$parentId = $params['parentid'];
					$siteId = $page[0]['fk_site'];
					$lang = $page[0]['language'];
					$lvl =2;
				}
				else 
				{
					$this->returnFalse($this->__translator->getTranslation('page-not-exist'));	
				}
				
			}
			else 
			{
				// you cannot add anything to an item
				$this->returnFalse($this->__translator->getTranslation('ajax-error'));
			}
			
			$defaultIcon = '';
			$defaultTemplate = 'article.html';
			$defaultState = 0;
			
			$defaultPageUrl = preg_replace("/[^A-Za-z0-9\-]/",'',str_replace(" ","-",$params['label']));   
			$params['label']  = htmlentities($params['label'],ENT_QUOTES,'UTF-8');
				
			$query = sprintf("INSERT INTO page (position,page_name,page_title,page_url,template,state,language,icon,fk_page,fk_site,fk_customer,create_date,mod_date) VALUES(99,'%s','%s','/%s','%s',%s,'%s','%s',%s,%s,%s,NOW(),NOW())",$params['label'],$params['label'],$defaultPageUrl,$defaultTemplate, $defaultState,$lang,$defaultIcon, $parentId,$siteId,$_SESSION['customerID']);
			
			
			
			if ($newInsert = $this->insertRow($query))
			{
				//insert content for this page right away
				
				if ($content_id = $this->insertRow(sprintf("INSERT INTO content SET create_date=NOW(),mod_date=NOW(),fk_page=%s,name='%s',fk_customer=%s",$newInsert,'Content',$_SESSION['customerID'])))
				{
					$result[] =  array(
						"id" => 'P'.$newInsert,
						"text" => $params['label'],
						"parentid" => $parentId,
						"icon" => $defaultIcon,
						"level" => $lvl,
						"value" => base64_encode(json_encode(array("t"=> "page","p"=>$parentId,"i"=>$newInsert,"pos"=>'99')) )
					);
					
					$this->__result['result'] = 'ok';
					$this->__result['json'] = $result;
					echo json_encode($this->__result);
				}
				else 
				{
					$this->returnFalse($this->__translator->getTranslation('database-error'));
				}
				
			}
			else 
			{
				$this->returnFalse($this->__translator->getTranslation('database-error'));
			}
			
				
		
		}
		else
		{
			$this->returnFalse($params['parentid']);
		}
	}
	
	
	private function buildQuery($table,$params,$skipMore=false) {
		
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
		foreach ($columns as $columnKey => $columnVal) 
		{
			
			if (!in_array($columnVal['Field'], $skipFields))
			{
				//state is a checkbox with enum value
				if ($columnVal['Field']=='state' || $columnVal['Field']=='is_menu')
				{
					if (isset($params[$columnVal['Field']]))
					{
						$queryAdd .= $columnVal['Field']."=1,";
					}
					else 
					{
						$queryAdd .= $columnVal['Field']."=0,";	
					}
				}
				else if ($columnVal['Field']=='publish_date' || $columnVal['Field']=='archive_date')
				{
					
					($columnVal['Field']=='publish_date' && empty($params[$columnVal['Field']]))? ($params[$columnVal['Field']]= date("Y-m-d H:i:s")):false;
					
					if ($columnVal['Field']=='archive_date' && empty($params[$columnVal['Field']]))
					{
						$queryAdd .= $columnVal['Field']."='0000-00-00 00:00:00',";
					}
					else 
					{
						$params[$columnVal['Field']]  = str_replace("/", "-",$params[$columnVal['Field']]);
						if (!empty($params[$columnVal['Field']]))
						{
							$queryAdd .= $columnVal['Field']."='".date("Y-m-d H:i:s", strtotime($params[$columnVal['Field']]))."',";		
						}	
					}
				}
				else if ($columnVal['Field']=='page_url' && strpos($params[$columnVal['Field']], "/")===false)
				{
					$queryAdd .= $columnVal['Field']."='/".$params[$columnVal['Field']]."',";
				}
				else if (isset($params[$columnVal['Field']]) && !in_array($columnVal['Field'], $skipFields))
				{
					$queryAdd .= $columnVal['Field']."='".$params[$columnVal['Field']]."',";
				}
			}
		}
		
		return $queryAdd;
	
	}
	
	//check required fields
	private function requiredFields($requiredFields,$params) {
	
		foreach ($requiredFields as $key => $value) 
		{
			if (isset($params[$value]) && !empty($params[$value]))
			{
				unset($requiredFields[$key]);
			}
		}
		
		if (count($requiredFields) > 0)
		{
			return false;	
		}
		else 
		{
			return true;
		}
	}
	
	private function storePageProperties($params) {
		
	
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
		
		if (!$this->requiredFields(array("page_name","page_title","page_url","publish_date"),$params))
		{
			$this->returnMessage($this->__translator->getTranslation('required-fields'));	
			exit();
		}
		
		if (!empty($params['publish_date']) && !empty($params['archive_date']) && $params['archive_date'] < $params['publish_date'])
		{
			$this->returnMessage($this->__translator->getTranslation('archive_date-lower-publish_date'));	
		}
		else 
		{
			
			$skipMoreFields = false;
			//check if current page is home
			if ($isHome = $this->queryDB(sprintf("SELECT is_home FROM page WHERE id=%s AND fk_customer=%s",$params['id'],$_SESSION['customerID'])))
			{
				if ($isHome[0]['is_home'])
				{
					$skipMoreFields = array("publish_date","archive_date","state","page_url","cms_template");
				}
			}
			
			$params['page_name'] = substr($params['page_name'], 0,20);
			
			$queryAdd = $this->buildQuery("page",$params,$skipMoreFields);
			$query = sprintf("UPDATE page SET %s WHERE id=%s AND fk_customer=%s",rtrim($queryAdd,","),$params['id'],$_SESSION['customerID']);
			if ($this->updateRow($query))
			{
				$this->__result['redirect'] = 'refreshItem';
				$this->returnMessage($this->__translator->getTranslation('page-update-success'),"redirect");	
			}
			else 
			{
				$this->returnFalse($this->__translator->getTranslation('database-error'));	
			}
		}
				
		
	
	}
	
	private function addItem($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
		
		if (isset($params['parentid']))
		{
			$p = explode(".", $params['parentid']);
			//needs to be at least 2 for it to be able to hold an item
			if (count($p) >=2)
			{
				//find the page and check if it exists for this customer
				if ($item = $this->queryDB(sprintf("SELECT p.id as page_id,s.id as site_id FROM page p LEFT JOIN site s ON p.fk_site=s.id WHERE s.id=%s and p.id=%s AND s.fk_customer=%s",$p[0],end($p),$_SESSION['customerID'])))
				{
				
					if ($content_id = $this->insertRow(sprintf("INSERT INTO content SET fk_page=%s,name='%s',fk_customer=%s",end($p),$params['label'],$_SESSION['customerID'])))
					{
						$result = array(
							"id" => $content_id,
							"label" => $params['label'],
							"icon" => ""
						);
						
						$this->__result['result'] = 'ok';
						$this->__result['json'] = $result;
						echo json_encode($this->__result);
					}
					else 
					{
						$this->returnFalse($this->__translator->getTranslation('database-error'));
					}
				}
				else 
				{
					$this->returnFalse($this->__translator->getTranslation('database-error'));	
				}
			}
			else  
			{
				//$this->returnFalse($params['parentid']);	
				//we have page lets add one item
				//find the page and check if it exists for this customer
				if ($item = $this->queryDB(sprintf("SELECT id FROM page WHERE id=%s AND fk_customer=%s",$params['parentid'],$_SESSION['customerID'])))
				{
				
					if ($content_id = $this->insertRow(sprintf("INSERT INTO content SET fk_page=%s,name='%s',fk_customer=%s",$params['parentid'],$params['label'],$_SESSION['customerID'])))
					{
						$result = array(array(
							"id" => $content_id,
							"label" => $params['label'],
							"icon" => ""
						));
						
						$this->__result['result'] = 'ok';
						$this->__result['json'] = $result;
						echo json_encode($this->__result);
					}
					else 
					{
						$this->returnFalse($this->__translator->getTranslation('database-error'));
					}
				}
				else 
				{
					$this->returnFalse($this->__translator->getTranslation('database-error'));	
				}
			
			}
			
		
		}
		else
		{
			$this->returnFalse($params['parentid']);
		}
	}
	
	private function moveItem($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
		
		$item = json_decode(base64_decode($params['itemId']));
		$dropItem = json_decode(base64_decode($params['dropItemId']));
		
		//SELECT id,position FROM page WHERE fk_customer=2 AND position > 7 AND fk_page=0 ORDER BY position ASC
		
		
		//select all moveableitems from database
		
		if (isset($params['dropPosition']) && $this->getMoveAbleItems($item))
		{
			
			$newpos = false;
			
			switch($params['dropPosition'])
			{
				case 'inside': 
						//we need to drop it inside but are you allowed to do it?
						if ($dropItem->t != 'content' && $params['dropItemLevel'] ==1)
						{
							if ($r = $this->queryDB(sprintf("SELECT MAX(position) as pos FROM %s WHERE fk_customer=%s AND fk_page=%s",$item->t,$_SESSION['customerID'],$dropItem->i)))
							{
								$pos = $r[0]['pos']+1;							
							}
							else 
							{
								$pos = 1;
							}
							
							if ($dropItem->i != $item->i)
							{
							
								if ($insert = $this->updateRow(sprintf("UPDATE %s SET fk_page=%s,position=%s WHERE fk_customer=%s AND id=%s", $item->t, $dropItem->i,$pos,$_SESSION['customerID'],$item->i)))
								{
									$this->__result['result'] = 'ok';						
								}
								else 
								{
									$this->__result['result'] = 'nok'.sprintf("UPDATE %s SET fk_page=%s,position=%s WHERE fk_customer=%s AND id=%s", $item->t, $dropItem->p,$pos,$_SESSION['customerID'],$item->i);	
								}
							}
							else 
							{
								$this->__result['result'] = 'nok';	
							}
						}
						else 
						{
							$this->__result['result'] = 'nok';	
						}
		
						break;
				case 'before': 
						if ($items = $this->queryDB(sprintf("SELECT id,position FROM %s WHERE fk_customer=%s AND position >= %s AND fk_page=%s ORDER BY position ASC",$dropItem->t,$_SESSION['customerID'],$dropItem->pos,$dropItem->p )))
						{
							if ($item->t == 'content' && $dropItem->p<=0)
							{
								$this->__result['result'] = 'nok';	
										
								echo json_encode($this->__result);
								exit;
							}
							
							foreach ($items as $key => $value) 
							{
								if ($value['id'] == $dropItem->i)
								{
									//set position of dropitem to item 
									$pos = $value['position'];
									$this->updateRow(sprintf("UPDATE %s SET position=%s, fk_page=%s WHERE fk_customer=%s AND id=%s",$item->t,$value['position'],$dropItem->p, $_SESSION['customerID'],$item->i ));
									
									//new post for dropItem
									$newpos = $pos+1;
									
									//update dropItem
									$this->updateRow(sprintf("UPDATE %s SET position=%s, fk_page=%s WHERE fk_customer=%s AND id=%s",$dropItem->t,$newpos,$dropItem->p, $_SESSION['customerID'],$value['id']));
									
									$newpos++;
								
								}
								else if ($value['id'] != $item->i)
								{
									
									//update rest but not dropitem or item
									$this->updateRow(sprintf("UPDATE %s SET position=%s, fk_page=%s WHERE fk_customer=%s AND id=%s",$item->t,$newpos,$dropItem->p,$_SESSION['customerID'],$value['id'] ));	
									$newpos++;
								}
									
							}
							
							$this->reOrderItemsByNum($dropItem->t, $dropItem->p);
						
						}
						$this->__result['result'] = 'ok';
						
						
						break;
				case 'after' : 
						if ($items = $this->queryDB(sprintf("SELECT id,position FROM %s WHERE fk_customer=%s AND fk_page=%s AND position >= %s ORDER BY position ASC",$dropItem->t,$_SESSION['customerID'],$dropItem->p,$dropItem->pos )))
						{
							
							if ($item->t == 'content' && $dropItem->p<=0)
							{
								$this->__result['result'] = 'nok';	
										
								echo json_encode($this->__result);
								exit;
							}
							
							foreach ($items as $key => $value) 
							{
								
								if ($value['id'] == $dropItem->i)
								{
									$newpos = $value['position']+1;
									
									$this->updateRow(sprintf("UPDATE %s SET position=%s, fk_page=%s WHERE fk_customer=%s AND id=%s",$item->t,$newpos,$dropItem->p, $_SESSION['customerID'],$item->i ));
									
									$newpos++;
								}
								else if ($value['id'] != $item->i)
								{
									$this->updateRow(sprintf("UPDATE %s SET position=%s, fk_page=%s WHERE fk_customer=%s AND id=%s",$item->t,$newpos,$dropItem->p,$_SESSION['customerID'],$value['id'] ));	
									$newpos++;
								}
							
							}
							
							
							$this->reOrderItemsByNum($dropItem->t, $dropItem->p);
							
							
							
						
						}
						$this->__result['result'] = 'ok';
						
						break;
			
			
			
			}
		
		}
		
		
		$result = array(
			
			
			"itemId" => 'id:'.$item->i.' parent:'.$item->p.' type:'.$item->t .' position:'.$item->pos,
			"dropPosition" => $params['dropPosition'],
			"dropItemId" => 'id:'.$dropItem->i.' parent:'.$dropItem->p.' type:'.$dropItem->t .' position:'.$dropItem->pos
		);
		
		
		$this->__result['json'] = $result;
		echo json_encode($this->__result);
		
		// data: {'action' : 'moveItem','itemId':item.id,'dropPosition':dropPosition,'dropItemId':dropItem.id}
	}
	
	//which items are allowed to be moved?
	private function getMoveAbleItems($item) {
		
		$moveable = false;
		
		if ($r = $this->queryDB(sprintf("SELECT value FROM config WHERE name='moveable_items' AND fk_site=%s",$item->site)))
		{
			$i = explode(",", $r[0]['value']);
			foreach ($i as $key => $value) 
			{
				if ($value==$item->t)
				{
					$moveable = true;
				}
			}
			
			
		}
		
		return $moveable;	
		
	
	}
	
	
	private function reOrderItemsByNum($table, $foreignKey) {
		//reorder all by beginning at pos 1
		if ($items = $this->queryDB(sprintf("SELECT * FROM %s WHERE fk_customer=%s AND fk_page=%s ORDER BY position ASC",$table,$_SESSION['customerID'],$foreignKey)))
		{
			
			$newpos = 1;
			foreach ($items as $key => $value) 
			{
				$this->updateRow(sprintf("UPDATE %s SET position=%s WHERE fk_customer=%s AND id=%s",$table,$newpos,$_SESSION['customerID'],$value['id']));
				$newpos++;
			}
		
			return true;													
		}
		else {
			return false;
		}
	
	}
	
	private function resetItem($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
		
		if (isset($params['itemID']))
		{
			//lets update
			$query1 = sprintf("SELECT text FROM content WHERE id=%s AND fk_customer=%s",$params['itemID'],$_SESSION['customerID']);
			
			if ($t = $this->queryDB($query1))
			{
				$query2 = sprintf("UPDATE content SET text ='%s',mod_date=NOW() WHERE id=%s AND fk_customer=%s",'',$params['itemID'],$_SESSION['customerID']);
				if ($this->updateRow($query2))
				{
					
					
					$this->__result['redirect'] = 'refreshItem';
					$this->returnMessage("Item reset to template","redirect");
				}
				
				else 
				{
					$this->returnMessage($this->__translator->getTranslation('database-error'));
				}
			}
		
		}
	}
	
	private function saveItem($params) {
		
		if (!$this->checkEditRights())	 
		{
			$this->returnMessage($this->__translator->getTranslation('no-editrights'));
			exit;
		}
		
		if (isset($params['itemID']) && isset($params['data']))
		{
			//lets update
			$query1 = sprintf("SELECT text FROM content WHERE id=%s AND fk_customer=%s",$params['itemID'],$_SESSION['customerID']);
			
			if ($t = $this->queryDB($query1))
			{
			
				
				if ($t != $params['data'])
				{	
				
					//$thumbnail = $this->createThumbNail($params['itemID']);
					//$this->returnMessage("Item updated");
					$query2 = sprintf("UPDATE content SET text ='%s',mod_date=NOW() WHERE id=%s AND fk_customer=%s",$params['data'],$params['itemID'],$_SESSION['customerID']);
					
					if ($this->updateRow($query2))
					{
						
						$this->returnMessage("Item updated");
					}
					
					else 
					{
						$this->returnMessage($this->__translator->getTranslation('database-error'));
					}
				
				}
				else 
				{
					$this->returnMessage("Data was not changed");	
				}
			}
		
		}
		else 
		{
			$this->returnMessage("itemID of data is leeg");		
		}			
	}
	
	private function createThumbNail() {
		
		require($_SERVER['DOCUMENT_ROOT']."/lib/application/webthumbnail.php");
		
		$path = tempnam('/files/thumb', 'thumb-');
		
		$thumb = new Webthumbnail("http://webthumbnail.org");
		$thumb
		    ->setWidth(320)
		    ->setHeight(240)
		    ->setFormat('png')
		    ->captureToFile($path);
		
		return $path;
	
	}
	
	private function returnFalse($msg) {
		$this->__result['result'] = 'nok';
		$this->__result['msg'] = $msg;
		echo json_encode($this->__result);
	
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
		if ($key=='data')
		{
			$params[$key] = htmlspecialchars_decode(htmlentities($value, ENT_QUOTES, 'UTF-8'),ENT_QUOTES);
		}
		else 
		{
			$params[$key] = htmlentities(strip_tags($value), ENT_QUOTES, 'UTF-8');
		}
		
		
	}
}
$ajaxcall = new pageTree($action,$params);



?>