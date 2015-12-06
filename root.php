<?php
date_default_timezone_set('Asia/Jakarta');
define('APP_PATH', dirname(__FILE__).'/');

//setup environment
$production = '/u/k2427808/home/kurs';
$testing = '/u/k2427808/sites/pre.indonesiasatu.co/kurs';

$dirname = dirname(__FILE__);
switch ($dirname){
    case $production: define('ENVIRONMENT', 'production'); break;
    case $testing: define('ENVIRONMENT', 'testing'); break;
    default: define('ENVIRONMENT', 'development');
}

//include all required files
require_once APP_PATH . 'MyBaseClass.php';