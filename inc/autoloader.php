<?php
/**
 * QuickStart Autoloading System
 *
 * @package QuickStart
 * @subpackage Autoloader
 * @since 2.0.0
 */

namespace QuickStart_Autoloader;

/**
 * Handle file locating and loading.
 *
 * @since 2.0.0
 *
 * @param string $type The type to try and look under (class, trait, etc.)
 * @param string $name The symbol name of the asset being requested.
 *
 * @return bool Wether or not the file was found and loaded.
 */
function find( $type, $name ) {
	// Just in case, trim off beginning backslash
	$name = ltrim( $name, '\\' );

	// Split into the namespace(s) and class name
	$parts = explode( '\\', $name );

	// Reformat names to wordpress filename standards
	str_replace_in_array( '_', '-', $parts );
	$parts = array_map( 'strtolower', $parts );

	// Get the class name
	$name = array_pop( $parts );

	// Build the filename with possible namespace directories
	$file = implode( '/', $parts ) . '/' . $type . '-' . $name . '.php';

	// Build the full path
	$path = plugin_dir_path( __FILE__ ) . '/' . $file;

	// Make sure the file exists before loading it
	if ( file_exists ( $path ) ){
		require( $path );
		return true;
	}

	return false;
}

/**
 * Find/load an QuickStart class.
 *
 * Will automatically initailize if it's a Functional sub-class.
 *
 * @since 2.0.0
 *
 * @see find() to find and load the class if it exists.
 *
 * @param string $class The name of the class being requested.
 */
function find_class( $class ) {
	find( 'class', $class );
}

/**
 * Find/load an QuickStart abstract class.
 *
 * @since 2.0.0
 *
 * @see find() to find and load the class if it exists.
 *
 * @param string $class The name of the class being requested.
 */
function find_abstract( $class ) {
	find( 'abstract', $class );
}

/**
 * Find/load an QuickStart trait.
 *
 * @since 2.0.0
 *
 * @see find() to find and load the trait if it exists.
 *
 * @param string $trait The name of the trait being requested.
 */
function find_trait( $trait ) {
	find( 'trait', $trait );
}

// Register the find
spl_autoload_register( __NAMESPACE__ . '\\find_class' );
spl_autoload_register( __NAMESPACE__ . '\\find_abstract' );
spl_autoload_register( __NAMESPACE__ . '\\find_trait' );