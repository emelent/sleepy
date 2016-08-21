<?php

class UserController extends RoutedController{

  public function login($app, $args){
    $this->failIfRequestNotIn($app, ['POST']); 
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $app->hashPassword($_POST['password']);
    $app->authenticateEmailPass($email, $password);
  }

  public function logout($app, $args){
    $this->failIfRequestNotIn($app, ['GET', 'POST']); 
    $app->authorise();
    $app->deauthenticateKey();
    $app->success(null);
  }

  public function create($app, $args){
    $this->failIfRequestNotIn($app, ['POST']); 
    $this->assertArrayKeysSet(['email', 'password'], $_POST);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $pass = $app->hashPassword($_POST['password']);
    $app->createUserValidated($email, $pass);
  }

  public function delete($app, $args){
    $this->failIfRequestNotIn($app, ['DELETE']); 
    $app->authorise();
    $user = Models::fetchById('User', $app->getAuth()->user_id);
    if($user == null){
      $app->fail("Not logged in");
    }else{
      $user->delete();
      $app->success(null);
    }
  }

  public function info($app, $arg){
    $app->authorise();
    $app->success(Models::fetchById('User', $app->getAuth()->user_id));
  }
}

class AuthController extends RoutedController{

  public function key($app, $args){
    $app->authorise();
    $app->success($app->auth->key);
  }

  public function deauthenticate($app, $args){
    $app->authorise();
    $app->deauthenticateKeys();
    $app->success(null);
  }
}
