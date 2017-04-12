<?php
require_once "create_db.php"

create_database("default_chess_db");
parse_pgn_file_to_db("millionbase-2.5.pgn", "default_chess_db");
?>
