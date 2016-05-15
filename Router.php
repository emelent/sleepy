<?php

// ===========================================================================
//
//
// This file contains the class declaration of the Router class
//
// Please note that any methods or classes beginning with an underscore whether
// public or private are not meant to be called directly by a user but are for 
// the inner functioning of the library as a whole.
//
//
// ===========================================================================


/*
 * Router class used to handle the routing of url's to specific controllers
 */
class Router{

  private $routes = [];
  private $domain;
  private static $url = null;

 /*
  * @param $domain =  String common domain route 
  */
  public function __construct($domain=''){
    if(substr($domain, -1, 1) != '/' && $domain != '')
      $domain .= '/';
    $this->domain = '/^' . str_replace('/', '\/', $domain);
  }



  /*
   * Returns all the routed paths as an array of regular expressions
   *
   * @return Array
   */
  public function getRoutes(){
    return array_keys($this->routes);
  }


  /*
   * Returns the request url written in a neat form with
   * each request parameter following the next after a forward slash,
   * much like what the .htaccess file does. This is to give order to
   * the position of the request parameters so that the request can
   * be routed using regular expressions
   *
   * @return String
   */
  public static function getURL(){
    if(Router::$url == null){
      Router::$url  = '';
      for($i=1; $i < 5; $i++){
        if(isset($_GET["p$i"]))
          Router::$url .= $_GET["p$i"] . '/';
      }
    }
    return Router::$url;
  }


  /*
   * Returns the controller routed to the current request url
   * or null if the url does not match any route
   *
   * @return Controller|null
   */
  public function getController(){
    $url = $this->getURL();
    foreach($this->routes as $regex  => $ctrl){
      if(preg_match($regex, $url)){
        return $ctrl;
      }
    }
    return null;
  }


  /*
   * Receives the given routes and turns all url routes into regular 
   * expressions and puts them in a key value store with the given 
   * Controller to be accessed later on
   *
   *
   * @param $routes = Assoc Array containing key value store of the path 
   *                  and controller/function
   *
   * @throws KnownException
   * @return null
   */
  public function route($routes){
    foreach($routes as $url => $controller){
      //always end routing url with '/'
      if(substr($url, -1, 1) != '/')
        $url .= '/';
      $exp = explode('/', $url);
      $regex = $this->domain;
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
        $controller = new _InjectController($controller);
      }

      // just in case the given Controller is not an instance of Controller
      // best to handle it ourselves with a tidy exception than have PHP
      // output warnings and errors
      if(!$controller instanceof Controller)
        throw new KnownException("Invalid object registered as controller", ERR_BAD_ROUTE);
      
      
      $controller->_setParams($params);
      $this->routes[$regex] = $controller;
    }
  }
}
