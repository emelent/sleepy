<?php


class Router{

  private $controllers = [];
  private $domain;
  private static $url = null;

  public function __construct($domain=''){
    if(substr($domain, -1, 1) != '/' && $domain != '')
      $domain .= '/';
    $this->domain = '/^' . str_replace('/', '\/', $domain);
  }

  public function getRoutes(){
    return array_keys($this->controllers);
  }

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

  public function getController(){
    $url = $this->getURL();
    foreach($this->controllers as $regex  => $ctrl){
      if(preg_match($regex, $url)){
        return $ctrl;
      }
    }
    return null;
  }

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
        $controller = new InjectController($controller);
      }
      if(!$controller instanceof Controller){
        $this->fail("Invalid object registered as controller");
      }
      $controller->_setParams($params);
      $this->controllers[$regex] = $controller;
    }
  }
}
