<?php
/**
 * Post Meta helpers; hooks/functions for quickly accessing post meta data.
 *
 * @package QuickStart
 * @subpackage Post_Meta
 * @since 1.0.0
 */

/**
 * Alternate form of WordPress' get_post_meta, assuming ID and taking field as first argument.
 *
 * @since 1.0.0
 *
 * @global wpdb $wpdb The database abstraction class.
 *
 * @param string     $key The name of the meta key to fetch.
 * @param int|object $id  Optional The ID of the post to fetch.
 *
 * @return mixed The requested meta value.
 */
function get_postmeta( $key, $id = null ) {
	global $wpdb;

	if ( is_null( $id ) ) {
		global $post;
		$id = $post->ID;
	} elseif ( is_object( $id ) ) {
		$id = $id->ID;
	}

	return get_post_meta( $id, $key, true );
}

/**
 * Echo alias of get_postmeta.
 *
 * @since 1.0.0
 *
 * @see get_postmeta()
 */
function the_postmeta( $key, $id = null ) {
	echo get_postmeta( $key, $id );
}