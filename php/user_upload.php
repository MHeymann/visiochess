<?php

require_once "create_db.php";
require_once "pgn_parser.php";
require_once "define.php";

/* TODO:
 * remove the following line, as this is for development purposes only
 */

ini_set('display_errors',1); // for the development PC only
error_reporting(E_ALL); // ALWAYS


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
 * Create a new database for this user. as we already checked for the
 * existance of a png file with the same name and passed to be able to
 * get here, it is reasonably  safe to assume that we can expect no clashes
 * from here on.
 */
create_database($hash);

/*
 * TODO:Parse the file into a db.
 * Also implement some syntax validation in the process
 */

////////////////////////////////////////
parse_pgn_file_to_db($target_file, $hash);

?>
