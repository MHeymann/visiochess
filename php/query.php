<?php
require_once "mysql_interface.php";
// this is actually still just a check to see if it works

// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');
$filters = array();
foreach($_POST as $filter_field => $value) {
  $filters[$filter_field] = $value;
}

$json = array(
	'error' => 'false',
	'message' => $filters
);
echo  json_encode($json);
?>
