<?php

$DEFAULT_NULL = ['default' => 'NULL'];
$UNIQUE = ['unique' => true];
$DEFAULT_CURRENT_DATE = ['default' => 'CURRENT_TIMESTAMP'];

class ProfileMeta extends ModelMeta{
  
  protected $actions = [
    'follow' => 'follow',
    'unfollow' => 'unfollow'
  ];

  public function __construct(){
    parent::__construct('profiles',[
      'name'        => new CharField(80),
      'email'       => new CharField(255),
      'telephone'   => new CharField(10),
      'description' => new TextField(),
      'created'     => new DateTimeField($DEFAULT_CURRENT_DATE),
      'owner'       => new ForeignKey('users'),
      'bgColour'    => new CharField(7, ['default' => '#fffff']),
      'bgURI'       => new CharField(255, $DEFAULT_NULL),
      'geo'         => new CharField(255, $DEFAULT_NULL),
      'website'     => new CharField(255, $DEFAULT_NULL)
    ]);

    $this->acl['WRITE'] = ModelMeta::$OWN_WRITE;
  }
}

class Profile extends Model{
  public function follow($user_id){
    if(isset($this->id)){
      new ProfileFollowers([
        'user_id' => $user_id,
        'profile_id' => $this->id,
      ]);
      return Response::success($this->name . ' followed.');
    }
    return Response::fail('Tried to follow invalid profile');
  }

  public function unfollow($user_id){
    if(isset($this->id)){
      $follow = Models::find('profile_followers', [
        'user_id' => $user_id,
        'profile_id' => $this->id
      ]);
      if($follow){
        $follow->delete();
      }
      return Response::success($this->name . ' unfollowed.');
    }
    return Response::fail('Tried to unfollow invalid profile');
  }
}

class ProfileFollowersMeta extends ModelMeta{
  public function __construct(){
    parent::__construct('profile_followers', [
      'user_id'  => new ForeignKey('users'),
      'profile_id'  => new ForeignKey('profiles'),
      new CompositeKey('user_id', 'profile_id')
    ]);
  }
}class ProfileMeta extends Model{}


class ProfileMediaMeta extends ModelMeta{
  public function __construct(){
    parent::__construct('profile_media', [
      'profile_id'  => new ForeignKey('profiles'),
      'uri'         => new CharField(255),
      'extension'        => new CharField(3),
    ]);
  }
}class ProfileMedia extends Model{}

