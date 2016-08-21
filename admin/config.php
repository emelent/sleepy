<?php

//=====================================
// CONFIGURATION 
//=====================================

/* Resource constants*/
$temp = explode('/', dirname(__FILE__));
array_pop($temp);

define('ROOT_DIR', implode('/', $temp) . '/');
define('LIB_DIR', ROOT_DIR . '/lib/');
define('HOME_URL', 'http://localhost/api2/');

/* Database constants*/
define('DSN', 'mysql');
define('DB_HOST', 'localhost');
define('DB_NAME', 'api_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');

/* Turn this to false for Production */
define('DEBUG', true);

define('GUEST_KEY', 'GUEST');
define('API_KEY', 'API_KEY');

/* ERROR CODES */
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

/* Use this to throw minor exceptions that don't need redirection */
class KnownException extends Exception{}

class UnknownMethodCallException extends Exception{}

//require lib files
require_once(LIB_DIR . 'DbManager.php');
require_once(LIB_DIR . 'Router.php');
require_once(LIB_DIR . 'Controller.php');
require_once(LIB_DIR . 'App.php');
require_once(LIB_DIR . 'Model.php');
require_once(LIB_DIR . 'ModelManager.php');
require_once(LIB_DIR . 'utils.php');

$MODULES = [
  'admin',
];

//require module files
foreach($MODULES as $module){
  require_once(ROOT_DIR . $module . '/models.php');
  require_once(ROOT_DIR . $module . '/controllers.php');
  require_once(ROOT_DIR . $module . '/routes.php');
}

//register models
ModelManager::register('User');
ModelManager::register('Auth');

