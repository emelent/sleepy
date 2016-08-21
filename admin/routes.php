<?php

$app->route([
  'user/*' => new UserController(),
  'auth/*' => new AuthController(),

  'migrate/' => function($app){
    //TODO remove this after adding proper migration script
    echo ModelManager::getSqlSchema() . "\n";
    ModelManager::recreateTables();
    $app->success("Migration successful");
  },

  '/' => function($app){
    $app->success("Kshhhh. Ground control to Major Tom, we have ReST");
  }
]);

