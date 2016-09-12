<?php

class UserController extends RoutedController{

  public function post_create($request, $args){
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $_POST['password'];

    //prevent duplicate email entries, this is already done by our DDL in
    //the User model's meta class, but to prevent from getting nasty SQL Db
    //exceptions for trying to enter a duplicate unique field we'll return
    //a failed response ourselves instead of letting the API handle the
    //exception thrown by our database so we can have a more detailed and clear
    //failure reason
    if(Models::fetchSingle('User', ['email'=> $email])){
      return Response::fail('Email already in use.');
    }
    $user = new User([
      'email' => $email,
      'password' => $pass,
      'username' => $email
    ]);
    $user->save();
    return Response::success();
  }

  public function delete_delete($request, $args){
    //TODO implement
    return Response::success();
  }

  public function index($request, $arg){
    $user = Auth::currentUser();
    //return Response::success($user->toJSON());
    return Response::success($user);
  }
}

class AuthController extends RoutedController{

  public function post_token($request, $args){
    return Auth::requestToken();
  }

  public function get_revoke($request, $args){
    return Auth::revokeToken();
  }

  public function post_revoke($request, $args){
    return Auth::revokeToken();
  }
}

