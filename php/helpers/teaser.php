<?php
/**
 * Teaser helpers; hooks/functions for creating a custom excerpt for the post.
 *
 * @package QuickStart
 * @subpackage Teaser
 * @since 1.0.0
 */
 
/**
 * Creates the teaser from a post of a specified length
 *
 * @since 1.0.0
 *
 * @param int    $length      Optional The length, in words, that the teaser should be
 * @param mixed  $post        Optional The post object or content (will default and use current post if available
 * @param bool   $use_excerpt Optional Wether or not to use the excerpt if available
 * @param bool   &$more       Optional A flag passed back by reference to indicate if there is more
 * @param string $trailer     Optional a string to trail the teaser with, such as an elipsis of some fashion
 * @return string The resulting teaser text
 */
function get_the_teaser( $length = 50, $post = null, $use_excerpt = false, &$more = false, $trailer = '...' ) {
	if ( is_null( $post ) ) {
		global $post;
	}

	if ( is_string( $post ) ) {
		// $post is $content string
		$content = $post;
	} elseif ( $post->post_excerpt ) {
		// excerpt exists, make it the $content
		$excerpt = true;
		$content = $post->post_excerpt;
	} elseif ( strpos( $post->post_content, '<!--more-->' ) ) {
		// content contains more tag, extract all text preceding first tag
		$excerpt = true;
		$content = preg_replace( '/^([\s\S]+?)<!--more-->[\s\S]+/', '$1', $post->post_content );
	} elseif ( ! ( $content = get_the_excerpt() ) ) {
		// cannot get the excerpt, just use post_content
		$content = $post->post_content;
	}

	// Strip out HTML tags
	$content = strip_tags( $content );

	// If we should use an excerpt and one is set, return content immediately and set $more to true
	if ( $use_excerpt && $excerpt ) {
		$more = true;
		return $content;
	}

	// Explode content into separate words
	$words = explode( ' ', $content );
	if ( count( $words ) > $length ) {
		// If content contains more then $length words, trim and set $more to true, then return teaser with desired trailer
		$more = true;
		array_splice( $words, $length );
		return implode( ' ', $words ) . $trailer;
	}
	return $content;
}

/**
 * Echo alias of get_the_teaser
 *
 * @since 1.0.0
 *
 * @uses get_the_teaser()
 *
 * @param int    $length      Optional The length, in words, that the teaser should be
 * @param mixed  $post        Optional The post object or content (will default and use current post if available
 * @param bool   $use_excerpt Optional Wether or not to use the excerpt if available
 * @param bool   &$more       Optional A flag passed back by reference to indicate if there is more
 * @param string $trailer     Optional a string to trail the teaser with, such as an elipsis of some fashion
 */
function the_teaser( $length = 50, $post = null, $use_excerpt = false, &$more = false, $trailer = '...' ) {
	echo get_the_teaser( $length, $post, $use_excerpt, $more );
}