<?php
namespace QuickStart;

/**
 * The Tools Kit: A collection of methods for use by the Setup class (and also external use).
 *
 * @package QuickStart
 * @subpackage Tools
 *
 * @since 1.9.0 Converted to Smart_Plugin based class and merged with Hooks class.
 * @since 1.0.0
 */

class Tools extends \Smart_Plugin {
	/**
	 * A list of internal methods and their hooks configurations.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $method_hooks = array(
		'relabel_posts_object'     => array( 'init', 10, 0 ),
		'relabel_posts_menu'       => array( 'admin_menu', 10, 0 ),
		'reorder_admin_menu'       => array( 'admin_menu', 11, 0 ),
		'fix_shortcodes'           => array( 'the_content', 10, 1 ),
		'do_quicktags'             => array( 'admin_print_footer_scripts', 10, 0 ),
		'disable_quickedit'        => array( 'post_row_actions', 10, 2 ),
		'frontend_enqueue'         => array( 'wp_enqueue_scripts', 10, 0 ),
		'backend_enqueue'          => array( 'admin_enqueue_scripts', 10, 0 ),
		'quick_frontend_enqueue'   => array( 'wp_enqueue_scripts', 10, 0 ),
		'quick_backend_enqueue'    => array( 'admin_enqueue_scripts', 10, 0 ),
		'post_type_save'           => array( 'save_post', 10, 1 ),
		'post_type_save_meta'      => array( 'save_post', 10, 1 ),
		'post_type_save_field'     => array( 'save_post', 10, 1 ),
		'post_type_count'          => array( 'dashboard_glance_items', 10, 1 ),
		'edit_meta_box'            => array( 'do_meta_boxes', 10, 2 ),
		'taxonomy_filter'          => array( 'restrict_manage_posts', 10, 0 ),
		'print_extra_editor'       => array( 'edit_form_after_editor', 10, 1 ),
		'print_extra_editor_above' => array( 'edit_form_after_title', 10, 1 ),
		'print_extra_editor_below' => array( 'edit_form_after_editor', 10, 1 ),
		'add_query_var'            => array( 'query_vars', 10, 1 ),
		'add_rewrites'             => array( 'init', 10, 0 ),
		'register_widget'          => array( 'widgets_init', 10, 0 ),
	);

	/**
	 * A list of post table fields that are prefixed with post_
	 *
	 * @since 1.10.0
	 *
	 * @access public
	 * @var array
	 */
	public static $prefixed_post_fields = array( 'author', 'content_filtered', 'content', 'date_gmt', 'date', 'excerpt', 'mime_type', 'modified_gmt', 'modified', 'name', 'parent', 'password', 'status', 'title', 'type' );

	/**
	 * A list of accepted attributes for tag building.
	 *
	 * @since 1.10.0 Removed type from list.
	 * @since 1.5.0  Moved from Form to Tools class.
	 * @since 1.0.0
	 *
	 * @access public
	 * @var array
	 */
	public static $accepted_attrs = array( 'accesskey', 'autocomplete', 'checked', 'class', 'cols', 'disabled', 'id', 'max', 'maxlength', 'min', 'multiple', 'name', 'placeholder', 'readonly', 'required', 'rows', 'size', 'step', 'style', 'tabindex', 'title', 'value' );

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
	 * A list of built-in admin pages and their slugs.
	 *
	 * @since 1.11.0
	 *
	 * @access public
	 * @var array
	 */
	protected static $admin_menu_slugs = array(
		'dashboard'  => 'index.php',
		'posts'      => 'edit.php',
		'media'      => 'upload.php',
		'links'      => 'link-manager.php',
		'pages'      => 'edit.php?post_type=page',
		'comments'   => 'edit-comments.php',
		'appearance' => 'themes.php',
		'plugins'    => 'plugins.php',
		'users'      => 'users.php',
		'tools'      => 'tools.php',
		'settings'   => 'options-general.php',
	);

	// =========================
	// !Basic Tools
	// =========================

	/**
	 * Build an HTML tag.
	 *
	 * @since 1.11.0 Now accepts an empty array for $accepted so it doesn't whitelist attributes.
	 * @since 1.10.0 Now adding 'type' to $accepted when doing an INPUT tag.
	 * @since 1.7.0  Further refined attribute filtering and escaping.
	 * @since 1.6.2  Added attribute escaping.
	 * @since 1.6.0  Revised handling of boolean attributes, added $void_elements.
	 * @since 1.5.0  Moved from Form to Tools class.
	 * @since 1.4.2  Updated boolean attribute handling.
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

		// Add 'type' to accepted attributes list if INPUT (provided it's an array of other attributes)
		if ( $tag == 'input' && $accepted && is_array( $accepted ) ) {
			$accepted[] = 'type';
		}

		$html = "<$tag";

		foreach ( $atts as $attr => $value ) {
			// Convert numerically added boolean attributes
			if ( is_numeric( $attr ) ) {
				$attr = $value;
				$value = true;
			}

			// Make sure it's a registerd attribute (or data- attribute)
			if ( $accepted && ! in_array( $attr, $accepted ) && strpos( $attr, 'data-' ) !== 0 ) {
				continue;
			}

			if ( 'value' != $attr && is_bool( $value ) ) {
				// Boolean attributes (e.g. checked, selected)
				$html .= $value ? " $attr" : '';
			} else {
				// Normal attribute
				if ( is_array( $value ) ) {
					// Implode into a space separated list
					$value = implode( ' ', $value );
				}

				// Escape the value for attribute use
				$value = esc_attr( $value );

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
	 * @since 1.13.0 Added entry for Unpublish helper.
	 * @since 1.11.0 Added entries for Sections and Term Meta helpers.
	 * @since 1.10.0 Added option to load all helpers, also sanitize helper name.
	 * @since 1.7.1  Added use of constants to flag which helpers have been loaded.
	 * @since 1.0.0
	 *
	 * @param mixed $helpers A name or array of helper files to load (sans extention).
	 */
	public static function load_helpers( $helpers ) {
		// Check if ALL helpers are requested
		if ( $helpers == 'all' ) {
			$helpers = array(
				'attachment',
				'family',
				'index',
				'media_manager',
				'post_chunks',
				'post_field',
				'post_meta',
				'sections',
				'teaser',
				'term_meta',
				'unpublish',
				'walkers',
				'wpedit',
			);
		}

		csv_array_ref( $helpers );
		foreach ( $helpers as $helper ) {
			$constant = 'QS_LOADED_' . strtoupper( sanitize_title( $helper ) );
			if ( defined( $constant ) ) {
				continue;
			}
			$file = QS_DIR . "/inc/helpers/$helper.php";
			if ( file_exists( $file ) ){
				define( $constant, true );
				require_once( $file );
			}
		}
	}

	/**
	 * Call the appropriate hide_[object] method(s).
	 *
	 * @since 1.10.0 Hide methods now separate helpers, reworked handling to match.
	 * @since 1.0.0
	 *
	 * @param mixed $objects An object name, comma separated string, or array of objects to disable.
	 */
	public static function hide( $objects ) {
		csv_array_ref( $objects );
		$helpers = array();
		foreach ( $objects as $object ) {
			$helpers[] = "hide/$object";
		}
		static::load_helpers( $helpers );
	}

	/**
	 * Take care of uploading and inserting an attachment.
	 *
	 * @since 1.11.0 Added detection/loading of wp_handle_upload().
	 * @since 1.0.0
	 *
	 * @param array $file The desired entry in $_FILES.
	 * @param array $attachment Optional An array of data for the attachment to be written to wp_posts.
	 */
	public static function upload( $file, $attachment = array() ) {
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
		}

		$file = wp_handle_upload( $file, array( 'test_form' => false ) );

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
	 * Check if a post field name needs to be prefixed with post_
	 *
	 * @since 1.10.0
	 *
	 * @param string $field The name of the field to check/prefix.
	 *
	 * @return string The possibly prefixed field.
	 */
	public static function maybe_prefix_post_field( $field ) {
		// Check if the $field doesn't start with post_ but should; prefix if so
		if ( strpos( $field, 'post_' ) !== 0 && in_array( $field, static::$prefixed_post_fields ) ) {
			$field = "post_$field";
		}

		return $field;
	}

	/**
	 * Run the appropriate checks to make sure that.
	 * this save_post callback should proceed.
	 *
	 * @since 1.12.2 Changed to use $_REQUEST instead of $_POST, also pass $post_id to current_user_can().
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
		// Abort if no post type is detected
		if ( ! isset( $_REQUEST['post_type'] ) ) {
			return false;
		}

		// Load the posted post type
		$post_type_obj = get_post_type_object( $_REQUEST['post_type'] );

		// Default post_type and nonce checks to true
		$post_type_check = $nonce_check = true;

		// If post type is provided, check it
		if ( ! is_null( $post_type ) ) {
			csv_array_ref( $post_type );
			$post_type_check = in_array( $post_type_obj->name, $post_type );
		}

		// If nonce name & value are passed, check it
		if ( ! is_null( $nonce_name ) ) {
			$nonce_check = isset( $_REQUEST[ $nonce_name ] ) && wp_verify_nonce( $_REQUEST[ $nonce_name ], $nonce_value );
		}

		// Check for autosave and post revisions
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
			wp_is_post_revision( $post_id ) ||
			// Check post type and nonce (if provided)
			! $post_type_check || ! $nonce_check ||
			// Check for capability to edit this post
			! current_user_can( $post_type_obj->cap->edit_post, $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Print the fields via Form or custom callback.
	 *
	 * Determines the fields array or callback based on the arguments.
	 *
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand().
	 * @since 1.10.0
	 *
	 * @param string $context The context (meta_box, setting, etc).
	 * @param string $field   The name/ID of the field/fieldset.
	 * @param array  $args    The original arguments used.
	 * @param mixed  $data    The data to use for the fields.
	 * @param string $source  The source type of the data.
	 * @param bool   $wrap    The default $wrap argument for build_fields.
	 */
	protected static function do_fields_or_callback( $context, $id, $args, $data, $source, $wrap ) {
		$fields = $callback = null;
		if ( isset( $args['callback'] ) && is_callable( $args['callback'] ) ) {
			$callback = $args['callback'];
		} elseif ( isset( $args['fields'] ) ) {
			if ( is_callable( $args['fields'] ) ) {
				$callback = $args['fields'];
			} else {
				$fields = $args['fields'];
			}
		} elseif ( isset( $args['field'] ) ) {
			if ( is_callable( $args['field'] ) ) {
				$callback = $args['field'];
			} else {
				$fields = $args['field'];
			}
		} elseif ( isset( $args['get_fields'] ) && is_callable( $args['get_fields'] ) ) {
			/**
			 * Dynamically generate the fields array.
			 *
			 * @since 1.10.0
			 *
			 * @param WP_Post $post   The post object.
			 * @param array   $args   The original arguments for the meta box.
			 * @param string  $id     The ID of the meta box.
			 * @param string  $source The source type for the data.
			 */
			$fields = call_user_func( $args['get_fields'], $data, $args, $id, $source );
		} elseif ( is_callable( $args ) ) {
			$callback = $args;
		} else {
			$fields = array( $id => $args );
		}

		if ( $callback ) {
			/**
			 * Build the HTML of the metabox.
			 *
			 * @since 1.10.0
			 *
			 * @param mixed  $data   The data for the fields.
			 * @param array  $args   The arguments for the fields.
			 * @param string $id     The id of the field(set).
			 * @param string $source The source type of the data.
			 */
			call_user_func( $callback, $data, $args, $id, $source );
		} elseif ( $fields ) {
			// Handle any shorthand in the fields
			handle_shorthand( 'field', $fields );

			// First, handle any special processing of the fields
			foreach ( $fields as $field => &$settings ) {
				if ( $context == 'meta_box' && isset( $settings['type'] ) ) {
					// Special conditions for meta boxes
					switch ( $settings['type'] ) {
						case 'editor':
							// Meta boxes can't have tinyce-enabled editors; they're buggy
							$settings['tinymce'] = false;
							break;
					}
				}
			}

			// Build the fields
			Form::build_fields( $fields, $data, $source, true, $wrap );
		}
	}

	/**
	 * Actually build a meta_box, either calling the callback or running the build_fields Form method.
	 *
	 * @since 1.10.0 Moved bulk of logic to do_fields_or_callback().
	 * @since 1.8.0  Fixed callback checking to check callback, fields AND field values.
	 *               Also added preprocessing of fields for meta box specific purposes.
	 * @since 1.6.0  Added use of get_fields option.
	 * @since 1.4.0  Added use of $source parameter in Form::build_fields().
	 * @since 1.3.0  Added option of callback key instead of fields for a callback.
	 * @since 1.0.0
	 * @uses Form::build_fields()
	 *
	 * @param object $post The post object to be sent when called via add_meta_box.
	 * @param array  $args The settings for the metabox, including callback args.
	 */
	public static function build_meta_box( $post, $args ) {
		// Extract $args depending on structure
		if ( isset( $args['args'] ) ) {
			// Passed via add_meta_box callback
			$id = $args['args']['id'];
			$args = $args['args']['args'];
		} else {
			// Passed manually
			$id = $args['id'];
		}

		// Print nonce field
		wp_nonce_field( $id, "_qsnonce-$id" );

		// Wrap in container for any specific targeting needed
		echo '<div class="qs-meta-box">';
			static::do_fields_or_callback( 'meta_box', $id, $args, $post, 'post', true );
		echo '</div>';
	}

	/**
	 * Build a settings fieldset, either calling the callback of running the build_fields Form method.
	 *
	 * @since 1.10.0 Reworked $args, Moved bulk of logic to do_fields_or_callback().
	 * @since 1.8.0
	 * @uses Form::build_fields()
	 *
	 * @param array $args An arguments list containting the setting name and fields array/callback.
	 */
	public static function build_settings_field( $args ) {
		// Extract $args
		$field_id = $args['field_id'];
		$args = $args['args'];

		// Wrap in container for any specific targeting needed
		echo '<div class="qs-settings-field" id="' . $field_id . '-settings-field">';
			static::do_fields_or_callback( 'field_row', $field_id, $args, null, 'option', false );
		echo '</div>';
	}

	/**
	 * Build a field row for a form table.
	 *
	 * @since 1.10.0
	 *
	 * @param string $field  The name/id of the field.
	 * @param array  $args   The arguments for the field.
	 * @param array  $data   The data to pass to Form::build_field().
	 * @param array  $source The source to pass to Form::build_field().
	 */
	public static function build_field_row( $field, $args, $data, $source ) {
		$default_args = array(
			'id'    => 'qs_field_' . Form::make_id( $field ),
			'name'  => $field,
			'title' => Form::make_label( $field ),
		);
		$args = wp_parse_args( $args, $default_args );

		echo '<tr class="form-field qs-form-field-row" id="' . $source . '-' . $field . '-wrap">';
			echo '<th scope="row">';
				// Print the row title as needed
				if ( isset( $args['title_not_label'] ) && $args['title_not_label'] ) {
					// Print just the title
					echo $args['title'];
				} else {
					// Print the title as a label
					printf( '<label for="%s">%s</label>', $args['id'], $args['title'] );
				}
			echo '</th>';
			echo '<td>';
				static::do_fields_or_callback( 'field_row', $field, $args, $data, $source, false );
			echo '</td>';
		echo '</tr>';
	}

	/**
	 * Setup an extra wp_editor for the edit post form.
	 *
	 * @since 1.10.0 Added support for post_field and location.
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
			'location' => 'below',
		) );

		// Determine and setup the necessary save hook, based on settings
		if ( isset( $settings['post_field'] ) ) {
			static::post_type_save_field( $settings['post_type'], $settings['post_field'], $settings['name'] );
		} else {
			static::post_type_save_meta( $settings['post_type'], $settings['meta_key'], $settings['name'] );
		}

		// Determine and call the necessary method, based on location
		$method = 'print_extra_editor_' . $settings['location'];
		static::$method( $settings );
	}

	/**
	 * Geocode an address using the Google Geocode JSON API.
	 *
	 * @since 1.11.0 Added use
	 * @since 1.10.0
	 *
	 * @param string $address The address string to geocode.
	 * @param string $api_key Optional The Google API key to use in the request.
	 *
	 * @return array The gecoded address; latitude and longitude.
	 */
	public static function geocode_address( $address = null, $api_key = null ) {
		// Get the API key if not passed.
		if ( is_null( $api_key ) ) {
			// Check if GOOGLE_API_SERVER_KEY is defined;
			$api_key = defined( 'GOOGLE_API_SERVER_KEY' ) ? GOOGLE_API_SERVER_KEY : null;

			/**
			 * Filter the API key for geocoding requests.
			 *
			 * Useful if you have it defined under a different constant
			 * or want to use your own separate key.
			 *
			 * @since 1.11.0
			 *
			 * @param string $api_key The API key to filter.
			 */
			$api_key = apply_filters( 'qs_helper_geocode_api_key', $api_key );
		}

		// Build the URL to query
		$url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query( array(
			'address' => str_replace( "\n", ' ', $address ),
			'key' => $api_key,
		) );

		// Get the JSON data, and return the first result's location if successful
		if ( ( $result = file_get_contents( $url ) )
		&& ( $result = json_decode( $result, true ) )
		&& 'OK' == $result['status'] ) {
			return $result['results'][0]['geometry']['location'];
		}

		return false;
	}

	// =========================
	// !Style/Script Enqueue Methods
	// =========================

	/**
	 * Helper function for static::enqueue()
	 *
	 * @since 1.12.2 Added mtime for $ver and CSV handling of $deps
	 * @since 1.9.0  Updated argument handling to use.
	 * @since 1.8.0
	 *
	 * @param mixed  $enqueues  The enqueues to handle.
	 * @param string $function  The function to call.
	 * @param string $option_var The name of the 5th enqueue argument (css = media, js = in_footer).
	 */
	protected static function do_enqueues( $enqueues, $function, $option_var ) {
		//  Check if its a callback, run it and get the value from that
		if ( is_callable( $enqueues ) ) {
			$enqueues = call_user_func( $enqueues );
		}

		// Run through the enqueues and hand them
		foreach ( (array) $enqueues as $handle => $args ) {
			if ( is_numeric( $handle ) ) {
				// Just enqueue it
				call_user_func( $function, $args );
			} else {
				// Must be registered first
				$args = (array) $args;

				// Default values of the args
				$src = $deps = $ver = $option = $$option_var = null;

				extract( get_array_values( $args, 'src', 'deps', 'ver', $option_var ) );
				$option = $$option_var;

				// If a condition callback was passed, test it and skip if it fails
				if ( isset( $args['condition'] ) && is_callable( $args['condition'] ) ) {
					/**
					 * Test if the current style should be enqueued.
					 *
					 * @since 1.8.0
					 *
					 * @param array $style The style settings.
					 *
					 * @return bool Wether or not to continue enqueuing.
					 */
					$result = call_user_func( $args['condition'], $args );
					if ( ! $result ) continue;
				}

				// Ensure $deps is an array
				csv_array_ref( $deps );

				// Check if version is supposed to be a timestamp
				if ( $ver == 'mtime' ) {
					$ver = time();
					$local = str_replace( get_option( 'siteurl' ), $_SERVER['DOCUMENT_ROOT'], $src );
					if ( file_exists( $local ) ) {
						$ver = filemtime( $local );
					}
				}

				// Enqueue it
				call_user_func( $function, $handle, $src, $deps, $ver, $option );
			}
		}
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @since 1.8.0 Moved shared logic to do_enqueues internal method.
	 *              This also adds conditional style/script support.
	 * @since 1.0.0
	 *
	 * @param array $enqueues Optional An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public static function enqueue( array $enqueues = array() ) {
		if ( isset( $enqueues['css'] ) ) {
			static::do_enqueues( $enqueues['css'], 'wp_enqueue_style', 'media' );
		}

		if ( isset( $enqueues['js'] ) ) {
			static::do_enqueues( $enqueues['js'], 'wp_enqueue_script', 'in_footer' );
		}
	}

	/**
	 * A shortcut for registering/enqueueing styles and scripts.
	 *
	 * This method is simpler but allows for no dependency listing,
	 * footer placement or other options. You can of course supply
	 * dependencies by listing their handles before your own files.
	 *
	 * @since 1.8.0
	 *
	 * @param string       $type  "css" or "js" for what styles/scripts respectively.
	 * @param string|array $files A path, handle, or array of paths/handles to enqueue.
	 */
	public static function quick_enqueue( $type, $files ) {
		$files = (array) $files;

		// Determin which function to use based on $type
		$func = 'css' == $type ? 'wp_enqueue_style' : 'wp_enqueue_script';

		// The regex to look for is-file detection
		$match = 'css' == $type ? '/\.css$/' : '/\.js$/';

		foreach ( $files as $file ) {
			// If it looks like a file, enqueue with generated $handle and $src
			if ( preg_match( $match, $file ) ) {
				$handle = sanitize_title( basename( $file ) );
				$args = array( $handle, $file );
			} else {
				// Assume pre-registered style/script
				$args = array( $file );
			}

			call_user_func_array( $func, $args );
		}
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
					list( $priority, $arguments ) = array_pad( $settings, 2, null );
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
				list( $priority, $arguments ) = array_pad( $hook, 2, null );
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
	// !Self-Hooking Tools
	// =========================

	/**
	 * Relabel the "post" post type.
	 *
	 * @since 1.9.2 Fixed method names.
	 * @since 1.9.0 Revised to use self-hooking methods instead of aononymous callbacks.
	 * 				Also reworked acceptance of label values.
	 * @since 1.0.0
	 *
	 * @params mixed $labels... Strings or an array of strings to use for singular/plural form and menu name.
	 */
	public static function relabel_posts( $labels ) {
		// Make sure labels is an array; use arguments list if not
		$args = func_get_args();
		if ( ! is_array( $labels ) ) {
			$labels = $args;
		}

		$singular = $plural = $menuname = null;

		// Get the labels
		extract( get_array_values( $labels, 'singular', 'plural', 'menuname' ) );

		// Figure out the plural form and menu name if not provided
		if ( ! $plural ) {
			$plural = pluralize( $singular );
		}
		if ( ! $menuname ) {
			$menuname = $plural;
		}

		// Update the post type directory
		static::relabel_posts_object( $singular, $plural );

		// Update the menus
		static::relabel_posts_menu( $singular, $plural, $menuname );
	}

	/**
	 * Used by relabel_posts() to update the $wp_post_types array.
	 *
	 * @since 1.9.0
	 *
	 * @global array $wp_post_types The registered post types array.
	 *
	 * @param string $singular The singular form to use in the replacement.
	 * @param string $plural   The plural form to use in the replacement.
	 */
	protected static function _relabel_posts_object( $singular, $plural ) {
		global $wp_post_types;

		str_replace_in_array(
			array( __( 'Posts' ), __( 'Post' ) ),
			array( $plural, $singular ),
			$wp_post_types['post']->labels
		);
	}

	/**
	 * Used by relabel_posts() to update the menus.
	 *
	 * @since 1.9.0
	 *
	 * @global array $menu The admin menu items array.
	 * @global array $submenu The admin submenu items array.
	 *
	 * @param string $singular The singular form to use in the replacement.
	 * @param string $plural   The plural form to use in the replacement.
	 * @param string $menuname The new menu name to use.
	 */
	protected static function _relabel_posts_menu( $singular, $plural, $menuname ) {
		global $menu, $submenu;

		$menu[5][0] = $menuname;
		str_replace_in_array(
			array( __( 'Posts' ), __( 'Post' ) ),
			array( $plural, $singular ),
			$submenu['edit.php']
		);
	}

	/**
	 * Find the corresponding item in the admin menu array.
	 *
	 * Looks in the $menu_slug entry (#3), automatically handling post types
	 * and other keywords to figure out what the slug actually is.
	 *
	 * @since 1.11.0
	 *
	 * @global array $menu The admin menu items array.
	 *
	 * @param string $target The item to search for.
	 *
	 * @return int $position The position of the item found.
	 */
	protected static function find_menu_item( $target ) {
		global $menu;

		// Convert into real menu slug if needed
		if ( post_type_exists( $target ) ) {
			// Post type, convert to edit.php URL
			if ( $target == 'post' ) {
				$target = 'edit.php';
			} else {
				$target = 'edit.php?post_type=' . $target;
			}
		} elseif ( isset( self::$admin_menu_slugs[ $target ] ) ) {
			// Built-in page, lookup real slug
			$target = self::$admin_menu_slugs[ $target ];
		}

		// Now go through the menu and return the first match
		foreach ( $menu as $position => $page ) {
			if ( $page[2] == $target ) {
				return $position;
			}
		}

		return false;
	}

	/**
	 * Reorder the admin menu withe the specified items first.
	 *
	 * @since 1.12.0 Fixed handling of adding remaining items.
	 * @since 1.11.0
	 *
	 * @global array $menu The admin menu items array.
	 *
	 * @param array  $items   The desired order of the items.
	 * @param string $section Optional The section your reordering (all, objects, utilities)
	 */
	protected static function _reorder_admin_menu( $items, $section = 'all' ) {
		global $menu;

		switch ( $section ) {
			case 'objects':
				$start = 5;
				$end = 58;
				break;

			case 'utilities':
				$start = 60;
				$end = 98;
				break;

			default:
				$start = 0;
				$end = end( array_keys( $menu ) );
		}

		// Storage for new and old items
		$new_menu = array();
		$old_menu = $menu;

		// Start by adding the items that preceed the block being edited
		for ( $i = 0; $i < $start; $i++ ) {
			if ( isset( $menu[ $i ] ) ){
				$new_menu[ $i ] = $menu[ $i ];

				// Remove from $old_menu
				unset( $old_menu[ $i ] );
			}
		}

		// Now add the specified items in order
		$n = $start;
		$is_assoc = is_assoc( $items );
		foreach ( $items as $order => $item ) {
			if ( $is_assoc ) {
				$n = $order;
			}

			if ( $position = self::find_menu_item( $item ) ) {
				$new_menu[ $n ] = $menu[ $position ];

				// Remove from $old_menu
				unset( $old_menu[ $position ] );
			} elseif ( $item == '-' ) {
				// Add separator
				$new_menu[ $n ] = array(
					'',
					'read',
					'separator' . $n,
					'',
					'wp-menu-separator'
				);
			}

			$n++;
		}

		// Add any remaining items
		$n = $end;
		foreach ( $old_menu as $position => $page ) {
			if ( $position < $end ) {
				$position = $n;
			}
			$new_menu[ $position ] = $page;
			$n++;
		}

		// Replace $menu with $new_menu
		$menu = $new_menu;
	}

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
	 * @uses static::$void_elements
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
				if ( in_array( $button, static::$void_elements ) ) {
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
	 * Alias to static::enqueue(), for the frontend.
	 *
	 * @since 1.0.0
	 * @uses static::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public static function _frontend_enqueue( $enqueues ) {
		static::enqueue( $enqueues );
	}

	/**
	 * Alias to static::enqueue() for the backend.
	 *
	 * @since 1.0.0
	 * @uses static::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css).
	 */
	public static function _backend_enqueue( $enqueues ) {
		static::enqueue( $enqueues );
	}

	/**
	 * Alias to static::quick_enqueue(), for the frontend.
	 *
	 * @since 1.8.0
	 * @uses static::quick_enqueue()
	 *
	 * @param string       $type  "css" or "js" for what styles/scripts respectively.
	 * @param string|array $files A path, handle, or array of paths/handles to enqueue.
	 */
	public static function _quick_frontend_enqueue( $type, $files ) {
		static::quick_enqueue( $type, $files );
	}

	/**
	 * Alias to static::quick_enqueue() for the backend.
	 *
	 * @since 1.8.0
	 * @uses static::quick_enqueue()
	 *
	 * @param string       $type  "css" or "js" for what styles/scripts respectively.
	 * @param string|array $files A path, handle, or array of paths/handles to enqueue.
	 */
	public static function _quick_backend_enqueue( $type, $files ) {
		static::quick_enqueue( $type, $files );
	}

	/**
	 * Call the save_post hook for a specific post_type.
	 *
	 * Runs passed callback after running static::save_post_check().
	 *
	 * @since 1.6.0
	 *
	 * @param int $post_id The ID of the post being saved (skip when saving).
	 * @param string $post_type The post_type this callback is intended for.
	 * @param callback $callback The callback to run after the check.
	 */
	protected static function _post_type_save( $post_id, $post_type, $callback ) {
		if ( ! static::save_post_check( $post_id, $post_type ) ) return;
		call_user_func( $callback, $post_id );
	}

	/**
	 * Save a specific meta field for a specific post_type.
	 *
	 * Saves desired field after running static::save_post_check().
	 *
	 * @since 1.13.0 Changed to use $_REQUEST instead of $_POST, also check if field is even present.
	 * @since 1.8.0
	 *
	 * @param int    $post_id    The ID of the post being saved (skip when saving).
	 * @param string $post_type  The post_type to limit this call to.
	 * @param string $meta_key   The meta_key to save the value to.
	 * @param string $field_name Optional The name of the $_POST field to use (defaults to $meta_key).
	 */
	protected static function _post_type_save_meta( $post_id, $post_type, $meta_key, $field_name = null ) {
		if ( ! static::save_post_check( $post_id, $post_type ) ) return;

		if ( is_null( $field_name ) ) {
			$field_name = $meta_key;
		}

		if ( ! isset( $_REQUEST[ $field_name ] ) ) {
			return;
		}

		$value = $_REQUEST[ $field_name ];
		update_post_meta( $post_id, $meta_key, $value );
	}

	/**
	 * Save a specific post field for a specific post_type.
	 *
	 * Saves desired field after running static::save_post_check().
	 *
	 * @since 1.12.2 Changed to use $_REQUEST instead of $_POST,
	 *               also check if field is even present, and
	 *               unslash the value before saving.
	 * @since 1.10.0
	 *
	 * @param int    $post_id    The ID of the post being saved (skip when saving).
	 * @param string $post_type  The post_type to limit this call to.
	 * @param string $post_field The field in the posts table to save the value to.
	 * @param string $field_name Optional The name of the $_POST field to use (defaults to $post_field).
	 */
	protected static function _post_type_save_field( $post_id, $post_type, $post_field, $field_name = null ) {
		if ( ! static::save_post_check( $post_id, $post_type ) ) return;
		global $wpdb;

		if ( is_null( $field_name ) ) {
			$field_name = $post_field;
		}

		if ( ! isset( $_REQUEST[ $field_name ] ) ) {
			return;
		}

		// Auto prefix if needed
		$post_field = static::maybe_prefix_post_field( $post_field );

		$value = $_REQUEST[ $field_name ];

		$wpdb->update( $wpdb->posts, array(
			$post_field => wp_unslash( $value ),
		), array(
			'ID' => $post_id,
		) );
	}

	/**
	 * Add counts for a post type to the Right Now widget on the dashboard.
	 *
	 * @since 1.12.2 Updated to print link vs span based on current user's capabilities.
	 * @since 1.3.1  Revised logic to work with the new dashboard_right_now markup.
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
			if ( current_user_can( $object->cap->edit_posts ) ) {
				$elements[] = '<a href="edit.php?post_type=' . $post_type . '">' . $label . '</a>';
            } else {
				$elements[] = '<span>' . $label . '</span>';
            }
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
			$args = array();

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
			if ( $args ) {
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
	 * @since 1.11.0 Now protected.
	 * @since 1.8.0  New method for checking for appropriate post type; now works for attachments too.
	 * @since 1.6.0  Now supports hierarchical terms via use of taxonomy_filter_options().
	 * @since 1.0.0
	 *
	 * @param object $taxonomy The taxonomy object to build from.
	 */
	protected static function _taxonomy_filter( $taxonomy ) {
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
	 * Print an extra wp_editor to the edit post form.
	 *
	 * @since 1.10.0 Fixed class & id of wrapper.
	 * @since 1.8.0
	 *
	 * @see Tools::add_extra_editor()
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
		if ( isset( $settings['post_field'] ) ) {
			// Auto prefix if needed
			$field = static::maybe_prefix_post_field( $settings['post_field'] );
			$value = $post->{$field};
		} elseif ( isset( $settings['meta_key'] ) ) {
			$value = get_post_meta( $post->ID, $settings['meta_key'], true );
		}

		printf( '<div class="qs-editor-wrap" id="%s-editor-wrap">', $settings['name'] );
			echo '<h3>' . $settings['title'] . '</h3>';
			echo Form::build_editor( $settings, $value );
		echo '</div>';
	}

	/**
	 * Alias of print_extra_editor() but fires before the main editor.
	 *
	 * @since 1.10.0
	 *
	 * @see Tools::print_extra_editor()
	 */
	public static function _print_extra_editor_above( $post, $settings = array() ) {
		static::_print_extra_editor( $post, $settings );
	}

	/**
	 * Alias of print_extra_editor() but fires after the main editor.
	 *
	 * @since 1.10.0
	 *
	 * @see Tools::print_extra_editor()
	 */
	public static function _print_extra_editor_below( $post, $settings = array() ) {
		static::_print_extra_editor( $post, $settings );
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

	/**
	 * Add custom rewrites.
	 *
	 * @since 1.10.1
	 *
	 * @param array $rewrites The rewrites to add, in pattern => rewrite form.
	 */
	public static function _add_rewrites( $rewrites ) {
		foreach ( $rewrites as $pattern => $rewrite ) {
			add_rewrite_rule( $pattern, $rewrite, 'top' );
		}
	}

	/**
	 * Register a widget.
	 *
	 * @since 1.11.0
	 *
	 * @param string $name The name of the widget class.
	 */
	protected static function _register_widget( $name ) {
		register_widget( $name );
	}

	/**
	 * Load a widget's PHP file and register it.
	 *
	 * @since 1.11.0
	 *
	 * @param string $file The path to the PHP file.
	 * @param string $name Optional The name of the widget class. (Defaults to filename)
	 */
	public static function setup_widget( $file, $name = null ) {
		// Abort if file isn't found
		if ( ! file_exists( $file ) ) {
			return;
		}

		// Load the file
		require_once( $file );

		// Get the name based on $file if $name isn't passed
		if ( is_null( $name ) ) {
			$name = pathinfo( $file, PATHINFO_FILENAME );
		}

		// Abort if the class still doesn't exist
		if ( ! class_exists( $name ) ) {
			return;
		}

		// Register the widget
		static::register_widget( $name );
	}

	/**
	 * Register multiple widgets under a shared directory.
	 *
	 * If $wp_naming is true, it will convert the names to the expected filenames
	 * according to WordPress naming conventions (e.g. My_Widget = class-my-widget).
	 *
	 * @since 1.11.0
	 *
	 * @param array  $widgets     An array of widget names.
	 * @param string $path        The directory to look in for the widget PHP files.
	 * @param bool   $wp_naming   Optional File uses WordPress naming convention.
	 * @param string $prefix      Optional A prefix for all the class/file names.
	 * @param bool   $prefix_file Optional Apply $prefix to filename as well.
	 */
	public static function setup_widgets( $widgets, $path, $wp_naming = false, $prefix = null, $prefix_file = false ) {
		// Loop through each widget, prefix/process name, and setup
		foreach ( $widgets as $name ) {
			// Use $name for the filename, with/without prefix
			if ( $prefix_file ) {
				$file = $prefix . $name;
			} else {
				$file = $name;
			}

			if ( $wp_naming ) {
				// WordPress naming convention; lowercase hyphenated with class- prefix
				$file = 'class-' . str_replace( '_', '-', strtolower( $file ) );
			}

			// Ensure $path has trailing slash
			trailingslashit( $path );

			// Setup the widget, adding $prefix to $name
			static::setup_widget( $path . $file . '.php', $prefix . $name );
		}
	}
}
