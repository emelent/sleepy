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
        $args[0]->result(null, true, null);
      };
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
      throw new exception(
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

//============================================================================
//
// RoutedController is no longer going to be used because of routing bugs
// when using named variables in url. It was an ambitious move but it would
// seem that life would be better without it
//
//===========================================================================

/*
 * RoutedController class is a Controller which contains it's own
 * Router object. This is to allow the grouping of related routes
 * with a common $domain name. The RoutedController will then
 * catch all requests beginning with domain name and then handle
 * specific routing. This also allows for the grouping of common
 * methods amongst the requests all contained in a single isolated
 * Controller class.
 *
 * E.g.
 *  A controller for all requests pertaining a user profile can
 *  be grouped into a class ProfileController extending RoutedController,
 *  with the $domain set to 'profiles/' in the contructor. So the class
 *  might look something like this.
 * 
 *
 *  class ProfileController extends RoutedController{
 *    public function __construct($domain){
 *      parent::__construct($domain);   //always call parent constructor
 *      //sub routing
 *      $this->route([
 *        'view/'        => function($app){...},
 *        'update/'      => function($app){...},
 *        'delete/'      => function($app){...},
 *      ]);
 *    }
 *  }
 *
 *  or if you want you can remove the constructor parameter and 
 *  have something like
 *
 *  class ProfileController extends RoutedController{
 *    public function __construct(){
 *      parent::__construct('profiles/');   //always call parent constructor
 *      //sub routing
 *      $this->route([
 *        'view/'        => function($app){...},
 *        'update/'      => function($app){...},
 *        'delete/'      => function($app){...},
 *      ]);
 *    }
 *  }
 *  
 *  then your routing to the App object will  look like
 *
 *  $app->route([
 *     'profiles/'    =>  new ProfileController(),
 *  ]);
 *
 *  or 
 *
 *  $app->route([
 *     'profiles/'    =>  new ProfileController('profiles/'),
 *  ]);
 *
 *  depending on how you defined your constructor. However the app route MUST
 *  be the same as the route you pass as the RoutedController $domain
 *
 */

//abstract class RoutedController extends Controller{
  //private $router;

  /*
   * @param $domain =  String common domain route 
   */
  //public function __construct($domain){
    //$this->router = new Router($domain);
    //$methods = ['delete', 'get', 'post', 'put'];
    //foreach($methods as $method){
      //$this->$method = function($args) use (&$method){
        //$url = $this->router->getURL();
        //$controller = $this->router->getController();
        //if($controller == null){
          //throw new KnownException("No route setup for '$url'", ERR_BAD_ROUTE);
        //}
        //$controller->$method($args[0]);
      //};
    //}
  //}

  /*
   * Handles routing via the Router object
   *
   * @throws KnownException
   * @return null
   */

  //protected function route($routes){
    //$this->router->route($routes);
  //}
//}
