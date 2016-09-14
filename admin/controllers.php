<?php

class UserController extends RoutedController{

  public function post_create($request, $args){
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = strtolower($_POST['email']);
    $pass = $_POST['password'];

    if(!validateEmail($email)){
      return Response::fail("Invalid email address.");
    }

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
      'username' => str_replace('.', '_', str_replace('@', '_',$email))
    ]);
    $user->save();
    //create new activation code
    $code = new ActivationCode([
      'user_id' => $user->getId(),
      'expires' => date("Y-m-d H:i:s", strtotime("+7 day")),
      'code'    => hash('sha256', uniqid('c0d3', true))
    ]);
    $code->save();

    //TODO send activation code via email
    //mail(...);
    return Response::success("Account successfully created.");
  }

  public function post_delete($request, $args){
    //TODO implement
    $user = Auth::currentUser();
    Auth::revokeToken();
    $user->delete();
    return Response::success("Account successfully deleted.");
  }

  public function get_emailAvailable($request, $args){
    if(count($args) < 2){
      //TODO put a proper http response code
      return Response::fail("No email address sent.");
    }
    $email = $args[1];
    if(!validateEmail($email)){
      return Response::fail("Invalid email address.");
    }
    if(Models::fetchSingle('User', ['email'=> $email]))
      return Response::fail("Email already in use.");
    return Response::success("Email available.");
  }

  public function get_usernameAvailable($request, $args){
    if(count($args) < 2){
      //TODO put a proper http response code
      return Response::fail("No username sent.");
    }
    $username = $args[1];
    if(!validateUsername($username)){
      return Response::fail("Invalid username.");
    }
    if(Models::fetchSingle('User', ['username'=> $username]))
      return Response::fail("Username already in use.");
    return Response::success("Username available.");
  }

  public function post_update($request, $args){
    //TODO sanitize inputs
    $user = Auth::currentUser();
    if(isset($_POST['password'])){
      $user->setPassword($_POST['password']);
    }

    //TODO because token relies on username, changing username requires
    //a token to be revoked, set this up so OAuth storage uses the user model's
    //id instead
    if(isset($_POST['username'])){
      if(!validateUsername($_POST['username'])){
        return Response::fail("Invalid username.");
      }
      $user->setUsername(strtolower($_POST['username']));
      Auth::revokeToken();
    }

    if(isset($_POST['email'])){
      if(!validateEmail($_POST['email'])){
        return Response::fail("Invalid email address.");
      }
      $user->setEmail($_POST['email']);
    }

    if(isset($_POST['first_name'])){
      if(!validateEmail($_POST['first_name'])){
        return Response::fail("Invalid first name.");
      }
      $user->setFirstName($_POST['first_name']);
    }

    if(isset($_POST['last_name'])){
      if(!validateEmail($_POST['last_name'])){
        return Response::fail("Invalid last name.");
      }
      $user->setLastName($_POST['last_name']);
    }
    $user->save();
    return Response::success($user);
  }

  public function get_activate($request, $args){
    if(count($args) < 2){
      //TODO put a proper http response code
      return Response::fail("No activation code sent.");
    }
    $code = Models::fetchSingle('ActivationCode', ['code' => $args[1]]);
    if($code == null){
      return Response::fail("Invalid or used activation code.");
    }
    $user = Models::fetchById('User', $code->getUserId());
    if($user == null){
      return Response::fail("Invalid activation code.");
    }
    $user->setActivated(true);
    $user->save();
    $code->delete();
    return Response::success("Account successfully activated.");
  }

  public function index($request, $arg){
    $user = Auth::currentUser();
    return Response::success($user);
  }
}

class AuthController extends RoutedController{

  public function post_token($request, $args){
    return Auth::requestToken();
  }

  public function post_revoke($request, $args){
    return Auth::revokeToken();
  }
}

