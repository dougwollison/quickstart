<?php
/**
 * Post Chunk helpers; hooks/functions for using more-tag separate chunks of post_content.
 *
 * @package QuickStart
 * @subpackage Post_Chunks
 * @since 1.0.0
 */

/**
 * Adds new property to $post object with chopped up version of the post.
 *
 * @since 1.8.0 Added filtering hook for the separator string used.
 * @since 1.0.0
 *
 * @param object $post The post to be chopped up.
 */
function post_chunks( $post ) {
	// Just in case, make sure $post is even an object
	if ( ! is_object( $post ) ) {
		return;
	}

	// The default separator is the more tag
	$sep = '<!--more-->';

	/**
	 * Filter the chunk separator string.
	 *
	 * @since 1.8.0
	 *
	 * @param string  $sep  The separator to filter.
	 * @param WP_Post $post The post object being used.
	 */
	$sep = apply_filters( 'qs_helper_chunk_separator', $sep, $post );

	// Escape it to make sure it works in a regex
	$sep_quoted = preg_quote( $sep, '/' );

	// Move closing tags after a more tag to before it, prevents broken code
	$post->post_content = preg_replace( '/(' . $sep_quoted . ')((?:\s*<\/\w+>\s*)+)/', '$2$1', $post->post_content );

	// Create the chunks
	$post->chunks = explode( $sep, $post->post_content );

	// Store the default chunk number for looping
	$post->chunk = 1;
}
add_action( 'the_post', 'post_chunks' );

/**
 * Return a specified chunk
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @param int  $i      Optional The number of the chunk, 1-indexed.
 * @param bool $filter Optional Wether or not to apply filters to the chunk.
 *
 * @return string The content chunk.
 */
function get_chunk( $i = null, $filter = 'the_content' ) {
	global $post;

	if ( is_null( $i ) ) {
		$i = $post->chunk;
		$post->chunk++;
	}

	$chunk = $post->chunks[ $i - 1 ];

	if ( $filter ) {
		$chunk = apply_filters( $filter, $chunk ) ;
	}

	return $chunk;
}

/**
 * Prints out a specified chunk.
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @param int $i Optional The number of the chunk, 1-indexed.
 *
 * @return string The processed content chunk.
 */
function the_chunk( $i = null ) {
	global $post;
	echo get_chunk( $i );
}

/**
 * Return wether or not there are still chunks to retrieve.
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @return bool Wether or not there are still chunks to retrieve.
 */
function have_chunks() {
	global $post;
	return $post->chunk <= count( $post->chunks );
}
