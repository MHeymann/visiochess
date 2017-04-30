<?php
require_once "mysql_interface.php";
// this is actually still just a check to see if it works

// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Africa/Johannesburg');
$filters = array();
foreach($_POST as $filter_field => $value) {
	$filters[$filter_field] = trim($value);
}


/* filter validation checks */
$filter_on = array();

if($filters['year-low']) {
	$filter_on[] = 'year-low';
	$year_low_ok = is_numeric($filters['year-low']) &&
		((int) $filters['year-low']) >= 0 &&
		((int) $filters['year-low']) <= 9999;
	if(!$year_low_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "year-low"
		));
		die();
	}
}

if($filters['year-high']) {
	$filter_on[] = 'year-high';
	$year_high_ok = is_numeric($filters['year-high']) &&
		((int) $filters['year-high']) <= (int) date("Y") &&
		((int) $filters['year-high']) >= 0;
	if(!$year_high_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "year-high"
		));
		die();
	}
}

if($filters['year-low'] && $filters['year-high']) {
	if($filters['year-low'] > $filters['year-high']) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "year"
		));
		die();
	}
}

if($filters['black-elo-low']) {
	$filter_on[] = 'black-elo-low';
	$black_elo_low_ok = is_numeric($filters['black-elo-low']) &&
		((int) $filters['black-elo-low']) >= 0 &&
		((int) $filters['black-elo-low']) <= 9999;
	if(!$black_elo_low_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "black-elo-low"
		));
		die();
	}
}

if($filters['black-elo-high']) {
	$filter_on[] = 'black-elo-high';
	$black_elo_high_ok = is_numeric($filters['black-elo-high']) &&
		((int) $filters['black-elo-high']) <= 9999 &&
		((int) $filters['black-elo-high']) >= 0;
	if(!$black_elo_high_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "black-elo-high"
		));
		die();
	}
}

if($filters['black-elo-low'] && $filters['black-elo-high']) {
	if($filters['black-elo-low'] > $filters['black-elo-high']) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "black-elo"
		));
		die();
	}
}

if($filters['white-elo-low']) {
	$filter_on[] = 'white-elo-low';
	$white_elo_low_ok = is_numeric($filters['white-elo-low']) &&
		((int) $filters['white-elo-low']) >= 0 &&
		((int) $filters['white-elo-low']) <= 9999;
	if(!$white_elo_low_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "white-elo-low"
		));
		die();
	}
}

if($filters['white-elo-high']) {
	$filter_on[] = 'white-elo-high';
	$white_elo_high_ok = is_numeric($filters['white-elo-high']) &&
		((int) $filters['white-elo-high']) <= 9999 &&
		((int) $filters['white-elo-high']) >= 0;
	if(!$white_elo_high_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "white-elo-high"
		));
		die();
	}
}

if($filters['white-elo-low'] && $filters['white-elo-high']) {
	if($filters['white-elo-low'] > $filters['white-elo-high']) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "white-elo"
		));
		die();
	}
}

if($filters['eco-type']) {
	$filter_on[] = 'eco-type';
	$eco_type_ok = preg_match("/^[A-E]$/", $filters['eco-type']);
	if(!$eco_type_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "eco-type"
		));
		die();
	}
}

if($filters['eco-low']) {
	$filter_on[] = 'eco-low';
	$eco_low_ok = is_numeric($filters['eco-low']) &&
		((int) $filters['eco-low']) >= 0 &&
		((int) $filters['eco-low']) <= 999;
	if(!$eco_low_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "eco-low"
		));
		die();
	}
}

if($filters['eco-high']) {
	$filter_on[] = 'eco-high';
	$eco_high_ok = is_numeric($filters['eco-high']) &&
		((int) $filters['eco-high']) <= 999 && // perhaps this value needs to be checked
		((int) $filters['eco-high']) >= 0;
	if(!$eco_high_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "eco-high"
		));
		die();
	}
}

if($filters['eco-low'] && $filters['eco-high']) {
	if($filters['eco-low'] > $filters['eco-high']) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "eco"
		));
		die();
	}
}

if(!$filters['database']) {
	$filters['database'] = 'default_chess_db';
}

// echo "filters: " . json_encode($filters) . "\n";

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
		if(!$query['date']) {
			$query['date'] = array();
		}

		if(contains($field, 'low')) {
			$query['date']['>='] = (int) $filters['year-low'];
		} else {
			$query['date']['<='] = (int) $filters['year-high'];
		}
	} else if(contains($field, 'elo')) {
		if(contains($field, 'black')) {
			if(!$query['blackElo']) {
				$query['blackElo'] = array();
			}

			if(contains($field, 'low')) {
				$query['blackElo']['>='] = (int) $filters['black-elo-low'];
			} else {
				$query['blackElo']['<='] = (int) $filters['black-elo-high'];
			}
		} else {
			if(!$query['whiteElo']) {
				$query['whiteElo'] = array();
			}

			if(contains($field, 'low')) {
				$query['whiteElo']['>='] = (int) $filters['white-elo-low'];
			} else {
				$query['whiteElo']['<='] = (int) $filters['white-elo-high'];
			}
		}
	} else if(contains($field, 'eco')) {
		if(contains($field, 'type')) {
			if(!$query['eco_alpha']) {
				$query['eco_alpha'] = array();
			}
			$query['eco_alpha']['LIKE'] = $filters['eco-type'];
		} else if(contains($field, 'low')) {
			if(!$query['eco_numero']) {
				$query['eco_numero'] = array();
			}
			$query['eco_numero'][">="] = $filters['eco-low'];
		} else {
			if(!$query['eco_numero']) {
				$query['eco_numero'] = array();
			}
			$query['eco_numero']["<="] = $filters['eco-high'];
		}
	}

	// for testing
	// // echo "\nquery: ". json_encode($query) . "\n";
}

// for testing
// echo "\nquery: ". json_encode($query) . "\n";

if (!$filters['query_type']) {
	echo json_encode(array(
		'error'=>true,
		'error_message'=>"Query type not specified on client side."
	));
	die();
} else if ($filters['query_type'] === 'category') {
	$result = $db->select_from(
		'tags',
		['`date`', 'eco_category as eco', 'COUNT(*) AS `popularity`'],
		$query,
		['GROUP BY `eco`, `date`', 'ORDER BY `date`, `popularity` DESC']
	);
} else if ($filters['query_type'] === 'tag') {
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
	if(!$data_by_date[$entry['date']]) {
		$data_by_date[$entry['date']] = array();
		$data_by_date[$entry['date']]['total'] = 0;
	}
	$data_by_date[$entry['date']][$entry['eco']] = $entry['popularity'];
	$data_by_date[$entry['date']]['total'] += $entry['popularity'];

	if(!$total_popularities[$entry['eco']]) {
		$total_popularities[$entry['eco']] = 0;
	}
	$total_popularities[$entry['eco']] += $entry['popularity'];
}

// testing
// echo "by date: " . json_encode($data_by_date) . "\n";

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
// echo "top ecos: " . json_encode($top_ecos) . "\n";

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
// echo "json data: " . json_encode($json_data) . "\n";

if($num_ecos > 9) {
	$top_ecos[] = 'other';
}

$json = array();
if($error) {
	$json['error'] = true;
	$json['error_message'] = "MySQL server could not process request";
} else {
	$json['error'] = false;
	$json['data'] = $json_data;
	$json['pop'] = $top_ecos;
}
echo json_encode($json);

// $data = array();
// $data["1990"] = [48.09, 24.14, 18.82, 7.46, 0.03, 1.32, 0, 0, 0, 0];
// $data["1991"] = [48, 24.19, 18.96, 7.36, 0.03, 1.32, 0.12, 0.01, 0, 0, 0, 0];
// $data["1992"] = [47.87, 24.44, 18.91, 7.27, 0.03, 1.36, 0.12, 0.01, 0, 0, 0, 0];
// $data["1993"] = [48.22, 23.83, 19.16, 7.24, 0.04, 1.39, 0.12, 0.01, 0, 0, 0, 0];
// $data["1994"] = [47.91, 23.86, 19.35, 7.31, 0.04, 1.41, 0.12, 0.01, 0, 0, 0, 0];
// $data["1995"] = [48.78, 21.14, 19.66, 8.42, 0.05, 1.83, 0.1 ,0.01, 0, 0, 0, 0];
// $data["1996"] = [49.43, 20.55, 19.42, 8.66, 0.05, 1.75, 0.12, 0.01, 0, 0, 0, 0];
// $data["1997"] = [48.98, 23.47, 18.84, 7.25, 0.04, 1.28, 0.12, 0.01, 0, 0, 0, 0];
// $data["1998"] = [48.69, 23.76, 18.89, 7.22, 0.04, 1.27, 0.11, 0.01, 0, 0, 0, 0];
// $data["1999"] = [49.17, 23.35, 18.91, 7.09, 0.04, 1.32, 0.12, 0.01, 0, 0, 0, 0];
// $data["2000"] = [49.32, 23.39, 18.76, 7.03, 0.04, 1.34, 0.11, 0, 0, 0, 0, 0];
// $data["2001"] = [49.39, 23.11, 18.84, 7.14, 0.04, 1.37, 0.1, 0, 0, 0, 0, 0];
// $data["2002"] = [49.77, 20.68, 19.23, 8.46, 0.05, 1.71, 0.09, 0.01, 0, 0, 0, 0];
// $data["2003"] = [50.07, 20.41, 18.91, 8.77, 0.05, 1.69, 0.1, 0.01, 0, 0, 0, 0];
// $data["2004"] = [49.32, 23.3, 18.54, 7.4, 0.04, 1.27, 0.11, 0.01, 0, 0, 0, 0];
//
// $json = array(
// 	'error' => false,
// 	'error_message' => "Some error message here.",
// 	'pop' => ["A10", "B20", "C30", "D40", "E50", "A13", "B18", "C19", "D10", "other"],
// 	'data' => $data
// );
// echo  json_encode($json);

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
