<?php
/**
 * Teaser helpers; hooks/functions for creating a custom excerpt for the post.
 *
 * @package QuickStart
 * @subpackage Teaser
 *
 * @since 1.0.0
 */

/**
 * Creates the teaser from a post of a specified length.
 *
 * @since 1.10.0 $length can now be the entire $args array.
 * @since 1.9.0  $post can now be an ID.
 * @since 1.6.0  Reworked to properly handle $use_excerpt boolean.
 * @since 1.0.0
 *
 * @param int    $length      Optional The length, in words, that the teaser should be.
 * @param mixed  $post        Optional The post object or content (will default and use current post if available.
 * @param bool   $use_excerpt Optional Wether or not to use the excerpt if available.
 * @param bool   &$more       Optional A flag passed back by reference to indicate if there is more.
 * @param string $trailer     Optional a string to trail the teaser with, such as an elipsis of some fashion.
 * @return string The resulting teaser text.
 */
function get_teaser( $length = 50, $post = null, $use_excerpt = false, &$more = false, $trailer = '...' ) {
	if ( is_array( $length ) ) {
		$args = $length;
		extract( $args );
	}

	if ( is_null( $post ) ) {
		global $post;
	} elseif ( is_int( $post ) ) {
		// Get the post
		$post = get_post( $post );
	}

	if ( is_string( $post ) ) {
		// $post is the $content string.
		$content = $post;
	} elseif ( $use_excerpt && $post->post_excerpt ) {
		// post_excerpt allowed and present, use it.
		$more = true;
		$content = $post->post_excerpt;
	} elseif ( strpos( $post->post_content, '<!--more-->' ) ) {
		// <more> tag(s) present, extract all content preceding the first tag.
		$more = true;
		$content = preg_replace( '/^([\s\S]+?)<!--more-->[\s\S]+/', '$1', $post->post_content );
	} else {
		// default to just the post_content.
		$content = $post->post_content;
	}

	// Strip out HTML tags...
	$content = strip_tags( $content );
	// ...and excess space.
	$content = preg_replace( '/(?:\s|&nbsp;)+/', ' ', $content );

	if ( $more ) {
		// Defined excerpt of some kind available; return it.
		return $content;
	}

	// Create an excerpt of $length words.
	$words = explode( ' ', $content );
	if ( count( $words ) > $length ) {
		// More words than needed, trim it, and flag $more as true.
		$more = true;
		array_splice( $words, $length );

		// Return generated excerpt, with desired trailer.
		return implode( ' ', $words ) . $trailer;
	}

	// By default, return full content
	return $content;
}

/**
 * Echo alias of get_the_teaser.
 *
 * @since 1.0.0
 *
 * @see get_teaser()
 */
function the_teaser( $length = 50, $post = null, $use_excerpt = false, &$more = false, $trailer = '...' ) {
	echo get_teaser( $length, $post, $use_excerpt, $more );
}