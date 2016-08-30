<?php

class UserController extends RoutedController{

  public function post_login($app, $args){
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $app->hashPassword($_POST['password']);
    $app->authenticateEmailPass($email, $password);
  }

  public function get_logout($app, $args){
    $app->authorise();
    $app->deauthenticateKey();
    $app->success(null);
  }

  public function post_create($app, $args){
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $app->hashPassword($_POST['password']);
    $app->createUserValidated($email, $pass);
  }

  public function delete_delete($app, $args){
    $app->authorise();
    $user = Models::fetchById('User', $app->getAuth()->user_id);
    if($user == null){
      $app->fail("Not logged in");
    }else{
      $user->delete();
      $app->success(null);
    }
  }

  public function all_info($app, $arg){
    $app->authorise();
    $app->success(Models::fetchById('User', $app->getAuth()->user_id));
  }

  public function index($app, $arg){
    $app->success("Yea baby");
  }
}

class AuthController extends RoutedController{

  public function post_deauthenticate($app, $args){
    $app->authorise();
    $app->deauthenticateKeys();
    $app->success(null);
  }
}
