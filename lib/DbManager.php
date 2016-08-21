<?php

// ===========================================================================
//
//
// This file contains the class declaration of the DbManager class
//
// Please note that any methods or classes beginning with an underscore whether
// public or private are not meant to be called directly by a user but are for 
// the inner functioning of the library as a whole.
//
//
// ===========================================================================


/*
 * DbManager class used for reading and writing to the database in a secure
 * and secure and simple manner.
 */
class DbManager{
  
  /// this maybe changed in future iterations to allow multiple db connections
  private static $pdo = null;

  public function __construct($dsn, $dbhost, $dbname, $dbuser, $dbpass, $port=3306){
    if($this::$pdo == null){
      try{
        $this::$pdo = new PDO("$dsn:dbname=$dbname;host=$dbhost;port=$port", $dbuser, $dbpass);
        $this::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      }catch(PDOException $e){
        throw new KnownException('Failed to initialize database => ' . 
          $e->getMessage(), ERR_DB_ERROR);
      }
    }
  }


  /*
   * Returns PDO connection
   *
   * @return PDOConnection
   */
  public static function getPdo(){
    return DbManager::$pdo;
  }


  /*
   * Runs INSERT query on database
   * e.g
   *   insert('people', ['name' => 'Ronald', 'age' => 32]);
   *
   * @param $table      = String table name
   * @param $data       = Array key-value store of columns and values
   *
   * @throws KnownException
   * @return null
   */

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


  /*
   * Runs SELECT query on database
   * This does not include the use of "LIKE" or other comparison key words and
   * only makes use of '='. This maybe added in future iterations.
   *
   * @param $table      = String table name
   *
   * @param $data       = Array key-value store of columns and values to search
   *                      for
   *
   * @param $select     = Array of columns to select from, default is null which 
   *                      uses 'SELECT *'
   *
   * @param $orderBy    = Array of columns to order the results by, default is
   *                      null, which does not order the columns.
   *
   * @param $orderAsc   = Boolean order in ascending or descending order
   *                      default is ascending 
   *
   * @param $inclusive  = Boolean perform inclusive search(use 'AND' between
   *                      all search data) or use exclusive search('OR')
   *                      default is true
   *
   * @param $all        = Boolean return fetch all results or fetch first 
   *                      result, default is true
   *
   * @throws KnownException
   * @return Array | StdObject
   */

  public function fetchAll(
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

  public function fetchSingle(
    $table, $data, $select=null, $orderBy=null, $orderAsc=true, $inclusive=true)
  {
    return $this->fetchAll($table, $data, $select, $orderBy, $orderAsc, $inclusive, false);
  }

  /*
   * Runs DELETE query on database
   *
   * @param $table      = String table name
   *
   * @param $data       = Array key-value store of columns and values to search
   *                      for
   *
   * @param $inclusive  = Boolean perform inclusive search(use 'AND' between
   *                      all search data) or use exclusive search('OR')
   *                      default is true
   *
   * @throws KnownException
   * @return null
   */

  public function delete($table, $data, $inclusive=true){
    $this->verifyData($data);
    $query = "DELETE FROM $table";
    if(count($data) > 0)
      $query .= " WHERE " . $this->whereClause($data, $inclusive);

    $this->execQuery($query, $data);
  }


  /*
   * Runs UPDATE query on database
   *
   * @param $table      = String table name
   *
   * @param $dataOld    = Array key-value store of columns and values to search
   *                      for
   * @param $dataNew    = Array key-value store of columns and values to 
   *                      replace
   *
   * @param $inclusive  = Boolean perform inclusive search(use 'AND' between
   *                      all search data) or use exclusive search('OR')
   *                      default is true
   *
   * @throws KnownException
   * @return null
   */
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



  /*
   * Runs query on database with given data
   *
   * @param $query      = String SQL query to be used in prepared statement
   *                     
   * @param $data       = Array key-value store of columns and values to use
   *                      when executing statement
   *
   *
   * @throws KnownException
   * @return null
   */
  public function execQuery($query, $data=null){
    $stmnt = $this::$pdo->prepare($query);
    $stmnt->execute($data);
  }


  /*
   * Runs query on database with given data and returns result
   *
   * @param $query      = String SQL query to be used in prepared statement
   *                     
   * @param $data       = Array key-value store of columns and values to use
   *                      when executing statement
   *
   *
   * @throws KnownException
   * @return null
   */
  public function fetchQuery($query, $data=null, $all=True){
    $stmnt = $this::$pdo->prepare($query);
    $stmnt->setFetchMode(PDO::FETCH_OBJ);

    $stmnt->execute($data);
    if($all)
      return $stmnt->fetchAll();
    return $stmnt->fetch();
  }


  /*
   * Returns last insert id
   *
   * @return id
   */
  public function getLastInsertId(){
    return $this::$pdo->lastInsertId();
  }


  /*
   * Verifies that the given data is an array
   *
   * @param $data     = Array
   *
   * @throws KnownException
   * @return null
   */
  private function verifyData($data){
    if(gettype($data) != 'array')
      throw new KnownException(
        '$data variable passed to DbManager CRUD method must be array',
        ERR_DB_ERROR
      );
  }


  /*
   * Used to form the WHERE clause in a statement given a key value store
   *
   * @param $data       = Array key-value store of columns and values to use
   *                      when executing statement
   *
   * @param $inclusive     = Boolean if true uses 'AND' to bind statements
   *                         and uses 'OR' if false
   *
   * @return String
   */
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
}
