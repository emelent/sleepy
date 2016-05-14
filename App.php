<?php

require_once 'Config.php';
require_once 'DbManager.php';
require_once 'Controller.php';

header('Content-Type: application/json');

class App{

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

  private static $USER_ERROR_MESSAGES = array(
              ERR_UNK_REQ => "Bad request",
              ERR_BAD_REQ => "Bad request",
              ERR_INCOMP_REQ => "Bad request",
              ERR_BAD_TOKEN => "Your token has expired, please login and try again",
              ERR_BAD_AUTH => "Your token has expired, please login and try again",
              ERR_EXP_AUTH => "Your token has expired, please login and try again",
              ERR_UNAUTHORISED => "You are not authorised to access that resource",
              ERR_UNEXPECTED => "Sorry, something unexpected happend, please try again.",
              ERR_DB_ERROR => "Sorry, there was a problem with the server, please try again later."
  );

  private $dbm;
  private $controllers = [];
  private $logging = true;

  public $params;

  public function __construct(){
    try{
      $this->dbm = new DbManager($this, DSN, DB_HOST, DB_NAME, DB_USER, DB_PASS);
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


  private function logRequest(){
    if(!$this->logging)
      return;
    $dbm = $this->dbm;
    $log = $dbm->getLogging();
    $data = ['GET' => $_GET, 'POST' => $_POST];
    $dbm->setLogging(false);
    $dbm->insert(
      'request_logs',
      [
        'user_id' =>  $this->getUserID(),
        'ip_addr' =>  $this->getUserIP(),
        'request' =>  $_SERVER['REQUEST_URI'],
        'data'    =>  json_encode($data)
      ]
    );
    $dbm->setLogging($log);
  }

  private function getURL(){
    $url  = '';
    for($i=1; $i < 5; $i++){
      if(isset($_GET["p$i"]))
        $url .= $_GET["p$i"] . '/';
    }
    return $url;
  }


  private function getController($url){
    foreach($this->controllers as $regex  => $ctrl){
      if(preg_match($regex, $url)){
        return $ctrl;
      }
    }
    return null;
  }


  public function setRequestLogging($log){
    $this->logging = $log;
  }


  public function setDbLogging($log){
    $this->dbm->setLogging($log);
  }

  public function getRoutes(){
    return array_keys($this->controllers);
  }
  public function route($routes){
    foreach($routes as $url => $controller){
      $exp = explode('/', $url);
      $regex = '/^';
      $first = true;
      $count =0;
      $params = [];
      foreach($exp as $part){
        if($count != 0){$regex .= '\/';}
        $count ++;
        if(substr($part, 0, 1) == ':'){
          $regex .= '([a-zA-Z0-9_-]+)';
          $params[substr($part, 1)] = $count;
          continue;
        }elseif($part == '*'){
          $regex .= '(.)*';
          continue;
        }
        $regex .= $part;
      }
      $regex .= '$/';
      if($controller instanceof Closure){
        $controller = new InjectController($controller);
      }
      if(!$controller instanceof Controller){
        $this->fail("Invalid object registered as controller");
      }
      $controller->_setParams($params);
      $this->controllers[$regex] = $controller;
    }
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
        /// following lines used for offline testing
        //$_SERVER = ['REQUEST_METHOD'=> 'DELETE'];
        //$_GET = ['arg1' => 'bye'];
        //$_DELETE = [];
        //$_PUT = [];
        //$_POST = [];
      }
      $this->logRequest();
      $method = strtolower($_SERVER['REQUEST_METHOD']);
      if(!in_array($method, ['delete', 'get', 'post', 'put'])){
        throw new KnownException("Unhandled HTTP request method", ERR_BAD_REQ);
      }
      $url = $this->getURL();
      $controller = $this->getController($url);
      if($controller == null){
        throw new KnownException("No controller for route '$url'", ERR_BAD_ROUTE);
      }
      //retreive params
      $this->params = $controller->_getParams();
      // run the method on controller
      $controller->$method($this);
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
   * Verifies given key, then logs user in based on key
   *
   * @param $key      = authorization key
   *
   * @throws KnownException
   * @return null
   */

  public function authenticateKey($key){
    $auth = $this->dbm->fetch('auth_keys', [
      'key'     => $key
    ]);
    //TODO use proper format
    if($auth == null){
      throw new KnownException('', ERR_BAD_AUTH);
    }
    $_SESSION['auth'] = $key;
    $_SESSION['uid'] = $auth->user_id;
  }


  /*
   * Deauthenticates all keys linked to given
   * user id
   *
   * @param $uid      = user id
   *
   * @throws KnownException
   * @return null
   */

  public function deauthenticateKeys($uid){
    $auth = $this->dbm->delete('auth_keys', [
      'uid'     => $uid
    ]);
    //TODO use proper format
    if($auth == null){
      throw new KnownException('', ERR_BAD_AUTH);
    }
    $_SESSION['auth'] = null;
    $_SESSION['uid'] = $auth->user_id;
  }


  /*
   * Generates an authorization key which can be used
   * instead of login details.
   *
   * @param $uid      = user id
   * @param $expire   = expiration date of authorization key
   *
   * @throws KnownException
   * @return null
   */
  public function generateKey($uid, $expire=null){
    $key = hash('SHA256', uniqid('auth', true));
    if($expire == null){
      $expire = date('tomorrow');
    }
    $this->dbm->insert('auth_keys', [
      'uid'     => $_SESSION['uid'],
      'key'     => $key,
      'expires' => $expire
    ]);
    $_SESSION['auth'] = $key;
  }


  /*
   * Checks if current user is logged in
   *
   * @return boolean
   */

  public function isLoggedIn(){
    return isset($_SESSION['uid0']);
  }

  public function login($uid){
    $_SESSION['uid0'] = $uid;
  }

  /*
   * Returns current user id
   *
   * @return integer
   */
  public function getUserID(){
    if($this->isLoggedIn()){
      return $_SESSION['uid0'];
    }
    return -1;
  }

  public function getUserIP(){
    if(isset($_SERVER['REMOTE_ADDR'])){
      return $_SERVER['REMOTE_ADDR'];
    }
    return '127.0.0.1';
  }

  /*
   * Returns current user auth key or null if none exists
   *
   * @return string | null
   */

  public function getAuthKey(){
    if(isset($_SESSION['auth']))
      return $_SESSION['auth'];
    return null;
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
  

  public function getDbManager(){
    return $this->dbm;
  }


  public function result($success, $data){
    die(json_encode([
      'successful'  => $success,
      'data'        => $data
    ]) . PHP_EOL);
  }

  public function success($data){
    $this->result(true, $data);
  }

  public function fail($data){
    $this->result(false, $data);
  }
}
