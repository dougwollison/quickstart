<?php
namespace QuickStart;

/**
 * The SetupHooks Kit: Auto hooking methods related to the Setup kit.
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.6.0
 */

class SetupHooks extends \SmartPlugin {
	/**
	 * A list of internal methods and their hooks configurations are.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array(
		'post_type_save'     => array( 'save_post', 10, 1 ),
		'post_type_count'    => array( 'dashboard_glance_items', 10, 1 ),
		'taxonomy_filter'    => array( 'restrict_manage_posts', 10, 0 ),
	);
	
	/**
	 * Call the save_post hook for a specific post_type.
	 *
	 * Runs passed callback after running Tools::save_post_check()
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
	protected static function _taxonomy_filter( $taxonomy ) {
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