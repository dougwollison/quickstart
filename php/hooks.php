<?php
/**
 * QuickStart Hooks
 *
 * @package QuickStart
 * @subpackage Hooks
 *
 * @since 1.11.0
 */

// =========================
// !Script/Style Enqueues
// =========================

/**
 * Enqueues the basic helper JavaScript and CSS files for QuickStart fields.
 *
 * @since 1.11.0 Moved to new General Hooks include.
 * @since 1.9.2  Added jquery-ui-sortable dependency for qs-helpers-js script.
 * @since 1.0.0
 *
 * @uses QuickStart\Tools::enqueue()
 */
function qs_helpers_enqueue(){
	QuickStart\Tools::enqueue( array(
		'css' => array(
			'qs-helpers-css' => array( plugins_url( '/css/qs-helpers.css', QS_FILE ) ),
		),
		'js' => array(
			'qs-helpers-js' => array( plugins_url( '/js/qs-helpers.js', QS_FILE ), array( 'underscore', 'jquery', 'jquery-ui-sortable' ) ),
		),
	) );
	define( 'QS_HELPERS_ENQUEUED', true );
}

add_action( 'admin_enqueue_scripts', 'qs_helpers_enqueue' );

/**
 * Enqueues the necessary JavaScript and CSS files for the media manager interfaces.
 *
 * @since 1.11.0 Moved to new General Hooks include.
 * @since 1.10.0 Renamed.
 * @since 1.0.0
 *
 * @uses QuickStart\Tools::enqueue()
 */
function qs_helper_mediamanager_enqueue(){
	wp_enqueue_media();

	QuickStart\Tools::enqueue( array(
		'css' => array(
			'qs-media-css' => array( plugins_url( '/css/qs-media.css', QS_FILE ), array( 'media-views' ) )
		),
		'js' => array(
			'qs-media-js' => array( plugins_url( '/js/qs-media.js', QS_FILE ), array( 'underscore', 'media-editor' ) )
		)
	) );
}

add_action( 'admin_enqueue_scripts', 'qs_helper_mediamanager_enqueue' );

// Backwards compatability; define flag signaling media manager helper is loaded
define( 'QS_LOADED_MEDIA_MANAGER', true );

// =========================
// !AJAX Handlers
// =========================

/**
 * AJAX Handler for Geocoding requests from the map field.
 *
 * @since 1.11.0
 *
 * @uses QuickStart\Tools::geocode_address()
 */
function qs_hook_ajax_geocode() {
	// Check for the address parameter
	if ( isset( $_REQUEST['address'] ) ) {
		// Get the geocode result
		$result = QuickStart\Tools::geocode_address( $_REQUEST['address'] );

		// Print the result
		echo json_encode( $result );
		exit;
	}

	die(0);
}

add_action( 'wp_ajax_qs_helper_geocode', 'qs_hook_ajax_geocode' );
add_action( 'wp_ajax_nopriv_qs_helper_geocode', 'qs_hook_ajax_geocode' );