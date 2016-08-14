<?php

require_once 'lib/App.php';



$app->route([
  'bye/:name/:surname/'      =>  function($app){
                    $app->success('Goodbye ' . $app->params['name'] . ' ' . $app->params['surname']);
                  },
  
  'tables/'     => function($app){
    $dbm = $app->getDbManager();
    $app->success($dbm->fetchQuery("show tables;", null)); 
  },
  'morning/'  =>  function($app){
                    $app->success('Lovely morning we have today');
                  },
  'testDate/' => function($app){
                  $dbm = $app->getDbManager();
                  $day = 24*60*60*365;
                  $date = date("Y-m-d H:i:s", time()+ $day);
                  $dbm->insert(
                          'test',
                          ['created' => $date]
                        );
                  $app->success("Date stored as tommorw -> " + $date);
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
                          $app->authenticateEmailPass($email, $pass);
                        },
  'user/create/'   =>   function($app){
                          if($_SERVER['REQUEST_METHOD'] != 'POST')
                            $app->fail('Unhandled request method');
                          $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                          $pass = $_POST['password'];
                          $app->createUserValidated($email, $pass);
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
