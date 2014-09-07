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
	 * A list of internal methods and their hooks configurations are.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array(
		'fix_shortcodes'     => array( 'the_content', 10, 1 ),
		'disable_quickedit'  => array( 'post_row_actions', 10, 2 ),
		'frontend_enqueue'   => array( 'wp_enqueue_scripts', 10, 0 ),
		'backend_enqueue'    => array( 'admin_enqueue_scripts', 10, 0 )
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
		if(in_array($post->post_type, $post_types)){
			unset($actions['inline hide-if-no-js']);
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
	 * Alias to Tools::enqueue() for the backend
	 *
	 * @since 1.0.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public function _backend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}
}