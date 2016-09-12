<?php

function arrayKeysSet($keys, $record){
  foreach($keys as $key){
      if(!isset($record[$key]))
          return false;
  } 
  return true;
}

function snakeToCamel($phrase){
  $parts = explode('_', $phrase);
  $newPhrase = '';
  $first = true;
  foreach($parts as $part){
    if($first){
      $first = false;
      $newPhrase .= $part;
      continue;
    }
    $newPhrase .= ucfirst($part);
  }
  return $newPhrase;
}

