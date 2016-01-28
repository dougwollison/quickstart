<?php
/**
 * Walkers helper; Custom nav walker classes and other utilities.
 *
 * @package QuickStart
 * @subpackage Walkers
 *
 * @since 1.12.0
 */

// =========================
// ! wp_nav_menu Hooks
// =========================

/**
 * Detect if the walker provided is Walker_Inline_Nav_Menu.
 *
 * Will alter items_wrap code to a <span> instead of a <ul>.
 *
 * @since 1.12.0
 *
 * @param array $args The arguments for wp_nav_menu().
 *
 * @return array The modified arguments.
 */
function qs_helper_walker_inline_wrap( $args ) {
	if ( is_object( $args['walker'] ) && is_a( $args['walker'], 'Walker_Inline_Nav_Menu' ) ) {
		// If 'items_wrap' is the default, replace is with the unwrapped content
		if ( '<ul id="%1$s" class="%2$s">%3$s</ul>' == $args['items_wrap'] ) {
			$args['items_wrap'] = '%3$s';
		}
	}

	return $args;
}
add_filter( 'wp_nav_menu_args', 'qs_helper_walker_inline_wrap' );

// =========================
// ! Walker Class Autoloader
// =========================

/**
 * Autoload custom walker classes.
 *
 * @since 1.12.0
 *
 * @param string $class The class name to load.
 */
function qs_helper_walker_autoload( $class ) {
	// Rename to wordpress standards
	$class = strtolower( str_replace( '_', '-', $class ) );

	// Build the filename
	$file = 'class-' . $class . '.php';

	// Build the full path
	$path = __DIR__ . '/walkers/' . $file;

	// Check if the file exists, load it if so
	if ( file_exists ( $path ) ){
		require( $path );
	}
}

spl_autoload_register( 'qs_helper_walker_autoload' );