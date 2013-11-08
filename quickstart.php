<?php
/*
Plugin Name: QuickStart
Plugin URI: https://github.com/dougwollison/quickstart
Description: A utility kit for quick development of WordPress themes (and plugins). ***YOUR CUSTOM THEME RELIES ON THIS PLUGIN***
Version: 1.0
Author: Doug Wollison
Author URI: http://dougw.me
License: GPL2
*/

// QuickStart global
global $QuickStart;
$QuickStart = null;

// Create root constant
define( 'QS_ROOT', __FILE__ );

// Load functions
require QS_ROOT . '/php/aliases.php';
require QS_ROOT . '/php/helpers.php';
require QS_ROOT . '/php/utilities.php';

// Load classes
require QS_ROOT . '/php/QuickStart/Callbacks.php';
require QS_ROOT . '/php/QuickStart/Exception.php';
require QS_ROOT . '/php/QuickStart/Form.php';
require QS_ROOT . '/php/QuickStart/Setup.php';
require QS_ROOT . '/php/QuickStart/Template.php';
require QS_ROOT . '/php/QuickStart/Utilities.php';