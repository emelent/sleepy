<?php

final class ModelManager{

  private static $models = [];
  
  public static function register($modelName){
    array_push(ModelManager::$models, $modelName);
  }

  public static function getSqlSchema(){
    $sqlString = '';
    foreach(ModelManager::$models as $model){
      $sql .= getMeta($model)->generateSqlSchema() . "\n";
    }

    $sqlString;
  }

  public static function clearDb(){
    $query = '';
    foreach(ModelManager::$models as $model){
      $meta = getMeta($model);
      $query .= 'DROP TABLE IF EXISTS `' . $meta->getTableName() . "`;\n";
    }
    App::getInstance()->getDbManager()->execQuery($query);
  }

  public static function createTables(){
    //TODO maybe you can speed things up by doing it in one query
    //but for now I do it separately for easier error handling
    $dbMgr = App::getInstance()->getDbManager();
    foreach(ModelManager::$models as $model){
      $dbMgr->execQuery(getMeta($model)->getSqlSchema()); 
    }
  }

  public static function recreateTables(){
    ModelManager::clearDb();
    ModelManager::createTables();
  }
}

