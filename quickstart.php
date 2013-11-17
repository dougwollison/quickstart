<?php
/*
Plugin Name: QuickStart
Plugin URI: https://github.com/dougwollison/quickstart
Description: A utility kit for quick development of WordPress themes (and plugins). ***YOUR CUSTOM THEME RELIES ON THIS PLUGIN***
Version: 1.0.0
Author: Doug Wollison
Author URI: http://dougw.me
License: GPL2
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// QuickStart global
global $QuickStart;
$QuickStart = null;

// Create root constant
define( 'QS_FILE', __FILE__ );
define( 'QS_DIR', __DIR__ );

// Load functions
require ( QS_DIR . '/php/aliases.php' );
require ( QS_DIR . '/php/constants.php' );
require ( QS_DIR . '/php/utilities.php' );

// Load classes
require ( QS_DIR . '/php/SmartPlugin.php' );
require ( QS_DIR . '/php/QuickStart/Callbacks.php' );
require ( QS_DIR . '/php/QuickStart/Exception.php' );
require ( QS_DIR . '/php/QuickStart/Features.php' );
require ( QS_DIR . '/php/QuickStart/Form.php' );
require ( QS_DIR . '/php/QuickStart/Hooks.php' );
require ( QS_DIR . '/php/QuickStart/Setup.php' );
require ( QS_DIR . '/php/QuickStart/Template.php' );
require ( QS_DIR . '/php/QuickStart/Tools.php' );