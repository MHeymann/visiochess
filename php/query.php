<?php
require_once "mysql_interface.php";
// this file just echos back a json (array actually) that contains the filters
// this is actually just a check to see if it works

// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');
$filters = array();
foreach($_POST as $filter_field => $value) {
  array_push($filters, "filter: " . $filter_field . ", value: " . $value);
}
echo "<p>";
echo json_encode($filters);
echo "</p>";
?>
