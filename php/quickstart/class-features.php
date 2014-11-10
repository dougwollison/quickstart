<?php
namespace QuickStart;

/**
 * The Features Kit: Utility methods for use by Setup when registering features.
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.0.0
 */

class Features extends \Smart_Plugin {
	/**
	 * A list of internal methods and their hooks configurations.
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

	// =========================
	// !Order Manager
	// =========================

	/**
	 * Setup an order manager for certain post types.
	 *
	 * @since 1.6.0 Added check if enqueues were already handled.
	 * @since 1.0.0
	 *
	 * @param array $args A list of options for the order manager.
	 */
	public function setup_order_manager( $args ) {
		// Don't bother if on the admin side.
		if ( ! is_admin() ) {
			return;
		}

		// Default post_type option to page
		if ( ! isset( $args['post_type'] ) ) {
			$args['post_type'] = 'page';
		}

		$post_types = csv_array( $args['post_type'] );

		// Use the provided save callback if provided
		if ( isset( $args['save'] ) && is_callable( $args['save'] ) ) {
			$callback = $args['save'];
		} else {
			// Otherwise, use the built in one
			$callback = array( __NAMESPACE__ . '\Features', 'save_menu_order' );
		}

		add_action( 'admin_init', $callback );

		// Enqueue the necessary scripts if not already
		if ( is_admin() && ( ! defined( 'QS_ORDER_ENQUEUED' ) || ! QS_ORDER_ENQUEUED ) ) {
			Hooks::backend_enqueue( array(
				'css' => array(
					'qs-order-css' => array( plugins_url( '/css/qs-order.css', QS_FILE ) ),
				),
				'js' => array(
					'jquery-ui-nested-sortable' => array( plugins_url( '/js/jquery.ui.nestedSortable.js', QS_FILE ), array( 'jquery-ui-sortable' ) ),
					'qs-order-js' => array( plugins_url( '/js/qs-order.js', QS_FILE ), array( 'jquery-ui-nested-sortable' ) ),
				),
			) );
			define( 'QS_ORDER_ENQUEUED', true );
		}

		// Setup the admin pages for each post type
		foreach ( $post_types as $post_type ) {
			Setup::register_page( "$post_type-order", array(
				'title'      => sprintf( __( '%s Order' ), make_legible( $post_type ) ),
				'capability' => get_post_type_object( $post_type )->cap->edit_posts,
				'callback'   => array( __NAMESPACE__ . '\Features', 'menu_order_manager' ),
			), $post_type );
		}
	}

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
	 * Build the tree of posts.
	 *
	 * @since 1.6.0 Added post_date to results selecting.
	 * @since 1.4.0 Added $nested argument.
	 * @since 1.0.0
	 *
	 * @param array $posts  The list of posts to go through.
	 * @param bool  $nested Optional Wether or not to create a nested array.
	 * @param int   $parent Optional The parent ID to filter by (if nesting).
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
			SELECT ID, post_title, post_parent, post_date
			FROM $wpdb->posts
			WHERE post_type = %s
			AND post_status NOT IN ('auto-draft', 'trash')
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
	 * @since 1.6.0 Added data attributes for quick sort purposes.
	 * @since 1.4.0 Added $nested argument.
	 * @since 1.0.0
	 *
	 * @param array $posts  The list of posts to go through.
	 * @param bool  $nested Optional Wether or not to list the nested posts and include a parent field.
	 */
	protected static function menu_order_list( $posts, $nested = false ) {
		?>
		<ol>
		<?php foreach ( $posts as $post ) : ?>
			<li data-date="<?php echo strtotime( $post->post_date )?>" data-name="<?php echo sanitize_title( $post->post_title )?>">
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

	// =========================
	// !Custom Index Pages
	// =========================

	/**
	 * Setup index page setting/hook for certain post types.
	 *
	 * @since 1.6.0
	 *
	 * @param array $args A list of options for the custom indexes.
	 */
	public function setup_index_page( $args ) {
		// Abort if no post types set
		if ( ! isset( $args['post_type'] ) ) {
			return;
		}

		$post_types = csv_array( $args['post_type'] );

		foreach ( $post_types as $post_type ) {
			// Make sure the post type is registered
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			if ( is_admin() ) {
				// Register the setting on the backend
				$this->register_setting( "page_for_{$post_type}_posts" , array(
					'title' => sprintf( __( 'Page for %s' ) , get_post_type_object( $post_type )->labels->name ),
					'field' => function( $value ) use ( $post_type ) {
						wp_dropdown_pages( array(
							'name'              => "page_for_{$post_type}_posts",
							'echo'              => 1,
							'show_option_none'  => __( '&mdash; Select &mdash;' ),
							'option_none_value' => '0',
							'selected'          => $value,
						) );
					}
				), 'default', 'reading' );
			} else {
				// Add the query/title hooks on the frontend
				self::index_page_query( $post_type );

				// Call the appropriate title hook
				if ( version_compare( get_bloginfo( 'version' ), '4.0', '>=' ) ) {
					// Use new wp_title_parts filter method
					self::index_page_title_part( $post_type );
				} else {
					// Use old wp_title filter method
					self::index_page_title( $post_type );
				}
			}
		}
	}

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
					$qv['post_type']             = $post_type;
					$qv['name']                  = '';
					$qv['pagename']              = '';
					$query->is_page              = false;
					$query->is_singular          = false;
					$query->is_archive           = true;
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