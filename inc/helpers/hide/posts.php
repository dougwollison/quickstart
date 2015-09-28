<?php
/**
 * Hide Helper - Posts: Disable posts UI.
 *
 * @package QuickStart
 * @subpackage Hide
 * @since 1.10.0
 */

// Remove Posts from admin menu
function qs_helper_hide_posts_adminmenu() {
	remove_menu_page( 'edit.php' );
}
add_action( 'admin_menu', 'qs_helper_hide_posts_adminmenu' );

// Remove Posts from admin bar
function qs_helper_hide_posts_adminbar() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'new-post', 'new-content' );
}
add_action( 'admin_bar_menu', 'qs_helper_hide_posts_adminbar', 300 );

// Remove Posts from favorite actions
function qs_helper_hide_posts_favorite( $actions ) {
	unset( $actions['edit-posts.php'] );
	return $actions;
}
add_filter( 'favorite_actions', 'qs_helper_hide_posts_favorite' );

// Remove Recent Posts widget
function qs_helper_hide_posts_widget() {
	unregister_widget( 'WP_Widget_Recent_Posts' );
}
add_action( 'widgets_init', 'qs_helper_hide_posts_widget' );