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

// Calculate hash
$hash = hash_file("sha256", $_FILES["user_db"]["tmp_name"]);
// TODO: get hash from user as well and compare to our hash

//Set up the target file name
$target_file = $target_dir . $hash . "." . pathinfo(basename(
	$_FILES["user_db"]["name"]), PATHINFO_EXTENSION);

$uploadOk = 1;

// Check if file already exists
if (file_exists($target_file)) {
    echo "file exists.";
    $uploadCheck = 0;
}

// Check file size
// TODO: this limit must be revised for realistic db sizes
if ($_FILES["user_db"]["size"] > 500000) {
    echo "File too large.";
    $uploadCheck = 0;
}

// Allow certain file formats
$imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
if($imageFileType != "pgn") {
    echo "Sorry, only PGN files are allowed.";
    $uploadCheck = 0;
}
// Check if $uploadCheck is set to 0 by an error
if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
} else {
    if (move_uploaded_file($_FILES["user_db"]["tmp_name"], SITE_ROOT . $target_file)) {
        echo "The file ". basename( $_FILES["user_db"]["name"]). " has been uploaded.";
    } else {
        echo "Upload failed.";
    }
}
?>
