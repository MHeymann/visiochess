<?php
require_once "mysql_interface.php";
require_once "eco_category.php";
require_once "validate.php";

// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');
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

if(!$filters['database']) {
	$filters['database'] = 'default_chess_db';
}

if($filters['eco-filter-type'] == 'category') {
	if($filters['eco-category']) {
		$filter_on[] = 'eco-category';
	}
} if($filters['eco-filter-type'] == 'class') {
	$eco_filters = get_eco_class($filters['eco-class']);
	if($eco_filters) {
		$filter_on[] = 'eco-category';
		$filter_on[] = 'eco-low';
		$filter_on[] = 'eco-high';
		$filters['eco-category'] = $eco_filters['category'];
		$filters['eco-low'] = $eco_filters['low'];
		$filters['eco-high'] = $eco_filters['high'];
	}
}

/* query sql using those filters */
$settings = parse_ini_file(__DIR__."/../.my.cnf", true);
$servername = $settings['client']['mysql_server'];
$username = $settings['client']['user'];
$password = $settings['client']['password'];

$db = new MySqlPhpInterface(
	$server=$servername,
	$user=$username,
	$password=$password
);
$db->connect();

$db->use_database($filters['database']);

$query = array();
foreach ($filter_on as $field) {
	if(contains($field, 'year')) {
		if(!isset($query['date'])) {
			$query['date'] = array();
		}

		if(contains($field, 'low')) {
			$query['date']['>='] = (int) $filters['year-low'];
		} else if(contains($field, 'high')) {
			$query['date']['<='] = (int) $filters['year-high'];
		} else {
			$query['date']['='] = (int) $filters['year'];
		}
	} else if(contains($field, 'elo')) {
		if(contains($field, 'black')) {
			if(!isset($query['blackElo'])) {
				$query['blackElo'] = array();
			}

			if(contains($field, 'low')) {
				$query['blackElo']['>='] = (int) $filters['black-elo-low'];
			} else {
				$query['blackElo']['<='] = (int) $filters['black-elo-high'];
			}
		} else {
			if(!isset($query['whiteElo'])) {
				$query['whiteElo'] = array();
			}

			if(contains($field, 'low')) {
				$query['whiteElo']['>='] = (int) $filters['white-elo-low'];
			} else {
				$query['whiteElo']['<='] = (int) $filters['white-elo-high'];
			}
		}
	} else if(contains($field, 'eco')) {
		if(contains($field, 'category')) {
			if(!isset($query['eco_alpha'])) {
				$query['eco_alpha'] = array();
			}
			$query['eco_alpha']['LIKE'] = $filters['eco-category'];
		} else if(contains($field, 'low')) {
			if(!isset($query['eco_numero'])) {
				$query['eco_numero'] = array();
			}
			$query['eco_numero'][">="] = $filters['eco-low'];
		} else {
			if(!isset($query['eco_numero'])) {
				$query['eco_numero'] = array();
			}
			$query['eco_numero']["<="] = $filters['eco-high'];
		}
	}
}

if (!$filters['eco-filter-type']) {
	echo json_encode(array(
		'error'=>true,
		'error_message'=>"Query type not specified on client side."
	));
	die();
} else if ($filters['eco-filter-type'] === 'category') {
	$result = $db->select_from(
		'tags',
		['`date`', 'eco_category as eco', 'COUNT(*) AS `popularity`'],
		$query,
		['GROUP BY `eco`, `date`', 'ORDER BY `date`, `popularity` DESC']
	);
} else if ($filters['eco-filter-type'] === 'class' ||
		$filters['eco-filter-type'] === 'code') {
	/* filter on finely grained tag data */
	$result = $db->select_from(
		'tags',
		['`date`', 'CONCAT(eco_alpha, eco_numero) as `eco`', 'COUNT(*) AS `popularity`'],
		$query,
		['GROUP BY `eco`, `date`', 'ORDER BY `date`, `popularity` DESC']
	);
} else {
	echo json_encode(array(
		'error'=>true,
		'error_message'=>"Query type not correctly specified on client side."
	));
	die();
}

$db->disconnect();

/* create json that we will send to client for visualization */
/* split into years first */
$data_by_date = array();
$total_popularities = array();
foreach ($result as $entry) {
	if(!isset($data_by_date[$entry['date']])) {
		$data_by_date[$entry['date']] = array();
		$data_by_date[$entry['date']]['total'] = 0;
	}
	$data_by_date[$entry['date']][$entry['eco']] = $entry['popularity'];
	$data_by_date[$entry['date']]['total'] += $entry['popularity'];

	if(!isset($total_popularities[$entry['eco']])) {
		$total_popularities[$entry['eco']] = 0;
	}
	$total_popularities[$entry['eco']] += $entry['popularity'];
}

/* sort from most popular to least popular */
arsort($total_popularities);

$num_ecos = count($total_popularities);

/* divide into top 9 (and other) or less openings */
$num_pops = min($num_ecos, 9);
$count = 0;
$top_ecos = array();
foreach ($total_popularities as $eco => $total) {
	$top_ecos[] = $eco;
	$count++;

	if($count >= $num_pops) {
		break;
	}
}

$json_data = array();
foreach ($data_by_date as $year => &$ecos) {
	/* get percentage for each opening */
	foreach ($ecos as $eco => $pop) {
		if($eco !== 'total') {
			$ecos[$eco] /= $ecos['total'];
		}
	}

	/* put relavant pops into the array */
	$json_data[$year] = array();
	$sum = 0;
	foreach ($top_ecos as $eco) {
		$value = 0;
		if(isset($ecos[$eco])) {
			$value = $ecos[$eco];
		}
		$json_data[$year][] = $value;
		$sum += $value;
	}

	if($sum < 1) {
		$json_data[$year][] = 1 - $sum;
	}
}

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

/*
 * this really has no busines here, it should actually be in a utils.php
 */
function contains($string, $token) {
	if(strpos($string, $token) !== false) {
		return true;
	} else {
		return false;
	}
}
?>
