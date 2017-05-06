<?php

/**
 * A simple function to check whether a certain string is contained in some
 * token.
 *
 * @param $string The string to check for the presense of.
 * @param $token The token we want to check whether the string is present
 * in.
 * @return true if string is present in token, false otherwise.
 */
function contains($string, $token) {
	if(strpos($string, $token) !== false) {
		return true;
	} else {
		return false;
	}
}

/**
 * Take an object, containing keys and values, and return string of the
 * keys pairs, seperated by commas
 *
 * @param object The object (array) to stringify
 * @return The string that represents the object.
 */
function stringify($object) {
	$string = "";
	foreach($object as $key => $value) {
		$string .= $key . " " . $value . ", ";
	}
	$string = substr($string, 0, count($string) - 3);
	return $string;
}

/**
 * Remove a specified amount of characters from the end of a string.
 *
 * @param $string The string to be trimmed.
 * @param $num_chars The number of characters to remove from the end.
 *
 * @return string $string with the last $num_chars characters removed.
 */
function trim_chars($string, $num_chars) {
	$length = count($string);
	$stop = $length - $num_chars - 1;
	return substr($string, 0, $stop);
}


function process_elo_data($result, &$num_ecos, &$top_ecos, &$json_data) {
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

		if ($sum < 1) {
			$json_data[$minElo][] = 1 - $sum;
		}
	}
}
function process_year_data($result, &$num_ecos, &$top_ecos, &$json_data) {
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

function set_query_where_fields($filter_on, $filters, &$query){
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
}


?>
