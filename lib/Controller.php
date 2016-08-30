<?php

// ===========================================================================
//
//
// This file contains the class declarations of all Controller types and 
// subtypes to be inherited by the user of the library.
//
// Please note that any methods or classes beginning with an underscore whether
// public or private are not meant to be called directly by a user but are for 
// the inner functioning of the library as a whole.
//
//
// ===========================================================================


/*
 * Base Controller class from which all Controllers must inherit
 */
abstract class Controller{
  
  private $params;
  public function __construct(){
    $methods = ['delete', 'get', 'post', 'put'];
    foreach($methods as $method){
      $this->$method = function($args){
        $args[0]->success(null);
      };
    }
  }

  protected function assertArrayKeysSet($keys, $array){
    if(!arrayKeysSet($keys, $array)){
      throw new KnownException('Missing request parameters', ERR_INCOMP_REQ);
    }
  }
  /*
   * Handle function calls to dynamic methods
   *
   * @param $method     =  method to be called
   * @param $args       =  method that the function receives
   *
   * @throws exception
   * @return null
   */

  public final function __call($method, $args){
    $classname = get_class($this);
    $args = array_merge(array($classname => $this), $args);
    if(isset($this->{$method}) && is_callable($this->{$method})){
      return call_user_func($this->{$method}, $args);
    }else{
      throw new UnknownMethodCallException(
        "$classname error: call to undefined method $classname::{$method}()");
    }
  }

  /*
   * Sets parameter values(params) for controller, params are significant 
   * values retreived from the request url, these can be specified with
   * ":<name>" within the routing url
   *
   * e.g. with a route "users/view/:username/"
   *      and request "users/view/Michael/"
   *      "username" would be parameter containing the value "Michael"
   *      
   * These are useful for processing user requests
   *
   * @param $params     = Array of request parameter links, these do not
   *                      contain the actual parameters but a reference
   *                      as to how to get them and are only retreived
   *                      when the actual request for the route is received
   *
   * @return null
   */

  public final function _setParams($params){
    $this->params = $params;
  }


  /*
   * Performs the actual retrieval of parameters from request using
   * the references given by Controller::_setParams(), this method
   * should only be called after _setParams() as it cannot do anything
   * without the references.
   *
   * @return Array
   */
  public function _getParams(){
    foreach($this->params as $key => $val){
      $this->params[$key] = $_GET["p$val"];
    }
    return $this->params;
  }
};

/*
 * _InjectController class is a Controller which receives a callback
 * argument in the constructor with the actual function that the controller
 * must run for each request. Unlike other Controller classes this class
 * is not to be inherited from unless expanding the library. It is used
 * by the Router class to allow for the routing of url's to methods.
 *
 * How it works can easily be see by reading the code in Router::route()
 * and App::serve
 */

class _InjectController extends Controller{

  /*
   *@param $callback     =  Closure/function
   */
  public function __construct($callback){
    $methods = ['delete', 'get', 'post', 'put'];
    foreach($methods as $method){
      $this->$method = function($args) use (&$callback){
        $callback($args[0]);
      };
    }
   
  }
}

/*
 * RoutedController class which allows user to access public method calls
 * via a url route, e.g.
 *
 *    class HelloWorldController extends RoutedController{
 *      public function world($app, $args){
 *        $app->success("Hello World"); 
 *      }
 *    }
 *    ...
 *    //controller routes must have the '*' to catch all routes that begin with
 *    // 'hello/'
 *    $app->route(['hello/*' => new HelloWorldController()]);
 *
 * 
 * After mapping that, access <api-route>/hello/world/ will execute 
 * HelloWorldController::world(...)
 */
abstract class RoutedController extends Controller{

  //TODO polish this up, remove these constant strings and replace them with
  //glob vars
  protected function failIfRequestNotIn($app, $methods){
    if(!in_array($_SERVER['REQUEST_METHOD'], $methods)){
      $app->fail("Invalid request method");
    }
  }

  public function index($app, $args){
    $app->success();
  }
}
