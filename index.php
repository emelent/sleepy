<?php

require_once 'App.php';

class MyController extends RoutedController{
  public function __construct(){
    parent::__construct("routes");
    $this->route([
      'all/'       =>  function($app){
                            $app->success("All things come to an end");
                          },
      'some/'       =>  function($app){
                            $app->success("Some things come to an end");
                          },
    ]);
  }
}

$app= new App();

$app->route([
  'bye/:name/:surname/'      =>  function($app){
                    $app->success('Goodbye ' . $app->params['name'] . ' ' . $app->params['surname']);
                  },
  'routes/*'    => new MyController(),
  'tables/'     => function($app){
    $dbm = $app->getDbManager();
    $app->success($dbm->fetchQuery("show tables;", null)); 
  },
  'morning/'  =>  function($app){
                    $app->success('Lovely morning we have today');
                  },
  'users/'    =>  function($app){
                    if($_SERVER['REQUEST_METHOD'] != 'GET')
                      return;
                    $dbm = $app->getDbManager();
                    $users = $dbm->fetch('users', []);
                    $app->success($users);
                  },
  'user/login/'     =>  function($app){
                          if($_SERVER['REQUEST_METHOD'] != 'POST')
                            $app->fail('Unhandled request method');
                          $dbm = $app->getDbManager();
                          $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                          $pass = hash('sha256', $_POST['password']);
                          $users = $dbm->fetch(
                            'users', 
                            ['email'=> $email, 'password'=> $pass],
                            ['id', 'email', 'created', 'auth_lvl']
                          );
                          if($users){
                            $user = $users[0];
                            $app->login($user->id);
                            $app->success($user);
                          }else{
                            $app->fail('Invalid email and/or password');
                          }
                        },
  'user/create/'   =>   function($app){
                          if($_SERVER['REQUEST_METHOD'] != 'POST')
                            $app->fail('Unhandled request method');
                          $dbm = $app->getDbManager();
                          $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                          $pass = hash('sha256', $_POST['password']);
                          $dbm->insert(
                            'users',
                            ['email' => $email, 'password'=> $pass, 'validated'=>true]
                          );
                          $app->success(null);
                        },
  'user/delete/' =>  function($app){
                        if($_SERVER['REQUEST_METHOD'] != 'POST')
                          $app->fail('Unhandled request method');
                        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                        $pass = hash('sha256', $_POST['password']);
                        $dbm = $app->getDbManager();
                        $dbm->delete(
                          'users',
                          ['email' => $email, 'password'=> $pass]
                        );
                        session_destroy();
                        $_SESSION = [];
                        $app->success(null); 
                      },
  'user/login/status/' =>   function($app){
                              $app->success($app->isLoggedIn()); 
                            }
]);

$app->serve();
