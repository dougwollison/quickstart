<?php
/**
 * WP Edit helpers; admin links to display 'Edit This' buttons on the frontend.
 *
 * @package QuickStart
 * @subpackage WPEdit
 *
 * @since 1.10.0
 */

/**
 * Enqueues the necessary CSS/JS files for the wpedit buttons.
 *
 * @since 1.11.0 Added JS enqueue.
 * @since 1.10.0
 *
 * @uses QuickStart\Tools::enqueue()
 */
function qs_helper_quickedit_enqueue(){
	// Abort if user isn't even logged in.
	if ( ! is_user_logged_in() ) {
		return;
	}

	qs_enqueue( array(
		'css' => array(
			'qs-wpedit-css' => array(
				'src' => plugins_url( '/css/wpedit.css', QS_FILE ),
				'media' => 'screen',
			),
		),
		'js' => array(
			'qs-wpedit-js' => array(
				'src' => plugins_url( '/js/wpedit.js', QS_FILE ),
				'in_footer' => true,
			),
		),
	) );
}
add_action( 'wp_enqueue_scripts', 'qs_helper_quickedit_enqueue' );

/**
 * Create the HTML for the edit button.
 *
 * @since 1.11.0 Reworked to accept arguments array, including speicifc capability to check for.
 *               Also renamed from get_edit_link().
 * @since 1.10.0
 *
 * @param array $options The options for the link.
 *		@option mixed  "target" Either a URL within the admin or a post to get the edit link for.
 *		@option string "text"   The text of the link, defaults to "Edit This".
 *      @option string "class"  The class(es) to add to the link.
 *      @option string "cap"    The capability to check for.
 */
function get_wpedit_link( $options ) {
	// Default options
	$defaults = array(
		'target' => null,
		'text' => 'Edit This',
		'class' => '',
		'cap' => 'manage_options',
		'attr' => array(),
	);

	// Check if options were passed as arguments (backwards compatability)
	if ( ! is_array( $options ) ) { // $options is actually target (string/int/object)
		$options = array();
		$args = array_slice( array_keys( $defaults ), 0, func_num_args() );
		foreach ( $args as $i => $arg ) {
			$options[ $arg ] = func_get_arg( $i );
		}
	}

	// Parse the options with the defaults
	$options = wp_parse_args( $options, $defaults );

	// Extract the options
	extract( $options, EXTR_SKIP );

	// Default to the current post if no target is set
	if ( ! $target ) {
		global $post;
		$target = $post->ID;
	}

	// Build the URL based on what $target is
	if ( is_string( $target ) ) {
		// A page within the admin
		$url = admin_url( $target ); // create the link
		$can = current_user_can( $cap ); // get the cap test result
	} else {
		// A post ID
		$url = get_edit_post_link( $target ); // get the link
		$cap = get_post_type_object( get_post_type( $target ) )->cap->edit_post; // get the cap for this post to test
		$can = current_user_can( $cap, $target ); // get the cap test result
	}

	// Return the link HTML if the cap test passes
	if ( $can ) {
		// Build the attributes for the link
		$attr = wp_parse_args( $attr, array(
			'href' => $url,
			'target' => '_blank',
			'class' => 'wpedit-link ' . $class,
		) );

		return qs_build_tag( 'a', $attr, $text, array() );
	}

	return '';
}

/**
 * Echo alias of get_edit_link()
 *
 * @see get_wpedit_link()
 *
 * @since 1.11.0 Renamed from the_edit_link()
 */
function wpedit_link() {
	echo call_user_func_array( 'get_wpedit_link', func_get_args() );
}

/**
 * Add toggle button for showing/hiding wpedit buttons to admin bar
 *
 * @since 1.11.0
 */
if ( ! is_admin() ) {
	function qs_helper_quickedit_togglebutton( $admin_bar ) {
		$admin_bar->add_node( array(
			'id'    => 'wpedit-toggle',
			'title' => 'Hide Edit Buttons',
			'href'  => '#',
		) );
	}
	add_action( 'admin_bar_menu', 'qs_helper_quickedit_togglebutton', 999 );
}

/**
 * Aliases for backwards compatability
 *
 * @deprecated Will be removed in version 1.12.0
 */
function get_edit_link() {
	return call_user_func_array( 'get_wpedit_link', func_get_args() );
}
function the_edit_link() {
	echo call_user_func_array( 'wpedit_link', func_get_args() );
}