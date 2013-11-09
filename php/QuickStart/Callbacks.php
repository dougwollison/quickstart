<?php
namespace QuickStart;

/**
 * The Callbacks Kit: A collection of callback methods for various purposes.
 *
 * @package QuickStart
 * @subpackage Callbacks
 * @since 1.0.0
 */

class Callbacks{
	/**
	 * Callback maker, returns a callable array for the disired function with the desired arguments
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name The name of the method for the callback
	 * @params string $arg  The arguments to pass to that callback (all values must be scalar)
	 *
	 * @return array $callback The callable array
	 */
	public static function make( $method ) {
		$args = func_get_args();
		array_shift( $args );

		$name = $method;

		if ( $args ) {
			foreach ( $args as $arg ) {
				if ( ! is_scalar( $arg ) )
					throw new Exception( 'All arguments must be scalar values.' );
			}
		
			$name .= ':' . implode( ',', $args );
		}

		return array( __NAMESPACE__ . '\Callbacks', $name );
	}
	
	/**
	 * Method overloader; calls appropriate method with arguments based on name
	 *
	 * You can pass scalar values as arguments right in the "name" like so:
	 * "method_name:arg1,arg2"
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the method being called
	 * @param array  $args The arguments passed to said method
	 */
	public static function __callStatic( $name, $args ) {
		$name = explode( ':', $name );
		if ( isset( $name[1] ) ){
			$name[1] = explode( ',', $name[1] );
		}

		return call_user_func_array( array( 'self', $name[0] ), (array) $name[1] );
	}

	/**
	 * Add counts for a post type to the Right Now widget on the dashboard
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The slug of the post type
	 */
	protected function post_type_count( $post_type ) {
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
	protected function taxonomy_count( $taxonomy ) {
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
	public static function taxonomy_filter( $taxonomy ) {
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
	 * Default admin page callback
	 * This will server for most admin pages; uses the WordPress Settings API
	 * to print out any fields registered.
	 *
	 * @since 1.0.0
	 */
	public static function default_admin_page() {
		$screen = get_current_screen();

		if ( preg_match( '/^.+?_page_(.+)$/', $screen->id, $matches ) ) {
			// Submenu page
			$page = $matches[1];
		} else {
			// Top level page
			$page = str_replace( 'toplevel_page_', '', $screen->id );
		}

		?>
		<div class="wrap">
			<?php screen_icon( 'generic' ); ?>
			<h2><?php echo get_admin_page_title(); ?></h2>

			<br>

			<form method="post" action="options.php">
				<?php settings_fields( $page ); ?>
				<?php do_settings_sections( $page ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}