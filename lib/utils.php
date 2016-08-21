<?php
  function arrayKeysSet($keys, $record){
    foreach($keys as $key){
        if(!isset($record[$key]))
            return false;
    } 
    return true;
  }
