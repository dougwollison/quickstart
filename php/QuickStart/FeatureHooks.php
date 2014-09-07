<?php
namespace QuickStart;

/**
 * The FeatureHooks Kit: Auto hooking methods related to the Features kit.
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.6.0
 */

class FeatureHooks extends \SmartPlugin {
	/**
	 * A list of internal methods and their hooks configurations are.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array(
		'index_page_query'      => array( 'parse_query', 10, 1 ),
		'index_page_title_part' => array( 'wp_title_parts', 10, 1 ),
		'index_page_title'      => array( 'wp_title', 10, 1 ),
	);
	
	/**
	 * Check if the page is a custom post type's index page.
	 *
	 * @since 1.6.0
	 *
	 * @param WP_Query $query     The query object (skip when saving).
	 * @param string   $post_type The post type to check for.
	 */
	protected function _index_page_query( $query, $post_type ) {
		$qv =& $query->query_vars;
		
		if ( '' != $qv['pagename'] ) {
			$index = get_option( "page_for_{$post_type}_posts" );
			if ( $query->queried_object_id == $index ) {
				$post_type_obj = get_post_type_object( $post_type );
				if ( ! empty( $post_type_obj->has_archive ) ) {
					$qv['post_type'] = $post_type;
					$qv['name'] = '';
					$qv['pagename'] = '';
					$query->is_page = false;
					$query->is_singular = false;
					$query->is_archive = true;
					$query->is_post_type_archive = true;
				}
			}
		}
	}
	
	/**
	 * Change the first part of the title to display the index page's title.
	 *
	 * @since 1.6.0
	 *
	 * @param string $title_array The parts of the page title (skip when saving).
	 * @param string $post_type   The post type to check for.
	 */
	protected function _index_page_title_part( $title_array, $post_type ) {
		$index = get_option( "page_for_{$post_type}_posts" );
		
		// Check if this is the right post type archive and an index page is set
		if ( is_post_type_archive() && get_query_var( 'post_type' ) == $post_type && $index ) {
			$title_array[0] = get_the_title( $index );
		}
		
		return $title_array;
	}
	
	/**
	 * Modify the title to display the index page's title.
	 *
	 * @since 1.6.0
	 *
	 * @deprecated 1.6.0 Exists solely for WordPress 3.9 and below.
	 *
	 * @param string $title       The page title (skip when saving).
	 * @param string $post_type   The post type to check for.
	 */
	protected function _index_page_title( $title, $post_type ) {
		$index = get_option( "page_for_{$post_type}_posts" );
		
		// Check if this is the right post type archive and an index page is set
		if ( is_post_type_archive() && get_query_var( 'post_type' ) == $post_type && $index ) {
			// Replace the archive title for the post type with the index page's title.
			$archive_title = post_type_archive_title( '', false );
			$page_title = get_the_title( $index );
			$title = str_replace( $archive_title, $page_title, $title );
		}
		
		return $title;
	}
}