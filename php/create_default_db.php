<?php
require_once "create_db.php";

/*
 * create the default database the users will query,
 * then parse the pgn and insert the data into the database
 */
create_database("default_chess_db","/data/millionbase-2.5.pgn");
parse_pgn_file_to_db("/data/millionbase-2.5.pgn", "default_chess_db", 160,
	$verbose=false);
?>
