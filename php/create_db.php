<?php
require_once "pgn_parser.php";
require_once "mysql_interface.php";

/**
 * Create a database with the required fields for this project, and an
 * index to help with queries on large databases.
 *
 * @param $db_name The name of the database to create
 * @param $target_file The path to the pgn file for getting information on
 * the longest move sequence.
 */
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

	/* create new database */
	$db->create_database($name=$db_name, $replace=true);

	/* select the new database for querying */
	$db->use_database($db_name);

	/* create a table with the required fields */
	$db->create_table(
		'tags',
		array(
			'gameID' => 'INT(10) PRIMARY KEY AUTO_INCREMENT',
			'event' => 'VARCHAR(50)',
			'site' => 'VARCHAR(50)',
			'date' => 'INT(4)',
			'round' => 'INT(3)', // we assume round <= 999
			'white' => 'VARCHAR(50)',
			'black' => 'VARCHAR(50)',
			'result' => 'VARCHAR(10)',
			'whiteElo' => 'INT(4)',
			'blackElo' => 'INT(4)',
			'minElo' => 'INT(4)',
			'eco_alpha' => 'CHAR(1)',
			'eco_numero' => 'INT(2)',
			'eco_category' => 'VARCHAR(5)'
		),
		$replace=true
	);

	/*
	 * create an index on the most important fields for querying more
	 * efficiently
	 */
	$db->create_index(
		'tag_index',
		'tags',
		array(
			'date',
			/* first by broadest */
			'eco_alpha',
			/* then by one of 13 */
			'eco_category',
			/* then by specific number */
			'eco_numero',
			'minElo',
			'whiteElo',
			'blackElo'
		));

	/*
	 * What follows was meant for the heat map. May still be used for our
	 * additional work
	 */
	if ($moves_approach == "star") {
		 /* create a table with the required fields */
	 	$db->create_table(
	 		'moves',
	 		array(
	 			'gameID' => "INT(10) REFERENCES $db_name.tags(gameID)",
	 			'move_index' => 'INT(3)',
	 			'white_move' => 'VARCHAR(10)', // we can probably make this less
				'black_move' => 'VARCHAR(10)' // we can probably make this less
	 		),
	 		$replace=true
	 	);

	 	/*
	 	 * create an index on the most important fields for querying more
	 	 * efficiently
	 	 */
	 	$db->create_index(
	 		'moves_index',
	 		'moves',
	 		array(
	 			'gameID',
	 			'move_index',
				'white_move',
				'black_move'
	 		)
		);
	} else {
		/* TODO: Remove comment once it has been tested
		 * I am putting this here for now but we should just make sure
		 * that this does not break anything on the client's side.
		 */
		echo "Moves table will not be added" .
		"as a supported scheme was not specified";
	}

	$db->disconnect();
}
?>
