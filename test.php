<?php

require_once('admin/config.php');


class GlobMeta extends ModelMeta{

  public function __construct(){
    parent::__construct(
      'globs', 
      [
        'name' => new CharField(50),
        'age' => new IntegerField(),
        'bio' => new TextField(),
        'created' => new DateTimeField(['default' => 'CURRENT_TIMESTAMP']),
      ]
    );
  }
}
class Glob extends Model {}

ModelManager::register('Glob');
ModelManager::recreateTables();
$glob = new Glob();
$glob->name = "Marcus";
$glob->age = 5;
$glob->bio = "This is my bio";
$glob->created = date("Y-m-d H:i:s");

$glob->save();


//$glob = Models::create('Glob', ['name'=>'Marcus', 'age'=> 5, 'bio'=>'This is the bio']);

