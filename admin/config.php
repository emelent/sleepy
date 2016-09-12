<?php

//=====================================
// CONFIGURATION 
//=====================================

/* Resource constants */
$temp = explode('/', dirname(__FILE__));
array_pop($temp);

/* Turn this to false for Production */
define('DEBUG', true);


/* Database constants */
define('DSN', 'mysql'); define('DB_HOST', 'localhost');
define('DB_NAME', 'api_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');

/* Directory constants */
define('ROOT_DIR', implode('/', $temp) . '/');
define('LIB_DIR', ROOT_DIR . 'lib/');
define('HOME_URL', 'http://localhost/api2/');


define('GUEST_KEY', 'GUEST');
define('API_KEY', 'API_KEY');

/* Error codes */
define('ERR_UNK_REQ', 0);  //unknown request method
define('ERR_BAD_REQ',  1); //invalid data
define('ERR_INCOMP_REQ', 2); //missing data
define('ERR_BAD_TOKEN',  3); //bad form token
define('ERR_BAD_AUTH',  4); //bad auth key
define('ERR_EXP_AUTH', 5);  //expired auth key
define('ERR_UNAUTHORISED', 6); //unauthorised access attempt
define('ERR_UNEXPECTED', 7); //unexpected error 
define('ERR_DB_ERROR', 8); //database and sql errors
define('ERR_BAD_LOGIN', 9); //thrown upon login failure
define('ERR_BAD_ROUTE',  10); //bad auth key


/* Set timezone */
date_default_timezone_set("UTC");

/* Use this to wrap exceptions thrown by library */
class KnownException extends Exception{}

class UnknownMethodCallException extends Exception{}


if(DEBUG){
  //Turn on error reporting
  ini_set('display_errors',1);
  error_reporting(E_ALL);
}else{
  // Turn off all error reporting
  error_reporting(0);
}


//Load OAuth2 modules
require_once(LIB_DIR . 'oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();


//=============================================
// LOAD CORE LIB
//=============================================

$CORE = [
  'App',
  'Auth',
  'Controller',
  'CustomStorage',
  'ErrorResponse',
  'Model',
  'ModelManager',
  'Request',
  'Response',
  'Router',
  'utils',
];

foreach($CORE as $src){
  require_once(LIB_DIR . "core/$src.php");
}



//=============================================
// OAUTH2 Config
//=============================================

define('CLIENT_ID', 'client');
define('CLIENT_SECRET', 'secret');
define('REDIRECT_URI', HOME_URL);

Auth::configOAuth(CLIENT_ID, CLIENT_SECRET, REDIRECT_URI);

//=============================================
// LOAD APP MODULES
//=============================================

$MODULES = [
  'admin',
];

foreach($MODULES as $module){
  require_once(ROOT_DIR . $module . '/models.php');
  require_once(ROOT_DIR . $module . '/controllers.php');
  require_once(ROOT_DIR . $module . '/routes.php');
}


