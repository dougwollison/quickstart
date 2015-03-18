<?php
namespace QuickStart;

/**
 * The Hooks Kit: A collection of handy auto hooking methods for various purposes.
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.0.0
 */

class Hooks extends \Smart_Plugin {
	/**
	 * A list of internal methods and their hooks configurations.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array(
		'fix_shortcodes'         => array( 'the_content', 10, 1 ),
		'do_quicktags'    	     => array( 'admin_print_footer_scripts', 10, 0 ),
		'disable_quickedit'      => array( 'post_row_actions', 10, 2 ),
		'frontend_enqueue'       => array( 'wp_enqueue_scripts', 10, 0 ),
		'backend_enqueue'        => array( 'admin_enqueue_scripts', 10, 0 ),
		'quick_frontend_enqueue' => array( 'wp_enqueue_scripts', 10, 0 ),
		'quick_backend_enqueue'  => array( 'admin_enqueue_scripts', 10, 0 ),
		'post_type_save'         => array( 'save_post', 10, 1 ),
		'post_type_save_meta'    => array( 'save_post', 10, 1 ),
		'post_type_count'        => array( 'dashboard_glance_items', 10, 1 ),
		'edit_meta_box'          => array( 'do_meta_boxes', 10, 2 ),
		'taxonomy_filter'        => array( 'restrict_manage_posts', 10, 0 ),
		'print_extra_editor'     => array( 'edit_form_after_editor', 10, 1 ),
		'add_query_var'          => array( 'query_vars', 10, 1 ),
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
	 * Handle QuickTags buttons, including settings up custom ones.
	 *
	 * Also returns the simplified csv list of buttons to register.
	 *
	 * @since 1.8.0
	 *
	 * @uses Tools::$void_elements
	 *
	 * @param array|string $buttons   The array/list of buttons.
	 * @param string       $editor_id Optional The ID of the wp_editor.
	 *
	 * @return string The csv list of buttons.
	 */
	public static function _do_quicktags( $settings, $editor_id = null ) {
		echo '<script type="text/javascript">';

		// Ensure it's in array form
		$buttons = csv_array( $settings );

		// These are the default buttons that we can ignore
		$builtin = array( 'strong', 'em', 'link', 'block', 'del', 'ins', 'img', 'ul', 'ol', 'li', 'code', 'more', 'close' );

		// Go through the buttons and auto-create them\
		foreach ( $buttons as $button ) {
			if ( ! in_array( $button, $builtin ) ) {
				// Handle void element buttons appropriately
				if ( in_array( $button, Tools::$void_elements ) ) {
					$open = "<$button />";
					$close = null;
				} else {
					$open = "<$button>";
					$close = "</$button>";
				}

				// Print out the QTags.addButton call with the arguments
				vprintf( 'QTags.addButton( "%s", "%s", "%s", "%s", "%s", "%s", %d, "%s" );', array(
					$button . '_tag', 	// id
					$button, 			// display
					$open, 				// arg1 (opening tag)
					$close, 			// arg2 (closing tag)
					null, 				// access_key
					$button . ' tag', 	// title
					1, 					// priority,
					$editor_id, 		// instance
				) );
			}
		}

		echo '</script>';
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
		if ( in_array( $post->post_type, $post_types ) ) {
			unset( $actions['inline hide-if-no-js'] );
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
	public static function _frontend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}

	/**
	 * Alias to Tools::enqueue() for the backend.
	 *
	 * @since 1.0.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public static function _backend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}

	/**
	 * Alias to Tools::quick_enqueue(), for the frontend.
	 *
	 * @since 1.8.0
	 * @uses Tools::quick_enqueue()
	 *
	 * @param string       $type  "css" or "js" for what styles/scripts respectively.
	 * @param string|array $files A path, handle, or array of paths/handles to enqueue.
	 */
	public static function _quick_frontend_enqueue( $type, $files ) {
		Tools::quick_enqueue( $type, $files );
	}

	/**
	 * Alias to Tools::quick_enqueue() for the backend.
	 *
	 * @since 1.8.0
	 * @uses Tools::quick_enqueue()
	 *
	 * @param string       $type  "css" or "js" for what styles/scripts respectively.
	 * @param string|array $files A path, handle, or array of paths/handles to enqueue.
	 */
	public static function _quick_backend_enqueue( $type, $files ) {
		Tools::quick_enqueue( $type, $files );
	}

	/**
	 * Call the save_post hook for a specific post_type.
	 *
	 * Runs passed callback after running Tools::save_post_check().
	 *
	 * @since 1.6.0
	 *
	 * @param int $post_id The ID of the post being saved (skip when saving).
	 * @param string $post_type The post_type this callback is intended for.
	 * @param callback $callback The callback to run after the check.
	 */
	protected static function _post_type_save( $post_id, $post_type, $callback ) {
		if ( ! Tools::save_post_check( $post_id, $post_type ) ) return;
		call_user_func( $callback, $post_id );
	}

	/**
	 * Save a specific meta field for a specific post_type.
	 *
	 * Saves desired field after running Tools::save_post_check().
	 *
	 * @since 1.8.0
	 *
	 * @param int    $post_id    The ID of the post being saved (skip when saving).
	 * @param string $post_type  The post_type to limit this call to.
	 * @param string $meta_key   The meta_key to save the value to.
	 * @param string $field_name Optional The name of the $_POST field to use (defaults to $meta_key).
	 */
	protected static function _post_type_save_meta( $post_id, $post_type, $meta_key, $field_name = null ) {
		if ( ! Tools::save_post_check( $post_id, $post_type ) ) return;

		if ( is_null( $field_name ) ) {
			$field_name = $meta_key;
		}

		$value = $_POST[ $field_name ];
		update_post_meta( $post_id, $meta_key, $value );
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
	protected static function _post_type_count( $elements, $post_type ) {
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
	 * Edit an existing registered meta box.
	 *
	 * This hook will fire on what should be the first round of do_meta_boxes
	 * (for the "normal" context).
	 *
	 * @since 1.8.0
	 *
	 * @param string       $post_type  The post type of the post (skip when saving).
	 * @param string       $context    The meta box context (skip when saving).
	 * @param string       $meta_box   The slug of the meta box to be edited.
	 * @param array        $changes    The properties to overwrite.
	 * @param string|array $post_types Optional The specific post type(s) under which to edit.
	 */
	public static function _edit_meta_box( $post_type, $context, $meta_box, $changes, $post_types = null ) {
		global $wp_meta_boxes;

		// We only want to run this once; we'll only do it on the "normal" context
		if ( 'normal' != $context ) {
			return;
		}

		// Ensure $post_types is in array form
		csv_array_ref( $post_types );

		foreach ( $wp_meta_boxes as $post_type => $contexts ) {
			// Reset $args each round
			$args = null;

			// Skip if this isn't post type isn't desired
			if ( $post_types && ! in_array( $post_type, $post_types ) ) {
				continue;
			}

			// Drill down through contexts and priorities to find the meta box
			foreach ( $contexts as $context => $priorities ) {
				foreach ( $priorities as $priority => $meta_boxes ) {
					// Check for a match, get arguments if so
					if ( isset( $meta_boxes[ $meta_box ] ) ) {
						$args = $meta_boxes[ $meta_box ];
						break 2;
					}
				}
			}

			// Now that we found it, modify it's arguments
			if ( $meta_box ) {
				$args = array_merge( $args, $changes );

				// Update the arguments with the modified ones
				$wp_meta_boxes[ $post_type ][ $context ][ $priority ][ $meta_box ] = $args;
			}
		}
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

			static::taxonomy_filter_options( $taxonomy, $selected, $term->term_id, $depth + 1 );
		}
	}

	/**
	 * Add a dropdown for filtering by the custom taxonomy.
	 *
	 * @since 1.8.0 New method for checking for appropriate post type; now works for attachments too.
	 * @since 1.6.0 Now supports hierarchical terms via use of taxonomy_filter_options().
	 * @since 1.0.0
	 *
	 * @param object $taxonomy The taxonomy object to build from.
	 */
	public static function _taxonomy_filter( $taxonomy ) {
		$taxonomy = get_taxonomy( $taxonomy );
		$screen = get_current_screen()->id;

		// Translate the screen id
		if ( $screen == 'upload' ) {
			// Upload is for attachments
			$screen = 'attachment';
		} else {
			// Remove edit- for post_types in case it's a post type
			$screen = preg_replace( '/^edit-/', '', $screen );
		}

		if ( in_array( $screen, $taxonomy->object_type ) ) {
			$var = $taxonomy->query_var;
			$selected = isset( $_GET[ $var ] ) ? $_GET[ $var ] : null;

			echo "<select name='$var'>";
				echo '<option value="">Show ' . $taxonomy->labels->all_items . '</option>';
				static::taxonomy_filter_options( $taxonomy->name, $selected );
			echo '</select>';
		}
	}

	/**
	 * Setup an extra wp_editor for the edit post form.
	 *
	 * @since 1.8.0
	 *
	 * @param string $name     The name of the field (by default also the meta_key).
	 * @param array  $settings Optional Any special settings such as post_type and title.
	 */
	public static function extra_editor( $name, $settings = array() ) {
		$settings = wp_parse_args( $settings, array(
			'name' => $name,
			'meta_key' => $name,
			'post_type' => 'page',
			'title' => make_legible( $name ),
		) );

		static::post_type_save_meta( $settings['post_type'], $settings['meta_key'], $settings['name'] );
		static::print_extra_editor( $settings );
	}

	/**
	 * Print an extra wp_editor to the edit post form.
	 *
	 * @since 1.8.0
	 *
	 * @see Hooks::add_extra_editor()
	 *
	 * @param object $post     The post object being edited (skip when saving).
	 * @param array  $settings Optional Any special settings such as post_type and title.
	 */
	public static function _print_extra_editor( $post, $settings = array() ) {
		$post_types = csv_array( $settings['post_type'] );
		if ( ! in_array( $post->post_type, $post_types ) ) {
			return;
		}

		// Get the value
		$value = get_post_meta( $post->ID, $settings['meta_key'], true );

		printf( '<div class="qs-editor" id="%s-editor">', $name );
			echo '<h3>' . $settings['title'] . '</h3>';
			echo Form::build_editor( $settings, $value );
		echo '</div>';
	}

	/**
	 * Register additional public query vars.
	 *
	 * @since 1.8.0
	 *
	 * @param array        $vars     The current list of query vars.
	 * @param string|array $new_vars A list of vars to add.
	 *
	 * @param return The updated list of vars.
	 */
	public static function _add_query_var( $vars, $new_vars ) {
		// Ensure the list is an array
		csv_array_ref( $new_vars );

		// Merge the arrays
		return array_merge( $vars, $new_vars );
	}
}