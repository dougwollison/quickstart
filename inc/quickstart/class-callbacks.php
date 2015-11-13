<?php
namespace QuickStart;

/**
 * The Callbacks Kit: A collection of callback methods for various purposes.
 *
 * @package QuickStart
 * @subpackage Callbacks
 *
 * @since 1.10.0 Now extends Smart_Plugin
 * @since 1.0.0
 */

class Callbacks extends \Smart_Plugin {
	/**
	 * Default admin page callback.
	 *
	 * This will server for most admin pages; uses the WordPress Settings API
	 * to print out any fields registered.
	 *
	 * @since 1.10.0 Added $page callback argument.
	 * @since 1.0.0
	 *
	 * @param string $page The page slug.
	 */
	public static function default_admin_page( $page ) {
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>

			<form method="post" action="options.php">
				<?php settings_fields( $page ); ?>
				<?php do_settings_sections( $page ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Menu order manager admin page.
	 *
	 * Prints a sortable list of all posts of a specific type, to manage menu_order.
	 *
	 * @since 1.10.0 Added support for term order management as well as post.
	 * @since 1.8.0  Restructured to reflect new handling for menu_order_list().
	 * @since 1.6.0  Fixed use of nested/hierarchical aspect, added quicksort buttons.
	 * @since 1.4.0  Added use of $nested option.
	 * @since 1.0.0
	 *
	 * @param string $object_type The type of the object this is for (post_type or taxonomy).
	 * @param string $object_name The objects name.
	 */
	public static function menu_order_admin_page( $object_type, $object_name ) {
		global $wpdb;
		if ( $object_type == 'taxonomy' ) {
			$object = get_taxonomy( $object_name );
		} else {
			$object = get_post_type_object( $object_name );
		}

		// Identify the list method to use
		$method = "menu_order_list_{$object_type}";
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title()?></h1>

			<br>

			<form method="post" action="edit.php">
				<input type="hidden" name="object_type" value="<?php echo $object_type?>" />
				<input type="hidden" name="object_name" value="<?php echo $object_name?>" />
				<?php wp_nonce_field( 'manage_menu_order', '_qsnonce' )?>
				<div class="qs-order-manager <?php if ( $object->hierarchical ) echo 'qs-nested'?>">
					<?php self::$method( $object )?>

					<?php if ( ! $object->hierarchical ) :?>
					<p class="qs-sort">
						<label>Quick Sort:</label>
						<button type="button" class="button-secondary" value="name">Alphabetical</button>
						<?php if ( $object_type == 'taxonomy' ): ?>
						<button type="button" class="button-secondary" value="count">Post Count</button>
						<?php else:?>
						<button type="button" class="button-secondary" value="date">Date</button>
						<?php endif; ?>
						<button type="button" class="button-secondary" value="flip">Reverse</button>
					</p>
					<?php endif;?>
				</div>
				<button type="submit" class="button-primary">Save Order</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Menu order manager: Print out the tree of posts.
	 *
	 * @since 1.10.0 Renamed to menu_order_list_post_type.
	 * @since 1.8.0  Restructured to also handle post fetching.
	 * @since 1.6.0  Added data attributes for quick sort purposes.
	 * @since 1.4.0  Added $nested argument.
	 * @since 1.0.0
	 *
	 * @param object $post_type The post type object.
	 * @param int    $parent    Optional The parent ID to filter by (for nesting).
	 */
	protected static function menu_order_list_post_type( $post_type, $parent = 0 ) {
		// Build the query
		$query = array(
			'qs-context' => 'order-manager',
			'post_type' => $post_type->name,
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
		);

		// Handle where clause for post_parent if needed
		if ( $post_type->hierarchical ) {
			$query['post_parent'] = $parent;
		}

		// Fetch the posts for the specified parent
		$posts = new \WP_Query( $query );

		global $post;
		// Print the post list
		if ( $posts->have_posts() ) : ?>
		<ol>
		<?php while ( $posts->have_posts() ) : $posts->the_post();?>
			<li data-date="<?php echo strtotime( $post->post_date )?>" data-name="<?php echo sanitize_title( $post->post_title )?>">
				<div class="inner">
					<input type="hidden" class="qs-order-id" name="menu_order[]" value="<?php echo $post->ID?>">
					<?php if ( $post_type->hierarchical ) : ?>
						<input type="hidden" class="qs-order-parent" name="parent[<?php echo $post->ID?>]" value="<?php echo $post->post_parent?>">
					<?php endif; ?>
					<?php echo $post->post_title?>
				</div>
				<?php
				// Print the children if hierarchical
				if ( $post_type->hierarchical ) {
					self::menu_order_list_post_type( $post_type, $post->ID );
				}
				?>
			</li>
		<?php endwhile;?>
		</ol>
		<?php endif;
	}

	/**
	 * Menu order manager: Print out the tree of terms.
	 *
	 * @since 1.10.0
	 *
	 * @param object $taxonomy The taxonomy object.
	 * @param int    $parent   Optional The parent ID to filter by (for nesting).
	 */
	protected static function menu_order_list_taxonomy( $taxonomy, $parent = 0 ) {
		// Get all terms
		$terms = get_terms( $taxonomy->name, array(
			'hide_empty' => false,
			'orderby' => 'meta_value_num',
			'meta_key' => 'menu_order',
			'parent' => $parent,
		) );

		// If no terms are found, try without meta ordering
		if ( ! $terms ) {
			$terms = get_terms( $taxonomy->name, array(
				'hide_empty' => false,
				'parent' => $parent,
			) );
		}

		// Print the term list
		if ( $terms ) : ?>
		<ol>
		<?php foreach ( $terms as $term ):?>
			<li data-count="<?php echo strtotime( $term->count )?>" data-name="<?php echo sanitize_title( $term->name )?>">
				<div class="inner">
					<input type="hidden" class="qs-order-id" name="menu_order[]" value="<?php echo $term->term_id?>">
					<?php if ( $taxonomy->hierarchical ) : ?>
						<input type="hidden" class="qs-order-parent" name="parent[<?php echo $term->term_id?>]" value="<?php echo $term->parent?>">
					<?php endif; ?>
					<?php echo $term->name?>
				</div>
				<?php
				// Print the children if hierarchical
				if ( $taxonomy->hierarchical ) {
					self::menu_order_list_taxonomy( $taxonomy, $term->term_id );
				}
				?>
			</li>
		<?php endforeach;?>
		</ol>
		<?php endif;
	}
}