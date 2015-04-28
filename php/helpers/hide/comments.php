<?php
/**
 * Hide Helper - Comments: Disable comments functionality and UI.
 *
 * @package QuickStart
 * @subpackage Hide
 * @since 1.10.0
 */

// Remove edit comments and discussion options from admin menu
function qs_helper_hide_comments_adminmenu() {
	remove_menu_page( 'edit-comments.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
}
add_action( 'admin_menu', 'qs_helper_hide_comments_adminmenu' );

// Remove Comments from admin bar
function qs_helper_hide_comments_adminbar() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'comments' );
}
add_action( 'admin_bar_menu', 'qs_helper_hide_comments_adminbar', 300 );

// Remove Comments meta box from dashboard
function qs_helper_hide_comments_dashboard() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'qs_helper_hide_comments_dashboard' );

// Remove Pages from favorite actions
function qs_helper_hide_comments_favorite( $actions ) {
	unset( $actions['edit-comments.php'] );
	return $actions;
}
add_filter( 'favorite_actions', 'qs_helper_hide_comments_favorite' );

// Remove Recent Pages widget
function qs_helper_hide_comments_widget() {
	unregister_widget( 'WP_Widget_Recent_Comments' );
}
add_action( 'widgets_init', 'qs_helper_hide_comments_widget' );

// Ensure comments_open and pings_open returns false
add_filter( 'comments_open', '__return_false', 999 );
add_filter( 'pings_open', '__return_false', 999 );

// Also disable XML-RPC
add_filter( 'xmlrpc_enabled', '__return_false' );

// Change default comment/ping status to closed if not already
function qs_helper_hide_comments_status() {
	return 'closed';
}
add_filter( 'pre_option_default_comment_status', 'qs_helper_hide_comments_status', 999 );
add_filter( 'pre_option_default_ping_status', 'qs_helper_hide_comments_status', 999 );

// Remove comment and trackback support from all post_types with it
function qs_helper_hide_comments_support() {
	foreach ( get_post_types() as $post_type ) {
		remove_post_type_support( $post_type, 'comments' );
		remove_post_type_support( $post_type, 'trackbacks' );
	}
}
add_action( 'init', 'qs_helper_hide_comments_support' );