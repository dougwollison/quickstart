<?php
/**
 * Family helpers; functions related to accessing and checking child/parent posts.
 *
 * @package QuickStart
 * @subpackage Family
 *
 * @since 1.6.0
 */

// =========================
// !Ancestry
// =========================

/**
 * Check if a page/post is an ancestor of another page/post.
 *
 * You can also have it check if it's a specific ancestor.
 * E.g. Parent = 1, Grandparent = 2, etc.
 *
 * @since 1.6.0
 *
 * @param int|string $child   The child to compare against (id or pagename).
 * @param int|object $post_id Optional The ID or object of the post to compare (or current post).
 * @param int        $level   Optional What specific level to check (0, -1 or null for none).
 *
 * @return bool The result of the comparision.
 */
function is_ancestor_of( $child, $post_id = null, $level = 0 ) {
	// Get the post ID
	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	} elseif ( is_object( $post_id ) ) {
		$post_id = $post->ID;
	}

	// If $child is a pagename, use get_page_by_path
	if ( is_string( $child ) ) {
		$child = get_page_by_path( $child );
	}

	// Get the child's ancestors
	$ancestors = get_post_ancestors( $child );

	// Immediately return false if child has no ancestors
	if ( ! $ancestors ) {
		return false;
	}

	// Determine if it's in the ancestors list, and what location if so
	$location = array_search( $post_id, $ancestors );

	if ( false === $location ) {
		return false;
	}

	// If a specific level is desired, see if it matches
	if ( ! is_null( $level ) && $level > 0 ) {
		return $location + 1 == $level;
	} else {
		return true;
	}
}

/**
 * Check if a page/post is an IMMEDIATE ancestor of another page/post.
 *
 * @since 1.6.0
 *
 * @uses is_ancestor_of()
 *
 * @param int|string $child   The child to compare against (id or pagename).
 * @param int|object $post_id Optional The ID or object of the post to compare (or current post).
 *
 * @return bool The result of the comparision.
 */
function is_parent_of( $child, $post_id = null ) {
	return is_ancestor_of( $child, $post_id, 1 );
}

/**
 * Check if a page/post has ANY children.
 *
 * @since 1.6.2 Fixed for non-pages.
 * @since 1.6.0
 *
 * @param int|object $post_id Optional The ID or object of the post to compare (or current post).
 *
 * @return bool Wether or not the post has children.
 */
function has_children( $post_id = null ) {
	global $wpdb;

	// Get the post ID
	if ( is_null( $post_id ) ) {
		global $post;
		$post_type = $post->post_type;
		$post_id = $post->ID;
	} else if ( is_object( $post_id ) ) {
		$post_type = $post_id->post_type;
		$post_id = $post_id->ID;
	} else {
		$post_type = get_post_type( $post_id );
	}

	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->posts WHERE post_status = 'publish' AND post_parent = %d AND post_type = %s", $post_id, $post_type ) );

	return $count > 0;
}

// =========================
// !Descendancy
// =========================

/**
 * Check if a page/post is a descendant of another page/post.
 *
 * You can also have it check if it's a specific descendant.
 * E.g. Child = 1, Grandchild = 2, etc.
 *
 * @since 1.6.0
 *
 * @param int|string $parent  The parent to compare against (id or pagename).
 * @param int|object $post_id Optional The ID or object of the post to compare (or current post).
 * @param int        $level   Optional What specific level to check (0, -1 or null for none).
 *
 * @return bool The result of the comparision.
 */
function is_descendant_of( $parent, $post_id = null, $level = 0 ) {
	// Get the post ID
	if ( is_null( $post_id ) ) {
		global $post;
		$post_id = $post->ID;
	} elseif ( is_object( $post_id ) ) {
		$post_id = $post->ID;
	}

	// If $parent is a pagename, use get_page_by_path
	if ( is_string( $parent ) ) {
		$parent = get_page_by_path( $parent )->ID;
	}

	// Get the ancestors
	$ancestors = get_post_ancestors( $post_id );

	// Immediately return false if child has no ancestors
	if ( ! $ancestors ) {
		return false;
	}

	// Reverse so it goes top down
	$ancestors = array_reverse( $ancestors );

	// Determine if $parent it's in the ancestors list, and what location if so
	$location = array_search( $parent, $ancestors );

	if ( false === $location ) {
		return false;
	}

	// If a specific level is desired, see if it matches
	if ( ! is_null( $level ) && $level > 0 ) {
		return $location + 1 == $level;
	} else {
		return true;
	}
}

/**
 * Check if a page/post is an IMMEDIATE descendant of another page/post.
 *
 * @since 1.6.0
 *
 * @uses is_descendant_of()
 *
 * @param int|string $parent  The parent to compare against (id or pagename).
 * @param int|object $post_id Optional The ID or object of the post to compare (or current post).
 *
 * @return bool The result of the comparision.
 */
function is_child_of( $parent, $post_id = null ) {
	return is_descendant_of( $parent, $post_id, 1 );
}

/**
 * Check if a page/post has ANY parents.
 *
 * @since 1.6.0
 *
 * @param int|object $post Optional The ID or object of the post to compare (or current post).
 *
 * @return bool Wether or not the post has parents.
 */
function has_parent( $post = null ) {
	// Get the post object
	if ( is_null( $post ) ) {
		global $post;
	} else if ( ! is_object( $post ) ) {
		$post = get_post( $post );
	}

	return $post->post_parent > 0;
}

// =========================
// !Miscellaneous
// =========================

/**
 * Check if a page/post is a sibling of another.
 *
 * @since 1.6.0
 *
 * @param int|string $sibling The sibling to compare against (id or pagename).
 * @param int|object $post_id Optional The ID or object of the post to compare (or current post).
 *
 * @return bool Wether or not the posts have the same parent.
 */
function is_sibling_of( $sibling, $post = null ) {
	// Get the post object
	if ( is_null( $post ) ) {
		global $post;
	} else if ( ! is_object( $post ) ) {
		$post = get_post( $post );
	}

	// Get the sibling object
	if ( ! is_object( $sibling ) ) {
		// If $sibling is a pagename, use get_page_by_path
		if ( is_string( $sibling ) ) {
			$sibling = get_page_by_path( $sibling );
		} else {
			$sibling = get_post( $sibling );
		}
	}

	return $sibling->post_parent == $post->post_parent;
}