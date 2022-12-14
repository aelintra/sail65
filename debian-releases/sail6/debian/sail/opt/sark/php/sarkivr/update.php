<?php
  require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkDbClass";
  require_once $_SERVER["DOCUMENT_ROOT"] . "../php/srkHelperClass";

  $tuple = array();
  
  $id = $_REQUEST['id'] ;
  $value = strip_tags($_REQUEST['value']) ;
  $column = $_REQUEST['columnName'] ;
  $columnPosition = $_REQUEST['columnPosition'] ;
  $columnId = $_REQUEST['columnId'] ;
  $rowId = $_REQUEST['rowId'] ;
  
  
  /* Update a record using information about id, columnName (property
     of the object or column in the table) and value that should be
     set */ 
  $helper = new helper;
  
    
  if ($column == 'desc') {
	  if (!preg_match("/^[\s\w\-0-9\(\)\.\*]+$/",$value)) {
		  echo "Description must be alphanumeric (no special characters)";
		  return;
	  }
  } 
  if ($column == 'options') {
	  if (!preg_match("/^[dhHnrRtTwWcikKxX]+$/",$value)) {
		  echo "Invalid option - allowed options are: dhHnrRtTwWcikKxX";
		  return;
	  }
  } 
  if ($column == 'pkey') {
	  if (!preg_match("/^[\w-]+$/",$value)) {
		  echo "key must be alphanumeric; no spaces";
		  return;
	  }
  } 
  if ($column == 'timeout' ) {	  
	  $tuple['timeoutrouteclass'] = $helper->setRouteClass($value);
  } 
  	  
/*  
 * set column=value in the array
 */
  $tuple[$column] = $value;
  $tuple['pkey'] = $id;
/*
 * call the setter
 */
  if ($column == 'pkey') {
	$ret = $helper->setTuple('ivrmenu',$tuple,$value);
  }
  else {
	$ret = $helper->setTuple('ivrmenu',$tuple);  
  }
  
  if ($ret == 'OK') {
	echo $_REQUEST['value'];
  }
  else {
	echo $ret;
  }  
?>
