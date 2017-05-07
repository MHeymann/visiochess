<?php
require_once("define.php");
require_once("mysql_interface.php");
require_once("eco_category.php");
require_once("utils.php");


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
	$ret_string = rtrim($ret_string, ' ');
	return $ret_string;
}

/**
 * Validate that a given line starts with the specified characters.
 * TODO or not TODO?: This can be made more robust by not only checking for
 * "contains" but also that the string the line should start with actually
 * occurs at the start of the line. Currently, a line such as "your mother"
 * and a start value of "our m" would evaluate as valid. However, this
 * would need proper further testing.
 *
 * @param $line The string that is being evaluated.
 * @param $should_start The characters that the line should start with.
 *
 * @return true if the line does contain with expected start character
 * sequence, false otherwise.
 */
function evaluate_line($line, $should_start) {
	if (contains($line, $should_start)) {
		return true;
	} else {
		echo "<p>vvvv---------vvvv</p>\n";
		echo "<p>Error with data for entry with line:</p>\n";
		echo "<p>'" . $line . "'</p>\n";
		echo "<p>Should start with: " . $should_start . "</p>\n";
		echo "<p>This event will not be parsed into the database</p>\n";
		echo "<p>^^^^---------^^^^</p>\n";
		return false;
	}
}

/**
 * Parse Through the lines until a non trivial line that does not only
 * contain whitespace is found. Note, this function assumes that dud_line
 * has already been read into once, but most likely would still work as
 * expected if this is not the case.
 *
 * @param $db_file A file object of the file being read.
 * @param $dud_line.  The current status of the line.
 */
function parse_white_space($db_file, $dud_line) {
	while (!feof($db_file) && (empty(trim($dud_line)))) {
		$dud_line = trim(fgets($db_file));
	}
	return $dud_line;
}

/*
 * This function parses a pgn file and inserts its entries into the
 * database one by one.  It does some loose syntax validation of the pgn
 * file provided; if a serious error is found, the game is not parsed into
 * the database.
 *
 * Data is inserted into the database in batches, as this is more efficient
 * than simply entering the data one database row at a time.
 *
 * @param $target_file The path to the file that is to be parsed.
 * @param $db_name The name of the database into which to insert the data.
 * @param $batch_size The number of games to insert in one query. Default
 * value is 200, as this has been found experimentally to be around the
 * "sweet spot".
 * @param $verbose Boolean value, if set to true, will print out more
 * information than normally.  False by default.
 */
function parse_pgn_file_to_db($target_file, $db_name,
   	$batch_size=200, $verbose=false) {

	if ($verbose || true) {
		echo "<p>File being parsed: " . $target_file . "</p>\n";
		echo "<p>Database to be parsed to: " . $db_name . "</p>\n";
		echo "<p>Batch size being parsed to db in single query: " .
			$batch_size . "</p>\n";
	}

	$global_batch_count = 0;
	$global_moves_count = 0;

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

	if ($verbose) {
		echo "<p>Starting parsing of data</p>\n";
	}

	$event_line = trim(fgets($db_file));
	$event_line = parse_white_space($db_file, $event_line);
	while (!feof($db_file)) {
		/* A boolean variable for some basic syntax validation */
		$entry_error = false;
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
		$entry_error = $entry_error || !evaluate_line($event_line, '[Event "');
		$event_name = sscan_tag($event_line, '[Event "');

		$site_line = trim(fgets($db_file));
		$site_line = parse_white_space($db_file, $site_line);
		$entry_error = $entry_error || !evaluate_line($site_line, '[Site "');
		$site_name = sscan_tag($site_line, '[Site "');

		$date_line = trim(fgets($db_file));
		$date_line = parse_white_space($db_file, $date_line);
		/*
		 * DONE: harvest the year out of the date string, as this is the
		 * only value of interest, in addition to many games in the default
		 * database being uncomplete beyond the year.
		 */
		$entry_error = $entry_error || !evaluate_line($date_line, '[Date "');
		$event_date = sscan_tag($date_line, '[Date "');
		// extract and keep year, throw away rest
		$event_date = explode('.', trim($event_date))[0];
		if (strlen($event_date) < 4) {
			$entry_error = true;
			echo "<p>vvvv---------vvvv</p>\n";
			echo "<p>Error with data for entry:</p>\n";
			echo "<p> Event date: '" . $event_date . "'</p>\n";
			echo "<p> length: '" . strlen($event_date) . "'</p>\n";
			echo "<p>Event name: " . $event_name . "</p>\n";
			echo "<p>This event will not be parsed into the database</p>\n";
			echo "<p>^^^^---------^^^^</p>\n";
		}

		$round_line = trim(fgets($db_file));
		$round_line = parse_white_space($db_file, $round_line);
		$entry_error = $entry_error || !evaluate_line($round_line, '[Round "');
		$event_round = sscan_tag($round_line, '[Round "');

		$white_line = trim(fgets($db_file));
		$white_line = parse_white_space($db_file, $white_line);
		$entry_error = $entry_error || !evaluate_line($white_line, '[White "');
		$white_name = sscan_tag($white_line, '[White "');
		/* remove the "(wh)" */
		$white_name = substr($white_name, 0, count($white_name) - 5);

		$black_line = trim(fgets($db_file));
		$black_line = parse_white_space($db_file, $black_line);
		$entry_error = $entry_error || !evaluate_line($black_line, '[Black "');
		$black_name = sscan_tag($black_line, '[Black "');
		/* remove the "(bl)" */
		$black_name = substr($black_name, 0, count($black_name) - 5);

		$result_line = trim(fgets($db_file));
		$result_line = parse_white_space($db_file, $result_line);
		$entry_error = $entry_error || !evaluate_line($result_line, '[Result "');
		$game_result = sscan_tag($result_line, '[Result "');

		/* Harvest ECO and elo's from optional tag data */
		$optional_line = trim(fgets($db_file));
		$optional_line = parse_white_space($db_file, $optional_line);
		while ($optional_line[0] == '[') {
			if (contains($optional_line, '[ECO "')) {
				$ECO_class = sscan_tag($optional_line, '[ECO "');
				$ECO_alpha = substr($ECO_class, 0, 1);
				$ECO_numero = intval(substr($ECO_class, 1, 2));
				$ECO_category = get_eco_category($ECO_alpha, $ECO_numero);
			} else if (contains($optional_line, '[BlackElo "')) {
				$black_elo = sscan_tag($optional_line, '[BlackElo "');
			} if (contains($optional_line, '[WhiteElo "')) {
				$white_elo = sscan_tag($optional_line, '[WhiteElo "');
			}

			$optional_line = trim(fgets($db_file));
			$optional_line = parse_white_space($db_file, $optional_line);
		}

		/* This project is very dependent on the presence of the ECO tag. */
		if ($ECO_class === "") {
			$entry_error = true;
			echo "<p>vvvvvvvvv---info---vvvvvvvvvv<p>\n";
			echo "<p>No ECO tag was provided.<p>\n";
			echo "<p>this is critical info, so this game is omitted.<p>\n";
			echo "<p>^^^^^^^^^---info---^^^^^^^^^^<p>\n";
		}

		if (!$entry_error) {
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
				'minElo' => min((int) $white_elo, (int) $black_elo),
				'eco_alpha' => $ECO_alpha,
				'eco_numero' => (int) $ECO_numero,
				'eco_category' => $ECO_category
			];
		} else {
			echo "<p>vvvvvvvvv---Chess pgn notation error---vvvvvvvvvv<p>\n";
			echo "<p>Error while parsing chess game, game will be omitted.</p>\n";
			echo "<p>The data recorded for this game looks as follows:</p>\n";
			echo "<p>Event name: " . $event_name . "</p>\n";
			echo "<p>Event date: " . $event_date . "</p>\n";
			echo "<p>White player: " . $white_name . "</p>\n";
			echo "<p>Black player: " . $black_name . "</p>\n";
			echo "<p>White ELO: " . $white_elo . "</p>\n";
			echo "<p>Black ELO: " . $black_elo . "</p>\n";
			echo "<p>Result: " . $game_result . "</p>\n";
			echo "<p>ECO tag: " . $ECO_alpha . $ECO_numero . "</p>\n";
			echo "<p>^^^^^^^^^---Chess pgn notation error---^^^^^^^^^^<p>\n";
		}
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

			/* put this print here so that there is not too much spam */
			if ($verbose) {
				echo "<p>entered moves batch into db, total_count " .
				$global_moves_count . "</p>\n";
			}
		}

		/* Parse moves into array structure. */
		$moves = "";
		$dud_line = $optional_line;
		$use_moves = true;
		if (!contains($dud_line, "1.")) {
			echo "<p>first moves are weird</p>\n";
			echo "<p>" . $dud_line . "</p>\n";

			$use_moves = false;
		}
		while (!feof($db_file) && !empty(trim($dud_line)) &&
			($dud_line[0] !== '[')) {
			$moves .= $dud_line . " ";
			$dud_line = trim(fgets($db_file));
			$dud_line = parse_white_space($db_file, $dud_line);
		}

		if($use_moves) {
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

				$num_moves = max(count($moves['white']), count($moves['black']));
				if(!isset($moves['black'][$num_moves-1])) {
					// to handle the case that white wins
					$moves['black'][$num_moves-1] = "";
				}

				$gameID = count($data_batch) + $global_batch_count*$batch_size;
				for ($i = 0; $i < $num_moves; $i++) {
					$moves_batch[] = [
						'gameID' => $gameID,
						'move_index' => $i + 1, // index for moves starts at 1
						'white_move' => $moves['white'][$i],
						'black_move' => $moves['black'][$i]
					];
				}

				/*
				 * a move insert is approximately half the size of a tags
				 * insert, thus insert moves in 2x bigger batches (make sense?)
				 */
				if (count($moves_batch) >= $batch_size * 2) {
					$db->insert_multiple(
						'moves',
						$moves_batch
					);

					$global_moves_count += count($moves_batch);
					$moves_batch = array();
				}

				// $moves_batch[] = $sql_moves;
				/*
				 * This simply constructs a string to display,
				 * thus is not needed anymore
				 */
				// $num_moves = max(count($moves['white']),
				// 	count($moves['black']));
				//
				// for ($i = 0; $i < $num_moves; $i++) {
				// 	$move = ($i+1) . ": white: " . $moves['white'][$i];
				// 	if(isset($moves['black'][$i])) {
				// 		$move .= ", black: " . $moves['black'][$i] . "<br>";
				// 	}
				// }
			}
		} else {
			echo "<p>vvvvvvvvv---Chess pgn notation error---vvvvvvvvvv<p>\n";
			echo "<p>Error while parsing chess game, game will be omitted.</p>\n";
			echo "<p>The data recorded for this game looks as follows:</p>\n";
			echo "<p>Event name: " . $event_name . "</p>\n";
			echo "<p>Event date: " . $event_date . "</p>\n";
			echo "<p>White player: " . $white_name . "</p>\n";
			echo "<p>Black player: " . $black_name . "</p>\n";
			echo "<p>White ELO: " . $white_elo . "</p>\n";
			echo "<p>Black ELO: " . $black_elo . "</p>\n";
			echo "<p>Result: " . $game_result . "</p>\n";
			echo "<p>ECO tag: " . $ECO_alpha . $ECO_numero . "</p>\n";
			echo "<p> Moves: $moves </p>\n";
			echo "<p>^^^^^^^^^---Chess pgn notation error---^^^^^^^^^^<p>\n";
		}

		$event_line = $dud_line;
		/* gets rid of dud empty lines between games */
		$event_line = parse_white_space($db_file, $event_line);
	} //while not eof

	if (count($data_batch) > 0) {
		$db->insert_multiple(
			'tags',
			$data_batch
		);

		if ($verbose) {
			echo "<p>inserted last batch into db, total now " .
				(count($data_batch) + $global_batch_count * $batch_size) .
			   	"</p>\n";
		}

		$data_batch = array();
	}

	if(count($moves_batch) > 0) {
		$db->insert_multiple(
			'moves',
			$moves_batch
		);

		if ($verbose) {
			echo "<p>entered last moves batch into db, total now " .
			(count($moves_batch) + $global_moves_count) . "</p>\n";
		}

		$moves_batch = array();
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
