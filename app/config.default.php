<?php

$config = array();
$config['db'] = array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'host'      => 'localhost',
        'dbname'    => 'banking',
        'user'      => 'banking_usr',
        'password'  => '',
    ),
);
$config['currency'] = 'EUR';

$config['debug'] = true;