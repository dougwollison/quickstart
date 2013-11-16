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
	 * @since 1.0.0
	 */
	public static function menu_order_manager() {
		global $wpdb;
		$type      = $_GET['post_type'];
		$icon      = $type == 'post' ? 'post' : 'page';
		$post_type = get_post_type_object( $type );

		$posts = static::menu_order_array( $type );
		?>
		<div class="wrap">
			<?php screen_icon( $icon )?>
			<h2><?php echo get_admin_page_title()?></h2>

			<br>

			<form method="post" action="edit.php">
				<?php wp_nonce_field( 'manage_menu_order', '_qsnonce' )?>
				<div class="qs-order-manager <?php if ( $post_type->hierarchical ) echo 'qs-nested'?>">
					<?php static::menu_order_list($posts)?>
				</div>
				<button type="submit" class="button-primary">Save Order</button>
			</form>
		</div>
		<?php
	}

	/**
	 * Build the tree of posts.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $posts The list of posts to go through
	 */
	protected static function menu_order_array( $type, $parent = 0 ) {
		global $wpdb;

		// Fetch the posts for the specified parent
		$posts = $wpdb->get_results( $wpdb->prepare( "
			SELECT ID, post_title, post_parent
			FROM $wpdb->posts
			WHERE post_type = %s
			AND post_status != 'auto-draft'
			AND post_parent = %d
			ORDER BY menu_order ASC
		", $type, $parent ) );

		foreach ( $posts as $post ) {
			// Loop through and repeat, deeper... and deeper... and deeper...
			// We must go deeper!
			$post->children = static::menu_order_array( $type, $post->ID );
		}

		return $posts;
	}

	/**
	 * Print out the tree of posts.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $posts The list of posts to go through
	 */
	protected static function menu_order_list( $posts ) {
		?>
		<ol>
		<?php foreach ( $posts as $post ):?>
			<li>
				<div class="inner">
					<input type="hidden" class="qs-order-id" name="menu_order[]" value="<?php echo $post->ID?>">
					<input type="hidden" class="qs-order-parent" name="parent[<?php echo $post->ID?>]" value="<?php echo $post->post_parent?>">
					<?php echo $post->post_title?>
				</div>
				<?php
				if ( $post->children ) {
					static::menu_order_list( $post->children );
				}
				?>
			</li>
		<?php endforeach;?>
		</ol>
		<?php
	}
}