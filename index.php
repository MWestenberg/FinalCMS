<?php
# Will include all other application classes needed and sets default variables
require_once('lib/bootstrapper.php');

#set pixelratio cookie
# apple still hasn't sorted this out




(!isset($_GET['page']))? ($_GET['page']=false):false;

/*
*	Load credentials class to check cookie variables (autologin)
*/
$cred = new credentials();
$cred->validateCookie(APPNAME);


/*
Load device type
*/
$device = new deviceCheck();

/*
* Situation: no language is selected
*	Define the default language e.g. "en" and make sure that directory is available in templates directory
*	and it is defined in the translation table
*/
# enable translater and set it to defaul language
$translator = new Translator(DEFAULT_LANGUAGE);


# set to different language when it exists
if (isset($_GET['language']) && $translator->checkLanguage($_GET['language']))
{
	$_SESSION['language'] = $_GET['language'];
}
else #There is no language set so move up the params and set the default language 
{
	if (!isset($_SESSION['language']) || (isset($_SESSION['language']) && !$translator->checkLanguage($_SESSION['language'])))
	{
		$_SESSION['language'] = DEFAULT_LANGUAGE;
	}

	# in reverse order reset all params
	(isset($_GET['getparam1'])) ? ($_GET['getparam2'] = $_GET['getparam1']):false;
	(isset($_GET['subpage'])) ? ($_GET['getparam1'] = $_GET['subpage']):false;
	(isset($_GET['page'])) ? ($_GET['subpage'] = $_GET['page']):false;
	(isset($_GET['language'])) ? ($_GET['page'] = $_GET['language']):false;
	
}



/*
*	Below template path definitions.
*	Set these to your template directories
*	TPL_DIR, PRIVATE_TPL, PVT_INCLUDES, PUBLIC_TPL, PUB_INCLUDES and MAIL_TPL
*/

#default page
DEFINE("DEFAULT_PAGE","/home");

# root template directory:
DEFINE("TPL_DIR", TPL_ROOT . $_SESSION['language']); # CWD . TPL_DIR

# mobile dir 
DEFINE("MOBILE_DIR", "mobile"); # CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL . DIRECTORY_SEPARATOR . MOBILE_DIR

# private template directory (make sure these are in the userrights table and in usergroup
DEFINE("PRIVATE_TPL","private"); # CWD . TPL_DIR. DIRECTORY_SEPARATOR . PRIVATE_TPL

# private internal templates directory
DEFINE("PVT_INCLUDES","includes"); # CWD . TPL_DIR . DIRECTORY_SEPARATOR . PRIVATE_TPL .  DIRECTORY_SEPARATOR . PVT_INCLUDES

# public tempalte directory   
DEFINE("PUBLIC_TPL","public");  # CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL

# public internal templates directory
DEFINE("PUB_INCLUDES","includes");  # CWD . TPL_DIR . DIRECTORY_SEPARATOR . PUBLIC_TPL .  DIRECTORY_SEPARATOR . PUB_INCLUDES	

# mail templates directory
DEFINE("MAIL_TPL","mailtpl");  # CWD . TPL_DIR . DIRECTORY_SEPARATOR . MAIL_TPL	

DEFINE("CMS_PAGES","pages");


/*
*	Start loading the templates
*/

/* Enable template class 
*  @debug: true will enable debug omitting it or false will disable debug
*  Make sure this is omitted or false in production
*/
$template = new Template();

if (isset($_SESSION['loginId']) && isset($_GET['page']) && !empty($_GET['page']) && strpos(constant("SECUREPATH"),$_GET['page'])!==false)
{
	# user logged in
	# all includes must go in private now 
	$template->setPrivate();
	# private ajax files are entered in the userrights table to prevent misuse
	$template->loadTemplate($_GET['subpage']);#default page to start always make sure SECUREPATH is set to something like secure or private
}
else 
{
	
	#user not logged in
	# all includes from public
	$template->setPublic();
	
		
	# here all ajax files are public
	switch($_GET['page'])
	{
		case 'login': 
				#redirect to HTTPS
				(!isset($_SERVER['HTTPS'])) ? (header("Location: https://". $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])):false; 
				if (isset($_SESSION['loginId'])) 
				{
					//admin has different start page
					($_SESSION['usergroup']==1)? (header("Location: ".SECUREPATH."/clientmanagement")):(header("Location: ".SECUREPATH."/start")); # we are logged in
				}
				else 
				{
					$template->loadTemplate('login'); #login page
				}

			break; 
		case 'reset-password' :
				
				if (!empty($_GET['subpage']) && $cred->validatePasswordReset($_GET['subpage']))
				{
					$_SESSION['resetpass'] = $_GET['subpage'];	
					header("Location: /reset-password");
				}
				else if (isset($_SESSION['resetpass']) && $cred->validatePasswordReset($_SESSION['resetpass']))
				{
					$template->loadTemplate($_GET['page']);
				}
				else 
				{
					header("Location: /login"); # redirect to home
				}
				
			break;
		default: $template->loadTemplate($_GET['page']); break; # load public template
		
	}
	
	
	
}






?>