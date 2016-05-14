<?php
require_once 'Config.php';

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

  public final function _setParams($params){
    $this->params = $params;
  }

  public function _getParams(){
    foreach($this->params as $key => $val){
      var_dump($_GET);
      $this->params[$key] = $_GET["p$val"];
    }
    return $this->params;
  }
};


class InjectController extends Controller{

  public function __construct($callback){
    $methods = ['delete', 'get', 'post', 'put'];
    foreach($methods as $method){
      $this->$method = function($args) use (&$callback){
        $callback($args[0]);
      };
    }
   
  }
}
