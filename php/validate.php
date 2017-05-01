<?php
/* Used to validate the date */
date_default_timezone_set('Africa/Johannesburg');

/*
 * If a value is not valid, a error object is returned
 * If a valie is valid, null is returned
 */

function validate_filters($filters) {
	$response = new_response();
	$response = validate_year($filters, $response);
	$response = validate_elos($filters, $response);
	$response = validate_eco($filters, $response);

	return $response;
}

function validate_year($filters, $response=null) {
	$year = $filters['year'];
	if($year) {
		$response = add_filter('year', $response);
		$response = validate_year($year, $response);
	}


	$year_low = $filters['year-low'];
	if($year_low) {
		$response = add_filter('year-low', $response);
		$response = validate_year_low($year_low, $response);
	}

	$year_high = $filters['year-high'];
	if($year_high) {
		$response = add_filter('year-high', $response);
		$response = validate_year_high($year_high, $response);
	}

	if($year_low && $year_high) {
		$response = low_greater_than_high(
			$year_low, $year_high, 'year', $response
		);
	}

	return $response;
}

function validate_year($year, $response=null) {
	$year_ok = is_numeric($year) &&
		((int) $year) >= 0 &&
		((int) $year) <= (int)date("Y");

	if(!$year__ok) {
		$response = add_error('year', $response);
	}

	return $response;
}

function validate_year_low($year, $response=null) {
	$year_low_ok = is_numeric($year) &&
		((int) $year) >= 0 &&
		((int) $year) <= (int) date("Y");

	if(!$year_low_ok) {
		$response = add_error('year-low', $response);
	}

	return $response;
}

function validate_year_high($year, $response=null) {
	$year_high_ok = is_numeric($year) &&
		((int) $year) <= (int) date("Y") &&
		((int) $year) >= 0;

	if(!$year_high_ok) {
		$response = add_error('year-high', $response);
	}

	return $response;
}

function validate_elos($filters, $response=null) {
	$response = validate_elo($filters, 'black', $response);
	$response = validate_elo($filters, 'white', $response);

	return $response;
}

function validate_elo($filters, $type, $response=null) {
	$elo_low = $filters["$type-elo-low"];
	if($elo_low) {
		$response = add_filter("$type-elo-low", $response);
		$response = validate_elo_low($elo_low, $type, $response);
	}

	$elo_high = $filters["$type-elo-high"];
	if($elo_high) {
		$response = add_filter("$type-elo-high", $response);
		$response = validate_elo_high($elo_high, $type, $response);
	}

	if($elo_low && $elo_high) {
		$response = low_greater_than_high(
			$elo_low, $elo_high, "$type-elo", $response
		);
	}

	return $response;
}

function validate_elo_low($elo, $type, $response=null) {
	$elo_low_ok = is_numeric($elo) &&
		((int) $elo) >= 0 &&
		((int) $elo) <= 3400;
	if(!$elo_low_ok) {
		$response = add_error("$type-elo-low", $response);
	}

	return $response;
}

function validate_elo_high($elo, $type, $response=null) {
	$elo_high_ok = is_numeric($elo) &&
		((int) $elo) >= 0 &&
		((int) $elo) <= 3400;
	if(!$elo_high_ok) {
		$response = add_error("$type-elo-high", $response);
	}

	return $response;
}

function validate_eco($filters, $response=null) {
	/* This is the only case that needs validation */
	if($filters['eco-filter-type'] == 'code') {
		/*
		 * Category does not need to be validated as the user
		 * may only select one of a few predetermined options
		 */
		if($filters['eco-category']) {
			$response = add_filter('eco-category', $response);
		}

		$eco_low = $filters['eco-low'];
		if($eco_low) {
			$response = add_filter('eco-low', $response);
			$response = validate_eco_low($eco_low, $response);
		}

		$eco_high = $filters['eco-high'];
		if($eco_high) {
			$response = add_filter('eco-high', $response);
			$response = validate_eco_high($eco_high, $response);
		}

		if($eco_low && $eco_high) {
			$response = low_greater_than_high(
				$eco_low, $eco_high, "eco", $response
			);
		}
	}

	return $response;
}

function validate_eco_low($eco, $response=null) {
	$eco_low_ok = is_numeric($eco) &&
		((int) $eco) >= 0 &&
		((int) $eco) <= 99;
	if(!$eco_low_ok) {
		$response = add_error('eco-low', $response);
	}

	return $response;
}

function validate_eco_high($eco, $response=null) {
	$eco_high_ok = is_numeric($eco) &&
		((int) $eco) >= 0 &&
		((int) $eco) <= 99;
	if(!$eco_high_ok) {
		$response = add_error('eco-high', $response);
	}

	return $response;
}

function new_response() {
	return array(
		'error' => false,
		'error_message' => '',
		'error_fields' => array(),
		'filter_on' => array()
	);
}

function add_error($error, $response=null) {
	if(!isset($response) || !$response) {
		$response = new_response();
	}

	$response['error'] = true;
	$response['error_message'] = 'validation error';
	$response['error_fields'][] = $error;

	return $response;
}

function add_filter($filter, $response=null) {
	if(!isset($response) || !$response) {
		$response = new_response();
	}

	$response['filter_on'][] = $filter;

	return $response;
}

function low_greater_than_high($low, $high, $filter, $response=null) {
	if(!isset($response) || !$response) {
		$response = new_response();
	}

	if($low > $high) {
		$response = add_error("$filter-low>high", $response);
	}

	return $response;
}
?>
