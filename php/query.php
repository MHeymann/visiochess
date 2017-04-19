<?php
require_once "mysql_interface.php";
// this file just echos back a json (array actually) that contains the filters
// this is actually just a check to see if it works

// necessary for testing, not sure if it will be needed in production
header('Access-Control-Allow-Origin: *');
$filters = array();
foreach($_POST as $filter_field => $value) {
  array_push($filters, "filter: " . $filter_field . ", value: " . $value);
}
echo "<p>";
echo json_encode($filters);
echo "</p>";
// only consists of test code demoing how the interface object works

$settings = parse_ini_file(__DIR__."/../.my.cnf", true);
$servername = $settings['client']['mysql_server'];
$username = $settings['client']['user'];
$password = $settings['client']['password'];

$db = new MySqlPhpInterface($server=$servername, $user=$username, $password=$password);
$db->connect();
$db->create_database('test');
$db->create_database($name='test', $replace=true);
$db->use_database('test');
$db->create_table(
  'test',
  array(
    'test_col' => 'varchar(15)',
    'test_col2' => 'int'
  ));
$db->delete_table('test');
$db->delete_database('test');
$db->sql('create database test;');
$db->sql('drop database test;');
$db->disconnect();
?>
