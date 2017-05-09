<?php
require_once "mysql_interface.php";
require_once "eco_category.php";
require_once "validate.php";
require_once "utils.php";

/* still needed for testing */
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$filters = array();
foreach($_POST as $filter_field => $value) {
	$filters[$filter_field] = trim($value);
}

/* filter validation checks */
$validation = validate_filters($filters);
if($validation['error']) {
	echo json_encode($validation);
	die();
}
$filter_on = $validation['filter_on'];

/* if no database set, set default */
if(!$filters['database']) {
	$filters['database'] = 'default_chess_db';
}


/* get login details */
$settings = parse_ini_file(__DIR__."/../.my.cnf", true);

/* setup database interface connection */
$db = new MySqlPhpInterface(
	$server=$settings['client']['mysql_server'],
	$user=$settings['client']['user'],
	$password=$settings['client']['password']
);
$db->connect();

$db->use_database($filters['database']);

/* set up the query "WHERE" fields */
$query = array();
set_query_where_fields($filter_on, $filters, $query);

/* Set up the query structures */
$select = ['`date`', 'CONCAT(eco_alpha, eco_numero) as `eco`', 'COUNT(*) AS `popularity`'];
$groups = ['GROUP BY `eco`, `date`', 'ORDER BY `date`, `popularity` DESC'];

$result = $db->select_from(
		'tags',
		$select,
		$query,
		$groups
	);

$db->disconnect();

if(!count($result)) {
	echo json_encode(array(
		'error'=>true,
		'error_message' => "No data exists that satisfy these filters."
	));
	die();
}

/* if some error occured, the program would have exited, so we can assume
 * we are in normal functioning. */

/* process returned data */
$num_ecos = 0;
$top_ecos = array();
$json_data = array();

process_year_data($result, $num_ecos, $top_ecos, $json_data);

if($num_ecos > 9) {
	$top_ecos[] = 'other';
}

$json = array();
if(isset($error) && $error) {
	$json['error'] = true;
	$json['error_message'] = "MySQL server could not process request";
} else {
	$json['error'] = false;
	$json['data'] = $json_data;
	$json['pop'] = $top_ecos;
}
echo json_encode($json);
?>
