<?php

//USER META
class UserMeta extends ModelMeta{
  public function __construct(){
    parent::__construct('users', [
      'created' => new DateTimeField(['default'=> 'CURRENT_TIMESTAMP']),
      'email'  => new CharField(40, ['unique' => true]),
      'password' => new CharField(64),
      'validated' => new BooleanField(['default'=>false]),
      'group' => new IntegerField(['default'=> 1])
    ]); 
  }
}

//USER MODEL
class User extends Model{
  public function setPassword($pass){
    //$this->password = App::getInstance()->hashPassword($pass);
    //TODO hash password
     
  }
}

//AUTH META
//class AuthMeta extends ModelMeta{
  //public function __construct(){
    //parent::__construct('auth', [
      //'created' => new DateTimeField(['default'=> 'CURRENT_TIMESTAMP']),
      //'expires' => new DateTimeField(),
      //'token' => new CharField(64),
      //'user_id' => new CustomField('INT REFERENCES `users`(id)'),
      //'level' => new IntegerField(['default' => 0])
    //]); 
  //}
//}

//AUTH MODEL
//class Auth extends Model{}

//register models
ModelManager::register('User');
//ModelManager::register('Auth');
