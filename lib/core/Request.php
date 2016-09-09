<?php

class Request{

  private static $INSTANCE = null;

  private $headers;
  private $params;
  private $url;
  //private $apiKey;

  public static function getInstance(){
    if(Request::$INSTANCE == null){
      Request::$INSTANCE = new Request();
    }
    return Request::$INSTANCE;
  }

  private function __construct(){
    if(function_exists('getallheaders')){
      $this->headers = getallheaders();
    }else{
      $this->headers = [];
      //TODO implement the nginx version
    }
    //$this->apiKey = getHeader(API_KEY);
  }

  //public function getApiKey(){
    //return $this->apiKey;
  //}
  public function getRequestMethod(){
    return $_SERVER['REQUEST_METHOD'];
  }

  public function getRemoteIp(){
    return $_SERVER['REMOTE_ADDR'];
  }

  public function getHeader($key){
    if(isset($this->headers[$key])){
      return $this->headers[$key];
    }
    return null;
  }

  public function _setParams($params){
    $this->params = $params;
    foreach($this->params as $key => $val){
      $this->params[$key] = $_GET["p$val"];
    }
    return $this->params;
  }

  public function getParam($key){
    if(isset($this->params[$key])){
      return $this->params[$key];
    return $this->params;
    }
    return null;
  }

  public function getParams(){
    return $this->params;
  }


  public function getHeaders(){
    return $this->headers;
  }

  public function getRawUrl(){
    return $_SERVER['REQUEST_URI'];
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
  public function getUrl(){
    if($this->url == null){
      $this->url  = '';
      for($i=1; $i < 5; $i++){
        if(isset($_GET["p$i"]))
          $this->url .= $_GET["p$i"] . '/';
      }
      //resolve empty string to '/'
      if($this->url == ''){
        $this->url='/';
      }
    }
    return $this->url;
  }
}
