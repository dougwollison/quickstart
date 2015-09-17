<?php
/*
Plugin Name: QuickStart
Plugin URI: https://github.com/dougwollison/quickstart
Description: A utility kit for quick development of WordPress themes (and plugins). <strong>YOUR CUSTOM THEME RELIES ON THIS PLUGIN</strong>
Version: 1.11.0
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
require ( QS_DIR . '/inc/aliases.php' );   // Aliases for easier external access
require ( QS_DIR . '/inc/constants.php' ); // Handy constants for paths/urls
require ( QS_DIR . '/inc/utilities.php' ); // Publicly accessible utilities
require ( QS_DIR . '/inc/hooks.php' );     // Publicly accessible hooks

// Class autoloader
spl_autoload_register( function( $class ) {
	// Split it to get the Namespace and Class names, in reverse order
	$class_parts = array_reverse( explode( '\\', $class ) );

	// Get just the class
	$class = $class_parts[0];

	// Prefix should be class...
	$prefix = 'class';
	// ...But if the name begins with an underscore it's a trait
	if ( strpos( $class, '_' ) === 0 ) {
		$prefix = 'trait';
		$class = substr( $class, 1 ); // remove the underscore
	}

	// Reformat to wordpress standards
	$file = $prefix . '-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';

	// If a namespace exists, prepend it
	if ( isset( $class_parts[1] ) ) {
		$file = strtolower( str_replace( '_', '-', $class_parts[1] ) ) . '/' . $file;
	}

	// Make sure the file exists before loading it
	if ( file_exists ( plugin_dir_path( __FILE__ ) . 'inc/' . $file ) ){
        include( plugin_dir_path( __FILE__ ) . 'inc/' . $file );
    }
});
