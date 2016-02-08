<?php

////////////////////////////////////////////////////////////////////////////////
///////////////////////  K U R O   W E B   E N G I N E  ////////////////////////
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//    Dependencies:                                                           //
//     - PHP 5.4                                                              //
//     - php mcrypt mod          // TODO really?                              //
//                                                                            //
//    Version     : 0.2.0.0                                                   //
//    Code quality: 0.6410 wtf/h (6 WTFs during 9.3606 hours)                 //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


# Configuration ################################################################

# debug mode (show all types of errors and the debug console)
define('DEBUG_MODE'    , true);
# absolute path to the Kuro directory (without trailing slash)
define('KURO_LOCATION' , '/data/ca1/software/dev/git/kuro-web-engine');

define('DEFAULT_LANG'  , 'en');	  # has to match 'KURO_LOCATION/loc/*.ini'
define('DEFAULT_PAGE'  , 'home'); # has to match 'KURO_LOCATION/page/*.php'

# global salt is used to salt user passwords and session hashes
define('GLOBAL_SALT'   , 'g_qOHvR;?eC)1}Qv^w{7');
# root of the public URL without trailing slash, e.g.
# - URL = 'http://foo.bar'     -> GLOBAL_ROOT = ''
# - URL = 'http://foo.bar/baz' -> GLOBAL_ROOT = '/baz' (baz is a directory)
define('GLOBAL_ROOT'   , '/~sai');
define('GLOBAL_DNT'    , $_SERVER['HTTP_DNT'] == '1'); # please don't be an asshole and respect the Do Not Track header
define('SESSION_ENABLE', true);
define('SESSION_TIME'  , 14515200); # 60 * 60 * 24 * 7 * 2 * 24 = 24 weeks (~6 months)
define('COOKIE_ENABLE' , false);
define('COOKIE_TIME'   , 14515200); # 60 * 60 * 24 * 7 * 2 * 24 = 24 weeks (~6 months)

define('DATABASE_HOST' , 'localhost');
define('DATABASE_BASE' , 'www-kuro');
define('DATABASE_USER' , 'www-kuro');
define('DATABASE_PASS' , '5hQhrWepECBMsZQC');

define('USER_PASSWORD_COST', 11);

define('CURRENT_PATH'  , $_GET['cmd'] == '' ? GLOBAL_ROOT : GLOBAL_ROOT . '/' . $_GET['cmd']);


# Run ##########################################################################

# start session
if (SESSION_ENABLE)
	session_start();

# init loader
require KURO_LOCATION . '/lib/loader.php';
$loader = new \Kuro\Loader(KURO_LOCATION, $_GET['cmd'], DEBUG_MODE);

# load libs
$loader->loadModule('lib/database');
$loader->loadModule('lib/user');

# connect to database
$db = new \Kuro\Database($loader);
$db->connect(DATABASE_HOST, DATABASE_BASE, DATABASE_USER, DATABASE_PASS);
#$db->setup(); // TODO

# users
$user = new \Kuro\User($loader, $db);

# actions
$loader->addActions($user->getActions());
$loader->processActions();
$loader->setLanguage($loader->remindLanguage());

#- try logout
#	- done
#	- try post
#		- done
#		- try session
#			- done
#			- try cookies
#				- done
#				- try register
#					- done
#					- cannot do anything

// XXX TMP
if (isset($_GET['logout'])) {
	$loader->debugLog('index', 'Logging out', DEBUG_STATUS_NOTICE);
	//$user->logOut();
#	header('Location: ' . $_SERVER['URL']);
#	exit();
}

// XXX TMP
if (
	isset($_POST['register_nick']) and
	($_POST['register_password'] == $_POST['register_password_retype'])
) {
$user->register($_POST['register_nick'],
		                        $_POST['register_password'],
		                        $_POST['register_email']);
}
elseif (isset($_POST['login_nick'])) {
	$user->logInUsingPassword($_POST['login_nick'], $_POST['login_password']);
}



# the code below is from the old version

#// TODO this code is shit, but it works, clean it up
#// TODO logout
#if (SESSION_ENABLE and isset($_SESSION['user_id']) and isset($_SESSION['hash'])) {
## log in using session
#	switch ($user->logInUsingSession($_SESSION['user_id'],
#	                                 $_SESSION['hash'])) {
#	case 0:
#		echo 'logged in using session';
#		break;
#	case 1:
#		echo 'invalid session';
#		unset($_SESSION['user_id']);
#		unset($_SESSION['hash']);
#		// TODO try cookies!
#		break;
#	}
#} else if (COOKIE_ENABLE and isset($_COOKIE['user_id']) and isset($_COOKIE['hash'])) {
## log in using cookies
#	$_SESSION['user_id'] = $_COOKIE['user_id'];
#	$_SESSION['hash']    = $_COOKIE['hash'];

#	// TODO merge this code with session
#	switch ($user->logInUsingSession($_SESSION['user_id'],
#	                                 $_SESSION['hash'])) {
#	case 0:
#		echo 'logged in using cookies';
#		break;
#	case 1:
#		echo 'invalid session (from cookies)';
#		unset($_SESSION['user_id']);
#		unset($_SESSION['hash']);
#		setcookie('user_id', '', 0);
#		setcookie('hash'   , '', 0);
#		break;
#	}
#} else if (isset($_POST['login_nick']) and isset($_POST['login_password'])) {
## log in using nick and password
#	switch ($user->logIn($_POST['login_nick'], $_POST['login_password'])) {
#	case 0:
#		echo 'logged in';
#		break;
#	case 1:
#		echo 'not logged in, wrong password';
#		break;
#	case 2:
#		echo 'not logged in, wrong nick';
#		break;
#	}
#} else if (isset($_POST['register_nick'])
#       and isset($_POST['register_password'])
#       and isset($_POST['register_password_retype'])) {
## register user
#	# check if passwords are the same
#	if ($_POST['register_password'] != $_POST['register_password_retype']) {
#		# passwords are not the same
#		echo 'passwords does not match';
#	} else {
#		switch ($user->register($_POST['register_nick'],
#		                        $_POST['register_password'],
#		                        $_POST['register_email'])) {
#		case 0:
#			echo 'register ok, logged in';
#			break;
#		case 1:
#			echo 'wrong password';
#			break;
#		case 2:
#			echo 'wrong email';
#			break;
#		case 10:
#			echo 'tried to register new user, but user already exists';
#			break;
#		case 20:
#			echo 'tried to update user, but user does not exist';
#			break;
#		case 30:
#			echo 'database error';
#			break;
#		case 100:
#			echo 'register ok, cannot log in, bad password';
#			break;
#		case 200:
#			echo 'register ok, cannot log in, bad nick';
#			break;
#		}
#	}
#}




# include page
$loader->loadTranslatedModule('page/' . $loader->getPage());

# print debug info
#$text = 'All work and no play makes Jack a dull boy';
#$loader->debugLog('index', $text, 0);
#$loader->debugLog('index', $text, 1);
#$loader->debugLog('index', $text, 2);
#$loader->debugLog('index', $text, 3);
#$loader->debugLog('index', $text, 5);
#$loader->debugLog('index', $text, 6);
$loader->printDebugLog();

