<?php
require_once "mysql_interface.php";
// this is actually still just a check to see if it works

error_reporting(E_ALL);
ini_set('display_errors', 'on');
// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');
date_default_timezone_set('Africa/Johannesburg');
$filters = array();
foreach($_POST as $filter_field => $value) {
	$filters[$filter_field] = trim($value);
}


/* filter validation checks */
$filter_on = array();

if(!isset($filters['year'])) {
	echo json_encode(array(
		'error' => true,
		'error_message' => "No year supplied for filtering"
	));
} else {
	$filter_on[] = 'year';
	$year_ok = is_numeric($filters['year']) &&
		((int) $filters['year']) >= 0 &&
		((int) $filters['year']) <= date("Y");;
	if(!$year_ok) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "Invalid year provided"
		));
		die();
	}
}

if(isset($filters['black-elo-low'])) {
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

if(isset($filters['black-elo-high'])) {
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

if(isset($filters['black-elo-low']) && isset($filters['black-elo-high'])) {
	if($filters['black-elo-low'] > $filters['black-elo-high']) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "black-elo"
		));
		die();
	}
}

if(isset($filters['white-elo-low'])) {
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

if(isset($filters['white-elo-high'])) {
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

if(isset($filters['white-elo-low']) && isset($filters['white-elo-high'])) {
	if($filters['white-elo-low'] > $filters['white-elo-high']) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "white-elo"
		));
		die();
	}
}

/*
if(isset($filters['eco-type'])) {
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

if(isset($filters['eco-low'])) {
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

if(isset($filters['eco-high'])) {
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

if(isset($filters['eco-low']) && isset($filters['eco-high'])) {
	if($filters['eco-low'] > $filters['eco-high']) {
		echo json_encode(array(
			'error' => true,
			'error_message' => "eco"
		));
		die();
	}
}
*/

if(!isset($filters['database'])) {
	$filters['database'] = 'default_chess_db';
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
$query['minElo'] = array();
$query['minElo']['>'] = 0;
foreach ($filter_on as $field) {
	if(contains($field, 'year')) {
		$query['date'] = array();
		$query['date']['='] = (int) $filters['year'];
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
		if(contains($field, 'type')) {
			if(!isset($query['eco_alpha'])) {
				$query['eco_alpha'] = array();
			}
			$query['eco_alpha']['LIKE'] = $filters['eco-type'];
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
} else if ($filters['query_type'] === 'elo_histo') {
	$result = $db->select_from(
		'tags',
		['`date`', '`eco_category` as eco', '`minElo`', 'COUNT(*) AS `popularity`'],
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

// testing
// echo "by date: " . json_encode($data_by_min_elo) . "\n";

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
// echo "json data: " . json_encode($json_data) . "\n";

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
