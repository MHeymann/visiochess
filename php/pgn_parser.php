<?php
require_once("define.php");


/*
 * Scan a tag as presented in PGN notation and return it's value.
 * @param $read_string:		The string containing the tag to be read.
 * @param $start_of_string: The start of the line being scanned,
 *							used to determine the offset into the tag being
 *							scanned. The default is the start of the
 *							"Event" tag.
 */
function sscan_tag($read_string, $start_of_string = "[Event \"") {
	$ret_string = "";
	$start_i = strlen($start_of_string);
	$end_i = strlen($read_string);
	for ($i = $start_i; //set index into read_string
		(($i < $end_i) &&
		($read_string[$i] !== '"')); //loop conditionals
			$i++) {
		$ret_string .= $read_string[$i];
	}
	return $ret_string;
}

/*
 * TODO:Parse the entries into a db.
 */
function parse_pgn_file_to_db($target_file, $db_name) {
	echo "Database to be parsed to: " . $db_name;

	$db_file = fopen(SITE_ROOT . $target_file, "r") or
		die("Opening file for parsing to database failed!");


	$event_line = fgets($db_file);
	while (!feof($db_file)) {
		/*
		 * Declare variables for this while loop, so that if any variables
		 * are missing in the PGN file, they are only given the empty string
		 * as values.
		 */
	/* "Seven for the Dwarf-lords in their halls of stone" */
		$event_name  = "";
		$site_name   = "";
		$event_date  = "";
		$event_round = "";
		$white_name  = "";
		$black_name  = "";
		$game_result = "";

	/* "Three rings for the Elven kings under the sky..." */
		$ECO_class	 = "";
		$black_elo	 = "";
		$white_elo	 = "";

		if (feof($db_file)) {
			break;
		}
		/*
		 * TODO: add some validation for syntax of file
		 */
		$event_name = sscan_tag($event_line, '[Event "');

		$site_line = fgets($db_file);
		$site_name = sscan_tag($site_line, '[Site "');

		$date_line = fgets($db_file);
		/*
		 * TODO: harvest the year out of the date string, as this is the only
		 * value of interest, in addition to many games in the default database
		 * being uncomplete beyond the year.
		 */
		$event_date = sscan_tag($date_line, '[Date "');

		$round_line = fgets($db_file);
		$event_round = sscan_tag($round_line, '[Round "');

		$white_line = fgets($db_file);
		$white_name = sscan_tag($white_line, '[White "');

		$black_line = fgets($db_file);
		$black_name = sscan_tag($black_line, '[Black "');

		$result_line = fgets($db_file);
		$game_result = sscan_tag($result_line, '[Result "');

	/* Harvest ECO and elo's from optional tag data */
		$optional_line = fgets($db_file);
		while ($optional_line[0] == '[') {
			$eleven_sub = substr($optional_line, 0, 11);

			if (substr($optional_line, 0, 6) == '[ECO "') {
				$ECO_class = sscan_tag($optional_line, '[ECO "');
			}

			if ($eleven_sub == '[BlackElo "') {
				$black_elo = sscan_tag($optional_line, '[BlackElo "');
			}

			if ($eleven_sub == '[WhiteElo "') {
				$white_elo = sscan_tag($optional_line, '[WhiteElo "');
			}

			$optional_line = fgets($db_file);
		}

		/*
		 * TODO: assert that dud_line is now an empty line
		 * before start of moves.
		 */

		/*
		 * Parse moves into array structure.
		 * DONE: The end of line of the entire moves array still has an extra
		 * two spaces after processing.  While this does not corrupt the data,
		 * it does make the count of the moves array misleading.  This is
		 * complicated by the fact, that the number of non-trivial entries is
		 * a multiple of 3 when black wins, and one less than a multiple of
		 * three when white wins.  Note, that, regardless of all of this, the
		 * move count numbers follow a distinctive pattern:  move i has its
		 * label i in array position (i - 1) * 3 in the $moves array. It may
		 * therefore be necessary to make some adjustments here, once the
		 * database structure has been finalized.
		 */
		$moves = "";
		$dud_line = trim(fgets($db_file));
		while (!feof($db_file) && !empty($dud_line) && ($dud_line[0] !== '[')) {
			$moves .= $dud_line . " ";
			$dud_line = trim(fgets($db_file));
		}

	/* turn into one huge array */
		$moves_messy = explode(" ", trim($moves));

	/* removes score from moves */
		unset($moves_messy[count($moves_messy) - 1]);

	/* sort into two arrays, one for each player */
		$moves = array("white" => array(), "black" => array());
		$prev_player = "black"; // set black as initial so that first push is on white
		foreach($moves_messy as $value) {
			$value = trim($value);
			$value = str_replace(".", "", $value);
			if(!is_numeric($value) && !empty($value) && ($value != $game_result)) {
				if($prev_player == "black") {
					array_push($moves["white"], $value);
					$prev_player = "white";
				} else {
					array_push($moves["black"], $value);
					$prev_player = "black";
				}
			}
		}

	/* get's rid of dud empty lines between games */
		while (!feof($db_file) && (empty($dud_line) || ($dud_line[0] !== '['))) {
			$dud_line = trim(fgets($db_file));
		}

		/*
		 * TODO: Instead of just echoing back the table entry info, add to
		 * database table.
		 */
		echo "<p>----------------start of entry---------------------</p>";
		echo "<p>event: " . $event_name . "</p><p>site: " . $site_name .
			"</p>";
		echo "<p>date: " . $event_date . "</p><p>round: " . $event_round .
			"</p>";
		echo "<p> white: " . $white_name . "</p><p>black: " . $black_name .
			"</p>";
		echo "<p>result: " . $game_result . "</p><p>ECO: " . $ECO_class .
			"</p>";
		echo "<p>white elo: " . $white_elo . "</p><p>black elo: " .
			$black_elo . "</p>";
		echo "<p>moves: </p><p>";
		$num_moves = max(count($moves['white']), count($moves['black']));
		for ($i = 0; $i < $num_moves; $i++) {
			$move = ($i+1) . ": white: " . $moves['white'][$i];
			if(isset($moves['black'][$i])) {
				$move .= ", black: " . $moves['black'][$i] . "<br>";
			}
			echo $move;
		}
		echo "</p><p>move array size: " . count($moves['white']) .
			", exactly what we expect. :)</p>";
		echo "<p>-----------------end of entry---------------------</p>";
		$event_line = $dud_line;
	}
	fclose($db_file);

}

/* Returns the longest string of chess moves, which will be used to
 * determine the width of the 'moves' column. 
 * @param $read_string:		The string containing the tag to be read.
 * @param $start_of_string: The start of the line being scanned.
*/
function get_longest_moves_string($target_file, $db_name) {
	$max_move_string = 0;
	
	echo "Database to be parsed to: " . $db_name;

	$db_file = fopen(SITE_ROOT . $target_file, "r") or
		die("Opening file for parsing to database failed!");


	$event_line = fgets($db_file);
	while (!feof($db_file)) {
		/*
		 * Declare variables for this while loop, so that if any variables
		 * are missing in the PGN file, they are only given the empty string
		 * as values.
		 */
	/* "Seven for the Dwarf-lords in their halls of stone" */
		$event_name  = "";
		$site_name   = "";
		$event_date  = "";
		$event_round = "";
		$white_name  = "";
		$black_name  = "";
		$game_result = "";

	/* "Three rings for the Elven kings under the sky..." */
		$ECO_class	 = "";
		$black_elo	 = "";
		$white_elo	 = "";

		if (feof($db_file)) {
			break;
		}
		/*
		 * TODO: add some validation for syntax of file
		 */
		$event_name = sscan_tag($event_line, '[Event "');

		$site_line = fgets($db_file);
		$site_name = sscan_tag($site_line, '[Site "');

		$date_line = fgets($db_file);
		/*
		 * TODO: harvest the year out of the date string, as this is the only
		 * value of interest, in addition to many games in the default database
		 * being uncomplete beyond the year.
		 */
		$event_date = sscan_tag($date_line, '[Date "');

		$round_line = fgets($db_file);
		$event_round = sscan_tag($round_line, '[Round "');

		$white_line = fgets($db_file);
		$white_name = sscan_tag($white_line, '[White "');

		$black_line = fgets($db_file);
		$black_name = sscan_tag($black_line, '[Black "');

		$result_line = fgets($db_file);
		$game_result = sscan_tag($result_line, '[Result "');

	/* Harvest ECO and elo's from optional tag data */
		$optional_line = fgets($db_file);
		while ($optional_line[0] == '[') {
			$eleven_sub = substr($optional_line, 0, 11);

			if (substr($optional_line, 0, 6) == '[ECO "') {
				$ECO_class = sscan_tag($optional_line, '[ECO "');
			}

			if ($eleven_sub == '[BlackElo "') {
				$black_elo = sscan_tag($optional_line, '[BlackElo "');
			}

			if ($eleven_sub == '[WhiteElo "') {
				$white_elo = sscan_tag($optional_line, '[WhiteElo "');
			}

			$optional_line = fgets($db_file);
		}

		/*
		 * TODO: assert that dud_line is now an empty line
		 * before start of moves.
		 */

		/*
		 * Parse moves into array structure.
		 * DONE: The end of line of the entire moves array still has an extra
		 * two spaces after processing.  While this does not corrupt the data,
		 * it does make the count of the moves array misleading.  This is
		 * complicated by the fact, that the number of non-trivial entries is
		 * a multiple of 3 when black wins, and one less than a multiple of
		 * three when white wins.  Note, that, regardless of all of this, the
		 * move count numbers follow a distinctive pattern:  move i has its
		 * label i in array position (i - 1) * 3 in the $moves array. It may
		 * therefore be necessary to make some adjustments here, once the
		 * database structure has been finalized.
		 */
		$moves = "";
		$dud_line = trim(fgets($db_file));
		while (!feof($db_file) && !empty($dud_line) && ($dud_line[0] !== '[')) {
			$moves .= $dud_line . " ";
			$dud_line = trim(fgets($db_file));
		}

		/* turn into one huge array */
		$moves_messy = explode(" ", trim($moves));

		/* removes score from moves */
		unset($moves_messy[count($moves_messy) - 1]);
		
		/* turn back into atring */
		$moves_string = implode(" ", $moves_messy);
		
		if (strlen($moves_string) > $max_move_string) {
			$max_move_string = strlen($moves_string);
			echo "long string: ". $moves_string;
		}	 

	}
	fclose($db_file);
	
	return $max_move_string;
}

?>
