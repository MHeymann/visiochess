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

?>
