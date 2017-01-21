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
        return Response::success();
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
        $newArgs = [];
        $index = 0;
        foreach($args as $val){
          if($index == 0){
            $index++;
            continue;
          }
          array_push($newArgs, $val);
        }
        return $callback(...$newArgs);
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


  public function index($request){
    return Response::success();
  }
}


/*
 * _ModelController is a controller used to create generic default 
 * CRUD endpoints for all registered models.
 *
 */

class _ModelController extends RoutedController{

  private static $READ    = 'READ';
  private static $WRITE   = 'WRITE';
  private static $CREATE  = 'CREATE';

  public function __construct($modelName){
    parent::__construct();
    $this->modelName = $modelName;
    $this->meta = getMeta($this->modelName);
  }

  protected function checkAccess($access){
    switch($access){
      case ModelMeta::$ALL_READ:
      case ModelMeta::$ALL_WRITE:
        break;

      case ModelMeta::$AUTH_READ:
      case ModelMeta::$AUTH_WRITE:
        Auth::requireAuth();
        break;

      case ModelMeta::$OWN_READ:
      case ModelMeta::$OWN_WRITE:
        return Auth::currentUser();
      case ModelMeta::$ADMIN_READ:
      case ModelMeta::$ADMIN_WRITE:
        return Auth::requireAdminAuth();
    }
    return null;
  }

  protected function requestAccess($type){
    $access = $this->meta->getAcl()[$type];   
    if(gettype($access) == 'array'){
      $count = 0;
      foreach($access as $acc){
        try{
          return $this->checkAccess($acc);
        }catch(KnownException $e){
          $count++;
          if($count >= count($access)){
            throw new KnownException('Not authorised.', ERR_UNAUTHORISED);
          }
        }
      }
    }

    return $this->checkAccess($access);
  }


  //TODO might want to comment this out on deploy
  public function index($request){
    $methName = '_index';
    if($request->getRequestMethod())
    if(method_exists($this->meta, $methName))
      return $this->meta->$methName($request);
    return Response::success("Yes... Tell me more about this '" . $this->modelName . "'");
  }

  public function _create($request){
    $this->requestAccess($this::$CREATE);
    $methName = '_create';
    if(method_exists($this->meta, $methName))
      return $this->meta->$methName($request);
    $data = array_intersect_key($_POST, array_flip($this->meta->getAttributeKeys()));
    $model = Models::create($this->modelName, $data); 

    return Response::success($model);
  }

  public function _delete($request){
    $access = $this->requestAccess($this::$WRITE);
    $methName = '_delete';
    if(method_exists($this->meta, $methName))
      return $this->meta->$methName($request);
    $data = array_intersect_key($_POST, array_flip(
      array_push($this->meta->getAttributeKeys(), 'id')));

    if($access != null){
      $data['creator'] = $access->getUid();
    }

    Models::delete($this->modelName, $data); 

    return Response::success($this->modelName . ' successfully deleted.');
  }

  public function _update($request){
    $access = $this->requestAccess($this::$WRITE);
    $methName = '_update';
    if(method_exists($this->meta, $methName))
      return $this->meta->$methName($request);
    $data = array_intersect_key($_POST, array_flip(
      array_push($this->meta->getAttributeKeys(), 'id')));
    if($access != null){
      $oldData = [
        'creator' => $access->getUid(),
        'id'  => $data['id']
      ];
      Models::updateAll($this->modelName, $data, $oldData); 
    }else{
      Models::update($this->modelName, $data); 
    }
    
    return Response::success($this->modelName . ' successfully updated.');
  }

  public function _updateAll($request){
    $access = $this->requestAccess($this::$WRITE);
    $methName = '_updateAll';
    if(method_exists($this->meta, $methName))
      return $this->meta->$methName($request);
    if(!arrayKeysSet(['find', 'set'], $_POST)){
      throw new KnownException("Missing params 'find' and 'set'.", ERR_INCOMP_REQ);
    }
    $oldData = json_decode($_POST['find'], true);
    $newData = json_decode($_POST['set'], true);

    if($access != null){
      $oldData['creator'] =$access->getUid();
    }
    Models::updateAll($this->modelName, $newData, $oldData);

    return Response::success($this->modelName . ' models updated.');
  }

  public function _find($request){
    $access = $this->requestAccess($this::$READ);
    $methName = '_find';
    if(method_exists($this->meta, $methName))
      return $this->meta->$methName($request);

    $arr = $this->meta->getAttributeKeys(); 
    array_push($arr, 'id');
    $data = array_intersect_key($_GET, array_flip($arr));
    $model = Models::find($this->modelName, $data); 

    return Response::success($model);
  }

  public function _findAll($request){
    $access = $this->requestAccess($this::$READ);
    $methName = '_findAll';
    if(method_exists($this->meta, $methName))
      return $this->meta->$methName($request);

    $arr = $this->meta->getAttributeKeys(); 
    array_push($arr, 'id');
    $data = array_intersect_key($_GET, array_flip($arr));
    $models = Models::findAll($this->modelName, $data); 

    return Response::success($models);
  }


  public function post_create($request){
    return $this->_create($request);
  }
  public function put_create($request){
    return $this->_create($request);
  }


  public function put_update($request){
    return $this->_update($request);
  }


  public function put_updateAll($request){
    return $this->_update($request);
  }


  public function get_find($request){
    return $this->_find($request);
  }


  public function get_findAll($request){
    return $this->_findAll($request);
  }


  public function post_delete($request){
    return $this->_delete($request);
  }
  public function delete_delete($request){
    return $this->_delete($request);
  }
}
