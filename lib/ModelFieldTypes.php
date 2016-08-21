<?php

require_once 'Config.php';


class BaseFieldType
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
        $this->stringValue .= ($this->null) ? '':' NOT NULL';
        $this->stringValue .= ($this->unique) ? ' UNIQUE': '';
        return $this->stringValue;
    }
}

class CustomField extends BaseFieldType{
  public function __construct($sqlString){
    $this->stringValue = $sqlString;
  }

  public function __toString(){
    return $this->stringValue;
  }
}

# STANDARD SQL TYPES
class CharField extends BaseFieldType
{

    protected $length = null;

    public function __construct($length, $assoc=null){
      parent::__construct($assoc);
      $this->length = $length;
    }
    public function __toString()
    {
      $this->stringValue = "VARCHAR($this->length)";
      return parent::__toString();
    }
}

;


class TextField extends BaseFieldType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "TEXT";
        return parent::__toString();
    }
}

;

class IntegerField extends BaseFieldType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "INT";
        return parent::__toString();
    }
}

class FloatField extends BaseFieldType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "FLOAT";
        return parent::__toString();
    }
}

class BooleanField extends BaseFieldType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "BOOLEAN";
        return parent::__toString();
    }
}

class BlobField extends BaseFieldType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "BLOB";
        return parent::__toString();
    }
}


class DateField extends BaseFieldType
{
    public function __toString()
    {
        //add the default
        $this->stringValue = "DATE";
        return parent::__toString();
    }
}

