<?php
namespace QuickStart;

/**
 * The Features Kit: Utility methods for use by Setup when registering features.
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.0.0
 */

class Features {
	// =========================
	// !Order Manager
	// =========================

	/**
	 * Default save callback for order manager.
	 *
	 * @since 1.0.0
	 */
	public static function save_menu_order() {
		global $wpdb;
		if ( isset( $_POST['_qsnonce'] ) && wp_verify_nonce( $_POST['_qsnonce'], 'manage_menu_order' ) ) {
			// Loop through the list of posts and update
			foreach ( $_POST['menu_order'] as $order => $id ) {
				// Get the parent
				$parent = $_POST['parent'][ $id ];

				// Update the post
				wp_update_post( array(
					'ID'          => $id,
					'menu_order'  => $order,
					'post_parent' => $parent,
				) );
			}

			// Redirect back to the refering page
			header( 'Location: ' . $_POST['_wp_http_referer'] );
			exit;
		}
	}

	/**
	 * Menu order manager admin page.
	 *
	 * Prints a sortable list of all posts of a specific type, to manage menu_order.
	 *
	 * @since 1.6.0 Fixed use of nested/hierarchical aspect, added quicksort buttons.
	 * @since 1.4.0 Added use of $nested option.
	 * @since 1.0.0
	 */
	public static function menu_order_manager() {
		global $wpdb;
		$type      = $_GET['post_type'];
		$icon      = $type == 'post' ? 'post' : 'page';
		$post_type = get_post_type_object( $type );

		// Get the post, nesting if the post type is hierarchical
		$posts = static::menu_order_array( $type, $post_type->hierarchical );
		?>
		<div class="wrap">
			<?php screen_icon( $icon )?>
			<h2><?php echo get_admin_page_title()?></h2>

			<br>

			<form method="post" action="edit.php">
				<?php wp_nonce_field( 'manage_menu_order', '_qsnonce' )?>
				<div class="qs-order-manager <?php if ( $post_type->hierarchical ) echo 'qs-nested'?>">
					<?php static::menu_order_list( $posts, $post_type->hierarchical )?>
				</div>
				<p class="qs-sort">
					<label>Quick Sort:</label>
					<button type="button" class="button-secondary" value="name">Alphabetical</button>
					<button type="button" class="button-secondary" value="date">Date</button>
					<button type="button" class="button-secondary" value="flip">Reverse</button>
				</p>
				<button type="submit" class="button-primary">Save Order</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Build the tree of posts.
	 *
	 * @since 1.4.0 Added $nested argument.
	 * @since 1.0.0
	 *
	 * @param array $posts  The list of posts to go through.
	 * @param bool  $nested Wether or not to create a nested array.
	 * @param int   $parent The parent ID to filter by (if nesting).
	 */
	protected static function menu_order_array( $type, $nested = false, $parent = 0 ) {
		global $wpdb;

		$post_parent = '';
		// If nesting, include the post_parent clause
		if ( $nested ) {
			$post_parent = "AND post_parent = $parent";
		}

		// Fetch the posts for the specified parent
		$posts = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, post_title, post_parent
			FROM $wpdb->posts
			WHERE post_type = %s
			AND post_status != 'auto-draft'
			$post_parent
			ORDER BY menu_order ASC
		", $type, $parent ) );

		if ( $nested ) {
			foreach ( $posts as $post ) {
				// Loop through and repeat, deeper... and deeper... and deeper...
				// We must go deeper!
				$post->children = static::menu_order_array( $type, $nested, $post->ID );
			}
		}

		return $posts;
	}

	/**
	 * Print out the tree of posts.
	 *
	 * @since 1.4.0 Added $nested argument.
	 * @since 1.0.0
	 *
	 * @param array $posts  The list of posts to go through.
	 * @param bool  $nested Wether or not to list the nested posts and include a parent field.
	 */
	protected static function menu_order_list( $posts, $nested = false ) {
		?>
		<ol>
		<?php foreach ( $posts as $post ) : ?>
			<li>
				<div class="inner">
					<input type="hidden" class="qs-order-id" name="menu_order[]" value="<?php echo $post->ID?>">
					<?php if ( $nested ) : ?>
						<input type="hidden" class="qs-order-parent" name="parent[<?php echo $post->ID?>]" value="<?php echo $post->post_parent?>">
					<?php endif; ?>
					<?php echo $post->post_title?>
				</div>
				<?php
				if ( $nested && $post->children ) {
					static::menu_order_list( $post->children, $nested );
				}
				?>
			</li>
		<?php endforeach;?>
		</ol>
		<?php
	}
}