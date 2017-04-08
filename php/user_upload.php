<?php
/* TODO:
 * remove the following line, as this is for development purposes only
 */
ini_set('display_errors',1); // for the development PC only
error_reporting(E_ALL); // ALWAYS

// site root, ie, where in the broader directory tree
// this site is hosted.  the Double dirname is to not
// have the path end with php, as we want the actual 
// site root.
define ('SITE_ROOT', realpath(dirname(dirname(__FILE__))));

$target_dir = "/data/";

/* receive the file and run some tests */

// Calculate hash
$hash = hash_file("sha256", $_FILES["user_db"]["tmp_name"]);
// TODO: get hash from user as well and compare to our hash

//Set up the target file name
$target_file = $target_dir . $hash . "." . pathinfo(basename(
	$_FILES["user_db"]["name"]), PATHINFO_EXTENSION);

$uploadOk = 1;

// Check if file already exists
if (file_exists(SITE_ROOT.$target_file)) {
    echo "file exists.";
    $uploadOk = 0;
}

// Check file size
// TODO: this limit must be revised for realistic db sizes
if ($_FILES["user_db"]["size"] > 5000000) {
    echo "File too large.";
    $uploadOk = 0;
}

// Allow certain file formats
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
if($imageFileType != "pgn") {
    echo "Sorry, only PGN files are allowed.";
    $uploadOk = 0;
}
// Check if $uploadOk is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
	exit ("Upload failed one of the checks.");
} else {
    if (move_uploaded_file($_FILES["user_db"]["tmp_name"], SITE_ROOT . $target_file)) {
        echo "The file ". basename( $_FILES["user_db"]["name"]). " has been uploaded.";
    } else {
        echo "Upload failed.";
		exit ("Upload failed.");
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

// Check the newly created connection
if ($connect->connect_error) {
	die("Connection failed: " . $connect->connect_error);
} 

//drop new database
$sql = "CREATE DATABASE " . $hash;
if ($connect->query($sql) === TRUE) {
	echo "Database created successfully";
} else {
	echo "Error creating database " . $hash .": " . $connect->error;
}


/*
 * TODO: create new table in new database.
 */

$connect->close();

/* 
 * TODO:Parse the file into a db.
 * Also implement some syntax validation in the process
 */



?>
