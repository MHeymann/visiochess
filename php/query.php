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
	$eco_filters = get_eco_class_ranges($filters['eco-category']);
	if($eco_filters) {
		$filter_on[] = 'eco-category';
		$filter_on[] = 'eco-low';
		$filter_on[] = 'eco-high';
		$filters['eco-class'] = $eco_filters['class'];
		$filters['eco-low'] = $eco_filters['low'];
		$filters['eco-high'] = $eco_filters['high'];
	} //else don't set
} else if($filters['eco-filter-type'] == 'class') {
	$eco_filters = $filters['eco-class'];
	if($eco_filters) {
		$filter_on[] = 'eco-class';
		$filters['eco-class'] = $eco_filters['class'];
	} //else don't set
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
		} else if($field == 'year') {
			$query['date']['='] = (int) $filters['year'];
		} else {
			echo json_encode(array('error'=>true,
			   	'error_message'=>"incorrect date filter ". $field));
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
			$query['eco_alpha']['LIKE'] = $filters['eco-class'];
		} else if(contains($field, 'low')) {
			if(!isset($query['eco_numero'])) {
				$query['eco_numero'] = array();
			}
			$query['eco_numero'][">="] = $filters['eco-low'];
		} else if(contains($field, 'high')) {
			if(!isset($query['eco_numero'])) {
				$query['eco_numero'] = array();
			}
			$query['eco_numero']["<="] = $filters['eco-high'];
		} else if(contains($field, 'class')) {
			if(!isset($query['eco_alpha'])) {
				$query['eco_alpha'] = array();
			}
			$query['eco_alpha']['LIKE'] = $filters['eco-class'];
		} else {
		}
	}
}

if (!isset($filters['eco-filter-type']) ||
   	!$filters['eco-filter-type']) {
	echo json_encode(array(
		'error'=>true,
		'error_message'=>"Query type not specified on client side."
	));
	die();
} else if ($filters['eco-filter-type'] === 'category') {
	$eco_filters = get_eco_class_ranges($filters['eco-category']);
	if ($eco_filters) {
		$select = ['`date`', 'CONCAT(eco_alpha, eco_numero) as `eco`', 'COUNT(*) AS `popularity`'];
	} else {
		$select = ['`date`', 'eco_category as eco', 'COUNT(*) AS `popularity`'];
	}
	$result = $db->select_from(
		'tags',
		$select,
		$query,
		['GROUP BY `eco`, `date`', 'ORDER BY `date`, `popularity` DESC']
	);
} else if ($filters['eco-filter-type'] === 'class') {
	$eco_filters = $filters['eco-class'];
	if($eco_filters) {
		$select = ['`date`', 'eco_category as eco', 'COUNT(*) AS `popularity`'];
	} else {
		$select = ['`date`', 'eco_alpha as `eco`', 'COUNT(*) AS `popularity`'];
	}
	/* filter on finely grained tag data */
	$result = $db->select_from(
		'tags',
		$select,
		$query,
		['GROUP BY `eco`, `date`', 'ORDER BY `date`, `popularity` DESC']
	);
} else if ($filters['eco-filter-type'] === 'code') {
	$select = ['`date`', 'CONCAT(eco_alpha, eco_numero) as `eco`', 'COUNT(*) AS `popularity`'];
	/* filter on finely grained tag data */
	$result = $db->select_from(
		'tags',
		$select,
		$query,
		['GROUP BY `eco`, `date`', 'ORDER BY `date`, `popularity` DESC']
	);
} else if ($filters['eco-filter-type'] === 'year-eco-analysis') {
	if (!isset($query['minElo'])){
		$query['minElo'] = array();
	}
	if (!isset($query['minElo']['>'])){
		$query['minElo']['>'] = 0;
	}
	$select = ['`date`', '`eco_category` as eco', '`minElo`', 'COUNT(*) AS `popularity`'];
	/* filter on finely grained tag data */
	$result = $db->select_from(
		'tags',
		$select,
		$query,
		['GROUP BY `minElo`, `eco_category`', 'ORDER BY `minElo` ASC']
	);
} else {
	echo json_encode(array(
		'error'=>true,
		'error_message'=>"Query type not correctly specified on client side."
	));
	die();
}

$db->disconnect();

$num_ecos = 0;
$top_ecos = array();
$json_data = array();
if ($filters['eco-filter-type'] === 'year-eco-analysis') {

	/* create json that we will send to client for visualization */
	/* split into years first */
	$data_by_min_elo = array();
	$total_popularities = array();
	foreach ($result as $entry) {
		if(!isset($data_by_min_elo[$entry['minElo']])) {
			$data_by_min_elo[$entry['minElo']] = array();
			$data_by_min_elo[$entry['minElo']]['total'] = 0;
		}
		$data_by_min_elo[$entry['minElo']][$entry['eco']] = $entry['popularity'];
		$data_by_min_elo[$entry['minElo']]['total'] += $entry['popularity'];

		if(!isset($total_popularities[$entry['eco']])) {
			$total_popularities[$entry['eco']] = 0;
		}
		$total_popularities[$entry['eco']] += $entry['popularity'];
	}

	/* sort from most popular to least popular */
	arsort($total_popularities);
	// testing
	// echo "total pop: " . json_encode($total_popularities) . "\n";

	$num_ecos = count($total_popularities);
	// testing
	// echo "num ecos: " . $num_ecos . "\n";

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

	$arr_keys = array_keys($data_by_min_elo);
	$smallest_min_elo = $arr_keys[0];
	$largest_min_elo = end($arr_keys);


	$data_by_groups = array();
	$offset = 30;
	for ($i = $smallest_min_elo; $i < $largest_min_elo; $i += $offset) {
		$data_by_groups[($i + $offset)] = array();
		for ($j = 0; $j < 10; $j++) {
			if (isset($data_by_min_elo[$i + $j])) {
				foreach($data_by_min_elo[$i + $j] as $eco=>$pop) {
					if (!isset($data_by_groups[($i + $offset)][$eco])) {
						$data_by_groups[($i + $offset)][$eco] = 0;
					}
					$data_by_groups[($i + $offset)][$eco] += $pop;
				}

			}
		}
	}

	$json_data = array();
	foreach ($data_by_groups as $minElo => &$ecos) {
		/* get percentage for each opening */
		foreach ($ecos as $eco => $pop) {
			if($eco !== 'total') {
				$ecos[$eco] /= $ecos['total'];
			}
		}

		/* put relavant pops into the array */
		$json_data[$minElo] = array();
		$sum = 0;
		foreach ($top_ecos as $eco) {
			$value = 0;
			if(isset($ecos[$eco])) {
				$value = $ecos[$eco];
			}
			$json_data[$minElo][] = $value;
			$sum += $value;
		}

		if($sum < 0.9999) {
			$json_data[$minElo][] = 1 - $sum;
		}
	}


} else {

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
	foreach ($total_popularities as $eco => $total) {
		$top_ecos[] = $eco;
		$count++;

		if($count >= $num_pops) {
			break;
		}
	}

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
