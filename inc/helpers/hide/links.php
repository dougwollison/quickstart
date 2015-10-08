<?php
/**
 * Hide Helper - Links: Disable links UI.
 *
 * @package QuickStart
 * @subpackage Hide
 * @since 1.10.0
 */

// Remove Pages from admin menu
function qs_helper_hide_links_adminmenu() {
	remove_menu_page( 'link-manager.php' );
}
add_action( 'admin_menu', 'qs_helper_hide_links_adminmenu' );

// Remove Pages from admin bar
function qs_helper_hide_links_adminbar() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'new-link', 'new-content' );
}
add_action( 'admin_bar_menu', 'qs_helper_hide_links_adminbar', 300 );

// Remove Pages from favorite actions
function qs_helper_hide_links_favorite( $actions ) {
	unset( $actions['link-add.php'] );
	return $actions;
}
add_filter( 'favorite_actions', 'qs_helper_hide_links_favorite' );

// Remove Recent Pages widget
function qs_helper_hide_links_widget() {
	unregister_widget( 'WP_Widget_Links' );
}
add_action( 'widgets_init', 'qs_helper_hide_links_widget' );