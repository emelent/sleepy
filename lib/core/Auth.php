<?php

const GUEST = 0;
const USER = 1;
const ADMIN = 9;

class Auth{

  private static $user;
  private static $auth;

  public static $GROUP = [
    GUEST  => 'guest',
    USER  => 'user',
    ADMIN => 'admin'
  ];

  public static function hashPassword($password){
    return password_hash($password, PASSWORD_BCRYPT);
  }

  public static function requireAuthentication(){
    $request = Request::getInstance(); 
    $key = $request->getApiKey();
    
    //TODO check key with key in db
    //if it doesn't match, throw an exception

    //TODO check if key expired
    //throw an exception if it is and remove it from the db

    //TODO update $user
    
    //TODO update $auth
  }

  public static function requireAuthorisation($level){
    requireAuthentication();
    $auth = Auth::getAuth(); 
    if($auth->level < $level){
      //TODO throw exception not authorised
    }
  }

  public static function requireUserGroup($group){
    requireAuthentication();
    $user = Auth::getUser();
    if($user->group < $group){
      //TODO throw exception restricted access
    }
  }

  public static function getAuth(){
    //if(Auth::$auth == null){
      //TODO get auth model from db
    //}
    return Auth::$auth;
  }
  public static function getUser(){
    //if(Auth::$user == null){
      ////TODO get user from db using api key
    //}
    return Auth::$user;
  }
}
