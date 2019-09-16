<?php
const ROOT_DIR = __DIR__ . "/";
require ROOT_DIR . 'vendor/autoload.php';
require ROOT_DIR . 'class.Swoop.php';
error_reporting(E_ERROR);
$main = new Swoop($argv);