<?php

function create_database($db_name, $target_file) {
	/* just for testing */
	$max_move_length = get_longest_moves_string($target_file);
	echo "The length of the longest move is: ". $max_move_length;
	
	$settings = parse_ini_file(__DIR__."/../.my.cnf", true);

	$servername = "localhost";
	$username = $settings['client']['user'];
	$password = $settings['client']['password'];
	/*
	 * Create connection to the local mysql server
	 * as provided by the LAMP/MAMP stack.
	 */
	$connect = new mysqli($servername, $username, $password);

	/* Check the newly created connection */
	if ($connect->connect_error) {
		die("Connection failed: " . $connect->connect_error);
	}

	/*drop new database */
	$sql = "CREATE DATABASE `" . $db_name . "`";
	if ($connect->query($sql) === TRUE) {
		echo "<p>Database created successfully\n</p>";
	} else {
		echo "<p>Error creating database " . $db_name .": " . $connect->error .
			"</p>";
	}

	/*
	 * TODO: create new table in new database.
	 */

	$connect->close();
}


?>
