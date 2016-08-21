<?php

require_once 'lib/Model.php';


class GlobMeta extends ModelMeta{

  public function __construct(){
    parent::__construct(
      'globs', 
      'Glob',
      [
        'name' => new CharField(50),
        'age' => new IntegerField(),
        'bio' => new TextField(),
      ]
    );
  }
}

class Glob extends Model{
  public function __construct(){
    parent::__construct(getMeta('GlobMeta'));
  }
}

$GLOB_META = getMeta('GlobMeta');

//echo $GLOB_META->getSqlSchema() . PHP_EOL;

$glob = ModelCRUD::create('Glob', ['name' => 'Mike', 'age' => 20, 'bio' => 'I like ants a bit']);
echo "JSON => " . json_encode($glob) . PHP_EOL;
echo "My name is " . $glob->getName() . PHP_EOL;
