<?php
require_once("define.php");
require_once("mysql_interface.php");
require_once("eco_category.php");
require_once("utils.php");


/**
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
 * @param $line The string that is being evaluated.
 * @param $should_start The characters that the line should start with.
 *
 * @return true if the line does contain with expected start character
 * sequence, false otherwise.
 */
function evaluate_line($line, $should_start) {
	if (contains($line, $should_start) && strpos($line, $should_start) == 0) {
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
 * Parse through the lines until a non trivial line that does not only
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

/**
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
   	$data_batch_size=160, $first_moves_batch_size=500, $flat_moves_batch_size=160, $verbose=false) {

	if ($verbose) {
		echo "<p>File being parsed: " . $target_file . "</p>\n";
		echo "<p>Database to be parsed to: " . $db_name . "</p>\n";
		echo "<p>Batch size being parsed to db in single query: " .
			$data_batch_size . "</p>\n";
	}

	$global_batch_count = 0;
	$global_first_moves_count = 0;
	$global_flat_moves_count = 0;

	$settings = parse_ini_file(__DIR__."/../.my.cnf", true);
	$servername = $settings['client']['mysql_server'];
	$username = $settings['client']['user'];
	$password = $settings['client']['password'];
	$moves_approach = $settings['client']['moves_table'];
	$data_batch = array();
	$first_moves_batch = array();
	$flat_moves_batch = array();

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
		 * Harvest the year out of the date string, as this is the
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
		// I found entries where these aren't present.
		//$white_name = substr($white_name, 0, count($white_name) - 5);

		$black_line = trim(fgets($db_file));
		$black_line = parse_white_space($db_file, $black_line);
		$entry_error = $entry_error || !evaluate_line($black_line, '[Black "');
		$black_name = sscan_tag($black_line, '[Black "');
		/* remove the "(bl)" */
		//$black_name = substr($black_name, 0, count($black_name) - 5);

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
			$gameID = count($data_batch) + $global_batch_count*$data_batch_size;
			$data_batch[] = [
				'gameID' => $gameID,
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



		if (count($data_batch) >= $data_batch_size) {
			$db->insert_multiple(
				'tags',
				$data_batch
			);
			$data_batch = array();
			$global_batch_count += 1;

			if ($verbose) {
				echo "<p>entered batch into db, total game count: " . $global_batch_count
					* $data_batch_size . "</p>\n";
				echo "<p>total move count: " .
					$global_first_moves_count . "</p>\n";
			}
		}


		/* Parse moves into array structure. */
		$moves = "";
		$dud_line = $optional_line;
		$use_moves = true;
		if (!contains($dud_line, "1.") || !contains($dud_line, "2.")) {
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

		if($use_moves && !$entry_error) {
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

			//	$num_moves = max(count($moves['white']), count($moves['black']));
				for ($i = 0; $i < 6; $i++) {
					/* to handle the case where there are fewer than 6 moves */
					if(!isset($moves['black'][$i])) {
						$moves['black'][$i] = "";
					}
					if(!isset($moves['white'][$i])) {
						$moves['white'][$i] = "";
					}
				}

				/* calculate and subtract one, as data_batch has since been
				 * increased by one */
				$gameID = count($data_batch) + $global_batch_count*$data_batch_size - 1;
				$first_moves_batch[] = [
					'gameID' => $gameID,
					'move_1' => $moves['white'][0] . " " . $moves['black'][0],
					'move_2' => $moves['white'][1] . " " . $moves['black'][1],
					'move_3' => $moves['white'][2] . " " . $moves['black'][2],
					'move_4' => $moves['white'][3] . " " . $moves['black'][3],
					'move_5' => $moves['white'][4] . " " . $moves['black'][4],
					'move_6' => $moves['white'][5] . " " . $moves['black'][5]
				];
				$flat_moves_batch[] = [
					'gameID' => $gameID,
					'pgn_string' => implode(" ", $moves_messy)
				];



				/*
				 * a move insert is approximately half the size of a tags
				 * insert, thus insert moves in 2x bigger batches (make sense?)
				 */
				if (count($first_moves_batch) >= $first_moves_batch_size) {
					$db->insert_multiple(
						'moves_six',
						$first_moves_batch
					);

					$global_first_moves_count += count($first_moves_batch);
					$first_moves_batch = array();
				}
				/*
				 */
				if (count($flat_moves_batch) >= $flat_moves_batch_size) {
					$db->insert_multiple(
						'moves_pgn',
						$flat_moves_batch
					);

					$global_flat_moves_count += count($flat_moves_batch);
					$flat_moves_batch = array();
				}
			}
		} else {
			if (!$use_moves) {
				echo "<p>vvvvvvvvv---Chess pgn moves notation error---vvvvvvvvvv<p>\n";
				echo "<p>Error while parsing chess game, game's moves will be omitted.</p>\n";
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
				echo "<p>^^^^^^^^^---Chess pgn moves notation error---^^^^^^^^^^<p>\n";
			}
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
				(count($data_batch) + $global_batch_count * $data_batch_size) .
			   	"</p>\n";
		}

		$data_batch = array();
	}

	if(count($first_moves_batch) > 0) {
		$db->insert_multiple(
			'moves_six',
			$first_moves_batch
		);

		if ($verbose) {
			echo "<p>entered last moves batch into db, total now " .
			(count($first_moves_batch) + $global_first_moves_count) . "</p>\n";
		}

		$first_moves_batch = array();
	}

	if(count($flat_moves_batch) > 0) {
		$db->insert_multiple(
			'moves_pgn',
			$flat_moves_batch
		);

		if ($verbose) {
			echo "<p>entered last moves batch into db, total now " .
			(count($flat_moves_batch) + $global_flat_moves_count) . "</p>\n";
		}

		$flat_moves_batch = array();
	}

	if ($verbose) {
		echo "inserting total first move counts";
	}
	$db->sql("INSERT INTO first_move(move, total) SELECT m.move_1, COUNT(*) FROM moves_six m GROUP BY m.move_1;");
	if ($verbose) {
		echo "inserted total first move counts";
	}

	$db->disconnect();
	fclose($db_file);

}

/**
 * Returns the longest string of chess moves, which will be used to
 * determine the width of the 'moves' column.
 *
 * @param $read_string:		The string containing the tag to be read.
 * @param $start_of_string: The start of the line being scanned.
*/
function get_longest_moves_string($target_file) {

	$db_file = fopen(SITE_ROOT . $target_file, "r") or
		die("Opening file for parsing to database failed!");
	$max = 0;

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
		 * Harvest the year out of the date string, as this is the
		 * only value of interest, in addition to many games in the default
		 * database being uncomplete beyond the year.
		 */
		$entry_error = $entry_error || !evaluate_line($date_line, '[Date "');
		$event_date = sscan_tag($date_line, '[Date "');
		// extract and keep year, throw away rest
		$event_date = explode('.', trim($event_date))[0];
		if (strlen($event_date) < 4) {
			$entry_error = true;
		}

		$round_line = trim(fgets($db_file));
		$round_line = parse_white_space($db_file, $round_line);
		$entry_error = $entry_error || !evaluate_line($round_line, '[Round "');
		$event_round = sscan_tag($round_line, '[Round "');

		$white_line = trim(fgets($db_file));
		$white_line = parse_white_space($db_file, $white_line);
		$entry_error = $entry_error || !evaluate_line($white_line, '[White "');
		$white_name = sscan_tag($white_line, '[White "');

		$black_line = trim(fgets($db_file));
		$black_line = parse_white_space($db_file, $black_line);
		$entry_error = $entry_error || !evaluate_line($black_line, '[Black "');
		$black_name = sscan_tag($black_line, '[Black "');

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
		}


		/* Parse moves into array structure. */
		$moves = "";
		$dud_line = $optional_line;
		$use_moves = true;
		if (!contains($dud_line, "1.") || !contains($dud_line, "2.")) {
			$use_moves = false;
		}
		while (!feof($db_file) && !empty(trim($dud_line)) &&
			($dud_line[0] !== '[')) {
			$moves .= $dud_line . " ";
			$dud_line = trim(fgets($db_file));
			$dud_line = parse_white_space($db_file, $dud_line);
		}

		if($use_moves && !$entry_error) {
			/* turn into one huge array */
			$moves_messy = explode(" ", trim($moves));
			/* removes score from moves */
			unset($moves_messy[count($moves_messy) - 1]);


			$moves = implode(" ", $moves_messy);
			if (strlen($moves) > $max) {
				$max = strlen($moves);
			}

		}

		$event_line = $dud_line;
		/* gets rid of dud empty lines between games */
		$event_line = parse_white_space($db_file, $event_line);
	} //while not eof

	fclose($db_file);
	return $max;

}
?>
