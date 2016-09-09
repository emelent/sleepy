<?php

class UserController extends RoutedController{

  public function post_login($request, $args){
    //TODO implement with OAuth2
  }

  public function get_logout($request, $args){
    //TODO implement with OAuth2
  }

  public function post_create($request, $args){
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = Auth::hashPassword($_POST['password']);
    return Response::success();
  }

  public function delete_delete($request, $args){
    //TODO implement
    return Response::success();
  }

  public function all_info($request, $arg){
    return Response::success();
  }

  public function index($request, $arg){
    return Response::success("Nothing here");
  }
}

class AuthController extends RoutedController{

  public function post_deauthenticate($request, $args){
  }
}
