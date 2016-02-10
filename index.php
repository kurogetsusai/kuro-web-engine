<?php

////////////////////////////////////////////////////////////////////////////////
///////////////////////  K U R O   W E B   E N G I N E  ////////////////////////
////////////////////////////////////////////////////////////////////////////////
//                                                                            //
//    Dependencies:                                                           //
//     - PHP 5.4                                                              //
//     - the .htaccess file                                                   //
//                                                                            //
//    Version     : 0.2.0.0                                                   //
//    Code quality: 0.3416 wtf/h (7 WTFs during 20.4917 hours)                //
//                                                                            //
////////////////////////////////////////////////////////////////////////////////


# Configuration ################################################################

# global
define('DEBUG_MODE'    , true); # debug mode (show all types of errors and the debug console)
define('KURO_LOCATION' , '/data/ca1/software/dev/git/kuro-web-engine'); # absolute path to the Kuro directory (without trailing slash)
define('DEFAULT_LANG'  , 'en');	  # has to match 'KURO_LOCATION/loc/*.ini'
define('DEFAULT_PAGE'  , 'home'); # has to match 'KURO_LOCATION/page/*.php'
define('SESSION_ENABLE', true);
define('SESSION_TIME'  , 14515200); # 60 * 60 * 24 * 7 * 2 * 24 = 24 weeks (~6 months)
define('COOKIE_ENABLE' , true);
define('COOKIE_TIME'   , 14515200); # 60 * 60 * 24 * 7 * 2 * 24 = 24 weeks (~6 months)
define('GLOBAL_SALT'   , 'g_qOHvR;?eC)1}Qv^w{7'); # global salt is currently used to salt user passwords
define('GLOBAL_DNT'    , $_SERVER['HTTP_DNT'] == '1'); # please don't be an asshole and respect the Do Not Track header
# GLOBAL_ROOT and CURRENT_PATH are used for HTML
# GLOBAL_ROOT is the real path (used for "absolute" URLs like "/admin")
# CURRENT_PATH is the path to the application = the semantic URL (used for "relative" URLs like "<current_URL>/set=lang-en")
# GLOBAL_ROOT: root of the public URL without trailing slash, e.g.
# - URL = 'http://foo.bar'     -> GLOBAL_ROOT = ''
# - URL = 'http://foo.bar/baz' -> GLOBAL_ROOT = '/baz' (baz is a directory)
define('GLOBAL_ROOT'   , '/~sai');
define('CURRENT_PATH'  , $_GET['cmd'] == '' ? GLOBAL_ROOT : GLOBAL_ROOT . '/' . $_GET['cmd']);

# lib/database
define('DATABASE_HOST', 'localhost');
define('DATABASE_BASE', 'www-kuro');
define('DATABASE_USER', 'www-kuro');
define('DATABASE_PASS', '5hQhrWepECBMsZQC');

# lib/user
define('USER_PASSWORD_COST', 11);

# Application ##################################################################

# start session
if (SESSION_ENABLE)
	session_start();

# init loader
require KURO_LOCATION . '/lib/loader.php';
$loader = new \Kuro\Loader(KURO_LOCATION, $_GET['cmd'], DEBUG_MODE);

# load libs
$loader->loadModule('lib/database');
$loader->loadModule('lib/user');

# init database and connect
$db = new \Kuro\Database($loader);
$db->connect(DATABASE_HOST, DATABASE_BASE, DATABASE_USER, DATABASE_PASS);
#$db->setup(); // TODO

# init user
$user = new \Kuro\User($loader, $db);

# setup environment
$loader->addActions($user->getActions());
$user->processRequestData();
$loader->processActions();
$loader->setLanguage($loader->remindLanguage());

# load page
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

