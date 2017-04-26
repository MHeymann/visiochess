<?php
require_once "pgn_parser.php";
require_once "mysql_interface.php";

function create_database($db_name, $target_file) {
	$settings = parse_ini_file(__DIR__."/../.my.cnf", true);

	$servername = $settings['client']['mysql_server'];
	$username = $settings['client']['user'];
	$password = $settings['client']['password'];
	$moves_approach = $settings['client']['moves_table'];
	/*
	 * Create connection to the local mysql server
	 * as provided by the LAMP/MAMP stack.
	 */
	$db = new MySqlPhpInterface(
		$server=$servername,
		$user=$username,
		$password=$password
	);
	$db->connect();

	/*
	 * TODO: make creating of databasis 'n single transaction, with all sql
	 * queries either succeeding or failing as a unit
	 */
/* create new database */
	$db->create_database($name=$db_name, $replace=true);

	$db->use_database($db_name);

	$db->create_table(
		'tags',
		array(
			'event' => 'VARCHAR(50)',
			'site' => 'VARCHAR(50)',
			'date' => 'INT(4)',
			'round' => 'INT(3)', //check this but we assume round <= 999
			'white' => 'VARCHAR(50)',
			'black' => 'VARCHAR(50)',
			'result' => 'VARCHAR(10)',
			'whiteElo' => 'INT(4)',
			'blackElo' => 'INT(4)',
			'eco' => 'VARCHAR(5)'
		));
	$db->create_index(
		'tag_index',
		'tags',
		array(
			'date',
			'eco',
			'whiteElo',
			'blackElo'
		));

	if ($moves_approach == "flat") {
		/* TODO: here the Moves table should be created, based on the longest moves
		 * sequence in the in the database
		 */

		$max_move_length = get_longest_moves_string($target_file);
		echo "<p>The length of the longest move is: ". $max_move_length. "</p>";
	} else {
		/* TODO: here the Moves table should be created, with a foreign key to
		 * the Tags table's private key.
		 */
	}


	/*
	 * TODO: create new table in new database.
	 */

	$db->disconnect();
}


?>
