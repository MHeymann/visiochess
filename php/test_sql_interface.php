<?php
require_once "mysql_interface.php";
// TODO: make this an actual test file
$settings = parse_ini_file(__DIR__."/../.my.cnf", true);

$db = new MySqlPhpInterface(
    $server=$settings['client']['mysql_server'],
    $user=$settings['client']['user'],
    $password=$settings['client']['password']
);
?>
