<?php
namespace QuickStart;

/**
 * The Tools Kit: A collection of methods for use by the Setup class (and also external use).
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.0.0
 */

class Tools {
	/**
	 * A list of accepted attributes for tag building.
	 *
	 * @since 1.5.0 Moved from Form to Tools class.
	 * @since 1.0.0
	 *
	 * @access public
	 * @var array
	 */
	public static $accepted_attrs = array( 'accesskey', 'autocomplete', 'checked', 'class', 'cols', 'disabled', 'id', 'max', 'maxlength', 'min', 'multiple', 'name', 'placeholder', 'readonly', 'required', 'rows', 'size', 'style', 'tabindex', 'title', 'type', 'value' );

	/**
	 * A list of tags that should have no content.
	 *
	 * @since 1.6.0
	 *
	 * @access public
	 * @var array
	 */
	public static $void_elements = array( 'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'menuitem', 'meta', 'param', 'source', 'track', 'wbr' );

	/**
	 * Build an HTML tag.
	 *
	 * @since 1.6.2 Added attribute escaping.
	 * @since 1.6.0 Revised handling of boolean attributes, added $void_elements.
	 * @since 1.5.0 Moved from Form to Tools class.
	 * @since 1.4.2 Updated boolean attribute handling.
	 * @since 1.0.0
	 *
	 * @param string $tag      The tag name.
	 * @param array  $atts     The tag attributes.
	 * @param string $content  Optional The tag content.
	 * @param string $accepted Optional The attribute whitelist.
	 *
	 * @return string The html of the tag.
	 */
	public static function build_tag( $tag, $atts, $content = false, $accepted = null ) {
		if ( is_null( $accepted ) ) {
			$accepted = static::$accepted_attrs;
		}

		$html = "<$tag";

		foreach ( $atts as $attr => $value ) {
			// Escape the value for attribute use
			if ( is_string( $value ) ) {
				$value = esc_attr( $value );
			} elseif ( is_array( $value ) ) {
				array_walk( $value, 'esc_attr' );
			}
			
			if ( is_numeric ( $attr ) ) {
				// Boolean attributes; method 1
				$html .= " $value";
			} else if ( in_array( $attr, $accepted ) && $attr != 'value' && is_bool( $value ) ) {
				// Boolean attributes, method 2
				$html .= $value ? " $attr" : '';
			} else {
				// Make sure it's a registerd attribute (or data- attribute)
				if ( ! in_array( $attr, $accepted ) && strpos( $attr, 'data-' ) !== 0 ) continue;

				if ( is_array( $value ) ) {
					// Implode into a space separated list
					$value = implode( ' ', $value );
				}

				$html .= " $attr=\"$value\"";
			}
		}

		// Handle closing of the tag
		if ( in_array( $tag, static::$void_elements ) ) {
			// Self closing tag
			$html .= '/>';
		} else {
			// Add content and closing tag
			$html .= ">$content</$tag>";
		}

		return $html;
	}

	/**
	 * Load the requested helper files.
	 *
	 * @param mixed $helpers A name or array of helper files to load (sans extention).
	 */
	public static function load_helpers( $helpers ) {
		csv_array_ref( $helpers );
		foreach ( $helpers as $helper ) {
			$file = QS_DIR . "/php/helpers/$helper.php";
			if ( file_exists( $file ) ){
				require_once( $file );
			}
		}
	}

	/**
	 * Actually build a meta_box, either calling the callback or running the build_fields Form method.
	 *
	 * @since 1.6.0 Added use of get_fields option.
	 * @since 1.4.0 Added use of $source parameter in Form::build_fields().
	 * @since 1.3.0 Added option of callback key instead of fields for a callback.
	 * @since 1.0.0
	 * @uses Form::build_fields()
	 *
	 * @param object $post The post object to be sent when called via add_meta_box.
	 * @param array $args The callback args to be sent when called via add_meta_box.
	 */
	public static function build_meta_box( $post, $args ) {
		// Extract $args
		$id = $args['args']['id'];
		$args = $args['args']['args'];

		// Print nonce field
		wp_nonce_field( $id, "_qsnonce-$id" );

		// Determine the callback or fields argument
		$callback = $fields = null;
		if ( isset( $args['callback'] ) ) {
			$callback = $args['callback'];
		} elseif ( is_callable( $args['fields'] ) ) {
			$callback = $args['fields'];
		} elseif ( isset( $args['fields'] ) ) {
			$fields = $args['fields'];
		} elseif ( isset( $args['get_fields'] ) && is_callable( $args['get_fields'] ) ) {
			/**
			 * Dynamically generate the fields array.
			 *
			 * @since 1.6.0
			 *
			 * @param WP_Post $post The post object.
			 * @param array   $args The original arguments for the metabox.
			 * @param string  $id   The ID of the metabox.
			 */
			$fields = call_user_func( $args['get_fields'], $post, $args, $id );
		}

		// Wrap in container for any specific targeting needed
		echo '<div class="qs-meta-box">';
			if ( $callback ) {
				/**
				 * Build the HTML of the metabox.
				 *
				 * @since 1.3.0 Use $callback from 'fields' or 'callback' arg.
				 * @since 1.0.0
				 *
				 * @param WP_Post $post The post object.
				 * @param array   $args The original arguments for the metabox
				 * @param string  $id   The ID of the metabox.
				 */
				call_user_func( $callback, $post, $args, $id );
			} elseif ( isset( $args['fields'] ) ) {
				// Build the fields
				Form::build_fields( $fields, $post, 'post', true );
			}
		echo '</div>';
	}

	/**
	 * Relabel the "post" post type.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $label Optional A string of the new label (singular) or an array of singular, plural for ms.
	 */
	public static function relabel_posts( $label = null ) {
		if ( is_array( $label ) ) {
			$singular = $plural = $menuname = $label[0];
			list( $singular, $plural, $menuname ) = fill_array( $label, 3 );
			if ( ! $plural ) $plural = pluralize( $singular );
			if ( ! $menuname ) $menuname = $plural;
		} else {
			$singular = $label;
			$plural = pluralize( $singular );
			$menuname = $plural;
		}

		/**
		 * Replace all instances off Post(s) with the new singular and plural strings.
		 *
		 * @since 1.0.0
		 *
		 * @global array $wp_post_types The registered post types array.
		 *
		 * @uses string $singular The new singular form.
		 * @uses string $plural The new plural form.
		 */
		add_action( 'init', function() use ( $singular, $plural ) {
			global $wp_post_types;
		    str_replace_in_array( array( __( 'Posts' ), __( 'Post' ) ), array( $plural, $singular ), $wp_post_types['post']->labels );
		} );

		/**
		 * Replace all instances off Post(s) with the new singular and plural strings.
		 *
		 * @since 1.0.0
		 *
		 * @global array $menu The admin menu items array.
		 * @global array $submenu The admin submenu items array.
		 *
		 * @uses string $singular The new singular form.
		 * @uses string $plural The new plural form.
		 * @uses string $menuname The new menu name.
		 */
		add_action( 'admin_menu', function() use ( $singular, $plural, $menuname ) {
			global $menu, $submenu;
		    $menu[5][0] = $menuname;
		    str_replace_in_array( array( __( 'Posts' ), __( 'Post' ) ), array( $plural, $singular ), $submenu['edit.php'] );
		} );
	}

	/**
	 * Add specified callbacks to various hooks ( good for adding a callback to multiple hooks... it could happen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $enqueues Optional An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public static function enqueue( $enqueues = null ) {
		if ( isset( $enqueues['css'] ) ) {
			//  Check if its a callback, run it and get the value from that
			if ( is_callable( $enqueues['css'] ) ) {
				$enqueues['css'] = call_user_func( $enqueues['css'] );
			}
			foreach ( (array) $enqueues['css'] as $handle => $style ) {
				if ( is_numeric( $handle ) ) {
					// Just enqueue it
					wp_enqueue_style( $style );
				} else {
					// Must be registered first
					$style = (array) $style;
					$src = $deps = $ver = $media = null;
					if ( is_assoc( $style ) ) {
						extract( $style );
					} else {
						list( $src, $deps, $ver, $media ) = fill_array( $style, 4 );
					}
					$deps = (array) $deps;
					wp_enqueue_style( $handle, $src, $deps, $ver, $media );
				}
			}
		}

		if ( isset( $enqueues['js'] ) ) {
			//  Check if its a callback, run it and get the value from that
			if ( is_callable( $enqueues['js'] ) ) {
				$enqueues['js'] = call_user_func( $enqueues['js'] );
			}
			foreach ( (array) $enqueues['js'] as $handle => $script ) {
				if ( is_numeric( $handle ) ) {
					// Just enqueue it
					wp_enqueue_script( $script );
				} else {
					// Must be registered first
					$script = (array) $script;
					$src = $deps = $ver = $in_footer = null;
					if ( is_assoc( $script ) ) {
						extract( $script );
					} else {
						list( $src, $deps, $ver, $in_footer ) = fill_array( $script, 4 );
					}
					$deps = (array) $deps;
					wp_enqueue_script( $handle, $src, $deps, $ver, $in_footer );
				}
			}
		}
	}

	/**
	 * Take care of uploading and inserting an attachment.
	 *
	 * @since 1.0.0
	 *
	 * @param array $file The desired entry in $_FILES.
	 * @param array $attachment Optional An array of data for the attachment to be written to wp_posts.
	 */
	public static function upload( $file, $attachment = array() ) {
		$file = wp_handle_upload( $file, array( 'test_for m' => false ) );

		if ( isset( $file['error'] ) ) {
			wp_die( $file['error'], __( 'Image Upload Error' ) );
		}

		$url  = $file['url'];
		$type = $file['type'];
		$file = $file['file'];
		$filename = basename( $file );

		$defaults = array(
			'post_title'     => $filename,
			'post_content'   => '',
			'post_mime_type' => $type,
			'post_status'	 => 'publish',
			'guid'           => $url,
		);

		$attachment = wp_parse_args( $attachment, $defaults );

		//  Save the data
		$attachment_id = wp_insert_attachment( $attachment, $file );
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );

		return $attachment_id;
	}

	/**
	 * Run the appropriate checks to make sure that.
	 * this save_post callback should proceed.
	 *
	 * @since 1.2.0
	 *
	 * @param int          $post_id     The ID of the post being saved.
	 * @param string|array $post_type   Optional The expected post type(s).
	 * @param string       $nonce_name  Optional the name of the nonce field to check.
	 * @param string       $nonce_value Optional the value of the nonce field to check.
	 *
	 * @return bool Wether or not to proceed.
	 */
	public static function save_post_check( $post_id, $post_type = null, $nonce_name = null, $nonce_value = null ) {
		// Load the posted post type
		$post_type_obj = get_post_type_object( $_POST['post_type'] );

		// Default post_type and nonce checks to true
		$post_type_check = $nonce_check = true;

		// If post type is provided, check it
		if ( ! is_null( $post_type ) ) {
			csv_array_ref( $post_type );
			$post_type_check = in_array( $post_type_obj->name, $post_type );
		}

		// If nonce name & value are passed, check it
		if ( ! is_null( $nonce_name ) ) {
			$nonce_check = isset( $_POST[ $nonce_name ] ) && wp_verify_nonce( $_POST[ $nonce_name ], $nonce_value );
		}

		// Check for autosave and post revisions
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			wp_is_post_revision( $post_id ) ||
			// Check post type and nonce (if provided)
			! $post_type_check || ! $nonce_check ||
			// Check for capability to edit this post
			! current_user_can( $post_type_obj->cap->edit_post ) ) {
			return false;
		}

		return true;
	}

	// =========================
	// !Hook/Callback Methods
	// =========================

	/**
	 * Add various callbacks to specified hooks.
	 *
	 * @since 1.0.0
	 *
	 * @param array $hooks An array of callbacks, keyed by hook name.
	 */
	public static function add_hooks( $hooks ) {
		foreach ( $hooks as $hook => $callbacks ) {
			foreach ( (array) $callbacks as $callback => $settings ) {
				$priority = 10;
				$arguments = 1;
				if ( is_numeric( $callback ) ) {
					$callback = $settings;
				} else {
					list( $priority, $arguments ) = fill_array( $settings, 2 );
				}
				add_filter( $hook, $callback, $priority, $arguments );
			}
		}
	}

	/**
	 * Add specified callbacks to various hooks (good for adding a callback to multiple hooks... it could happen.).
	 *
	 * @since 1.0.0
	 *
	 * @param array $callbacks An array of hooks, keyed by callback name.
	 */
	public static function add_callbacks( $callbacks ) {
		foreach ( $callbacks as $function => $hooks ) {
			if ( is_int( $function ) ) {
				$function = array_shift( $hooks );
			}
			foreach ( (array) $hooks as $hook ) {
				list( $priority, $arguments ) = fill_array( $hook, 2 );
				add_filter( $hook, $function, $priority, $arguments );
			}
		}
	}

	// =========================
	// !Shortcode Methods
	// =========================

	/**
	 * Simple div shortcode with name as class and attributes taken verbatim.
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts The array of attributes for the shortcode.
	 * @param string $content The content of the shortcode if applicable.
	 * @param string $tag The name of the shortcode being parsed.
	 * @return string $html The html of the processed shortcode.
	 */
	public static function simple_shortcode( $atts, $content, $tag ) {
		$html = '<div ';

		if ( ! isset( $atts['class'] ) ) {
			$atts['class'] = $tag;
		} else {
			$atts['class'] .= " $tag";
		}

		foreach ( $atts as $att => $val ) {
			$html .= "$att='$val'";
		}

		$content = do_shortcode( $content );
		$html .= ">$content</div>";

		return $html;
	}

	/**
	 * Setup a series of shortcodes, in tag => callback format.
	 * (specify comma separated list of tags to have them all use the same callback)
	 *
	 * @since 1.0.0
	 *
	 * @param array $shortcodes The list of tags and their callbacks.
	 */
	public static function register_shortcodes( $shortcodes ) {
		csv_array_ref( $shortcodes );
		foreach ( $shortcodes as $tags => $callback ) {
			if ( is_int( $tags ) ) {
				// No actual callback, use simple_shortcode
				$tags = $callback;
				$callback = array( __CLASS__, 'simple_shortcode' );
			}
			csv_array_ref( $tags );
			foreach ( $tags as $tag ) {
				add_shortcode( $tag, $callback );
			}
		}
	}

	// =========================
	// !Hide Methods
	// =========================

	/**
	 * Call the appropriate hide_[object] method(s).
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $objects An object name, comma separated string, or array of objects to disable.
	 */
	public static function hide( $objects ) {
		csv_array_ref( $objects );
		foreach ( $objects as $object ) {
			$method = "hide_$object";
			if ( method_exists( __CLASS__, $method ) ) {
				self::$method();
			}
		}
	}

	/**
	 * Remove Posts from menus and dashboard.
	 *
	 * @since 1.0.0
	 */
	public static function hide_posts() {
		// Remove Posts from admin menu
		add_action( 'admin_menu', function() {
			remove_menu_page( 'edit.php' );
		} );

		// Remove Posts from admin bar
		add_action( 'admin_bar_menu', function() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'new-post', 'new-content' );
		}, 300 );

		// Remove Posts from favorite actions
		add_filter( 'favorite_actions', function( $actions ) {
			unset( $actions['edit-posts.php'] );
			return $actions;
		} );

		// Remove Recent Posts widget
		add_action( 'widgets_init', function() {
			unregister_widget( 'WP_Widget_Recent_Posts' );
		} );
	}

	/**
	 * Remove Pages from menus and dashboard.
	 *
	 * @since 1.0.0
	 */
	public static function hide_pages() {
		// Remove Pages from admin menu
		add_action( 'admin_menu', function() {
			remove_menu_page( 'edit.php?post_type=page' );
		} );

		// Remove Pages from admin bar
		add_action( 'admin_bar_menu', function() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'new-page', 'new-content' );
		}, 300 );

		// Remove Pages from favorite actions
		add_filter( 'favorite_actions', function( $actions ) {
			unset( $actions['edit-posts.php?post_type=page'] );
			return $actions;
		} );

		// Remove Pages widget
		add_action( 'widgets_init', function() {
			unregister_widget( 'WP_Widget_Pages' );
		} );
	}

	/**
	 * Remove Comments from menus, dashboard, editor, etc.
	 *
	 * @since 1.0.0
	 */
	public static function hide_comments() {
		// Remove Comment support from all post_types with it
		add_action( 'init', function() {
			foreach ( get_post_types( array( 'public' => true, '_builtin' => true ) ) as $post_type ) {
				if ( post_type_supports( $post_type, 'comments' ) ) {
					remove_post_type_support( $post_type, 'comments' );
				}
			}
		} );

		// Remove edit comments and discussion options from admin menu
		add_action( 'admin_menu', function() {
			remove_menu_page( 'edit-comments.php' );
			remove_submenu_page( 'options-general.php', 'options-discussion.php' );
		} );

		// Remove Comments from admin bar
		add_action( 'admin_bar_menu', function() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'comments' );
		}, 300 );

		// Remove Comments meta box from dashboard
		add_action( 'wp_dashboard_setup', function() {
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		} );

		// Remove Comments/Trackback meta boxes from post editor
		add_action( 'admin_init', function() {
			remove_meta_box( 'trackbacksdiv',    'post', 'normal' );
			remove_meta_box( 'commentstatusdiv', 'post', 'normal' );
			remove_meta_box( 'commentsdiv',      'post', 'normal' );
			remove_meta_box( 'trackbacksdiv',    'page', 'normal' );
			remove_meta_box( 'commentstatusdiv', 'page', 'normal' );
			remove_meta_box( 'commentsdiv',      'page', 'normal' );
		} );

		// Remove Comments column from Posts/Pages editor
		$removeCommentsColumn = function( $defaults ) {
			unset( $defaults["comments"] );
			return $defaults;
		};
		add_filter( 'manage_posts_columns', $removeCommentsColumn );
		add_filter( 'manage_pages_columns', $removeCommentsColumn );

		// Remove Recent Comments widget
		add_action( 'widgets_init', function() {
			unregister_widget( 'WP_Widget_Recent_Comments' );
		} );

		// Remove Comments from favorite actions
		add_filter( 'favorite_actions', function( $actions ) {
			unset( $actions['edit-comments.php'] );
			return $actions;
		} );

		// Make comments number always return 0
		add_action( 'get_comments_number', function() {
			return 0;
		} );

		// Edit $wp_query to clear comment related data
		add_action( 'comments_template', function() {
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
	 * Remove Links from menus and dashboard.
	 *
	 * @since 1.0.0
	 */
	public static function hide_links() {
		// Remove Links from admin menu
		add_action( 'admin_menu', function() {
			remove_menu_page( 'link-manager.php' );
		} );

		// Remove Links from admin bar
		add_action( 'admin_bar_menu', function() {
			global $wp_admin_bar;
			$wp_admin_bar->remove_menu( 'new-link', 'new-content' );
		}, 300 );

		// Remove Links from favorite actions
		add_filter( 'favorite_actions', function( $actions ) {
			unset( $actions['link-add.php'] );
			return $actions;
		} );

		// Remove Links widget
		add_action( 'widgets_init', function() {
			unregister_widget( 'WP_Widget_Links' );
		} );
	}

	/**
	 * Remove the wp_head garbage.
	 *
	 * @since 1.0.0
	 */
	public static function hide_wp_head() {
		// links for adjacent posts
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );
		// category feeds
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		// post and comment feeds
		remove_action( 'wp_head', 'feed_links', 2 );
		// index link
		remove_action( 'wp_head', 'index_rel_link' );
		// previous link
		remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
		remove_action( 'wp_head', 'rel_canonical', 10, 1 );
		// EditURI link
		remove_action( 'wp_head', 'rsd_link' );
		// start link
		remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
		// windows live writer
		remove_action( 'wp_head', 'wlwmanifest_link' );
		// WP version
		remove_action( 'wp_head', 'wp_generator' );
		// links for adjacent posts
		remove_action( 'wp_head', 'wp_shortlink_wp_head', 10, 0 );

		// remove WP version from css/js
		$remove_ver = function( $src ) {
			if ( strpos( $src, 'ver=' ) ) {
				$src = remove_query_arg( 'ver', $src );
			}
			return $src;
		};
		add_filter( 'style_loader_src', $remove_ver, 9999 );
		add_filter( 'script_loader_src', $remove_ver, 9999 );
	}
}