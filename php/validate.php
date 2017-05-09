<?php
/* Used to validate the date */
date_default_timezone_set('Africa/Johannesburg');

/**
 * Check if the filters submitted for a query is of valid datatypes and
 * ranges.
 *
 * @param $filters an associative array of filters posted by the user.
 * @return A response datastructure, that has an error field set if an
 * error was picked up, and the data to filter on collected in a useful new
 * array structure.
 */
function validate_filters($filters) {
	$response = new_response();
	$response = validate_year($filters, $response);
	$response = validate_elos($filters, $response);
	$response = validate_eco($filters, $response);

	return $response;
}

/**
 * Check if the give $filters structure has a year field or fields for year
 * ranges.  If any of them are set, run tests to see if the data is
 * numeric.  If both range fields are set, check that the lower range is
 * lower than the higher range.
 *
 * @param $filters The filters supplied by the user that is to be
 * validated.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_year($filters, $response=null) {

	if (isset($filters['year'])) {
		$year = $filters['year'];
		if($year) {
			$response = add_filter('year', $response);
			$response = validate_given_year($year, $response);
		}
	}

	$year_low = null;
	$year_high = null;
	if (isset($filters['year-low'])) {
		$year_low = $filters['year-low'];
		if($year_low) {
			$response = add_filter('year-low', $response);
			$response = validate_year_low($year_low, $response);
		}
	}

	if (isset($filters['year-high'])) {
		$year_high = $filters['year-high'];
		if($year_high) {
			$response = add_filter('year-high', $response);
			$response = validate_year_high($year_high, $response);
		}
	}

	if($year_low && $year_high) {
		$response = low_greater_than_high(
			$year_low, $year_high, 'year', $response
		);
	}

	return $response;
}

/**
 * Check if a year is field is a number and whether it is within realistic
 * ranges.
 *
 * @param $year The year to validate.
 * @param $response The datastructure on which valid filter data is
 * collected and on which errors are set.
 */
function validate_given_year($year, $response=null) {
	$year_ok = is_numeric($year) &&
		((int) $year) >= 0 &&
		((int) $year) <= (int)date("Y");

	if(!$year_ok) {
		$response = add_error('year', $response);
	}

	return $response;
}

/**
 * Check if a year is field is a number and whether it is within realistic
 * ranges. If so, set a year-low filter field in response.
 *
 * @param $year The year to validate.
 * @param $response The datastructure on which valid filter data is
 * collected and on which errors are set.
 */
function validate_year_low($year, $response=null) {
	$year_low_ok = is_numeric($year) &&
		((int) $year) >= 0 &&
		((int) $year) <= (int) date("Y");

	if(!$year_low_ok) {
		$response = add_error('year-low', $response);
	}

	return $response;
}
/**
 * Check if a year is field is a number and whether it is within realistic
 * ranges. If so, set a year-high filter field in response.
 *
 * @param $year The year to validate.
 * @param $response The datastructure on which valid filter data is
 * collected and on which errors are set.
 */
function validate_year_high($year, $response=null) {
	$year_high_ok = is_numeric($year) &&
		((int) $year) <= (int) date("Y") &&
		((int) $year) >= 0;

	if(!$year_high_ok) {
		$response = add_error('year-high', $response);
	}

	return $response;
}

/**
 * Check if the elo range filters are present and whether they are
 * realistic and valid numbers.
 *
 * @param $filters The filters supplied by the user that is to be
 * validated.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_elos($filters, $response=null) {
	$response = validate_elo($filters, 'black', $response);
	$response = validate_elo($filters, 'white', $response);

	return $response;
}

/**
 * Validate the elo range filter values for a given player colour.
 *
 * @param $filters The filters supplied by the user that is to be
 * validated.
 * @param $type Indicates the colour of the elo being checked for.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_elo($filters, $type, $response=null) {
	$elo_low = null;
	$elo_high = null;
	if (isset($filters["$type-elo-low"])) {
		$elo_low = $filters["$type-elo-low"];
		if($elo_low) {
			$response = add_filter("$type-elo-low", $response);
			$response = validate_elo_low($elo_low, $type, $response);
		}
	}

	if (isset($filters["$type-elo-high"])) {
		$elo_high = $filters["$type-elo-high"];
		if($elo_high) {
			$response = add_filter("$type-elo-high", $response);
			$response = validate_elo_high($elo_high, $type, $response);
		}
	}

	if($elo_low && $elo_high) {
		$response = low_greater_than_high(
			$elo_low, $elo_high, "$type-elo", $response
		);
	}

	return $response;
}

/**
 * Check whether an elo lower range is valid  for a given player colour.
 *
 * @param $elo The range value being checked for validity.
 * @param $type Indicates the colour of the elo being checked for.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_elo_low($elo, $type, $response=null) {
	$elo_low_ok = is_numeric($elo) &&
		((int) $elo) >= 0 &&
		((int) $elo) <= 3400;
	if(!$elo_low_ok) {
		$response = add_error("$type-elo-low", $response);
	}

	return $response;
}

/**
 * Check whether an elo upper range is valid  for a given player colour.
 *
 * @param $elo The range value being checked for validity.
 * @param $type Indicates the colour of the elo being checked for.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_elo_high($elo, $type, $response=null) {
	$elo_high_ok = is_numeric($elo) &&
		((int) $elo) >= 0 &&
		((int) $elo) <= 3400;
	if(!$elo_high_ok) {
		$response = add_error("$type-elo-high", $response);
	}

	return $response;
}

/**
 * Check whether a given eco value filter is correct. This depends on the
 * exact eco filter being set.  For ECO categories, the correct format must
 * be in place.  For eco class, there may only be exactly one character in
 * the range between A-E.  If ECO code numeric ranges are set, they may
 * only be between 0 and 99, both included, and the lower bound must be
 * less than or equal to the upper bound.
 *
 * @param $filters The filters supplied by the user that is to be
 * validated.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_eco($filters, $response=null) {
	if($filters['eco-filter-type'] == 'code') {
		if($filters['eco-class']) {
			$response = add_filter('eco-class', $response);
			$response = validate_eco_class($filters['eco-class'], $response);
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

/**
 * Validate the correctness of an eco category specification.
 *
 * @param $eco_category The category field that is being vaidated.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_eco_cat($eco_category, $response=null) {
	$eco_type_ok = false;
	$regex_checks = [
		"/^A[1-5].[1-5]$/",
		"/^[B-E][1-2].[1-2]$/"
	];

	foreach($regex_checks as $regex) {
		$eco_type_ok |= preg_match($regex, $eco_category);
	}

	if(!$eco_type_ok) {
		$response = add_error(
			"eco-category: given " . $eco_category,
			$response
		);
	}

	$response = add_filter("eco-category", $response);
	return $response;
}

/**
 * Validate the correctness of an eco class specification.
 *
 * @param $eco_category The category field that is being vaidated.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_eco_class($eco_class, $response=null) {
	$eco_type_ok = preg_match("/^[A-E]$/", $eco_class);
	if(!$eco_type_ok) {
		$response = add_error("eco-class: given " . $eco_class,
			$response);
	}

	return $response;
}

/**
 * Validate if a filter for eco tag numeric values are numeric and within
 * the range of 0-99, both included.
 *
 * @param $eco The eco numeric filter being validated.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_eco_low($eco, $response=null) {
	$eco_low_ok = is_numeric($eco) &&
		((int) $eco) >= 0 &&
		((int) $eco) <= 99;
	if(!$eco_low_ok) {
		$response = add_error('eco-low', $response);
	}

	return $response;
}

/**
 * Validate if a filter for eco tag numeric values are numeric and within
 * the range of 0-99, both included.
 *
 * @param $eco The eco numeric filter being validated.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 */
function validate_eco_high($eco, $response=null) {
	$eco_high_ok = is_numeric($eco) &&
		((int) $eco) >= 0 &&
		((int) $eco) <= 99;
	if(!$eco_high_ok) {
		$response = add_error('eco-high', $response);
	}

	return $response;
}

/**
 * @return A new instance of the response datastructure with clear fields.
 */
function new_response() {
	return array(
		'error' => false,
		'error_message' => '',
		'error_fields' => array(),
		'filter_on' => array()
	);
}

/**
 * Log an error to the response datastructure.  If it doesn't yet exist,
 * create a new one.
 *
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 *
 * @return The response datastructure with the error added.
 */
function add_error($error, $response=null) {
	if(!isset($response) || !$response) {
		$response = new_response();
	}

	$response['error'] = true;
	$response['error_message'] = 'validation error';
	$response['error_fields'][] = $error;

	return $response;
}

/**
 * Add a new filter tot the return datastructure.  If the datastructure
 * doesn't yeat exist, create one.
 *
 * @param $filter The name of the filter being set.
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 *
 * @return The response datastructure with the filter added.
 */
function add_filter($filter, $response=null) {
	if(!isset($response) || !$response) {
		$response = new_response();
	}

	$response['filter_on'][] = $filter;

	return $response;
}

/**
 * Check if two numeric boundaries are valid, in that the lower bound may
 * not be strictly greater than the upper bound.
 *
 * @param $low The lower bound number being checked
 * @param $high The upper bound number being checked
 * @param filter The name of the filter the bounds are for
 * @param $response The response structure on which valid filters are
 * collected and on which errors are indicated when picked up.
 * @return The response datastructure.
 */
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
