<?php
/**
 * Hide Helper - Pages: Disable pages UI.
 *
 * @package QuickStart
 * @subpackage Hide
 * @since 1.10.0
 */

// Remove Pages from admin menu
function qs_helper_hide_pages_adminmenu() {
	remove_menu_page( 'edit.php?page_type=page' );
}
add_action( 'admin_menu', 'qs_helper_hide_pages_adminmenu' );

// Remove Pages from admin bar
function qs_helper_hide_pages_adminbar() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'new-page', 'new-content' );
}
add_action( 'admin_bar_menu', 'qs_helper_hide_pages_adminbar', 300 );

// Remove Pages from favorite actions
function qs_helper_hide_pages_favorite( $actions ) {
	unset( $actions['edit-posts.php?post_type=pages'] );
	return $actions;
}
add_filter( 'favorite_actions', 'qs_helper_hide_pages_favorite' );

// Remove Recent Pages widget
function qs_helper_hide_pages_widget() {
	unregister_widget( 'WP_Widget_Pages' );
}
add_action( 'widgets_init', 'qs_helper_hide_pages_widget' );