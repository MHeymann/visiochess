<?php
require_once("define.php");
require_once("mysql_interface.php");


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
function parse_pgn_file_to_db($target_file, $db_name,
   	$batch_size=1000, $verbose=true) {
	// echo "Database to be parsed to: " . $db_name;

	$global_batch_count = 0;

	$settings = parse_ini_file(__DIR__."/../.my.cnf", true);
	$servername = $settings['client']['mysql_server'];
	$username = $settings['client']['user'];
	$password = $settings['client']['password'];
	$moves_approach = $settings['client']['moves_table'];
	$data_batch = array();

	$db = new MySqlPhpInterface(
		$server=$servername,
		$user=$username,
		$password=$password
	);
	$db->connect();
	$db->use_database($db_name);

	$db_file = fopen(SITE_ROOT . $target_file, "r") or
		die("Opening file for parsing to database failed!");


	$event_line = fgets($db_file);
	while (!feof($db_file)) {
		/*
		 * Declare variables for this while loop, so that if any variables
		 * are missing in the PGN file, they are only given the empty
		 * string as values.
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
		/* This LOR quote is still applicable if you consider ECO
		 * as part of one category of optional tag :P */
		$ECO_class	 = "";
		$ECO_alpha	 = "";
		$ECO_numero	 = -1;
		$ECO_category = "";
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
		 * TODO: harvest the year out of the date string, as this is the
		 * only value of interest, in addition to many games in the default
		 * database being uncomplete beyond the year.
		 */
		$event_date = sscan_tag($date_line, '[Date "');
		// extract and keep year, throw away rest
		$event_date = explode('.', trim($event_date))[0];

		$round_line = fgets($db_file);
		$event_round = sscan_tag($round_line, '[Round "');

		$white_line = fgets($db_file);
		$white_name = sscan_tag($white_line, '[White "');
		// remove the "(wh)"
		$white_name = substr($white_name, 0, count($white_name) - 5);

		$black_line = fgets($db_file);
		$black_name = sscan_tag($black_line, '[Black "');
		// remove the "(bl)"
		$black_name = substr($black_name, 0, count($black_name) - 5);

		$result_line = fgets($db_file);
		$game_result = sscan_tag($result_line, '[Result "');

		/* Harvest ECO and elo's from optional tag data */
		$optional_line = fgets($db_file);
		while ($optional_line[0] == '[') {
			$eleven_sub = substr($optional_line, 0, 11);

			if (substr($optional_line, 0, 6) == '[ECO "') {
				$ECO_class = sscan_tag($optional_line, '[ECO "');
				$ECO_alpha = substr($ECO_class, 0, 1);
				$ECO_numero = intval(substr($ECO_class, 1, 2));
				$ECO_category = "blamo";
				if ($verbose) {
//					echo "<p>Scanned ECO: " . $ECO_alpha . $ECO_numero .
//						" which really should be " . $ECO_class . "</p>\n";
//					echo "<p>Category: " . $ECO_category . "</p>\n";
				}
			}

			if ($eleven_sub == '[BlackElo "') {
				$black_elo = sscan_tag($optional_line, '[BlackElo "');
			}

			if ($eleven_sub == '[WhiteElo "') {
				$white_elo = sscan_tag($optional_line, '[WhiteElo "');
			}

			$optional_line = fgets($db_file);
		}

		/* add tag details to database */
		$data_batch[] = [
			'event' => $event_name,
			'site' => $site_name,
			'date' => (int) $event_date,
			'round' => (int) $event_round,
			'white' => $white_name,
			'black' => $black_name,
			'result' => $game_result,
			'whiteElo' => (int) $white_elo,
			'blackElo' => (int) $black_elo,
			'eco_alpha' => $ECO_alpha,
			'eco_numero' => (int) $ECO_numero,
			'eco_category' => $ECO_category
		];
		if (count($data_batch) >= $batch_size) {
			$db->insert_multiple(
				'tags',
				$data_batch
			);
			$data_batch = array();
			$global_batch_count += 1;
			if ($verbose) {
				echo "<p>entered batch into db, total_count " . $global_batch_count
				   	* $batch_size . "</p>\n";
			}
		}

		/*
		 * TODO: assert that dud_line is now an empty line
		 * before start of moves.
		 */

		/*
		 * Parse moves into array structure.
		 */
		$moves = "";
		$dud_line = trim(fgets($db_file));
		while (!feof($db_file) && !empty($dud_line) &&
			($dud_line[0] !== '[')) {
			$moves .= $dud_line . " ";
			$dud_line = trim(fgets($db_file));
		}

		/* turn into one huge array */
		$moves_messy = explode(" ", trim($moves));
		/* removes score from moves */
		unset($moves_messy[count($moves_messy) - 1]);


		if ($moves_approach == "flat") {
			$moves = implode(" ", $moves_messy);
		} else {
			/* sort into two arrays, one for each player */
			$moves = array("white" => array(), "black" => array());
			/* set black as initial so that first push is on white */
			$prev_player = "black";
			foreach($moves_messy as $value) {
				$value = trim($value);
				$value = str_replace(".", "", $value);
				if(!is_numeric($value) && !empty($value) &&
					($value != $game_result)) {
					if($prev_player == "black") {
						array_push($moves["white"], $value);
						$prev_player = "white";
					} else {
						array_push($moves["black"], $value);
						$prev_player = "black";
					}
				}
			}
			$num_moves = max(count($moves['white']),
				count($moves['black']));
			for ($i = 0; $i < $num_moves; $i++) {
				$move = ($i+1) . ": white: " . $moves['white'][$i];
				if(isset($moves['black'][$i])) {
					$move .= ", black: " . $moves['black'][$i] . "<br>";
				}
			}
		}
		/* get's rid of dud empty lines between games */
		while (!feof($db_file) && (empty($dud_line) ||
			($dud_line[0] !== '['))) {
			$dud_line = trim(fgets($db_file));
		}

		$event_line = $dud_line;
	} //while not eof

	if (count($data_batch) > 0) {
		$db->insert_multiple(
			'tags',
			$data_batch
		);
		if ($verbose) {
			echo "<p>inserted last into db, total now " .
				(count($data_batch) + $global_batch_count * $batch_size) . "</p>";
		}
		$data_batch = array();
	}

	$db->disconnect();
	fclose($db_file);

}

/* Returns the longest string of chess moves, which will be used to
 * determine the width of the 'moves' column.
 * @param $read_string:		The string containing the tag to be read.
 * @param $start_of_string: The start of the line being scanned.
*/
function get_longest_moves_string($target_file) {
	$max_move_string = 0;

	$db_file = fopen(SITE_ROOT . $target_file, "r") or
		die("Opening file for parsing to database failed!");


	$event_line = fgets($db_file);
	while (!feof($db_file)) {
		/*
		 * Declare variables for this while loop, so that if any variables
		 * are missing in the PGN file, they are only given the empty
		 * string as values.
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
		 * DONE: The end of line of the entire moves array still has an
		 * extra two spaces after processing.  While this does not corrupt
		 * the data,
		 * it does make the count of the moves array misleading.  This is
		 * complicated by the fact, that the number of non-trivial entries
		 * is a multiple of 3 when black wins, and one less than a multiple
		 * of three when white wins.  Note, that, regardless of all of
		 * this, the move count numbers follow a distinctive pattern:  move
		 * i has its label i in array position (i - 1) * 3 in the $moves
		 * array. It may therefore be necessary to make some adjustments
		 * here, once the database structure has been finalized.
		 */
		$moves = "";
		$dud_line = trim(fgets($db_file));
		while (!feof($db_file) && !empty($dud_line) &&
			($dud_line[0] !== '[')) {
			$moves .= $dud_line . " ";
			$dud_line = trim(fgets($db_file));
		}

		/* turn into one huge array */
		$moves_messy = explode(" ", trim($moves));

		/* removes score from moves */
		unset($moves_messy[count($moves_messy) - 1]);

		/* turn back into atring */
		$moves_string = implode(" ", $moves_messy);

		/* find longer string */
		if (strlen($moves_string) > $max_move_string) {
			$max_move_string = strlen($moves_string);
		}

	}
	fclose($db_file);

	return $max_move_string;
}
?>
