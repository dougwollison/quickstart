<?php
/**
 * Attachment helpers; shortcut functions for attachment (including post thumbnail) related things.
 *
 * @package QuickStart
 * @subpackage Thumbnail
 * @since 1.6.0
 */

/**
 * Return just the URL of the attachment image.
 *
 * @since 1.6.0
 *
 * @param int    $attachment_id The ID of the image to get.
 * @param string $size          Optional The size of the image to get.
 *
 * @return string The URL of the image in that size.
 */
function get_attachment_image_url( $attachment_id, $size = 'full' ) {
	$src = wp_get_attachment_image_src( $attachment_id, $size );
	return $src[0];
}

/**
 * Echo alias of get_attachment_image_url()
 *
 * @see get_attachment_image_url()
 */
function the_attachment_image_url( $attachment_id, $size = 'full' ) {
	echo get_attachment_image_url( $attachment_id, $size );
}

/**
 * Return just the URL of the post thumbnail.
 *
 * @since 1.8.0 Fixed get_post_thumbnail_id call to use $post_id.
 * @since 1.6.0
 *
 * @param int    $post_id The ID of the post for the thumbnail.
 * @param string $size    Optional The size of the image to get.
 *
 * @return string The URL of the image in that size.
 */
function get_post_thumbnail_url( $post_id = null, $size = 'full' ) {
	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	}

	$attachment_id = get_post_thumbnail_id( $post_id );
	return get_attachment_image_url( $attachment_id, $size );
}

/**
 * Echo alias of get_post_thumbnail_url()
 *
 * @see get_post_thumbnail_url()
 */
function the_post_thumbnail_url( $post_id = null, $size = 'full' ) {
	echo get_post_thumbnail_url( $post_id, $size );
}