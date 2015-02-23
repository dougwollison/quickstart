<?php
namespace QuickStart;

/**
 * The Callbacks Kit: A collection of callback methods for various purposes.
 *
 * @package QuickStart
 * @subpackage Callbacks
 * @since 1.0.0
 */

class Callbacks {
	/**
	 * Default admin page callback.
	 *
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

	/**
	 * Menu order manager admin page.
	 *
	 * Prints a sortable list of all posts of a specific type, to manage menu_order.
	 *
	 * @since 1.8.0 Restructured to reflect new handling for menu_order_list().
	 * @since 1.6.0 Fixed use of nested/hierarchical aspect, added quicksort buttons.
	 * @since 1.4.0 Added use of $nested option.
	 * @since 1.0.0
	 */
	public static function menu_order_admin_page() {
		global $wpdb;
		$type      = $_GET['post_type'];
		$icon      = $type == 'post' ? 'post' : 'page';
		$post_type = get_post_type_object( $type );
		?>
		<div class="wrap">
			<?php screen_icon( $icon )?>
			<h2><?php echo get_admin_page_title()?></h2>

			<br>

			<form method="post" action="edit.php">
				<?php wp_nonce_field( 'manage_menu_order', '_qsnonce' )?>
				<div class="qs-order-manager <?php if ( $post_type->hierarchical ) echo 'qs-nested'?>">
					<?php self::menu_order_list( $post_type )?>

					<?php if ( ! $post_type->hierarchical ) :?>
					<p class="qs-sort">
						<label>Quick Sort:</label>
						<button type="button" class="button-secondary" value="name">Alphabetical</button>
						<button type="button" class="button-secondary" value="date">Date</button>
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
	 * @since 1.8.0 Restructured to also handle post fetching.
	 * @since 1.6.0 Added data attributes for quick sort purposes.
	 * @since 1.4.0 Added $nested argument.
	 * @since 1.0.0
	 *
	 * @param object $post_type The post type object.
	 * @param int    $parent    Optional The parent ID to filter by (for nesting).
	 */
	protected static function menu_order_list( $post_type, $parent = 0 ) {
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
					self::menu_order_list( $post_type, $post->ID );
				}
				?>
			</li>
		<?php endwhile;?>
		</ol>
		<?php endif;
	}
}