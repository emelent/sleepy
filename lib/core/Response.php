<?php

class Response{

  private $status;
  private $success;
  private $data;

  public function __construct($success, $data, $status=200){
    $this->status = $status;
    $this->success = $success;
    $this->data = $data;
  }

  public static function success($data=null){
    return new Response(true, $data);
  }

  public static function fail($data, $status){
    return new Response(false, $data, $status);
  }

  public function toJSON(){
    return json_encode([
      'successful' => $this->success,
      'data' =>  $this->data
    ]);
  }

  public function unwrap(){
    //TODO change this to block all output streams and only let unwrap
    //TODO set the response status of the request and so forth
    header('Content-Type: application/json');
    http_response_code($this->status);
    die($this->toJSON() . PHP_EOL);
  }

  public function getStatus(){
    return $this->status;
  }

  public function isSuccessful(){
    return $this->success;
  }

  public function getData(){
    return $this->data;
  }
}
