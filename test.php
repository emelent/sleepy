<?php

require_once('admin/config.php');


class GlobMeta extends ModelMeta{

  public function __construct(){
    parent::__construct(
      'globs',  //table name
      [ //table columns
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
$glob = new Glob(['name'=>'Marcus', 'age'=> 5, 'bio'=>'This is the bio']);
$glob->save();


//$glob = Models::create('Glob', ['name'=>'Marcus', 'age'=> 5, 'bio'=>'This is the bio']);

