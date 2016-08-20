<?php

// ===========================================================================
//
//
// This is the contains the declaration of the App object which is the core
// of the REST api. The API currently only works with json, simply as a matter
// of preference and ease of use.
//
// Please note that any methods or classes beginning with an underscore whether
// public or private are not meant to be called directly by a user but are for 
// the inner functioning of the library as a whole.
//
// 
// All requests follow the general structure of:
//
//    http(s)://<path to api>/<api url route>
//
// with API_KEY sent in with headers.
// ===========================================================================


require_once 'Config.php';
require_once 'DbManager.php';
require_once 'Controller.php';
require_once 'Router.php';


// set the header  content type
header('Content-Type: application/json');


/*
 * App class, core of the library
 */
class App{

  /// error code values for debugging
  private static $DEBUG_ERROR_MESSAGES = array(
              ERR_UNK_REQ => "UNKNOWN REQUEST METHOD",
              ERR_BAD_REQ => "INVALID REQUEST FORMAT",
              ERR_INCOMP_REQ => "INCOMPLETE REQUEST",
              ERR_BAD_TOKEN => "INVALID FORM TOKEN",
              ERR_BAD_AUTH => "INVALID AUTH KEY",
              ERR_EXP_AUTH => "EXPIRED AUTH KEY",
              ERR_UNAUTHORISED => "UNAUTHORISED ACCESS ATTEMPT",
              ERR_UNEXPECTED => "UNKNOWN ERROR",
              ERR_DB_ERROR => "DATABASE ERROR",
              ERR_BAD_ROUTE => "UNMATCHED ROUTE"
  );

  /// output messages for user
  private static $USER_ERROR_MESSAGES = array(
              ERR_UNK_REQ => "Bad request",
              ERR_BAD_REQ => "Bad request",
              ERR_INCOMP_REQ => "Bad request",
              ERR_BAD_TOKEN => "Your token has expired, please login and try again",
              ERR_BAD_AUTH => "Your token has expired, please login and try again",
              ERR_EXP_AUTH => "Your token has expired, please login and try again",
              ERR_UNAUTHORISED => "You are not authorised to access that resource",
              ERR_UNEXPECTED => "Sorry, something unexpected happend, please try again.",
              ERR_DB_ERROR => "Sorry, there was a problem with the server, please try again later.",
              ERR_BAD_ROUTE => "The page you are looking for does not exist"
  );

  private $dbm;       
  private $router;
  private $auth = null;

  private static $INSTANCE = null;

  public $params;

  public static function getInstance(){
    if(App::$INSTANCE == null){
      App::$INSTANCE = new App();
    }
    return App::$INSTANCE;
  }

  private function __construct(){
    $this->router = new Router();
    try{
      $this->dbm = new DbManager(DSN, DB_HOST, DB_NAME, DB_USER, DB_PASS);
    }
    catch(KnownException $e){
      /* Handle known exceptions i.e. exceptions thrown by our logic */
      if(DEBUG){
        $this->fail(
          '[' . $this::$DEBUG_ERROR_MESSAGES[$e->getCode()] .
          '] ' . $e->getMessage()
        );
      }else{
        $this->fail($this::$USER_ERROR_MESSAGES[$e->getCode()]);
      }
    }

    catch(Exception $e){
      /* Handle unknown exceptions i.e. exceptions thrown by PHP */
      if(DEBUG){
        $this->fail(
          '[' . $this::$DEBUG_ERROR_MESSAGES[ERR_UNEXPECTED] . '] ' . 
          $e->getMessage()
        );
      }else{
        $this->fail($this::$USER_ERROR_MESSAGES[ERR_UNEXPECTED]);
      }
    }
    session_start();
  }


  /*
   * Returns all the routed paths as an array of regular expressions
   *
   * @return Array
   */

  public function getRoutes(){
    return $this->router->getRoutes();
  }


  /*
   * Routes given paths to controllers
   *
   * @param $routes = Assoc Array containing key value store of the path 
   *                  and controller/function
   *
   * @throws KnownException
   * @return null
   */

  public function route($routes){
    $this->router->route($routes);
  }

  /*
   * Delegates the request to the appropriate Controller
   *
   * @param $method     = Type of request (get, put, post, delete)
   *
   * @throws KnownException
   * @return null
   */

  public function serve(){
    try{
      if(!isset($_SERVER['REQUEST_METHOD'])){
        throw new KnownException('No request was made to the server', ERR_BAD_REQ);
      }
      $method = strtolower($_SERVER['REQUEST_METHOD']);
      if(!in_array($method, ['delete', 'get', 'post', 'put'])){
        throw new KnownException("Unhandled HTTP request method", ERR_BAD_REQ);
      }

      $key = (isset($_SERVER['API_KEY']))? $_SERVER['API_KEY']: GUEST_KEY;

      $this->authenticateKey($key);
      $url = $this->router->getURL();
      $controller = $this->router->getController();
      if($controller == null){
        throw new KnownException("No controller for route '$url'", ERR_BAD_ROUTE);
      }
      //retreive params
      $this->params = $controller->_getParams();

      $this->runControllerMethod($url, $method, $controller);
    }
    catch(KnownException $e){
      /* Handle known exceptions i.e. exceptions thrown by our logic */
      if(DEBUG){
        $this->fail(
          '[' . $this::$DEBUG_ERROR_MESSAGES[$e->getCode()] .
          '] ' . $e->getMessage()
        );
      }else{
        $this->fail($this::$USER_ERROR_MESSAGES[$e->getCode()]);
      }
    }

    catch(Exception $e){
      /* Handle unknown exceptions i.e. exceptions thrown by PHP */
      if(DEBUG){
        $this->fail(
          '[' . $this::$DEBUG_ERROR_MESSAGES[ERR_UNEXPECTED] . '] ' . 
          $e->getMessage()
        );
      }else{
        $this->fail($this::$USER_ERROR_MESSAGES[ERR_UNEXPECTED]);
      }
    }
  }

  /*
   * Runs the requested method on a given controller.
   *
   * @param $url              = Url Route
   * @param $request_method   = Request method (post, get, put, delete) 
   * @param $controller       = Controller to be used 
   *
   * @throws KnownException
   * @return null
   */

  private function runControllerMethod($url, $request_method, $controller){
      if($controller instanceof RoutedController){
        $args = explode('/', substr($url, 0, strlen($url) -1));
        $args = array_reverse($args);
        array_pop($args);
        $args = array_reverse($args);
        try{
          $controller->{$args[0]}($this, $args);
        }catch(Exception $e){
          throw new KnownException("No controller for route '$url'", ERR_BAD_ROUTE);
        }
      }else{
        $controller->$request_method($this);
      }
  }

  /*
   * Verifies given key, calls $this->fail if invalid
   *
   * @param $key      = authorization key
   *
   * @throws KnownException
   * @return null
   */

  public function authenticateKey($key){
    //for activity that needs no authorisation, i.e logging in
    if($key == GUEST_KEY){
      $this->auth = null;
      return;
    }
    $auth = $this->dbm->fetchSingle('auth_keys', [
      'auth_key'     => $key
    ]);
    //TODO use proper format
    if($auth == null){
      throw new KnownException('', ERR_BAD_AUTH);
    }
    $this->auth = $auth; //authorise user
  }

  public function isAuthenticated(){
    return $this->auth != null;
  }

  /*
   * Authenticates user using email and password,
   * if successful returns an authorization to be
   * used with api
   *
   * @param $email      = user email
   * @param $password   = user password 
   *
   * @throws KnownException
   * @return null
   */

  public function authenticateEmailPass($email, $password){
    $user = $this->dbm->fetchSingle('users', ['email' => $email, 'password' => $password]);
    if($user == null){
      $this->fail("Email password authentication failed.");
    }
    $auth = $this->dbm->fetchSingle('auth_keys', [
      'user_id'     => $user->id
    ]);
    if($auth == null){
      $this->success($this->generateKey($user->id));
    }
    $this->success($auth->auth_key);
  }

  /*
   * Generate hash for  password
   *
   * @param $password   =  password 
   *
   * @throws KnownException
   * @return null
   */

  public function hashPassword($password){
    return hash('sha256', $_POST['password']);
  }

  /*
   * Authentication is done with uid/user_id instead of 
   * password, therefore external party should provide
   * the user id
   *
   * @param $email      = user email
   * @param $uid        = user uid 
   *
   * @throws KnownException
   * @return null
   */
  public function authenticateExternal($email, $uid){
    $user = $this->dbm->fetchSingle('users', ['email' => $email]);
    if($user == null){ //if user doesn't exist
      //create external user account
      $this->createUserExternal($email, $uid);
      $user = $this->dbm->fetchSingle('users', ['email' => $email, 'id' => $uid]);
    }else{ 
      //if user id doesn't match, failed login, wrong uid
      if($user->id != $uid)
        $this->fail("Authentication failed.");
    }

    $auth = $this->dbm->fetchSingle('auth_keys', [
      'user_id'     => $user->id
    ]);

    //generate key if there is no key
    if($auth == null){
      $this->success($this->generateKey($user->id));
    }
    $this->success($auth->auth_key);
  }


  /*
   * Create a new user which has the validated attribute
   * set to true
   *
   * @param $email      =  user email
   * @param $password   =  user password
   *
   * @throws KnownException
   * @return null
   */

  public function createUserValidated($email, $password){
    $this->createUser($email, $password, false);
    $this->authenticateEmailPass($email, $pass);
  }

  /*
   * Create a new user which has the validated attribute
   * set to false, to set up things such as email validation
   *
   * @param $email      =  user email
   * @param $password   =  user password
   *
   * @throws KnownException
   * @return null
   */
  public function createUserUnvalidated($email, $password){
    $this->createUser($email, $password, false);
    $this->authenticateEmailPass($email, $pass);
  }


  /*
   * Generate UID
   *
   *
   * @return string
   */

  public function generateUID($length=64, $strong=true){
    return bin2hex(openssl_random_pseudo_bytes($length/2, $strong));
  }


  /*
   * Creates a new user using the given credentials, then logs the user in
   *
   * @throws KnownException
   * @return null
   *
   */
  private function createUser($email, $password, $validated){
    $dbm = $this->getDbManager();
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $this->hashPassword($password); 
    $uid = $this->generateUID();
    $dbm->insert(
      'users',
      [
        'uid' => $uid,
        'email' => $email, 
        'password' => $pass,
        'validated' => $validated
      ]
    );
  }

  private function createUserExternal($email, $uid){
    //set password to randomized string since authorization 
    //will be handled by external party and we wont handle logins 
    //for this user personally, ideally, no one will guess the password
    $password = $this->generateUID();     
    $this->createUser($email, $password, $uid, true);
  }

  /*
   * Perform a check on the user's usergroup, if the group level is
   * lower than the value specifed by @param group, then they are not 
   * authorised, and an exception will be thrown
   *
   * @param group = group level required
   * @throws KnownException
   * @return null
   */

  public function authorise($group=0){
    if($this->$auth == null){
      throw new KnownException('', ERR_UNAUTHORISED);
    }
    if($auth->user_group < $group){
      throw new KnownException('', ERR_UNAUTHORISED);
    }
  }

  /*
   * Deauthenticates all keys linked to given
   * user id
   *
   * @throws KnownException
   * @return null
   */
  public function deauthenticateKeys(){
    if($this->auth){
      $auth = $this->dbm->delete('auth_keys', [
        'user_id' => $this->auth->user_id
      ]);
    }
  }


  /*
   * Generates an authorization key which can be used
   * instead of login details.
   *
   * @param $user_id      = user id
   *
   * @throws KnownException
   * @return null
   */
  private function generateKey($user_id, $expire=null){
    $this->deauthenticateKeys();
    $key = hash('SHA256', uniqid('auth', true));
    if($expire == null){
      $expire = date('Y-m-d H:i:s', time() + 24*60*60* 7); //set auth key to expire after 7 days
    }
    $this->dbm->insert('auth_keys', [
      'user_id'   => $user_id,
      'auth_key'  => $key,
      'expires'   => $expire   
    ]);
    return $key;
  }

  /*
   * Returns current user form token
   *
   * @return string
   */

  public function getFormToken(){
      if(!isset($_SESSION['form_token']))
          $_SESSION['form_token'] = md5(uniqid('auth', true));
      return $_SESSION['form_token'];
  }


  /*
   * Authenticates form token
   *
   * @param $token      = form token
   * 
   * @throws KnownException
   * @return null
   */

  public function authenticateFormToken($token){
      if($token != $this->getFormToken())
        throw new KnownException('', ERR_BAD_TOKEN);
  }
  

  /*
   * Get database manager
   *
   * @return DbManager
   */

  public function getDbManager(){
    return $this->dbm;
  }


  /*
   * Echoes result in json form using die, hence it can only be called once,
   * and should be called at the end of a controller method
   *
   * @param $success    = boolean, whether or not the request was successful
   * @param $data       = ?   any data that accompanies the result
   * @return null
   */

  public function result($success, $data){
    die(json_encode([
      'successful'  => $success,
      'data'        => $data
    ]) . PHP_EOL);
  }


  /*
   * Convenience method for App::result(true, $data), which is a successful
   * request.
   *
   * @return null
   */

  public function success($data){
    $this->result(true, $data);
  }


  /*
   * Convenience method for App::result(false, $data);, which is a failed 
   * request
   *
   * @return null
   */

  public function fail($data){
    $this->result(false, $data);
  }


  /*
   * Checks if the current request method matches the given one, and if it
   * doesn't then it fails
   *
   * @return null
   */
  public function assertRequestMethod($method){
    if($_SERVER['REQUEST_METHOD'] != $method)
      $this->fail("Invalid request method");
  }

  /*
   * Returns true if the array passed in $array has all the keys
   * passed in array $keys set to non-null value
   *
   * @param $keys = array of keys
   * @param $array = array of values
   *
   * @throws KnownException
   * @return null
   */

  public function assertArrayKeysSet($keys, $array, $msg = ''){
    foreach($keys as $key){
      if(!isset($array[$key]))
        throw new KnownException($msg, ERR_INCOMP_REQ);
    }
  }
}

$app = App::getInstance();
