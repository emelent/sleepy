<?php
// DO NOT EDIT
require_once('admin/config.php');

try{
  App::serve(Request::getInstance());
}
catch(KnownException $exception){
  //Catch Known Exceptions
  ErrorResponse::resolveKnownException($exception);
}
catch(Exception $exception){
  //Catch Unexpected or Unknown Exceptions
  ErrorResponse::resolveUnknownException($exception);
}
