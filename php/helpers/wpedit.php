<?php
/**
 * WP Edit helpers; admin links to display 'Edit This' buttons on the frontend.
 *
 * @package QuickStart
 * @subpackage WPEdit
 * @since 1.10.0
 */

/**
 * Enqueues the necessary CSS files for the media manager interfaces.
 *
 * @since 1.10.0
 *
 * @uses QuickStart\Tools::enqueue()
 */
function quickstart_enqueue_wpedit(){
	// Abort if user isn't even logged in.
	if ( ! is_user_logged_in() ) {
		return;
	}

	QuickStart\Tools::enqueue( array(
		'css' => array(
			'qs-wpedit-css' => array( plugins_url( '/css/qs-wpedit.css', QS_FILE ) ),
			'condition' => 'is_user_logged_in',
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'quickstart_enqueue_wpedit' );

/**
 * Create the HTML for the edit button.
 *
 * @since 1.10.0
 *
 * @param mixed  $target Optional The admin url, post ID or post object to use for the link.
 * @param string $text   Optional The text for the button.
 * @param string $class  Optional Any additional CSS classes to add.
 */
function get_edit_link( $target = null, $text = 'Edit This', $class = '' ) {
	// Default to the current post
	if ( ! $target ) {
		global $post;
		$target = $post;
	}

	// Build the URL based on what $target is
	if ( is_string( $target ) ) {
		// A page within the admin
		$url = admin_url( $target );
	} elseif ( is_object( $target ) ) {
		$url = get_edit_post_link( $target->ID );
	} else {
		$url = get_edit_post_link( $target );
	}

	if ( current_user_can( 'manage_options' ) ) {
		return sprintf( '<a href="%s" target="_blank" class="wpedit-link %s">%s</a>', $url, $class, $text );
	}

	return '';
}

/**
 * Echo alias of get_edit_link()
 *
 * @see get_edit_link()
 */
function the_edit_link( $target = null, $text = 'Edit This', $class = '' ) {
	echo get_edit_link( $target, $text, $class );
}