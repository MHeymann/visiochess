<?php
require_once "pgn_parser.php";

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
	$connect = new mysqli($servername, $username, $password);

	/* Check the newly created connection */
	if ($connect->connect_error) {
		die("Connection failed: " . $connect->connect_error);
	}

	/*
	 * TODO: make creating of databasis 'n single transaction, with all sql
	 * queries either succeeding or failing as a unit
	 */
	/* create new database */
	$sql = "CREATE DATABASE `" . $db_name . "`";
	if ($connect->query($sql) === TRUE) {
		echo "<p>Database created successfully\n</p>";
	} else {
		echo "<p>Error creating database " . $db_name .": " . $connect->error .
			"</p>";
	}

	$sql = "USE `" . $db_name . "`";
	if ($connect->query($sql) === TRUE) {
		echo "<p>Database set to be used successfully\n</p>";
	} else {
		echo "<p>Error using database " . $db_name .": " . $connect->error .
			"</p>";
	}

	$sql = "CREATE TABLE `Tags` (Event VARCHAR(50), Site VARCHAR(50), Date INT, Round INT, White VARCHAR(50), Black VARCHAR(50), Result VARCHAR(10), WhiteElo INT, BlackElo INT, Eco VARCHAR(5))";
	if ($connect->query($sql) === TRUE) {
		echo "<p>Tags table created successfully\n</p>";
	} else {
		echo "<p>Error creating Tags table for database " . $db_name .": " . $connect->error .
			"</p>";
	}

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

	$connect->close();
}


?>
