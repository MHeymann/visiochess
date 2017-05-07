<?php
require_once "create_db.php";
require_once "pgn_parser.php";
require_once "define.php";

/* Keep the commented out version here, as this makes potential debugging
 * easier, negating the need to first google it :) */
//ini_set('display_errors',1); // for the development PC only
//error_reporting(E_ALL); // ALWAYS

// TODO: remove this and check if everything works on hosted server
header('Access-Control-Allow-Origin: *');

$target_dir = "/data/";

/* receive the file and run some tests */

/* Calculate hash */
$hash = hash_file("sha256", $_FILES["user_db_uploader"]["tmp_name"]);

/*Set up the target file name */
$target_file = $target_dir . $hash . "." . pathinfo(basename(
	$_FILES["user_db_uploader"]["name"]), PATHINFO_EXTENSION);

$uploadOk = true;

/* Check if file already exists */
if (file_exists(SITE_ROOT.$target_file)) {
    echo "<p>file exists.\n</p>";
  //  $uploadOk = false;
}

/* Check file size */
if ($_FILES["user_db_uploader"]["size"] > 10000000) {
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
	if (move_uploaded_file($_FILES["user_db_uploader"]["tmp_name"], SITE_ROOT .
		$target_file)) {
    } else {
        echo "<p>Upload failed.\n</p>";
		exit ("Upload failed.\n");
    }
}

/*
 * Create a new database for this user. as we already checked for the
 * existance of a png file with the same name and passed to be able to
 * get here, it is reasonably  safe to assume that we can expect no clashes
 * from here on.
 */
create_database($hash, $target_file);

parse_pgn_file_to_db($target_file, $hash, 200,false);
?>
