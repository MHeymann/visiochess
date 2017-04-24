<?php
require_once "mysql_interface.php";
// only consists of test code demoing how the interface object works
$settings = parse_ini_file(__DIR__."/../.my.cnf", true);
$servername = $settings['client']['mysql_server'];
$username = $settings['client']['user'];
$password = $settings['client']['password'];

$db = new MySqlPhpInterface($server=$servername, $user=$username, $password=$password);
$db->connect();
$db->create_database($name='test', $replace=true);
$db->use_database('test');
$db->create_table(
  'test',
  array(
    'test_col' => 'varchar(15)',
    'test_col2' => 'int'
  ));
$db->insert(
  'test',
  array(
    'test_col' => 'one',
    'test_col2' => 1
  ));
$db->insert(
  'test',
  array(
    'test_col' => 'seven',
    'test_col2' => 7
  ));

// example query for:
// select test_col from test where test_col2 = 1
$data = $db->select_from(
  'test',
  ['test_col'],
  [
    'test_col2' => [
      '=' => 1
    ]
  ]
);
echo "<p>" . json_encode($data) . "</p>\n";

// example query for:
// select test_col, test_col2 from test where test_col > a and test_col2 > 1 and test_col2 < 7
$data = $db->select_from(
  'test',
  ['test_col', 'test_col2'],
  [
    'test_col' => [
      '>' => 'a'
    ],
    'test_col2' => [
      '>' => 0,
      '<' => 7
    ]
  ]
);
echo "<p>" . json_encode($data) . "</p>\n";
// $db->delete_table('test');
// $db->delete_database('test');
// $db->sql('create database test;');
// $db->sql('drop database test;');
$db->disconnect();
?>
