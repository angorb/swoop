<?php

require 'vendor/autoload.php';
require 'class.Swoop.php';

use oxcrime\Swoop\Swoop;

error_reporting(E_ERROR);

$main = new Swoop($argv);
