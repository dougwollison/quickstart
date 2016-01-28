<?php
/**
 * Attachment helpers; shortcut functions for attachment (including post thumbnail) related things.
 *
 * @package QuickStart
 * @subpackage Thumbnail
 *
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
 * @since 1.12.1 Renamed to avoid conflict with WordPress 4.4 functions.
 * @since 1.8.0  Fixed get_post_thumbnail_id call to use $post_id,
 *				 Also added support for passing a post object, and
 *				 $meta_key arg for alternative post thumbnails.
 * @since 1.6.0
 *
 * @param int|object $post_id The ID of the post for the thumbnail.
 * @param string     $size    Optional The size of the image to get.
 *
 * @return string The URL of the image in that size.
 */
function get_post_attachment_image_url( $post_id = null, $size = 'full', $meta_key = '_thumbnail_id' ) {
	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	} elseif ( is_object( $post_id ) ) {
		$post_id = $post_id->ID;
	}

	if ( $meta_key == '_thumbnail_id' ) {
		$attachment_id = get_post_thumbnail_id( $post_id );
	} else {
		$attachment_id = get_post_meta( $post_id, $meta_key, true );
	}

	return get_attachment_image_url( $attachment_id, $size );
}

/**
 * Echo alias of get_post_thumbnail_url()
 *
 * @see get_post_attachment_image_url()
 */
function the_post_attachment_image_url( $post_id = null, $size = 'full', $meta_key = '_thumbnail_id' ) {
	echo get_post_attachment_image_url( $post_id, $size, $meta_key );
}