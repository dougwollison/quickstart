<?php
namespace QuickStart;

/**
 * The Tools Kit: A collection of methods for use by the Setup class ( and also external use ).
 *
 * @package QuickStart
 * @subpackage Tools
 * @since 1.0.0
 */

class Tools{
	/**
	 * Actually build a meta_box, either calling the callback or running the build_fields Form method.
	 *
	 * @since 1.0.0
	 * @uses Form::build_fields()
	 *
	 * @param object $post The post object to be sent when called via add_meta_box
	 * @param array $args The callback args to be sent when called via add_meta_box
	 */
	public static function build_meta_box( $post, $args ) {
		// Extract $args
		$id = $args['args']['id'];
		$args = $args['args']['args'];

		// Print nonce field
		wp_nonce_field( $id, "_qsnonce-$id" );

		// Wrap in container for any specific targeting needed
		echo '<div class="quickstart-meta-box">';
			if ( is_callable( $args['fields'] ) ) {
				// Call the function, passing the post, the metabox args, and the id if it's needed
				call_user_func( $args['fields'], $post, $args, $id );
			} else {
				// Build the fields
				Form::build_fields( $args['fields'], $post, true );
			}
		echo '</div>';
	}

	/**
	 * Relabel the "post" post type
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $label A string of the new label (singular) or an array of singular, plural for ms.
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
		add_action( 'init', function () use ( $singular, $plural ) {
			global $wp_post_types;
		    str_replace_in_array( array( 'Posts', 'Post' ), array( $plural, $singular ), $wp_post_types['post']->labels );
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
		add_action( 'admin_menu', function () use ( $singular, $plural, $menuname ) {
			global $menu, $submenu;
		    $menu[5][0] = $menuname;
		    str_replace_in_array( array( 'Posts', 'Post' ), array( $plural, $singular ), $submenu['edit.php'] );
		} );
	}

	/**
	 * Add specified callbacks to various hooks ( good for adding a callback to multiple hooks... it could happen.
	 *
	 * @since 1.0.0
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type ( js/css )
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
	 * Register custom styles for MCE
	 *
	 * @since 1.0.0
	 *
	 * @param array $styles An array of styles to register
	 */
	public static function register_mce_styles( $styles ) {
		add_filter( 'mce_buttons_2', function ( $buttons ) {
			if ( ! in_array( 'styleselect', $buttons ) ) {
				array_splice( $buttons, 1, 0, 'styleselect' );
			}
			return $buttons;
		} );

		add_filter( 'tiny_mce_befor e_init', function ( $settings ) use ( $styles ) {
			$style_formats = array();

			if ( isset( $settings['style_formats'] ) ) {
				$style_formats = json_decode( $settings['style_formats'] );
			}

			$style_formats = array_merge( $style_formats, $styles );

			$settings['style_formats'] = json_encode( $style_formats );

			return $settings;
		} );
	}

	/**
	 * Enable existing buttons for MCE
	 *
	 * This simply adds them, if they aren't registered, nothing happens.
	 *
	 * @since 1.0.0
	 *
	 * @param array|string $_buttons A list of buttons to enable
	 */
	public static function enable_mce_buttons( $btns ) {
		// comma split if string
		if ( is_string( $btns ) ) {
			$btns = preg_split( '/\s*,\s*/', $btns );
		}

		add_filter( 'mce_buttons', function ( $buttons ) use ( $btns ) {
			$buttons = array_merge( $buttons, $btns );
			return $buttons;
		} );
	}

	/**
	 * Add various callbacks to specified hooks
	 *
	 * @since 1.0.0
	 *
	 * @param array $hooks An array of callbacks, keyed by hook name
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
	 * Add specified callbacks to various hooks ( good for adding a callback to multiple hooks... it could happen. )
	 *
	 * @since 1.0.0
	 *
	 * @param array $callbacks An array of hooks, keyed by callback name
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

	/**
	 * Take care of uploading and inserting an attachment
	 *
	 * @since 1.0.0
	 *
	 * @param array $file The desired entry in $_FILES
	 * @param array $attachment Optional An array of data for the attachment to be written to wp_posts
	 */
	public static function upload( $file, $attachment = array() ) {
		$file = wp_handle_upload( $file, array( 'test_for m' => false ));

		if ( isset( $file['error'] ) )
			wp_die( $file['error'], 'Image Upload Error' );

		$url = $file['url'];
		$type = $file['type'];
		$file = $file['file'];
		$filename = basename( $file );

		$defaults = array(
			'post_title'     => $filename,
			'post_content'   => '',
			'post_mime_type' => $type,
			'post_status'	 => 'publish',
			'guid'           => $url
		);

		$attachment = wp_parse_args( $attachment, $defaults );

		//  Save the data
		$attachment_id = wp_insert_attachment( $attachment, $file );
		$attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
		wp_update_attachment_metadata( $attachment_id, $attachment_data );

		return $attachment_id;
	}

	/**
	 * Setup filter to unwrap shortcodes for proper processing
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $tags The list of block level shortcode tags that should be unwrapped, either and array or comma/space separated list
	 */
	public static function fix_shortcodes( $tags ) {
		if ( ! is_array( $tags ) ) {
			$tags = csv_array( $tags );
		}

		$tags = implode( '|', $tags );
		add_filter( 'the_content', function ( $content ) use ( $tags ) {
			// Strip closing p tags and opening p tags from beginning/end of string
			$content = preg_replace( '#^\s*( ?:</p> )\s*( [\s\S]+ )\s*( ?:<p.*?> )\s*$#', '$1', $content );
			// Unwrap tags
			$content = preg_replace( "#( ?:<p.*?> )?( \[/?( ?:$tags ).*\] )( ?:</p> )?#", '$1', $content );

			return $content;
		} );
	}

	/**
	 * Simple div shortcode with name as class and attributes taken verbatim
	 *
	 * @since 1.0.0
	 *
	 * @param array $atts The array of attributes for the shortcode
	 * @param string $content The content of the shortcode if applicable
	 * @param string $tag The name of the shortcode being parsed
	 * @return string $html The html of the processed shortcode
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
	 * Setup a series of shortcodes, in tag => callback format
	 * ( specify comma separated list of tags to have them all use the same callback )
	 *
	 * @since 1.0.0
	 *
	 * @param array $shortcodes The list of tags and their callbacks
	 */
	public static function register_shortcodes( $shortcodes ) {
		$shortcodes = csv_array( $shortcodes );
		foreach ( $shortcodes as $tags => $callback ) {
			if ( is_int( $tags ) ) {
				$tags = $callback;
				$callback = array( __CLASS__, 'simple_shortcode' );
			}
			$tags = csv_array( $tags );
			foreach ( $tags as $tag ) {
				add_shortcode( $tag, $callback );
			}
		}
	}
}