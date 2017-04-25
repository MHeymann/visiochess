<?php
require_once "mysql_interface.php";
// this is actually still just a check to see if it works

// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');
$filters = array();
foreach($_POST as $filter_field => $value) {
  $filters[$filter_field] = $value;
}

/*
 * TODO: change filters into queryable form and pass along to
 * mysql interface.
 */


/* TODO: Take results from mysql query and shape into data format as
 * currently shown in example below.
 */

$data = array();
$data["1990"] = [48.09, 24.14, 18.82, 7.46, 0.03, 1.32, 0, 0, 0, 0];
$data["1991"] = [48, 24.19, 18.96, 7.36, 0.03, 1.32, 0.12, 0.01, 0, 0, 0, 0];
$data["1992"] = [47.87, 24.44, 18.91, 7.27, 0.03, 1.36, 0.12, 0.01, 0, 0, 0, 0];
$data["1993"] = [48.22, 23.83, 19.16, 7.24, 0.04, 1.39, 0.12, 0.01, 0, 0, 0, 0];
$data["1994"] = [47.91, 23.86, 19.35, 7.31, 0.04, 1.41, 0.12, 0.01, 0, 0, 0, 0];
$data["1995"] = [48.78, 21.14, 19.66, 8.42, 0.05, 1.83, 0.1 ,0.01, 0, 0, 0, 0];
$data["1996"] = [49.43, 20.55, 19.42, 8.66, 0.05, 1.75, 0.12, 0.01, 0, 0, 0, 0];
$data["1997"] = [48.98, 23.47, 18.84, 7.25, 0.04, 1.28, 0.12, 0.01, 0, 0, 0, 0];
$data["1998"] = [48.69, 23.76, 18.89, 7.22, 0.04, 1.27, 0.11, 0.01, 0, 0, 0, 0];
$data["1999"] = [49.17, 23.35, 18.91, 7.09, 0.04, 1.32, 0.12, 0.01, 0, 0, 0, 0];
$data["2000"] = [49.32, 23.39, 18.76, 7.03, 0.04, 1.34, 0.11, 0, 0, 0, 0, 0];
$data["2001"] = [49.39, 23.11, 18.84, 7.14, 0.04, 1.37, 0.1, 0, 0, 0, 0, 0];
$data["2002"] = [49.77, 20.68, 19.23, 8.46, 0.05, 1.71, 0.09, 0.01, 0, 0, 0, 0];
$data["2003"] = [50.07, 20.41, 18.91, 8.77, 0.05, 1.69, 0.1, 0.01, 0, 0, 0, 0];
$data["2004"] = [49.32, 23.3, 18.54, 7.4, 0.04, 1.27, 0.11, 0.01, 0, 0, 0, 0];

$json = array(
	'error' => false,
	'error_message' => "Some error message here.",
	'pop' => ["A10", "B20", "C30", "D40", "E50", "A13", "B18", "C19", "D10", "other"],
	'data' => $data
);
echo  json_encode($json);
?>
