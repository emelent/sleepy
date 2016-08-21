<?php

require_once 'lib/Model.php';
require_once 'lib/ModelManager.php';


class GlobMeta extends ModelMeta{

  public function __construct(){
    parent::__construct(
      'globs', 
      [
        'name' => new CharField(50),
        'age' => new IntegerField(),
        'bio' => new TextField(),
      ]
    );
  }
}
class Glob extends Model {}

ModelManager::register('Glob');


