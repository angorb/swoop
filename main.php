<?php
define('ROOT_DIR', realpath(__DIR__ . "/"));
require ROOT_DIR . '/vendor/autoload.php';
require ROOT_DIR . '/class.Swoop.php';
error_reporting(E_ERROR);
$main = new Swoop($argv);