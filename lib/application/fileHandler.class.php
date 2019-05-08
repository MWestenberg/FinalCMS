<?php


class fileHandler {

	private $__Exception;
	protected $_debug =false,$_files = false,$_dirTree = array();
	public $_currentDir="";
	
	public function __construct($debug=false) {
		$this->_debug= $debug;
		$this->__Exception = new errorHandler;
	}

	/*
	* read's a an entire directory into an array
	* skips subdirectories only the files are returned
	* @dir: the directory that needs to be read
	*/
	public function ReadaDir($dir) {
		if ($dir[strlen($dir)-1] != '/') {$dir .= '/'; }
		if (!is_dir($dir)) 
		{ 
			$this->setException(__FUNCTION__,"Directory $dir does not exist");
			($this->_debug)? ($this->getException(__FUNCTION__)):false;
			return false; 
		}
		else 
		{
			$files = array(); $subdirs=array();
			$this->_currentDir = $dir;
			$dir_handle  = opendir($dir);
			$dir_objects = array();
			while ($object = readdir($dir_handle)) {
				if (!in_array($object, array('.','..')))
				{
					//get files only
					$filename    = $dir . $object;
					$file_object = array(
						'name' => $object,
						'size' => ceil((filesize($filename)/1024))." KB",
						'type' => filetype($filename),
						'time' => date("d F Y H:i:s", filemtime($filename)),
						'ext'  => @pathinfo($filename, PATHINFO_EXTENSION)
					);
					
					if ($file_object['type']=='dir')
					{
						$subdirs[] = $file_object;
					}
					else 
					{
						$files[] = $file_object;
					}
				
				}
				
					
			}
			
			usort($files, array($this,"sortByOrder"));
			usort($subdirs, array($this,"sortByOrder"));
			
			$this->_files = array_merge($subdirs,$files);
			
			return $this->_files;				
						
		}
	}
		
	private function sortByOrder($a, $b) {
	    return $a['name'] > $b['name'];
	}
		
	/*
	*	create a dir
	*/
	
	public function createDirectory($dir) {
		
		if (isset($_SESSION['usertype']) && $_SESSION['usertype'] >= 90)
		{
			if (!is_dir($dir))
			{	
				$oldmask = umask(0);
				if (mkdir($dir,0777,true))
				{
					umask($oldmask);
					return true;
				}
				else 
				{
					$this->setException(__FUNCTION__,"Error when creating a directory");
					($this->_debug)? ($this->getException(__FUNCTION__)):false;
					return false;	
				}
			}
			else 
			{
				$this->setException(__FUNCTION__,"Directory already exists");
				($this->_debug)? ($this->getException(__FUNCTION__)):false;
				return false;	
			}		
		}
		else 
		{
			$this->setException(__FUNCTION__,"Administrator rights are required when creating a directory");
			($this->_debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		}
			
	}
	
	public function renameDirectory($old, $new) {
		
		if (isset($_SESSION['usertype']) && $_SESSION['usertype'] >= 90)
		{
			if ($old==$new && is_dir($new))
			{	
				return true; //nothing to move
			}
			else 
			{
				if (is_dir($old) && !is_dir($new))
				{
					$oldmask = umask(0);
					if (rename($old,$new))
					{
						umask($oldmask);
						return true;
					}
					else 
					{
						$this->setException(__FUNCTION__,"Error when moving a directory");
						($this->_debug)? ($this->getException(__FUNCTION__)):false;
						return false;	
					}
				}
				else if (!is_dir($old) && !is_dir($new))
				{
					return $this->createDirectory($new);
				}
				else 
				{
					$this->setException(__FUNCTION__,"Directory already exists");
					($this->_debug)? ($this->getException(__FUNCTION__)):false;
					return false;	
				}	
			}

		}
		else 
		{
			$this->setException(__FUNCTION__,"Administrator rights are required when creating a directory");
			($this->_debug)? ($this->getException(__FUNCTION__)):false;
			return false;	
		} 
	
	}
	
		
	public function getDir($dir) {
			
		if (!$this->_subdirs)
		{
			$this->setException(__FUNCTION__,"Directory does not exist or make sure ".__CLASS__."->ReadaDir(dir,true) is excecuted first.");
			($this->_debug)? ($this->getException(__FUNCTION__)):false;
			return false;
		}
		else 
		{
			foreach ($this->_subdirs as $key => $value) {
				if ($dir == $value['name'])
				{
					return $this->_subdirs[$key];
				}
			} 
			
			$this->setException(__FUNCTION__,"File $dir was not found in directory ".$this->_currentDir);
			($this->_debug)? ($this->getException(__FUNCTION__)):false;
			return false;
		}

	}
	
	public function rrmdir($dir) { 
		if (is_writable($dir) && is_dir($dir)) { 
			$objects = scandir($dir); 
			foreach ($objects as $object) { 
				if ($object != "." && $object != "..") { 
					if (is_writable($dir."/".$object))
					{
						if (filetype($dir."/".$object) == "dir")
						{
							$this->rrmdir($dir."/".$object); 
						}	
						else 
						{
							unlink($dir."/".$object); 
						}
					}       
				} 
			} 
			reset($objects); 
			rmdir($dir); 
		} 
	}
	
	
	public function getFile($file) {
		
		if (!$this->_files)
		{
			$this->setException(__FUNCTION__,"Empty directory or make sure ".__CLASS__."->ReadaDir(dir) is excecuted first.");
			($this->_debug)? ($this->getException(__FUNCTION__)):false;
			return false;
		}
		else 
		{
			foreach ($this->_files as $key => $value) {
				if ($file == $value['name'])
				{
					return $this->_files[$key];
				}
			} 
			
			$this->setException(__FUNCTION__,"File $file was not found in directory ".$this->_currentDir);
			($this->_debug)? ($this->getException(__FUNCTION__)):false;
			return false;
		}

	}
	
	
	public static function dirToArray($dir) {
				
		$result = array();
		
		$cdir = scandir($dir);
		foreach ($cdir as $key => $value)
		{
			if (!in_array($value,array(".","..")))
			{
				if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
				{
					
					$result[$value] = self::dirToArray($dir . DIRECTORY_SEPARATOR . $value);
				}
				else
				{
					$result[] = $value;
				} 
			}
		}

		return $result;
	}
	
	
	public function getDirectory( $path = '.', $level = 0 ){ 
	
	    $ignore = array( 'cgi-bin', '.', '..' ); 
	    // Directories to ignore when listing output. Many hosts 
	    // will deny PHP access to the cgi-bin. 
	
	    $dh = @opendir( $path ); 
	    // Open the directory to the handle $dh 
	     
	    while( false !== ( $file = readdir( $dh ) ) ){ 
	    // Loop through the directory 
	     
	        if( !in_array( $file, $ignore ) ){ 
	        // Check that this file is not to be ignored 
	             
	            $spaces = str_repeat( '&nbsp;', ( $level * 4 ) ); 
	            // Just to add spacing to the list, to better 
	            // show the directory tree. 
	             
	            if( is_dir( "$path/$file" ) ){ 
	            // Its a directory, so we need to keep reading down... 
	             
	                echo "<strong>$spaces $file</strong><br />"; 
	                getDirectory( "$path/$file", ($level+1) ); 
	                // Re-call this same function but on a new directory. 
	                // this is what makes function recursive. 
	             
	            } else { 
	             
	                echo "$spaces $file<br />"; 
	                // Just print out the filename 
	             
	            } 
	         
	        } 
	     
	    } 
	     
	    closedir( $dh ); 
	    // Close the directory handle 
	
	}
	
	public function find_all_files($dir) 
	{ 
	    $root = scandir($dir); 
	    foreach($root as $value) 
	    { 
	        if($value === '.' || $value === '..') {continue;} 
	        if(is_file("$dir/$value")) {$result[]="$dir/$value";continue;} 
	        foreach($this->find_all_files("$dir/$value") as $value) 
	        { 
	            $result[]=$value; 
	        } 
	    } 
	    return $result; 
	}
	
	public function getSystemTempDir() {
		if ( !function_exists('sys_get_temp_dir')) {
		  function sys_get_temp_dir() {
		    if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
		    if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
		    if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
		    $tempfile=tempnam(uniqid(rand(),TRUE),'');
		    if (file_exists($tempfile)) {
		    unlink($tempfile);
		    return realpath(dirname($tempfile));
		    }
		  }
		}
		return realpath(sys_get_temp_dir());
	}
	
	private function setException($method,$str) {
		$this->__Exception->setError(__CLASS__,$method,$str);
		
	
	}
	
	private function getException($method) {
		$this->__Exception->printError(__CLASS__,$method);
	}
	

}

?>