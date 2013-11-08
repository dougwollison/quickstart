<?php
/**
 * Post Field helpers; hooks/functions for quickly accessing post object attributes.
 *
 * @package QuickStart
 * @subpackage Post_Fields
 * @since 1.0.0
 */

/**
 * Fetch the specified field from the specified post (by ID or post_name).
 *
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
	
	$post_fields = array(
		'author',
		'date',
		'date_gmt',
		'content',
		'title',
		'excerpt',
		'status',
		'password',
		'name',
		'modified',
		'modified_gmt',
		'content_filtered',
		'parent',
		'type',
		'mime_type'
	);

	$where = 'ID';
	$format = '%d';

	// If the ID isn't numeric, assume it's the post_name
	if ( ! is_numeric( $id ) ) {
		$where = 'post_name';
		$format = '%s';
	}

	// Check if the $field doesn't start with post_ but should; prefix if so
	if ( strpos( $field, 'post_' ) !== 0 && in_array( $field, $post_fields ) ) {
		$field = "post_$field";
	}

	return $wpdb->get_var( $wpdb->prepare( "SELECT $field FROM $wpdb->posts WHERE $where = $format", $id ) );
}

/**
 * Echo alias of get_postfield
 *
 * @since 1.0.0
 *
 * @uses get_postfield()
 *
 * @param string     $key The name of the meta key to fetch
 * @param int|object $id  Optional The ID of the post to fetch
 */
function the_postfield( $key, $id = null ) {
	echo get_postfield( $key, $id );
}