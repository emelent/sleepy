<?php
namespace Model;

require_once 'Config.php';


class BaseSQLDataType
{
    protected $stringValue = '';
    protected $null = false;
    protected $default = false;
    protected $unique = false;

    public function __construct($assoc = null)
    {
        if ( $assoc == null )
            return;

        //set the properties according to provided assoc array
        foreach ($assoc as $property => $value) {
            if (isset($this->{$property})) {
                $this->{$property} = $value;
            }
        }
    }

    //creates a string that is used to define sql column-type
    public function __toString()
    {
        $this->stringValue .= ($this->default) ? " DEFAULT '$this->default'" : '';
        $this->stringValue .= ($this->null) ? '':" NOT NULL";
        $this->stringValue .= ($this->unique) ? '':" UNIQUE";
        return $this->stringValue;
    }
}

# STANDARD SQL TYPES
class VARCHAR extends BaseSQLDataType
{

    private $length = null;

    public function __toString()
    {
      if($this->length == null)
        throw new KnownException('Length not defined in VARCHAR SQLDataType', ERR_UNEXPECTED);
      $this->stringValue = "VARCHAR($this->length)";
      return parent::__toString();
    }
}

;


class TEXT extends BaseSQLDataType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "TEXT";
        return parent::__toString();
    }
}

;

class INTEGER extends BaseSQLDataType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "INT";
        return parent::__toString();
    }
}

//class FLOAT extends BaseSQLDataType
//{
    //public function __toString()
    //{
        ////add the default
        //$this->stringValue = "FLOAT";
        //return parent::__toString();
    //}
//}

class BOOLEAN extends BaseSQLDataType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "BOOLEAN";
        return parent::__toString();
    }
}

class BLOB extends BaseSQLDataType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "BLOB";
        return parent::__toString();
    }
}


class DATE extends BaseSQLDataType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "DATE";
        return parent::__toString();
    }
}

# Database Relations

class BaseDBRel{

    protected $stringValue;
    private $relatedTable;

    public function __construct($relatedTable, $unique){
        $this->stringValue = "INT NOT NULL ";
        $this->stringValue .= ($unique)? "UNIQUE " : "";
        $this->stringValue .= "REFERENCES $relatedTable(id)";
        $this->relatedTable = $relatedTable;
    }

    public function getRelatedTableName(){
        return $this->relatedTable;
    }
}

class OneToOneRel extends BaseDBRel{
    
    /*This class handles 1..1 relations between tables*/
    public function __construct($relatedTable){
        parent::__construct($relatedTable, true);
    }

    public function __toString()
    {
        return $this->stringValue;
    }
}

class OneToManyRel extends BaseDBRel{
    
    /*This class handles 1..1 relations between tables*/
    public function __construct($relatedTable){
        parent::__construct($relatedTable, false);
    }

    public function __toString()
    {
        return $this->stringValue;
    }
}


