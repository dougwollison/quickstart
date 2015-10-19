<?php
/**
 * Hide Helper - WP_Head: Remove all undesirable wp_head stuff.
 *
 * @package QuickStart
 * @subpackage Hide
 * @since 1.10.0
 */

// links for adjacent posts
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
// category feeds
remove_action( 'wp_head', 'feed_links_extra', 3 );
// post and comment feeds
remove_action( 'wp_head', 'feed_links', 2 );
// index link
remove_action( 'wp_head', 'index_rel_link' );
// previous link
remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
remove_action( 'wp_head', 'rel_canonical', 10, 1 );
// EditURI link
remove_action( 'wp_head', 'rsd_link' );
// start link
remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
// windows live writer
remove_action( 'wp_head', 'wlwmanifest_link' );
// WP version
remove_action( 'wp_head', 'wp_generator' );
// links for adjacent posts
remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );
// emoji stuff
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// remove WP version from css/js
function qs_helper_hide_wphead_version( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
};
add_filter( 'style_loader_src', 'qs_helper_hide_wphead_version', 9999 );
add_filter( 'script_loader_src', 'qs_helper_hide_wphead_version', 9999 );
