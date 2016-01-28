<?php
/**
 * Post Field helpers; hooks/functions for quickly accessing post object attributes.
 *
 * @package QuickStart
 * @subpackage Post_Fields
 *
 * @since 1.0.0
 */

/**
 * Fetch the specified field from the specified post (by ID or post_name).
 *
 * @since 1.10.0 Moved $post_fields to Tools
 * @since 1.0.0
 *
 * @global wpdb $wpdb The database abstraction class.
 *
 * @param string     $key The name of the meta key to fetch.
 * @param int|object $id  The ID or post_name of the post to fetch.
 *
 * @return mixed The requested meta value.
 */
function get_postfield( $field, $id ) {
	global $wpdb;

	$where = 'ID';
	$format = '%d';

	// If the ID isn't numeric, assume it's the post_name
	if ( ! is_numeric( $id ) ) {
		$where = 'post_name';
		$format = '%s';
	}

	// Prefix the field if necessary
	$field = QuickStart\Tools::maybe_prefix_post_field( $field );

	return $wpdb->get_var( $wpdb->prepare( "SELECT $field FROM $wpdb->posts WHERE $where = $format", $id ) );
}

/**
 * Echo alias of get_postfield
 *
 * @since 1.0.0
 *
 * @see get_postfield()
 */
function the_postfield( $key, $id = null ) {
	echo get_postfield( $key, $id );
}