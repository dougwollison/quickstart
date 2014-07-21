<?php
/**
 * Post Chunk helpers; hooks/functions for using more-tag separate chunks of post_content.
 *
 * @package QuickStart
 * @subpackage Post_Chunks
 * @since 1.0.0
 */

/**
 * Adds new property to $post object with chopped up version of the post
 *
 * @since 1.0.0
 *
 * @param object $post The post to be chopped up
 */
function post_chunks( $post ) {
	// Just in case, make sure $post is even an object
	if ( ! is_object( $post ) ) {
		return;
	}
	
	$post->post_content = preg_replace( '/(<!--more-->)((?:\s*<\/\w+>\s*)+)/', '$2$1', $post->post_content );

	$post->chunks = explode( '<!--more-->', $post->post_content );
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
 * @param int  $i      The number of the chunk, 1-indexed
 * @param bool $filter Wether or not to apply filters to the chunk
 *
 * @return string The content chunk
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
 * Prints out a specified chunk,
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @param int $i The number of the chunk, 1-indexed
 *
 * @return string The processed content chunk
 */
function the_chunk( $i = null ) {
	global $post;
	echo get_chunk( $i );
}

/**
 * Return wether or not there are still chunks to retrieve
 *
 * @since 1.0.0
 *
 * @global WP_Post $post The current post object.
 *
 * @return bool $have_chunks Wether or not there are still chunks to retrieve
 */
function have_chunks() {
	global $post;
	return $post->chunk <= count( $post->chunks );
}
