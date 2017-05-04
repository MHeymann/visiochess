<?php
require_once "create_db.php";

create_database("default_chess_db","/data/millionbase-2.5.pgn");
parse_pgn_file_to_db("/data/millionbase-2.5.pgn", "default_chess_db", 200,
	$verbose=true);
?>
