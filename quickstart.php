<?php
/*
Plugin Name: QuickStart
Plugin URI: https://github.com/dougwollison/quickstart
Description: A utility kit for quick development of WordPress themes (and plugins). <strong>YOUR CUSTOM THEME RELIES ON THIS PLUGIN</strong>
Version: 1.11.1
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

// Update notice checker
add_action( 'in_plugin_update_message-' . plugin_basename( QS_FILE ), 'quickstart_check_update_notice' );
function quickstart_check_update_notice( $plugin ) {
	// Get the version number that the update is for
	$version = $plugin['new_version'];

	// Check if there's a notice about the update
	$transient = "nlingual-update-notice-{$version}";
	$notice = get_transient( $transient );
	if ( $notice === false ) {
		// Hasn't been saved, fetch it from the SVN repo
		$notice = file_get_contents( "http://plugins.svn.wordpress.org/nlingual/assets/notice-{$version}.txt" ) ?: '';

		// Save the notice
		set_transient( $transient, $notice, YEAR_IN_SECONDS );
	}

	// Print out the notice if there is one
	if ( $notice ) {
		echo apply_filters( 'the_content', $notice );
	}
}