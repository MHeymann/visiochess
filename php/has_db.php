<?php
// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');

function has_db_available($name) {
	/* Get log in details */
	$settings = parse_ini_file(__DIR__."/../.my.cnf", true);
	$servername = $settings['client']['mysql_server'];
	$username = $settings['client']['user'];
	$password = $settings['client']['password'];

	/* Create connection */
	$conn = new mysqli($servername, $username, $password);

	/* Check connection */
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}

	/* Make query */
	$sql = "SHOW DATABASES LIKE '" . $name . "';";
	$result = $conn->query($sql);
	if ($result->num_rows <= 0) {
		$result = false;
	} else {
		$result = true;
	}

	/* Close connection */
	$conn->close();

	return $result;
}

$hash = $_POST["hash"];
$exists = has_db_available($hash);

$json = array(
	'db_present' => $exists,
	'hash' => $hash
);
echo json_encode($json);


?>
