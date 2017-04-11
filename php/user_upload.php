<?php
/* TODO:
 * remove the following line, as this is for development purposes only
 */

ini_set('display_errors',1); // for the development PC only
error_reporting(E_ALL); // ALWAYS

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
			(($i < $end_i) && ($read_string[$i] !== '"')); //loop conditionals
			$i++) {
		$ret_string .= $read_string[$i];
	}
	return $ret_string;
}


/*
 * site root, ie, where in the broader directory tree
 * this site is hosted.  the Double dirname is to not
 * have the path end with php, as we want the actual 
 * site root.
 */
define ('SITE_ROOT', realpath(dirname(dirname(__FILE__))));

$target_dir = "/data/";

/* receive the file and run some tests */

/* Calculate hash */
$hash = hash_file("sha256", $_FILES["user_db"]["tmp_name"]);

/* TODO: get hash from user as well and compare to our hash */

/*Set up the target file name */
$target_file = $target_dir . $hash . "." . pathinfo(basename(
	$_FILES["user_db"]["name"]), PATHINFO_EXTENSION);

$uploadOk = true;

/* Check if file already exists */
if (file_exists(SITE_ROOT.$target_file)) {
    echo "<p>file exists.\n</p>";
    $uploadOk = false;
}

/* Check file size */
/* TODO: this limit must be revised for realistic db sizes */
if ($_FILES["user_db"]["size"] > 5000000) {
    echo "<p>File too large.\n</p>";
    $uploadOk = false;
}

/* Allow certain file formats */
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
if($imageFileType != "pgn") {
    echo "<p>Sorry, only PGN files are allowed.\n</p>";
    $uploadOk = false;
}

/* Check if $uploadOk is set to false by an error */
if ($uploadOk == false) {
    echo "<p>Sorry, your file was not uploaded.\n</p>";
	exit ("Upload failed one of the checks.");
} else {
	if (move_uploaded_file($_FILES["user_db"]["tmp_name"], SITE_ROOT .
		$target_file)) {
		echo "<p>The file " . basename( $_FILES["user_db"]["name"]) .
			" has been uploaded.\n</p>";
    } else {
        echo "<p>Upload failed.\n</p>";
		exit ("Upload failed.\n");
    }
}

/*
 * Create a new database for this user.
 * as we already checked for the existance
 * of a png file with the same name
 * and passed to be able to get here,
 * it is safe to assume that we can expect no
 * clashes from here on.
 */

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
$sql = "CREATE DATABASE " . $hash;
if ($connect->query($sql) === TRUE) {
	echo "<p>Database created successfully\n</p>";
} else {
	echo "<p>Error creating database " . $hash .": " . $connect->error .
		"</p>";
}


/*
 * TODO: create new table in new database.
 */

$connect->close();

/* 
 * TODO:Parse the file into a db.
 * Also implement some syntax validation in the process
 */

$db_file = fopen(SITE_ROOT . $target_file, "r") or
	die("Opening file for parsing to database failed!");



$event_line = fgets($db_file);
while (!feof($db_file)) {
	/*
	 * Declare variables for this while loop, so that if any variables
	 * are missing in the PGN file, they are only given the empty string
	 * as values.
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
	/*
	 * TODO: harvest the year out of the date string, as this is the only
	 * value of interest, in addition to many games in the default database
	 * being uncomplete beyond the year.
	 */
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

		if ($eleven_sub == '[BlackElo "') {
			$black_elo = sscan_tag($optional_line, '[BlackElo "');

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
	 * TODO: Change following while to actually parse
	 * moves, instead of just skippin lines
	 */
	$dud_line = fgets($db_file);
	while (!feof($db_file) && $dud_line{0} !== '[') {
		$dud_line = fgets($db_file);
	}



	/*
	 * TODO: instead of just echoing back the table entry info, add to data base
	 * table
	 */
	echo "<p>----------------start of entry---------------------</p>";
	echo "<p>event: " . $event_name . "</p><p>site: " . $site_name . "</p>";
	echo "<p>date: " . $event_date . "</p><p>round: " . $event_round . "</p>";
	echo "<p> white: " . $white_name . "</p><p>black: " . $black_name . "</p>";
	echo "<p>result: " . $game_result . "</p><p>ECO: " . $ECO_class . "</p>";
	echo "<p>white elo: " . $white_elo . "</p><p>black elo: " . $black_elo . "</p>";

	echo "<p>-----------------end of entry---------------------</p>\n<p></p>";
	$event_line = $dud_line;
}



fclose($db_file);

?>
