<?php

//=====================================
// CONFIGURATION 
//=====================================

/* Resource constants*/
$temp = explode('/', dirname(__FILE__));
array_pop($temp);

define('ROOT_DIR', implode('/', $temp) . '/');
define('LIB_DIR', ROOT_DIR . 'lib/');
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

$CLIENT_ID = 'DEFAULT_CLIENT';
$CLIENT_SECRET = 'CHANGE-ME-PLEASE';
$CLIENT_REDIRECT = HOME_URL;


/* Set timezone */
date_default_timezone_set("UTC");

/* Use this to throw minor exceptions that don't need redirection */
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

//=============================================
// OAUTH2 Config
//=============================================

//load OAuth2 modules
//require_once(LIB_DIR . 'oauth2/Autoloader.php');
require_once(LIB_DIR . 'oauth2-server-php/src/OAuth2/Autoloader.php');
OAuth2\Autoloader::register();

// $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
$storage = new OAuth2\Storage\Pdo(array(
  'dsn' => DSN . ':dbname=' . DB_NAME .';host=' . DB_HOST, 
  'username' => DB_USER, 'password' => DB_PASS
));

// Pass a storage object or array of storage objects to the OAuth2 server class
$server = new OAuth2\Server($storage);

// Add the "Client Credentials" grant type (it is the simplest of the grant types)
$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

// Add the "Authorization Code" grant type (this is where the oauth magic happens)
$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

//=============================================
// REQUIRE CORE LIB
//=============================================

$CORE = [
  'App',
  'Auth',
  'Controller',
  'DbManager',
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
// REQUIRE APP MODULES
//=============================================

$MODULES = [
  'admin',
];

//require module files
foreach($MODULES as $module){
  require_once(ROOT_DIR . $module . '/models.php');
  require_once(ROOT_DIR . $module . '/controllers.php');
  require_once(ROOT_DIR . $module . '/routes.php');
}


