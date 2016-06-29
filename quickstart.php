<?php
/*
Plugin Name: QuickStart
Plugin URI: https://github.com/dougwollison/quickstart
Description: A utility kit for quick development of WordPress themes (and plugins). <strong>YOUR CUSTOM THEME RELIES ON THIS PLUGIN</strong>
Version: 1.13.0
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

// Require includes
require ( QS_DIR . '/inc/utilities.php' );            // Publicly accessible utilities
require ( QS_DIR . '/inc/autoloader.php' );           // Class autoloaders
require ( QS_DIR . '/inc/aliases.php' );              // Aliases for easier external access
require ( QS_DIR . '/inc/constants.php' );            // Handy constants for paths/urls
require ( QS_DIR . '/inc/hooks.php' );                // Publicly accessible hooks
require ( QS_DIR . '/inc/quickstart/functions.php' ); // Internal-use utilities