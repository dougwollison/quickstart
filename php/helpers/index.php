<?php
/**
 * Index helpers; functions for getting the index page for an archive.
 *
 * @package QuickStart
 * @subpackage Index
 * @since 1.6.0
 */

/**
 * Get the ID or full post object of the index page.
 *
 * @since 1.6.0
 *
 * @param string $post_type Optional The post type to get the index page for.
 * @param string $return    Optional What to return ('id' or 'object').
 */
function get_index( $post_type = null, $return = 'id' ) {
	// If no post type specified, determine it.
	if ( is_null( $post_type ) ) {
		// Get the queried object
		$object = get_queried_object();

		// If it's an archive or the home page, and the queried object is a post, use that
		if ( ( is_post_type_archive() || is_home() ) && is_a( $object, 'WP_Post' ) ) {
			// Return the desired value
			return $return == 'id' ? $object->ID : $object;
		} else {
			// Otherwise, attempt to determine it
			if ( is_post_type_archive() ) {
				// If it's a post type archive, use the query var
				$post_type = get_query_var( 'post_type' );
			} elseif ( is_tax() ) {
				// If it's a taxonomy page, assume first object type for the taxonomy
				$tax = $object->taxonomy;
				$tax = get_taxonomy( $tax );
				$post_type = $tax->object_type[0];
			} else {
				// Default to post
				$post_type = get_query_var( 'post_type' );
			}

			// Recall this function with the determined post type
			return get_index( $post_type, $return );
		}
	} else {
		// If it's not the post post_type, see if an index page is set for this type
		if ( $post_type != 'post' && $page = get_option( "page_for_{$post_type}_posts" ) ) {
			$index = $page;
		} else {
			// Default to the page for posts
			$index = get_option( 'page_for_posts' );
		}

		// Return the desired value
		return $return == 'id' ? $index : get_post( $index );
	}
}

/**
 * Setup the postdata for the page for the current index
 *
 * @since 1.6.0
 *
 * @see get_index()
 */
function the_index() {
	global $post;

	$post = get_index( null, 'object' );
	setup_postdata( $post );
}

/**
 * Check if the a post is an index page.
 *
 * If you don't pass a post type, it'll return the slug of the post
 * type it's the index page for. Otherwise, it'll return true/false.
 *
 * @since 1.8.0
 *
 * @param int|object $post_id          Optional The ID of the post to check.
 * @param string     $$match_post_type Optional The post type to match.
 *
 * @return string|bool The result of the test.
 */
function is_index_page( $post_id = null, $match_post_type = null ) {
	// Handle no post or post object, also get the post type
	if ( is_null( $post_id ) ) {
		global $post;
		$post_type = $post->post_type;
		$post_id = $post->ID;
	} elseif ( is_object( $post_id ) ) {
		$post_type = $post_id->post_type;
		$post_id = $post_id->ID;
	} else {
		$post_type = get_post_type( $post_id );
	}
	
	// Automatically return false if not a page
	if ( $post_type != 'page' ) {
		return false;
	}
	
	// Reverse lookup the option name that this post's ID is the value of,
	// provided it's a page_for_ option.
	$option = $wpdb->get_var( $wpdb->prepare( "
		SELECT option_name FROM $wpdb->options WHERE option_value = %d AND
		(  option_name = 'page_for_posts' OR option_name LIKE 'page\_for\_%%\_posts' )
		ORDER BY option_id DESC
	", $post->ID ) );
	
	// Set the properties for testing and details if a match
	if ( $option ) {
		// Extract the post_type slug from the option name (default is post)
		$for_post_type = 'post';
		if ( preg_match( '/page_for_(.+)_posts/', $option, $matches ) ) {
			$for_post_type = $matches[1];
		}
		
		if ( is_null( $match_post_type ) ) {
			// No match requested, return the post type
			return $for_post_type;
		} else {
			// Match test requested, return result
			return $match_post_type == $for_post_type;
		}
	}
	
	return false;
}