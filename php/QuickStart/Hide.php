<?php
namespace QuickStart;

/**
 * The Hide Kit; Callbacks for hiding various WordPress features.
 *
 * @copyright 2012 Doug Wollison
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 * @version Release: 1.0
 * @link https://github.com/dougwollison/quickstart
 */

class Hide{
	/**
	 * Call the appropriate no_[object] method(s)
	 *
	 * @since 1.0
	 *
	 * @param mixed $objects An object name, comma separated string, or array of objects to disable
	 */
	public static function these( $objects ) {
		if ( !is_array( $objects ) ) {
			$objects = preg_split( '/\s*,\s*/', $objects );
		}
		foreach ( $objects as $object ) {
			if ( method_exists( __CLASS__, $object ) ) {
				self::$object();
			}
		}
	}

	/**
	 * Remove Posts from menus and dashboard
	 *
	 * @since 1.0
	 */
	public static function posts() {
		// Remove Posts from admin menu
		add_action( 'admin_menu', function () {
			remove_menu_page( 'edit.php' );
		} );

		// Remove Posts from admin bar
		add_action( 'admin_bar_menu', function () {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'new-post', 'new-content' );
		}, 300 );

		// Remove Posts from favorite actions
		add_filter( 'favorite_actions', function ( $actions ) {
			unset( $actions['edit-posts.php'] );
			return $actions;
		} );

		// Remove Recent Posts widget
		add_action( 'widgets_init', function () {
			unregister_widget( 'WP_Widget_Recent_Posts' );
		} );
	}

	/**
	 * Remove Pages from menus and dashboard
	 *
	 * @since 1.0
	 */
	public static function pages() {
		// Remove Pages from admin menu
		add_action( 'admin_menu', function () {
			remove_menu_page( 'edit.php?post_type=page' );
		} );

		// Remove Pages from admin bar
		add_action( 'admin_bar_menu', function () {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'new-page', 'new-content' );
		}, 300 );

		// Remove Pages from favorite actions
		add_filter( 'favorite_actions', function ( $actions ) {
			unset( $actions['edit-posts.php?post_type=page'] );
			return $actions;
		} );

		// Remove Pages widget
		add_action( 'widgets_init', function () {
			unregister_widget( 'WP_Widget_Pages' );
		} );
	}

	/**
	 * Remove Comments from menus, dashboard, editor, etc.
	 *
	 * @since 1.0
	 *
	 * @return bool true
	 */
	public static function comments() {
		// Remove Comment support from all post_types with it
		add_action( 'init', function () {
			foreach ( get_post_types( array( 'public' => true, '_builtin' => true ) ) as $post_type ) {
				if ( post_type_supports( $post_type, 'comments' ) ) {
					remove_post_type_support( $post_type, 'comments' );
				}
			}
		} );

		// Remove edit comments and discussion options from admin menu
		add_action( 'admin_menu', function () {
			remove_menu_page( 'edit-comments.php' );
			remove_submenu_page( 'options-general.php', 'options-discussion.php' );
		} );

		// Remove Comments from admin bar
		add_action( 'admin_bar_menu', function () {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'comments' );
		}, 300 );

		// Remove Comments meta box from dashboard
		add_action( 'wp_dashboard_setup', function () {
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		} );

		// Remove Comments/Trackback meta boxes from post editor
		add_action( 'admin_init', function () {
			remove_meta_box( 'trackbacksdiv','post','normal' );
			remove_meta_box( 'commentstatusdiv','post','normal' );
			remove_meta_box( 'commentsdiv','post','normal' );
			remove_meta_box( 'trackbacksdiv','page','normal' );
			remove_meta_box( 'commentstatusdiv','page','normal' );
			remove_meta_box( 'commentsdiv','page','normal' );
		} );

		// Remove Comments column from Posts/Pages editor
		$removeCommentsColumn = function ( $defaults ) {
			unset( $defaults["comments"] );
			return $defaults;
		};
		add_filter( 'manage_posts_columns', $removeCommentsColumn );
		add_filter( 'manage_pages_columns', $removeCommentsColumn );

		// Remove Recent Comments widget
		add_action( 'widgets_init', function () {
			unregister_widget( 'WP_Widget_Recent_Comments' );
		} );

		// Remove Comments from favorite actions
		add_filter( 'favorite_actions', function ( $actions ) {
			unset( $actions['edit-comments.php'] );
			return $actions;
		} );

		// Make comments number always return 0
		add_action( 'get_comments_number', function () {
			return 0;
		} );

		// Edit $wp_query to clear comment related data
		add_action( 'comments_template', function () {
			global $wp_query;
			$wp_query->comments = array();
			$wp_query->comments_by_type = array();
			$wp_query->comment_count = 0;
			$wp_query->post->comment_count = 0;
			$wp_query->post->comment_status = 'closed';
			$wp_query->queried_object->comment_count = 0;
			$wp_query->queried_object->comment_status = 'closed';
		} );
	}

	/**
	 * Remove Links from menus and dashboard
	 *
	 * @since 1.0
	 */
	public static function links() {
		// Remove Links from admin menu
		add_action( 'admin_menu', function () {
			remove_menu_page( 'link-manager.php' );
		} );

		// Remove Links from admin bar
		add_action( 'admin_bar_menu', function () {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'new-link', 'new-content' );
		}, 300 );

		// Remove Links from favorite actions
		add_filter( 'favorite_actions', function ( $actions ) {
			unset( $actions['link-add.php'] );
			return $actions;
		} );

		// Remove Links widget
		add_action( 'widgets_init', function () {
			unregister_widget( 'WP_Widget_Links' );
		} );
	}
}