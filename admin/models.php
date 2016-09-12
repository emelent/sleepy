<?php

//USER META
class UserMeta extends ModelMeta{
  protected $hidden_attr = ['password'];

  public function __construct(){
    parent::__construct('users', [
      'email'  => new CharField(40, ['unique' => true]),
      'username' => new CharField(255, ['unique' => true]),
      'password' => new CharField(255),
      'created' => new DateTimeField(['default'=> 'CURRENT_TIMESTAMP']),
      'validated' => new BooleanField(['default'=> 'FALSE']),
      'group' => new IntegerField(['default'=> 1]),
      'first_name' => new CharField(255, ['null'=>true]),  
      'last_name' => new CharField(255, ['null'=>true]),  
    ]); 
  }
}

//USER MODEL
class User extends Model{

  public function setPassword($pass){
    $this->password = Auth::hashPassword($pass);
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
