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

// =========================
// ! Update Notice System
// =========================

/**
 * Check the public SVN repo for a notice about the new update.
 *
 * If a notice is found, it prints out the content inside the update message block.
 *
 * @since 1.11.1
 *
 * @param array $plugin A list of details about the plugin and the new version.
 */
function quickstart_check_update_notice( $plugin ) {
	// Get the version number that the update is for
	$version = $plugin['new_version'];

	// Check if there's a notice about the update
	$transient = "quickstart-update-notice-{$version}";
	$notice = get_transient( $transient );
	if ( $notice === false ) {
		// Hasn't been saved, fetch it from the SVN repo
		$notice = file_get_contents( "http://plugins.svn.wordpress.org/quickstart/assets/notice-{$version}.txt" ) ?: '';

		// Save the notice
		set_transient( $transient, $notice, YEAR_IN_SECONDS );
	}

	// Print out the notice if there is one
	if ( $notice ) {
		echo apply_filters( 'the_content', $notice );
	}
}

add_action( 'in_plugin_update_message-' . plugin_basename( QS_FILE ), 'quickstart_check_update_notice' );