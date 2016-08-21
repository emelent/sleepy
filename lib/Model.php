<?php

//require_once 'Config.php';
//require_once 'App.php';
require_once 'ModelFieldTypes.php';
//require_once 'utils.php';

class MetaFactory{

  private static $metas  = [];

  public static function getMeta($metaName){
    if(!isset(MetaFactory::$metas[$metaName])){
      MetaFactory::$metas[$metaName] = new $metaName();
    }
    return MetaFactory::$metas[$metaName];
  }
}

function getMeta($modelName){
  return MetaFactory::getMeta($modelName . 'Meta');
}

abstract class ModelMeta{

  private static $pdo = null;

  private $tableName; 
  private $modelName;
  private $attr_define;

  private $insertStatement;
  private $updateStatement;
  private $deleteStatement;
  private $selectStatement;
  private $selectAllStatement;

  //TODO delete all statement, delete multiple, update multiple

  private $sqlSchema;


  public function __construct($tableName, $attr_define){
    if(ModelMeta::$pdo == null)
      ModelMeta::$pdo = App::getInstance()->getDbManager()->getPdo();
    if(count($this->attr_define)){
        throw new KnownException('ModelMeta created without any attributes', ERR_UNEXPECTED);
    }
    $this->tableName = $tableName;
    $this->modelName = substr(get_class($this), 0, strlen(get_class($this)) - strlen('Meta'));
    $this->attr_define = $attr_define;
    $this->prepareStatements();
  }
  
  private function prepareStatements(){
    $tn = $this->tableName;
    $pdo = $this->getPdo();

    $insert = "INSERT INTO `$tn` (%s) VALUES (%s)";
    $update = "UPDATE `$tn` SET %s WHERE id = :id";
    $delete = "DELETE FROM `$tn` WHERE id = :id";
    $select = "SELECT %s FROM `$tn` WHERE id = :id";
    $selectAll = "SELECT %s FROM `$tn`";

    $col_str = '';
    $insert_str  = '';
    $update_str = '';
    $comma_delim = ', ';
    $len_comma_delim = strlen($comma_delim);

    foreach($this->attr_define as $key => $value){
      $col_str .= "`$key`$comma_delim";
      $insert_str .= ":$key$comma_delim";
      $update_str .= "`$key` = :$key$comma_delim";
    }

    //remove last ', '
    $col_str = substr($col_str, 0, strlen($col_str) - $len_comma_delim);
    $insert_str = substr($insert_str, 0, strlen($insert_str) - $len_comma_delim);
    $update_str = substr($update_str, 0, strlen($update_str) - $len_comma_delim);

    $insert = sprintf($insert, $col_str, $insert_str);
    $select = sprintf($select, "`id`, $col_str");
    $selectAll = sprintf($selectAll, "`id`, $col_str");
    $update = sprintf($update, $update_str);

    //create pdo statements
    $this->insertStatement = $pdo->prepare($insert);
    $this->updateStatement = $pdo->prepare($update);
    $this->deleteStatement = $pdo->prepare($delete);
    $this->selectStatement = $pdo->prepare($select);
    $this->selectAllStatement = $pdo->prepare($selectAll);
  }

  public function getSqlSchema(){
    $tn = $this->tableName;
    $indent='   ';
    $sqlSchema ="CREATE TABLE IF NOT EXISTS `$tn`(\n"
     . "$indent`id` INT PRIMARY KEY AUTO_INCREMENT,\n" 
      ;
    foreach($this->attr_define as $key => $value){
      $parent = get_parent_class($value);
      if ($parent == 'BaseFieldType'){
       $sqlSchema .= "$indent`$key` $value,\n"; 
      }else{
        throw new KnownException('Invalid ModelMeta DataType', ERR_UNEXPECTED);
      }
    }

    //remove last ','
    $sqlSchema = substr($sqlSchema, 0, strlen($sqlSchema) -2);
    $sqlSchema .= "\n);\n";

    return $sqlSchema;
  }

  public function getTableName(){
    return $this->tableName;
  }

  public function getModelName(){
    return $this->modelName;
  }

  public function getInsertStatement(){
    return $this->insertStatement;
  }

  public function getDeleteStatement(){
    return $this->deleteStatement;
  }

  public function getUpdateStatement(){
    return $this->updateStatement;
  }

  public function getSelectStatement(){
    return $this->selectStatement;
  }

  public function getSelectAllStatement(){
    return $this->selectAllStatement;
  }
  public function getAttributeDefinitions(){
    return $this->attr_define;
  }

  public function getAttributeKeys(){
    return array_keys($this->attr_define);
  }

  public static function getPdo(){
    return ModelMeta::$pdo;
  }
}

final class Models{

  private static function throwInvalidData($method){
      throw new KnownException("Invalid data passed to Models::$method()", ERR_BAD_REQ);
  }

  public static function create($modelName, $data){
    $meta = getMeta($modelName);
    $stmnt = $meta->getInsertStatement();
    if(!arrayKeysSet($meta->getAttributeKeys(), $data)){
      Models::throwInvalidData('create');
    }
    //TODO check if $data doesn't contain extra values
    $stmnt->execute($data);
    $id = $meta->getPdo()->lastInsertId();

    return Models::fetchById($modelName, $id);
  }

  public static function delete($modelName, $data){
    $meta = getMeta($modelName);
    $stmnt = $meta->getDeleteStatement();
    $stmnt->execute($data);
  }

  public static function update($modelName, $data){
    $meta = getMeta($modelName);
    if(!isset($data['id'])){
      Models::throwInvalidData('update');
    }
    $stmnt = $meta->getUpdateStatement();
    $stmnt->execute($data);
  }

  public static function fetchSingle($modelName, $data=null){
    if($data == null){
      $stmnt = getMeta($modelName)->getSelectAllStatement();
      $stmnt->execute();
    }else{
      $stmnt = Models::createSelectStatement($modelName, $data);
      $stmnt->execute($data);
    }

    return $stmnt->fetch();
  } 

  public static function fetchAll($modelName, $data=null){
    if($data == null){
      $stmnt = getMeta($modelName)->getSelectAllStatement();
      $stmnt->execute();
    }else{
      $stmnt = Models::createSelectStatement($modelName, $data);
      $stmnt->execute($data);
    }

    return $stmnt->fetchAll();
  }

  public static function fetchById($modelName, $id){
    $meta = getMeta($modelName);
    $stmnt = $meta->getSelectStatement();
    $stmnt->execute(['id' => $id]);

    $stmnt->setFetchMode(PDO::FETCH_CLASS, $meta->getModelName());

    return $stmnt->fetch();
  }

  private static function createSelectStatement($modelName, $data){
    $meta = getMeta($modelName);
    //create query str
    $query = 'SELECT * FROM `' . $meta->getTableName() . '` WHERE %s';
    $str = '';
    $delim = ' AND ';
    foreach($data as $key => $value){
      $str .= "`$key` = :$key$delim";
    }
    $str = substr($str, 0, strlen($str) - strlen($delim));
    $query = sprintf($query, $str);

    $stmnt = $meta->getPdo()->prepare($query);
    $stmnt->setFetchMode(PDO::FETCH_CLASS, $meta->getModelName());
    return $stmnt;
  }
}

abstract class Model{

  protected $className;
  protected $pdo;
  protected $meta;


  public function __construct(){
    $this->className = get_class($this);
    $this->pdo = App::getInstance()->getDbManager()->getPdo();
    $this->meta = getMeta($this->className);
    $this->createGettersAndSetters();
  }

  public final function __call($method, $args){
    $classname = get_class($this);
    $args = array_merge(array($classname => $this), $args);
    if(isset($this->{$method}) && is_callable($this->{$method})){
      return call_user_func($this->{$method}, $args);
    }else{
      throw new exception(
        "$classname error: call to undefined method $classname::{$method}()");
    }
  }
  
  private function createGettersAndSetters(){
    //TODO figure out how to make dynamic methods not get picked up by
    //json_encode
    foreach($this->meta->getAttributeKeys() as $attr_name){
      $this->{"set" . ucfirst($attr_name)} = function ($args) use ($attr_name) {
          $this->$attr_name = $args[0];
      };

      $this->{"get" . ucfirst($attr_name)} = function () use ($attr_name) {
          return $this->$attr_name;
      };
    }
  }

  public final function save(){
    //save model to database
    if(isset($this->id)){
      Models::update($this->className, $this->toArray());
    }else{
      $obj = Models::create($this->className, $this->toArrayNoId());
      $this->id = $obj->id;
    }
  }

  public final function delete(){
    //delete model from database
    Models::delete($this->className, ['id' => $this->id]);

    //clear id, since the record is no longer in the database
    $this->id = null;
  }

  public final function toArray(){
    $record = ['id' => $this->id];
    foreach($this->meta->getAttributeKeys() as $key){
      $record[$key] = $this->{$key};
    }
    return $record;
  }

  public final function toArrayNoId(){
    $record = [];
    foreach($this->meta->getAttributeKeys() as $key){
      if($key == 'id') continue;
      $record[$key] = $this->{$key};
    }
    return $record;
  }

  public final function getMeta(){
    return $this->meta;
  }


}
