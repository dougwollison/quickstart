<?php
/**
 * Section helpers; hooks/functions to be used with the Section Manager feature.
 *
 * @package QuickStart
 * @subpackage Sections
 *
 * @since 1.11.0
 */

/**
 * Enqueues the necessary JavaScript and CSS files for the section manager interface.
 *
 * @since 1.11.0
 *
 * @uses QuickStart\Tools::enqueue()
 */
function qs_helper_sections_enqueue(){
	QuickStart\Tools::enqueue( array(
		'css' => array(
			'qs-sections-css' => array( plugins_url( '/css/qs-sections.css', QS_FILE ) )
		),
		'js' => array(
			'qs-sections-js' => array( plugins_url( '/js/qs-sections.js', QS_FILE ), array( 'jquery' ) )
		)
	) );
}
add_action( 'admin_enqueue_scripts', 'qs_helper_sections_enqueue' );

/**
 * Get the sections for a post.
 *
 * @since 1.13.0 Added fix to prevent accidentally fetching all sections.
 * @since 1.11.0
 *
 * @param int|object $post_id Optional The ID or object of the post to get sections for.
 *
 * @return WP_Query The query results of the sections for the post.
 */
function get_sections( $post_id = null ) {
	if ( is_null( $post ) ) {
		global $post;
		$post_id = $post->ID;
	} elseif ( is_object( $post ) ) {
		$post_id = $post_id->ID;
	}

	// Get the list of sections
	$sections = get_post_meta( $post_id, '_qs_section' );
	// Add blank ID to prevent accidentally fetching ALL sections
	$sections[] = 0;

	// Execute the query and return the result
	$query = new WP_Query( array(
		'post_type'      => 'qs_section',
		'post__in'       => $sections,
		'posts_per_page' => -1,
		'orderby'        => 'post__in',
		'order'          => 'asc',
	) );
	return $query;
}