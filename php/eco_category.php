<?php

/**
* Given an ECO class and subclass number, determine what ECO category this
* falls into
*
* @param $ECO_alpha The ECO class this falls into
* @param $ECO_numero The number specifying in which subclass the eco code
* falls.
*
* @return The string value of the ECO category in which this ECO code
* falls.
*/
function get_eco_category($ECO_alpha, $ECO_numero) {
	$class = "blamo";
	switch ($ECO_alpha) {
		case "A":
			if ($ECO_numero < 0) {
				$class = "Invalid Numero";
			} else if ($ECO_numero < 40) {
				$class = "A1.1";
			} else if ($ECO_numero < 45) {
				$class = "A1.2";
			} else if ($ECO_numero < 50) {
				$class = "A1.3";
			} else if ($ECO_numero < 80) {
				$class = "A1.4";
			} else if ($ECO_numero < 100) {
				$class = "A1.5";
			} else {
				$class = "Invalid Numero";
			}
			break;
		case "B":
			if ($ECO_numero < 0) {
				$class = "Invalid Numero";
			} else if ($ECO_numero < 20) {
				$class = "B2.1";
			} else if ($ECO_numero < 100) {
				$class = "B2.2";
			} else {
				$class = "Invalid Numero";
			}
			break;
		case "C":
			if ($ECO_numero < 0) {
				$class = "Invalid Numero";
			} else if ($ECO_numero < 20) {
				$class = "C3.1";
			} else if ($ECO_numero < 100) {
				$class = "C3.2";
			} else {
				$class = "Invalid Numero";
			}
			break;
		case "D":
			if ($ECO_numero < 0) {
				$class = "Invalid Numero";
			} else if ($ECO_numero < 70) {
				$class = "D4.1";
			} else if ($ECO_numero < 100) {
				$class = "D4.2";
			} else {
				$class = "Invalid Numero";
			}
			break;
		case "E":
			if ($ECO_numero < 0) {
				$class = "Invalid Numero";
			} else if ($ECO_numero < 60) {
				$class = "E5.1";
			} else if ($ECO_numero < 100) {
				$class = "E5.2";
			} else {
				$class = "Invalid Numero";
			}
			break;

		default:
			$class = "Invalid Alpha";
			break;
	}
	return $class;
}

/**
* Given an ECO category, return the ECO Class and numeric ranges of the
* category.  Essentially the inverse of get_eco_category.
*
* @param $category A string value of the category being looked up.
*
* @return an associative array with the class and numeric ranges of the
* given category.
*/
function get_eco_class_ranges($category) {
	$eco_details = array(
		'class' => null,
		'low' => null,
		'high' => null
	);

	switch($category) {
		case "A1.1":
		$eco_details['class'] = 'A';
		$eco_details['low'] = 0;
		$eco_details['high'] = 39;
		break;
		case "A1.2":
		$eco_details['class'] = 'A';
		$eco_details['low'] = 40;
		$eco_details['high'] = 44;
		break;
		case "A1.3":
		$eco_details['class'] = 'A';
		$eco_details['low'] = 45;
		$eco_details['high'] = 49;
		break;
		case "A1.4":
		$eco_details['class'] = 'A';
		$eco_details['low'] = 50;
		$eco_details['high'] = 79;
		break;
		case "A1.5":
		$eco_details['class'] = 'A';
		$eco_details['low'] = 80;
		$eco_details['high'] = 99;
		break;
		case "B2.1":
		$eco_details['class'] = 'B';
		$eco_details['low'] = 0;
		$eco_details['high'] = 19;
		break;
		case "B2.2":
		$eco_details['class'] = 'B';
		$eco_details['low'] = 20;
		$eco_details['high'] = 99;
		break;
		case "C3.1":
		$eco_details['class'] = 'C';
		$eco_details['low'] = 0;
		$eco_details['high'] = 19;
		break;
		case "C3.2":
		$eco_details['class'] = 'C';
		$eco_details['low'] = 20;
		$eco_details['high'] = 99;
		break;
		case "D4.1":
		$eco_details['class'] = 'D';
		$eco_details['low'] = 0;
		$eco_details['high'] = 69;
		break;
		case "D4.2":
		$eco_details['class'] = 'D';
		$eco_details['low'] = 70;
		$eco_details['high'] = 99;
		break;
		case "E5.1":
		$eco_details['class'] = 'E';
		$eco_details['low'] = 0;
		$eco_details['high'] = 59;
		break;
		case "E5.2":
		$eco_details['class'] = 'E';
		$eco_details['low'] = 60;
		$eco_details['high'] = 99;
		break;
		default:
		return null;
	}

	return $eco_details;
}

?>
