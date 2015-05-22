<?php
/**
 * Setup contants for more efficient access to common values, namely paths/URIs
 *
 * @since 1.11.0
 */
function qs_constants() {
	/**
	 * The URL of the website.
	 *
	 * @since 1.0.0
	 */
	if ( ! defined( 'HOME_URL' ) ) {
		define( 'HOME_URL', get_bloginfo( 'url' ) );
	}

	/**
	 * The URL to the parent theme's directory, or current theme's if there isn't one.
	 *
	 * @since 1.0.0
	 */
	if ( ! defined( 'TEMPLATE_URL' ) ) {
		define( 'TEMPLATE_URL', get_bloginfo( 'template_url' ) );
	}

	/**
	 * The URL to the current theme's directory.
	 *
	 * @since 1.0.0
	 */
	if ( ! defined( 'THEME_URL' ) ) {
		define( 'THEME_URL', get_bloginfo( 'stylesheet_directory' ) );
	}

	/**
	 * The path to the parent theme, or current theme if there isn't one.
	 *
	 * @since 1.0.0
	 */
	if ( ! defined( 'TEMPLATE_PATH' ) ) {
		define( 'TEMPLATE_PATH', get_theme_root().'/'.get_template() );
	}

	/**
	 * The path to the current theme.
	 *
	 * @since 1.0.0
	 */
	if ( ! defined( 'THEME_PATH' ) ) {
		define( 'THEME_PATH', get_stylesheet_directory() );
	}
}

// Setup to run immediately after all plugins are loaded
// This should end up firing after stuff like MU Domain Mapping
add_action( 'plugins_loaded', 'qs_constants', 0 );