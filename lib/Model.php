<?php

require_once 'Config.php';


final class ModelMeta{

  private static $pdo = null;

  protected $attr_define = [];
  protected $attr_hidden = [];

  private $tableName; 
  private $modelName;
  private $insertStatement;
  private $updateStatement;
  private $deleteStatement;

  private $sqlSchema;

  public function __construct($tableName, $modelName){
    if($this::$pdo == null)
      $this::$pdo = $app->getDbManager()->getPdo();
    if(count($this->attr_define)){
        throw new KnownException('ModelMeta created without any attributes', ERR_UNEXPECTED);
    }
    $this->tableName = $tableName;
    $this->modelName = $modelName;
    $this->prepareStatements();
  }
  
  private function prepareStatements(){
    $tn = $this->tableName;
    $pdo = $this::$pdo;

    $columns = implode(', ', array_keys($this->attr_define));
    $insert = "INSERT INTO `$tn` (%s) VALUES (%s)";
    $update = "UPDATE `$tn` SET %s WHERE id = :id";
    $delete = "DELETE FROM `$tn` WHERE id = :id";

    $col_str = '';
    $insert_str  = '';
    $update_str = '';
    foreach($this->attr_define as $key => $value){
      $col_str .= "`$key`, ";
      $insert_str .= ":$key, ";
      $update_str .= "`$key` = :$key AND ";
    }

    //remove last ','
    $col_str = substr($col_str, strlen($col_str)-1);
    $insert_str = substr($insert_str, strlen($insert_str)-1);

    //remove last 'AND '
    $update_str = substr($update_str, strlen($update_str)- strlen("AND "));

    $insert = sprintf($insert, $col_str, $insert_str);
    $update = sprintf($update, $update_str);

    //create pdo statements
    $this->insertStatement = $pdo->prepare($insert);
    $this->updateStatement = $pdo->prepare($update);
    $this->deleteStatement = $pdo->prepare($delete);
  }

  public function getSqlSchema(){
    $tn = $this->tableName;

    $sqlSchema ="CREATE TABLE IF NOT EXISTS `$tn`(\n"
     . "`id` INT PRIMARY KEY AUTO_INCREMENT,\n" 
      ;
    foreach($this->attr_define as $key => $value){
      $parent = get_parent_class($value);
      if ($parent == 'DataType\BaseDataType' || $parent == 'DataType\BaseDBRel'){
       $sqlSchema .= " `$key` $value,"; 
      }else{
        throw new KnownException('Invalid ModelMeta DataType', ERR_UNEXPECTED);
      }
    }

    //remove last ','
    $sqlSchema = substr($sqlSchema, 0, strlen($sqlSchema) -1);
    $sqlSchema .= "\n);\n";

    return $sqlSchema;
  }

  public function getTableName(){
    return $this->tableName;
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

  public function getAttributeDefinitions(){
    return $this->attr_define;
  }

  public function getAttributeKeys(){
    return array_keys($this->attr_define);
  }

  public function getHiddenAttributes(){
    return $this->attr_hidden;
  }
}


abstract class Model{

  protected $className;
  protected $pdo;
  protected $meta;


  public function __construct($meta){
    $this->className = get_class($this);
    $this->pdo = $app->getDbManager()->getPdo();
    $this->prepareRetrievalMethods();
    $this->meta = $meta;
  }

  private final function sync($model){
    if(get_class($model) != get_class($this)){
      throw new KnownException('Model sync with non-matching types', ERR_UNEXPECTED);
    }
    foreach($this->meta->getAttributeKeys() as $key){
      $this->{$key} = $model->{$key};
    }
  }

  public final function create($record){
    $stmnt = $this->meta->getInsertStatement();
    $stmnt->execute($record);
    $id = $this->pdo->getLastInsertId();
    sync($this::fetchById($id));
  }

  public final function save(){
    //save model to database
    $record = [];
    foreach($this->meta->getAttributeKeys() as $key){
      $record[$key] = $this->{$key};
    }
    $this->meta->getUpdateStatement()->execute($record);
  }

  public final function delete(){
    //delete model from database
    $record = ['id' => $this->id];
    $this->meta->getDeleteStatement()->execute($record);
  }
}
