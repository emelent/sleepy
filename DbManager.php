<?php

require_once 'Config.php';
class DbManager{
  
  private static $pdo = null;
  private $app;
  private $logging = true;

  public function __construct($app, $dsn, $dbhost, $dbname, $dbuser, $dbpass){
    try{
      $this::$pdo = new PDO("$dsn:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
      $this::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch(PDOException $e){
      throw new KnownException('Failed to initialize database => ' . 
        $e->getMessage(), ERR_DB_ERROR);
    }
    $this->app = $app;
  }

  public static function getPDO(){
    return DbManager::$pdo;
  }

  public function insert($table, $data){
    $this->verifyData($data);
    $query = "INSERT INTO $table VALUES ";
    $query = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(array_keys($data), ", "),
        ':' . implode(array_keys($data), ", :")
    );

    $this->execQuery($query, $data);
  }

  public function fetch(
    $table, $data, $select=null, $orderBy=null, $orderAsc=true, $inclusive=true, $all=true)
  {
    $this->verifyData($data);
    if($select == null){
      $query = "SELECT * FROM $table";
    }else{
      $query = "SELECT " . implode(', ', $select) . " FROM $table";
    }
    if(count($data) > 0)
      $query .= " WHERE " . $this->whereClause($data, $inclusive);

    //ordering
    if($orderBy != null){
      $orderList = implode(', ', $orderBy);
      $order = ($orderAsc)? 'ASC':'DESC';
      $query .= " ORDER BY $orderList $order";
    }
    return $this->fetchQuery($query, $data, $all);
  }

  public function fetchQuery($query, $data, $all=True){
    $stmnt = $this::$pdo->prepare($query);
    $stmnt->setFetchMode(PDO::FETCH_OBJ);

    $stmnt->execute($data);
    $this->log($query, $data);

    if($all)
      return $stmnt->fetchAll();
    return $stmnt->fetch();
  }


  public function delete($table, $data, $inclusive=true){
    $this->verifyData($data);
    $query = "DELETE FROM $table";
    if(count($data) > 0)
      $query .= " WHERE " . $this->whereClause($data, $inclusive);

    $this->execQuery($query, $data);
  }

  public function update($table, $dataOld, $dataNew, $inclusive=true){
    $this->verifyData($dataOld);
    $this->verifyData($dataNew);
    $query = "UPDATE $table SET ";
    $set = "";
    $arrNew = []; 
    foreach($$dataOld as $key => $val){
      $arrNew["_$key"] = $val;
    }

    foreach($arrNew as $col){
      $c = substr($col, 1);
      if($first){
        $first = false;
        $set .= "$c = :$col";
        continue;
      }
      $set .= ", $c = :$col";
    }
    $query .= $set;
    if(count($dataOld) > 0)
      $query .= " WHERE " . $this->whereClause($data, $inclusive);

    $this->execQuery($query, array_merge($arrNew, $dataNew));
  }

  public function execQuery($query, $data){
    $stmnt = $this::$pdo->prepare($query);
    $stmnt->execute($data);
    $this->log($query, $data);
  }

  public function rawSQL($query){
    $result = $this::$pdo->query($query);
    $this->log($query, null);
    return $result;
  }

  public function setLogging($log){
    $this->logging = $log;
  }

  public function getLogging(){
    return $this->logging;
  }

  //public function createTable($table, $data){
    //$query = "CREATE `$table` (";
  //}

  //public function dropTable(){
  //}

  private function verifyData($data){
    if(gettype($data) != 'array')
      throw new KnownException(
        '$data variable passed to DbManager CRUD method must be array',
        ERR_DB_ERROR
      );
  }

  private function whereClause($data, $inclusive){
    $bind = ' AND';
    if(!$inclusive){
      $bind = ' OR';
    }
    $first = true;
    $where = "";
    foreach($data as $col => $val){
      if($first){
        $first = false;
        $where .= "$col = :$col";
        continue;
      }
      $where .= "$bind $col = :$col";
    }

    return $where;
  }

  private function log($query, $data){
    if(!$this->logging)
      return;
    //echo "[QUERY] $query" . PHP_EOL;
    $stmnt = $this::$pdo->prepare(
      'INSERT INTO db_logs ' .
      '(user_id, ip_addr, query, data) ' . 
      'VALUES (:user_id, :ip_addr, :query, :data) ');
    $logData = [
      'user_id' => $this->app->getUserID(),
      'ip_addr' => $this->app->getUserIP(),
      'query'   => $query,
      'data'    => json_encode($data)
    ];
    $stmnt->execute($logData);
  }
}
