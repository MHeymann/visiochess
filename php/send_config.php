<?php
$settings = parse_ini_file(__DIR__."/../.my.cnf", true);
$config = $settings['client'];

// remove the sensitive details before sending
unset($config['user']);
unset($config['password']);

// change dev_mode to a boolean
$config['dev_mode'] = (boolean) $config['dev_mode'];

echo json_encode($config);
?>
