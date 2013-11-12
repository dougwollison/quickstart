<?php
namespace QuickStart;

/**
 * The Hooks Kit: A collection of handy auto hooking methods for various purposes.
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.0.0
 */

class Hooks extends \SmartPlugin{
	/**
	 * A list of internal methods and their hooks configurations are.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array(
		'fix_shortcodes' => array( 'the_content', 10, 1 ),
		'post_type_count' => array( 'right_now_content_table_end', 10, 0 ),
		'taxonomy_count' => array( 'right_now_content_table_end', 10, 0 ),
		'taxonomy_filter' => array( 'restrict_manage_posts', 10, 0 ),
		'frontend_enqueue' => array( 'wp_enqueue_scripts', 10, 0 ),
		'backend_enqueue' => array( 'admin_enqueue_scripts', 10, 0 )
	);

	/**
	 * Setup filter to unwrap shortcodes for proper processing
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The post content to process. (skip when saving).
	 * @param mixed  $tags    The list of block level shortcode tags that should be unwrapped, either and array or comma/space separated list.
	 */
	public static function _fix_shortcodes( $content, $tags ) {
		csv_array_ref( $tags );
		$tags = implode( '|', $tags );

		var_dump($content);

		// Strip closing p tags and opening p tags from beginning/end of string
		$content = preg_replace( '#^\s*(?:</p>)\s*([\s\S]+)\s*(?:<p.*?>)\s*$#', '$1', $content );
		// Unwrap tags
		$content = preg_replace( "#(?:<p.*?>)?(\[/?(?:$tags).*\])(?:</p>)?#", '$1', $content );

		return $content;
	}

	/**
	 * Add counts for a post type to the Right Now widget on the dashboard
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The slug of the post type
	 */
	protected function _post_type_count( $post_type ) {
		// Make sure the post type exists
		if ( ! $object = get_post_type_object( $post_type ) ) return;

		$singular = $object->labels->singular_name;
		$plural = $object->labels->name;

		$num_posts = wp_count_posts( $post_type );

		echo '<tr>';

		$num = intval( $num_posts->publish );
		$text = _n( $singular, $plural, $num );
		if ( current_user_can( 'edit_posts' ) ) {
			$num = "<a href='edit.php?post_type=$post_type'>$num</a>";
			$text = "<a href='edit.php?post_type=$post_type'>$text</a>";
		}
		echo "<td class='first b b-$post_type'>$num</td>";
		echo "<td class='t $post_type'>$text</td>";

		if ( $num_posts->pending > 0 ) {
			$num = intval( $num->pending );
			$text = __( _n( $singular, $plural, $num ) . ' Pending' );
			if ( current_user_can( 'edit_posts' ) ) {
				$num = "<a href='edit.php?post_type=$post_type'>$num->pending</a>";
				$text = "<a href='edit.php?post_type=$post_type'>$text</a>";
			}
			echo "<td class='first b b-$post_type'>$num->pending</td>";
			echo "<td class='t $post_type'>$text</td>";
		}

		echo '</tr>';
	}

	/**
	 * Add counts for a taxonomy to the Right Now widget on the dashboard
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy The slug of the taxonomy
	 */
	protected function _taxonomy_count( $taxonomy ) {
		// Make sure the post type exists
		if ( ! $object = get_taxonomy( $taxonomy ) ) return;

		$singular = $object->labels->singular_name;
		$plural = $object->labels->name;

		echo '<tr>';

		$num = wp_count_terms( $taxonomy, 'hide_empty=0' );
		$text = _n( $singular, $plural, $num );
		if ( current_user_can( 'edit_posts' ) ) {
			$num = "<a href='edit-tags.php?taxonomy=$taxonomy'>$num</a>";
			$text = "<a href='edit-tags.php?taxonomy=$taxonomy'>$text</a>";
		}
		echo "<td class='first b b-$taxonomy'>$num</td>";
		echo "<td class='t $taxonomy'>$text</td>";

		echo '</tr>';
	}

	/**
	 * Add a dropdown for filtering by the custom taxonomy
	 *
	 * @since 1.0.0
	 *
	 * @param object $taxonomy The taxonomy object to build from
	 */
	public static function _taxonomy_filter( $taxonomy ) {
		global $typenow;
		$taxonomy = get_taxonomy( $taxonomy );
		if ( in_array( $typenow, $taxonomy->object_type ) ) {
			$var = $taxonomy->query_var;
			$selected = isset( $_GET[$var] ) ? $_GET[$var] : null;

			echo "<select name='$var'>";
				echo '<option value="">Show ' . $taxonomy->labels->all_items . '</option>';
				foreach ( get_terms( $taxonomy->name ) as $term ) {
					echo '<option value="' . $term->slug . '" ' . ($term->slug == $selected ? 'selected' : '') . '>' . $term->name . '</option>';
				}
			echo '</select>';
		}
	}

	/**
	 * Alias to Tools::enqueue(), for the frontend
	 *
	 * @since 1.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css)
	 */
	public function _frontend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}

	/**
	 * Alias to Tools::enqueue() for the backend
	 *
	 * @since 1.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css)
	 */
	public function _backend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}
}