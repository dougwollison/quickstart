<?php
namespace QuickStart;

/**
 * The Hooks Kit: A collection of handy auto hooking methods for various purposes.
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.0.0
 */

class Hooks extends \SmartPlugin {
	/**
	 * A list of internal methods and their hooks configurations.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array(
		'fix_shortcodes'     => array( 'the_content', 10, 1 ),
		'disable_quickedit'  => array( 'post_row_actions', 10, 2 ),
		'frontend_enqueue'   => array( 'wp_enqueue_scripts', 10, 0 ),
		'backend_enqueue'    => array( 'admin_enqueue_scripts', 10, 0 ),
		'post_type_save'     => array( 'save_post', 10, 1 ),
		'post_type_count'    => array( 'dashboard_glance_items', 10, 1 ),
		'taxonomy_filter'    => array( 'restrict_manage_posts', 10, 0 ),
	);

	/**
	 * Setup filter to unwrap shortcodes for proper processing.
	 *
	 * @since 1.6.0 Slightly refined regular expression.
	 * @since 1.0.0
	 *
	 * @param string $content The post content to process. (skip when saving).
	 * @param mixed  $tags    The list of block level shortcode tags that should be unwrapped, either and array or comma/space separated list.
	 */
	public static function _fix_shortcodes( $content, $tags ) {
		csv_array_ref( $tags );
		$tags = implode( '|', $tags );

		// Strip closing p tags and opening p tags from beginning/end of string
		$content = preg_replace( '#^\s*(?:</p>)\s*([\s\S]+)\s*(?:<p[^>]*?>)\s*$#', '$1', $content );

		// Unwrap tags
		$content = preg_replace( "#(?:<p[^>]*?>)?(\[/?(?:$tags).*?\])(?:</p>)?#", '$1', $content );

		return $content;
	}

	/**
	 * Remove inline quickediting from a post type.
	 *
	 * @since 1.3.0
	 *
	 * @param array $actions The list of actions for the post row. (skip when saving).
	 * @param \WP_Post $post The post object for this row. (skip when saving).
	 * @param mixed $post_types The list of post types to affect, either an array or comma/space separated list.
	 */
	public static function _disable_quickedit( $actions, $post, $post_types ) {
		csv_array_ref( $post_types );
		if ( in_array( $post->post_type, $post_types ) ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	/**
	 * Alias to Tools::enqueue(), for the frontend.
	 *
	 * @since 1.0.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public function _frontend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}

	/**
	 * Alias to Tools::enqueue() for the backend.
	 *
	 * @since 1.0.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public function _backend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}

	/**
	 * Call the save_post hook for a specific post_type.
	 *
	 * Runs passed callback after running Tools::save_post_check().
	 *
	 * @since 1.6.0
	 *
	 * @param int $post_id The ID of the post being saved (skip when saving).
	 * @param string $post_type The post_type this callback is intended for.
	 * @param callback $callback The callback to run after the check.
	 */
	protected function _post_type_save( $post_id, $post_type, $callback ) {
		if ( ! Tools::save_post_check( $post_id, $post_type ) ) return;
		call_user_func( $callback, $post_id );
	}

	/**
	 * Add counts for a post type to the Right Now widget on the dashboard.
	 *
	 * @since 1.3.1 Revised logic to work with the new dashboard_right_now markup.
	 * @since 1.0.0
	 *
	 * @param array  $elements  The list of items to add (skip when saving).
	 * @param string $post_type The slug of the post type.
	 */
	protected function _post_type_count( $elements, $post_type ) {
		// Make sure the post type exists
		if ( ! $object = get_post_type_object( $post_type ) ) {
			return;
		}

		// Get the number of posts of this type
		$num_posts = wp_count_posts( $post_type );
		if ( $num_posts && $num_posts->publish ) {
			$singular = $object->labels->singular_name;
			$plural = $object->labels->name;

			// Get the label based on number of posts
			$format = _n( "%s $singular", "%s $plural", $num_posts->publish );
			$label = sprintf( $format, number_format_i18n( $num_posts->publish ) );

			// Add the new item to the list
			$elements[] = '<a href="edit.php?post_type=' . $post_type . '">' . $label . '</a>';
		}

		return $elements;
	}

	/**
	 * Utility for _taxonomy_filter.
	 *
	 * Prints options for categories for a specific parent.
	 *
	 * @since 1.6.0
	 *
	 * @param string $taxonomy The name of the taxonomy to get terms from.
	 * @param string $selected The slug of the currently selected term.
	 * @param int    $parent   The current parent term to get terms from.
	 * @param int    $depth    The current depth, for indenting purposes.
	 */
	protected static function taxonomy_filter_options( $taxonomy, $selected, $parent = 0, $depth = 0 ) {
		// Get the terms for this level
		$terms = get_terms( $taxonomy, 'parent=' . $parent );

		$space = str_repeat( '&nbsp;', $depth * 3 );

		foreach ( $terms as $term ) {
			// Print the option
			printf( '<option value="%s" %s>%s</option>', $term->slug, $term->slug == $selected ? 'selected' : '', $space . $term->name );

			self::taxonomy_filter_options( $taxonomy, $selected, $term->term_id, $depth + 1 );
		}
	}

	/**
	 * Add a dropdown for filtering by the custom taxonomy.
	 *
	 * @since 1.6.0 Now supports hierarchical terms via use of taxonomy_filter_options().
	 * @since 1.0.0
	 *
	 * @param object $taxonomy The taxonomy object to build from.
	 */
	public static function _taxonomy_filter( $taxonomy ) {
		global $typenow;
		$taxonomy = get_taxonomy( $taxonomy );
		if ( in_array( $typenow, $taxonomy->object_type ) ) {
			$var = $taxonomy->query_var;
			$selected = isset( $_GET[ $var ] ) ? $_GET[ $var ] : null;

			echo "<select name='$var'>";
				echo '<option value="">Show ' . $taxonomy->labels->all_items . '</option>';
				self::taxonomy_filter_options( $taxonomy->name, $selected );
			echo '</select>';
		}
	}
}