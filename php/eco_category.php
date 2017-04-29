<?php
function get_eco_category($ECO_alpha, $ECO_numero) {
	$class = "blamo";
	switch ($ECO_alpha) {
		case "A":
			if ($ECO_numero < 40) {
				$class = "A1.1";
			} else if ($ECO_numero < 45) {
				$class = "A1.2";
			} else if ($ECO_numero < 50) {
				$class = "A1.3";
			} else if ($ECO_numero < 80) {
				$class = "A1.4";
			} else {
				$class = "A1.5";
			}
			break;
		case "B":
			if ($ECO_numero < 20) {
				$class = "B2.1";
			} else {
				$class = "B2.2";
			}
			break;
		case "C":
			if ($ECO_numero < 20) {
				$class = "C3.1";
			} else {
				$class = "C3.2";
			}
			break;
		case "D":
			if ($ECO_numero < 70) {
				$class = "D4.1";
			} else {
				$class = "D4.2";
			}
			break;
		case "E":
			if ($ECO_numero < 60) {
				$class = "E5.1";
			} else {
				$class = "E5.2";
			}
			break;

		default:
				$class = "blamo";
			break;
	}
	return $class;
}

?>
