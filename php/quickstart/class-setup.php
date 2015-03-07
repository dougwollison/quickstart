<?php
namespace QuickStart;

/**
 * The Setup Class: The dynamic class that processes the configuration instructions.
 *
 * @package QuickStart
 * @subpackage Setup
 * @since 1.0.0
 */

class Setup extends \Smart_Plugin {
	/**
	 * The configuration array.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $config = array();

	/**
	 * The defaults array.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * A list of internal methods and their hooks configurations are.
	 *
	 * @since 1.8.0 Added hooks from Setup/Feature merge.
	 * @since 1.1.4 Added regster_page_setting(s) entries.
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $method_hooks = array(
		// Content Hooks
		'run_theme_setups'       => array( 'after_theme_setup', 10, 0 ),

		// Metabox Hooks
		'save_meta_box'          => array( 'save_post', 10, 1 ),
		'add_meta_box'           => array( 'add_meta_boxes', 10, 0 ),

		// MCE Hooks
		'add_mce_buttons'        => array( 'mce_buttons', 10, 1 ),
		'add_mce_buttons_2'      => array( 'mce_buttons_2', 10, 1 ),
		'add_mce_buttons_3'      => array( 'mce_buttons_3', 10, 1 ),
		'add_mce_plugin'         => array( 'mce_external_plugins', 10, 1),
		'add_mce_style_formats'  => array( 'tiny_mce_before_init', 10, 1 ),

		// Settings Hooks
		'register_setting'       => array( 'admin_init', 10, 0 ),
		'register_settings'      => array( 'admin_init', 10, 0 ),

		// Menu Page Hooks
		'register_page_setting'  => array( 'admin_init', 10, 0 ),
		'register_page_settings' => array( 'admin_init', 10, 0 ),
		'add_page_to_menu'       => array( 'admin_menu', 0 ),

		// Feature Hooks
		'index_page_query'      => array( 'parse_query', 10, 1 ),
		'index_page_link'       => array( 'post_type_archive_link', 10, 2 ),
		'index_page_title_part' => array( 'wp_title_parts', 10, 1 ),
	);

	// =========================
	// !Main Setup Function
	// =========================

	/**
	 * Processes configuration options and sets up necessary hooks/callbacks.
	 *
	 * @since 1.8.0 Added quick-enqueue handling.
	 * @since 1.4.0 Added helpers css/js backend enqueue.
	 * @since 1.1.0 Added tinymce key; mce is deprecated.
	 * @since 1.0.0
	 *
	 * @param array $configs  The theme configuration options.
	 * @param array $defaults Optional The default values.
	 */
	public function __construct( array $configs, $defaults = array() ) {
		// Store the configuration array
		$this->configs = $configs;

		// Merge default_args with passed defaults
		$this->defaults = wp_parse_args( $defaults, $this->defaults );

		foreach ( $configs as $key => $value ) {
			// Proceed simple options based on what $key is
			switch ( $key ) {
				case 'hide':
					// Hide certain aspects of the backend
					Tools::hide( $value );
				break;
				case 'shortcodes':
					// Register the passed shortcodes
					Tools::register_shortcodes( $value );
				break;
				case 'relabel_posts':
					// Relabel Posts to the desired string(s)
					Tools::relabel_posts( $value );
				break;
				case 'helpers':
					// Load the requested helper files
					Tools::load_helpers( $value );
				break;
				case 'enqueue':
					// Enqueue frontend scripts/styles if set
					if ( isset( $value['frontend'] ) ) {
						Hooks::frontend_enqueue( $value['frontend'] );
					}
					// Enqueue backend scripts/styles if set
					if ( isset( $value['backend'] ) ) {
						Hooks::backend_enqueue( $value['backend'] );
					}
				break;
				case 'css':
				case 'js':
					// Process quick enqueue scripts/styles for the frontend
					Hooks::quick_frontend_enqueue( $key, $value );
				break;
				case 'admin_css':
				case 'admin_js':
					// Process quick enqueue scripts/styles for the backend
					$key = str_replace( 'admin_', '', $key );
					Hooks::quick_backend_enqueue( $key, $value );
				break;
				case 'tinymce':
				case 'mce':
					// Enable buttons if set
					if ( isset( $value['buttons'] ) ) {
						$this->add_mce_buttons( $value['buttons'] );
					}
					// Register plugins if set
					if ( isset( $value['plugins'])){
						$this->register_mce_plugins( $value['plugins'] );
					}
					// Register custom styles if set
					if ( isset( $value['styles'] ) ) {
						$this->register_mce_styles( $value['styles'] );
					}
				break;
				case 'settings':
					$this->register_settings( $value );
				break;
				case 'pages':
					$this->register_pages( $value );
				break;
			}
		}

		// Run the content setups
		$this->run_content_setups();

		// Run the theme setups
		$this->run_theme_setups();

		// Enqueue the general css/js if not already
		if ( is_admin() && ( ! defined( 'QS_HELPERS_ENQUEUED' ) || ! QS_HELPERS_ENQUEUED ) ) {
			Hooks::backend_enqueue( array(
				'css' => array(
					'qs-helpers-css' => array( plugins_url( '/css/qs-helpers.css', QS_FILE ) ),
				),
				'js' => array(
					'qs-helpers-js' => array( plugins_url( '/js/qs-helpers.js', QS_FILE ), array( 'underscore', 'jquery' ) ),
				),
			) );
			define( 'QS_HELPERS_ENQUEUED', true );
		}
	}

	// =========================
	// !Internal Utilities
	// =========================

	/**
	 * Utility method: prepare the provided defaults with the custom ones from $this->defaults.
	 *
	 * @since 1.0.0
	 *
	 * @param string $key       The key of the array in $this->defaults to pull.
	 * @param array  &$defaults The defaults to parse.
	 */
	protected function prep_defaults( $key, &$defaults ) {
		if ( isset( $this->defaults[ $key ] ) && is_array( $this->defaults[ $key ] ) ) {
			$defaults = wp_parse_args( $this->defaults[ $key ], $defaults );
		}
	}

	/**
	 * Utility method: take care of processing the labels based on the provided arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param string $object   The slug of teh post_type/taxonomy in question.
	 * @param array  &$args    The arguments for the post_type/taxonomy registration.
	 * @param array  $template The template of special labels to create.
	 */
	protected static function maybe_setup_labels( $object, &$args, $template ) {
		// Check if labels need to be auto created
		if ( ! isset( $args['labels'] ) ) {
			// Auto create the singular form if needed
			if ( ! isset( $args['singular'] ) ) {
				if ( isset( $args['plural'] ) ) {
					$args['singular'] = singularize( $args['plural'] );
				} else {
					$args['singular'] = make_legible( $object );
				}
			}

			// Auto create the plural form if needed
			if ( ! isset( $args['plural'] ) ) {
				$args['plural'] = pluralize( $args['singular'] );
			}

			// Auto create the menu name if needed
			if ( ! isset( $args['menu_name'] ) ) {
				$args['menu_name'] = $args['plural'];
			}

			$singular  = $args['singular'];
			$plural    = $args['plural'];
			$menu_name = $args['menu_name'];

			$labels = array(
				'name'               => _x( $plural, 'post type general name' ),
				'singular_name'      => _x( $singular, 'post type singular name' ),
				'menu_name'          => _x( $menu_name, 'post type menu name' ),
				'add_new'            => _x( 'Add New', $object ),
			);

			$template = wp_parse_args( $template, array(
				'add_new_item'       => 'Add New %S',
				'edit_item'          => 'Edit %S',
				'new_item'           => 'New %S',
				'view_item'          => 'View %S',
				'all_items'          => 'All %P',
				'search_items'       => 'Search %P',
				'parent_item_colon'  => 'Parent %S:',
				'not_found'          => 'No %p found.',
			) );

			$find = array( '%S', '%P', '%s', '%p' );
			$replace = array( $singular, $plural, strtolower( $singular ), strtolower( $plural ) );

			foreach ( $template as $label => $format ) {
				$text = str_replace( $find, $replace, $format );
				$labels[ $label ] = __( $text );
			}

			/**
			 * Filter the processed labels list.
			 *
			 * @since 1.0.0
			 *
			 * @param array  $labels The list of labels for the object.
			 * @param string $object The slug of the post_type/taxonomy.
			 * @param array  $args   The registration arguments.
			 */
			$labels = apply_filters( 'qs_setup_labels', $labels, $object, $args );

			$args['labels'] = $labels;
		}
	}

	/**
	 * Check a list of fields for types that would require the media_manager helper.
	 *
	 * @since 1.7.1
	 *
	 * @param array $fields The list of fields to check through.
	 */
	protected static function maybe_load_media_manager( $fields ) {
		if ( ! defined( 'QS_LOADED_MEDIA_MANAGER' ) || ! QS_LOADED_MEDIA_MANAGER ) {
			foreach ( $fields as $field ) {
				$dependants = array( 'addfile', 'editgallery', 'setimage' );
				if ( isset( $field['type'] ) && in_array( $field['type'], $dependants ) ) {
					// Make sure the media_manager helper is loaded
					Tools::load_helpers( 'media_manager' );
					break;
				}
			}
		}
	}

	/**
	 * Process the meta box args to define a dumb meta box.
	 * (simple text field with no label)
	 *
	 * @since 1.2.0
	 *
	 * @param array  $args The meta box arguments.
	 * @param string $name The name of the meta box the args belong to.
	 *
	 * @return array The processed $args.
	 */
	protected static function make_dumb_meta_box( $args, $name ) {
		$args = (array) $args;

		$args['fields'] = array(
			$name => array(
				'class'           => 'widefat',
				'wrap_with_label' => false,
			),
		);

		return $args;
	}

	// =========================
	// !Custom Content Setups
	// =========================

	/**
	 * Proccess the content setups; extracting any taxonomies/meta_boxes defined.
	 * within a post_type configuration.
	 *
	 * @since 1.8.0 Tweaked check for post-thumbnails support.
	 * @since 1.6.0 Add meta boxes/features numerically to prevent overwriting.
	 * @since 1.3.3 Removed callback chek on feature args.
	 * @since 1.2.0 Added check for dumb meta box setup
	 * @since 1.0.0
	 *
	 * @param array &$configs Optional The post types, taxonomies and meta boxes to setup.
	 */
	public function run_content_setups( $configs = null ) {
		// If no $configs is passed, load the locally stored ones.
		if ( is_null( $configs ) ) {
			$configs = &$this->configs;
		}

		$configs = array_merge( array(
			'post_types' => array(),
			'taxonomies' => array(),
			'meta_boxes' => array(),
			'features'   => array(), // Custom QuickStart features
			'columns'    => array(), // Custom manager columns
		), $configs );

		// Loop through each post_type, check for supports, taxonomies or meta_boxes
		foreach ( $configs['post_types'] as $post_type => &$pt_args ) {
			make_associative( $post_type, $pt_args );

			// Force theme and post type supports into array form
			csv_array_ref( $configs['supports'] );
			csv_array_ref( $pt_args['supports'] );

			// Check if this post type uses thumbnails, and
			// make sure the theme supports includes it
			if ( in_array( 'thumbnail', $pt_args['supports'] ) && ! in_array( 'post-thumbnails', $configs['supports'] ) && ! isset( $config['supports']['post-thumbnails'] ) ) {
				$configs['supports'][] = 'post-thumbnails';
			}

			// Check for taxonomies to register for the post type
			if ( isset( $pt_args['taxonomies'] ) ) {
				// Loop through each taxonomy, move it to $taxonomies if not registered yet
				foreach ( $pt_args['taxonomies'] as $taxonomy => $tx_args ) {
					// Fix if dumb taxonomy was passed (numerically, not associatively)
					make_associative( $taxonomy, $tx_args );

					// Check if the taxonomy is registered yet
					if ( ! taxonomy_exists( $taxonomy ) ) {
						// Add this post type to the post_types argument to this taxonomy
						$tx_args['post_type'] = array( $post_type );

						// Add this feauture to features list
						$configs['taxonomies'][ $taxonomy ] = $tx_args;
						//and remove from this post type
						unset( $pt_args['taxonomies'][ $taxonomy ] );
					}
				}
			}

			// Check for meta boxes to register for the post type
			if ( isset( $pt_args['meta_boxes'] ) ) {
				foreach ( $pt_args['meta_boxes'] as $meta_box => $mb_args ) {
					// Fix if dumb meta box was passed (numerically, not associatively)
					make_associative( $meta_box, $mb_args );

					// Check if the arguments are a callable, restructure to proper form
					if ( is_callable( $mb_args ) ) {
						$mb_args = array(
							'fields' => $mb_args,
						);
					} elseif ( empty( $mb_args ) ) {
						// Or, if no args passed, make a "dumb" meta box
						$mb_args = self::make_dumb_meta_box( $mb_args, $meta_box );
					}

					// Add this post type to the post_types argument to this meta box
					$mb_args['post_type'] = array( $post_type );

					// Add this feauture to features list
					$configs['meta_boxes'][] = array(
						'id' => $meta_box,
						'args' => $mb_args,
					);
					//and remove from this post type
					unset( $pt_args['meta_boxes'][ $meta_box ] );
				}
			}

			// Check for features to register for the post type
			if ( isset( $pt_args['features'] ) ) {
				csv_array_ref( $pt_args['features'] );
				foreach ( $pt_args['features'] as $feature => $ft_args ) {
					// Fix if dumb feature was passed (numerically, not associatively)
					make_associative( $feature, $ft_args );

					// Add this post type to the post_types argument to this meta box
					$ft_args['post_type'] = array( $post_type );

					// Add this feauture to features list
					$configs['features'][] = array(
						'id' => $feature,
						'args' => $ft_args,
					);
					//and remove from this post type
					unset( $pt_args['features'][ $feature ] );
				}
			}

			// Check for columns to register for the post type
			if ( isset( $pt_args['columns'] ) ) {
				// Add this column for this post type to the columns section of $config
				$configs['columns'][ $post_type ] = $pt_args['columns'];
			}
		}

		// Run the content setups
		$this->register_post_types( $configs['post_types'] ); // Will run during "init"
		$this->register_taxonomies( $configs['taxonomies'] ); // Will run during "init"
		$this->register_meta_boxes( $configs['meta_boxes'] ); // Will run now and setup various hooks
		$this->setup_columns( $configs['columns'] ); // Will run now and setup various hooks
		$this->setup_features( $configs['features'] ); // Will run now and setup various hooks
	}

	// =========================
	// !Post Type Setups
	// =========================

	/**
	 * Register the requested post_type.
	 *
	 * @since 1.6.0 Modified handling of save_post callback to Tools::post_type_save().
	 * @since 1.2.0 Added use of save argument for general save_post callback.
	 * @since 1.0.0
	 *
	 * @param string $post_type The slug of the post type to register.
	 * @param array  $args      Optional The arguments for registration.
	 */
	public function _register_post_type( $post_type, array $args = array() ) {
		// Make sure the post type doesn't already exist
		if ( post_type_exists( $post_type ) ) {
			return;
		}

		// Setup the labels if needed
		self::maybe_setup_labels( $post_type, $args, array(
			'new_item'           => 'New %S',
			'not_found_in_trash' => 'No %p found in Trash.',
		) );

		// Default arguments for the post type
		$defaults = array(
			'public'      => true,
			'has_archive' => true,
		);

		// Prep $defaults
		$this->prep_defaults( 'post_type', $defaults );

		// Parse the arguments with the defaults
		$args = wp_parse_args( $args, $defaults );

		// Now, register the post type
		register_post_type( $post_type, $args );

		// If a save hook is passed, register it
		if ( isset( $args['save'] ) ) {
			Hooks::post_type_save( $post_type, $args['save'] );
		}

		// Now that it's registered, fetch the resulting show_in_menu argument,
		// and add the post_type_count hook if true
		if ( get_post_type_object( $post_type )->show_in_menu ){
			Hooks::post_type_count( $post_type );
		}
	}

	/**
	 * Register the requested post types.
	 *
	 * Simply loops through and calls Setup::_register_post_type().
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_types The list of post types to register.
	 */
	public function _register_post_types( array $post_types ) {
		foreach ( $post_types as $post_type => $args ) {
			make_associative( $post_type, $args );
			$this->_register_post_type( $post_type, $args );
		}
	}

	// =========================
	// !Taxonomy Setups
	// =========================

	/**
	 * Register the requested taxonomy.
	 *
	 * @sicne 1.8.0 Added enforcement of array for for post_type value.
	 * @since 1.6.0 Updated static replacement meta box title based on $multiple.
	 * @since 1.5.0 Added "static" option handling (custom taxonomy meta box).
	 * @since 1.3.1 Removed Hooks::taxonomy_count call.
	 * @since 1.0.0
	 *
	 * @param string $taxonomy The slug of the taxonomy to register.
	 * @param array  $args     The arguments for registration.
	 */
	public function _register_taxonomy( $taxonomy, $args ) {
		// Ensure post_type is in array form if set.
		if ( isset( $args['post_type'] ) ) {
			csv_array_ref( $args['post_type'] );
		}

		// Check if the taxonomy exists, if so, see if a post_type
		// is set in the $args and then tie them together.
		if ( taxonomy_exists( $taxonomy ) ) {
			if ( isset( $args['post_type'] ) ) {
				foreach ( (array) $args['post_type'] as $post_type ) {
					register_taxonomy_for_object_type( $taxonomy, $post_type );
				}
			}
			return;
		}

		// Setup the labels if needed
		self::maybe_setup_labels( $taxonomy, $args, array(
			'new_item_name' => 'New %S Name',
			'parent_item' => 'Parent %S',
			'popular_items' => 'Popular %P',
			'separate_items_with_commas' => 'Separate %p with commas',
			'add_or_remove_items' => 'Add or remove %p',
			'choose_from_most_used' => 'Choose from most used %p',
		) );

		// Default arguments for the taxonomy
		$defaults = array(
			'hierarchical' => true,
			'show_admin_column' => true,
		);

		// Prep $defaults
		$this->prep_defaults( 'taxonomy', $defaults );

		// Parse the arguments with the defaults
		$args = wp_parse_args( $args, $defaults );

		// Check for the "static" option, set it up
		$static = false;
		if ( isset( $args['static'] ) && $args['static'] ) {
			$static = true;

			// Disable the default meta box
			$args['meta_box_cb'] = false;

			$multiple = false;
			// Default the "multiple" flag to false
			if ( isset( $args['multiple'] ) ) {
				$multiple = $args['multiple'];
				unset( $args['multiple'] );
			}

			// Remove the static argument before saving
			unset( $args['static'] );
		}

		// Now, register the post type
		register_taxonomy( $taxonomy, $args['post_type'], $args );

		// Proceed with post-registration stuff, provided it was successfully registered.
		if ( ! ( $taxonomy_obj = get_taxonomy( $taxonomy ) ) ) {
			return;
		}

		// Now that it's registered, see if there are preloaded terms to add
		if ( isset( $args['preload'] ) && is_array( $args['preload'] ) ) {
			$is_assoc = is_assoc( $args['preload'] );

			foreach ( $args['preload'] as $term => $args ) {
				// Check if the term was added numerically on it's own
				if ( ! $is_assoc ) {
					$term = $args;
					$args = array();
				}

				// If $args is not an array, assume slug => name format
				if ( ! is_array( $args ) ) {
					$slug = $term;
					$term = $args;
					$args = array( 'slug' => $slug );
				}

				// Check if it exists, skip if so
				if ( get_term_by( 'name', $term, $taxonomy ) ) {
					continue;
				}

				// Insert the term
				wp_insert_term( $term, $taxonomy, $args );
			}
		}

		// Finish setting up the static taxonomy meta box if needed
		if ( $static ) {
			$this->register_meta_box( "$taxonomy-terms", array(
				'title'     => ( $multiple ? $taxonomy_obj->labels->name : $taxonomy_obj->labels->singular_name ),
				'post_type' => $taxonomy_obj->object_type,
				'context'   => 'side',
				'priority'  => 'core',
				'name'      => $taxonomy,
				'type'      => $multiple ? 'checklist' : 'select',
				'class'     => 'widefat static-terms',
				'null'      => '&mdash; None &mdash;',
				'taxonomy'  => $taxonomy,
			) );
		}

		// Now that it's registered, fetch the resulting show_ui argument,
		// and add the taxonomy_filter hooks if true
		if ( $taxonomy_obj->show_ui ){
			Hooks::taxonomy_filter( $taxonomy );
		}
	}

	/**
	 * Register the requested taxonomies.
	 *
	 * Simply loops through and calls Setup::_register_taxonomy().
	 *
	 * @since 1.0.0
	 *
	 * @param array $taxonomies The list of taxonomies to register.
	 */
	public function _register_taxonomies( array $taxonomies ) {
		foreach ( $taxonomies as $taxonomy => $args ) {
			make_associative( $taxonomy, $args );
			$this->_register_taxonomy( $taxonomy, $args );
		}
	}

	// =========================
	// !Meta Box Setups
	// =========================

	/**
	 * Register the requested meta box.
	 *
	 * @since 1.8.0 Added use of register_meta() for sanitizing and protection,
	 *              also added handling of condition setting, modified single
	 *				field handling to account for callbacks.
	 * @since 1.7.1 Added use of maybe_load_media_manager()
	 * @since 1.3.5 Added use-args-as-field-args handling.
	 * @since 1.3.3 Fixed bug with single field expansion.
	 * @since 1.2.0 Moved dumb meta box logic to Setup::make_dumb_meta_box().
	 * @since 1.0.0
	 *
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments for registration.
	 */
	public function register_meta_box( $meta_box, $args ) {
		if ( empty( $args ) ) {
			// Empty array; make dumb meta box
			$args = self::make_dumb_meta_box( $args, $meta_box );
		} elseif ( is_callable( $args ) ) {
			// A callback, recreate into proper array
			$args = array(
				'callback' => $args,
			);
		} elseif ( isset( $args['field'] ) ) {
			// A single field is provided, the name of the metabox is also the name of the field
 
            // Default the wrap_with_label argument to false if applicable
            if ( ! is_callable( $args['field'] ) && is_array( $args['field'] ) && ! isset( $args['field']['wrap_with_label'] ) ) {
                // Auto set wrap_with_label to false if not present already
                $args['field']['wrap_with_label'] = false;
            }
 
            // Create a fields entry
            $args['fields'] = array(
                $meta_box => $args['field'],
            );
		} elseif ( ! isset( $args['fields'] ) && ! isset( $args['callback'] ) ) {
			// No separate fields list or callback passed

			// Turn off wrapping by default, unless a label is set
			if ( ! isset( $args['wrap_with_label'] ) && ! isset( $args['label'] ) ) {
				$args['wrap_with_label'] = false;
			}

			// Use meta box args as the field args as well
			$args['fields'] = array(
				$meta_box => $args,
			);
		}

		$defaults = array(
			'title'     => make_legible( $meta_box ),
			'context'   => 'normal',
			'priority'  => 'high',
			'post_type' => 'post',
		);

		// Prep $defaults and parse the $args
        $this->prep_defaults( 'meta_box', $defaults );
        $args = wp_parse_args( $args, $defaults );

		// Check if condition callback exists; test it before proceeding
		if ( isset( $args['condition'] ) && is_callable( $args['condition'] ) ) {
			// Get the ID of the current post
			$post_id = null;
			if ( isset( $_POST['post_ID'] ) ) {
				$post_id = $_POST['post_ID'];
			} else if ( isset( $_GET['post'] ) ) {
				$post_id = $_GET['post'];
			}

			/**
			 * Test if the meta box should be registered for this post.
			 *
			 * @since 1.8.0
			 *
			 * @param int    $post_id  The ID of the current post (null if new).
			 * @param string $meta_box The slug of the meta box to register.
			 * @param array  $args     The arguments for registration.
			 *
			 * @return bool The result of the test.
			 */
			$result = call_user_func( $args['condition'], $post_id, $meta_box, $args );

			// If test fails, don't setup the meta box
			if ( ! $result ) return;
		}

		// Check if media_manager helper needs to be loaded
		self::maybe_load_media_manager( $args['fields'] );

		// Register all meta keys found
		foreach ( $args['fields'] as $field => $_args ) {
			// By default, the field name is the meta key
			$meta_key = $field;

			// Attempt to override with name or data_name if set
			if ( isset( $_args['data_name'] ) ) {
				$meta_key = $_args['data_name'];
			} elseif ( isset( $_args['name'] ) ) {
				$meta_key = $_args['name'];
			}

			// Get sanitize callback if set
			$sanitize_callback = null;
			if ( isset( $_args['sanitize'] ) ) {
				$sanitize_callback = $_args['sanitize'];
			}

			// Register the meta (it will automatically be protected)
			register_meta( 'post', $meta_key, $sanitize_callback, '__return_false' );
		}

		// Setup the save hook and register the actual meta_box
		$this->save_meta_box( $meta_box, $args );
		$this->add_meta_box( $meta_box, $args );
	}

	/**
	 * Register the requested meta boxes.
	 *
	 * Simply loops through and calls Setup::register_meta_box().
	 *
	 * @since 1.6.0 Handle meta boxes added numerically via run_content_setups().
	 * @since 1.0.0
	 *
	 * @param array $meta_boxes The list of meta boxes to register.
	 */
	public function register_meta_boxes( array $meta_boxes ) {
		foreach ( $meta_boxes as $meta_box => $args ) {
			if ( ! make_associative( $meta_box, $args ) ) {
				// Metabox was added numerically, assume id, args format.
				$meta_box = $args['id'];
				$args = $args['args'];
			}
			$this->register_meta_box( $meta_box, $args );
		}
	}

	/**
	 * Setup the save hook for the meta box.
	 *
	 * @since 1.8.0 Added use of "save_single" option and support for foo[bar] style fields,
	 *				$meta_key value now defaults to the same as $post_key.
	 * @since 1.6.0 Restructured for better handling.
	 * @since 1.5.0 Added taxonomy meta box saving.
	 * @since 1.4.2 Added "post_field" update handling.
	 * @since 1.2.0 Moved save check functionality to Tools::save_post_check().
	 * @since 1.1.1 Fixed typo causing $args['fields'] saving to save the $_POST key, not the value.
	 * @since 1.0.0
	 *
	 * @param int    $post_id  The ID of the post being saved. (skip when saving)
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments from registration.
	 */
	public function _save_meta_box( $post_id, $meta_box, $args ) {
		if ( ! Tools::save_post_check( $post_id, $args['post_type'], "_qsnonce-$meta_box", $meta_box ) ) return;

		// Determine method to save meta box data
		if ( isset( $args['save'] ) && is_callable( $args['save'] ) ) {
			// Method 1: explicit save callback

			/**
			 * Desired processing to be done when saving this meta box
			 *
			 * @since 1.6.0 Callback is now passed meta box details
			 * @since 1.0.0
			 *
			 * @param int    $post_id  The ID of the post being saved.
			 * @param array  $args     The settings of the meta box.
			 * @param string $meta_box The ID of the meta box.
			 */
			call_user_func( $args['save'], $post_id, $args, $meta_box );
		} elseif ( isset( $args['save_fields'] ) ) {
			// Method 2: explicit list of fields to save
			csv_array_ref( $args['save_fields'] );

			foreach ( $args['save_fields'] as $meta_key => $field ) {
				// Assume meta_key and field are the same if not string
				if ( is_int( $meta_key ) ) {
					$meta_key = $field;
				}

				// Check if POST field exists, save it if so.
				if ( isset( $_POST[ $field ] ) ) {
					update_post_meta( $post_id, $meta_key, $_POST[ $field ] );
				}
			}
		} elseif ( isset( $args['callback'] ) || ( isset( $args['fields'] ) && is_callable( $args['fields'] ) ) ) {
			// Method 3: If generated by callback, attempt to save $_POST[$meta_box] if present
			if ( isset( $_POST[ $meta_box ] ) ) {
				update_post_meta( $post_id, $meta_box, $_POST[ $meta_box ] );
			}
		} elseif ( isset( $args['fields'] ) || isset( $args['get_fields'] ) ) {
			// Method 4: Attempt to save the fields to their respective meta keys
			if ( isset( $args['get_fields'] ) && is_callable( $args['get_fields'] ) ) {
				/**
				 * Dynamically generate the fields array.
				 *
				 * @since 1.6.0
				 *
				 * @param WP_Post $post The post object.
				 * @param array   $args The original arguments for the meta box.
				 * @param string  $id   The ID of the meta box.
				 */
				$fields = call_user_func( $args['get_fields'], $post, $args, $id );
			} else {
				$fields = $args['fields'];
			}

			// Keep track of completed fields
			$saved_fields = array();

			foreach ( $fields as $field => $settings ) {
				if ( is_int( $field ) ) {
					$field = $settings;
				}

				// By default, post and meta keys are the same as the field name
				$post_key = $meta_key = $field;

				// By default, array values are stored in a single entry
				$save_single = true;

				// If there are settings to work with, check for specific $post_key and $meta_key names,
				// as well as an override for $save_single
				if ( is_array( $settings ) ) {
					// Overide $post_key with name setting if present
					if ( isset( $settings['name'] ) ) {
						$post_key = $settings['name'];
					}

					// Overide $meta_key with data_name setting if present, otherwise with $post_key
					if ( isset( $settings['data_name'] ) ) {
						$meta_key = $settings['data_name'];
					} else {
						$meta_key = $post_key;
					}

					// Override $save_single if present
					if ( isset( $settings['save_single'] ) ) {
						$save_single = $settings['save_single'];
					}
				}

				// If the post key is an array, get the root key specifically
				if ( preg_match( '/^([\w-]+)\[([\w-]+)\](.*)$/', $post_key, $matches ) ) {
					$post_key = $matches[1];

					// Update $meta_key to match if it wasn't overwritten
					if ( ! isset( $settings['data_name'] ) ) {
						$meta_key = $post_key;
					}
				}

				// If this post key has already been handled, skip it
				if ( in_array( $post_key, $saved_fields ) ) {
					continue;
				}

				$value = isset( $_POST[ $post_key ] ) ? $_POST[ $post_key ] : null;

				// If there are settings to work with, check for saving as a post_field or taxonomy term
				if ( is_array( $settings ) ) {
					// If "post_field" is present, update the field, not a meta value
					if ( isset( $settings['post_field'] ) && $settings['post_field'] ) {
						global $wpdb;

						// Directly update the entry in the database
						$wpdb->update( $wpdb->posts, array(
							$settings['post_field'] => $value,
						), array(
							'ID' => $post_id,
						) );

						// We're done, next field
						continue;
					}

					// If "taxonomy" is present, update the terms, not a meta value
					if ( isset( $settings['taxonomy'] ) && $settings['taxonomy'] ) {
						// Default the terms to null
						$terms = null;

						if ( ! empty( $value ) ) {
							// Get the terms, ensure it's an array
							$terms = (array) $value;

							// Ensure the values are integers
							$terms = array_map( 'intval', $terms );
						}

						// Update the terms
						wp_set_object_terms( $post_id, $terms, $settings['taxonomy'] );

						// We're done, next field
						continue;
					}
				}

				// Save the post meta
				if ( $save_single ) {
					// All in one entry
					update_post_meta( $post_id, $meta_key, $value );
				} else {
					// Save individually...

					// First, delete all existing values
					delete_post_meta( $post_id, $meta_key );

					// Next, make sure the value is an array if set
					if ( ! is_null( $value ) ) {
						$value = (array) $value;

						// Finally, loop through the values and save
						foreach ( $value as $val ) {
							add_post_meta( $post_id, $meta_key, $val );
						}
					}
				}

				$saved_fields[] = $post_key;
			}
		}
	}

	/**
	 * Add the meta box to WordPress.
	 *
	 * @since 1.6.0 Added qs_metabox_ prefix to meta box id.
	 * @since 1.0.0
	 *
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments from registration.
	 */
	public function _add_meta_box( $meta_box, $args ) {
		$post_types = csv_array( $args['post_type'] );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'qs_metabox_' . $meta_box,
				$args['title'],
				array( __NAMESPACE__ . '\Tools', 'build_meta_box' ),
				$post_type,
				$args['context'],
				$args['priority'],
				array(
					'id' => $meta_box,
					'args' => $args,
				)
			);
		}
	}

	// =========================
	// !Column Setups
	// =========================

	/**
	 * Setup the requested columns for the post type.
	 *
	 * @since 1.8.0
	 *
	 * @param string $post_type The slug of the post type to setup for.
	 * @param array  $columnset The list of columns to use/register.
	 */
	public function _setup_columnset( $post_type, $columnset ) {
		switch ( $post_type ) {
			case 'page':
				$filter_hook = 'manage_pages_columns';
				$action_hook = 'manage_pages_custom_column';
				break;
			case 'post':
				$filter_hook = 'manage_posts_columns';
				$action_hook = 'manage_posts_custom_column';
				break;
			default:
				$filter_hook = 'manage_' . $post_type . '_posts_columns';
				$action_hook = 'manage_' . $post_type . '_posts_custom_column';
				break;
		}

		// Create the hook settings and arguments list
		$filter_hook = array( $filter_hook, 10, 1 );
		$action_hook = array( $action_hook, 10, 2 );
		$args = array( $columnset );

		// Save the callbacks
		$this->save_callback( 'edit_columns', $args, $filter_hook );
		$this->save_callback( 'do_columns', $args, $action_hook );
	}

	/**
	 * Sets up the requested columns for each post type.
	 *
	 * Simply loops through and calls Setup::_setup_columnset().
	 *
	 * @since 1.8.0
	 *
	 * @param array $feature The list of features to register.
	 */
	public function _setup_columns( array $columns ) {
		foreach ( $columns as $post_type => $columnset ) {
			$this->_setup_columnset( $post_type, $columnset );
		}
	}

	/**
	 * Edit the list of columns using the passed columnset.
	 *
	 * @since 1.8.0
	 *
	 * @param array $old_columns The current columns to edit (skip when saving).
	 * @param array $new_columns The list of desired columns.
	 *
	 * @return array The updated columns list.
	 */
	public function _edit_columns( $old_columns, $new_columns ) {
		$columns = array();

		// Go through the columns, and add/modify as needed
		foreach ( $new_columns as $column_id => $args ) {
			// Handle non-associative entries
			if ( is_int( $column_id ) ) {
				$column_id = $args;
				$args = array();
			}

			if ( isset( $old_columns[ $column_id ] ) ) { // Use the existing column and edit the title if set
				// Old value
				$title = $old_columns[ $column_id ];

				if ( $args ) {
					// Replace the title if set
					if ( is_array( $args ) && isset( $args['title'] ) ){
						$title = $args['title'];
					} elseif ( is_string( $args ) ) {
						$title = $args;
					}
				}

				$columns[ $column_id ] = $title;
			} elseif ( is_array( $args ) ) { // Add a new column, but only if it has arguments
				// Default title is legible version of id
				$title = make_legible( $id );

				if ( isset( $args['title'] ) ) {
					$title = $args['title'];
				}

				$columns[ $column_id ] = $title;
			}
		}

		// Return the modified columns
		return $columns;
	}

	/**
	 * Do whatever output is needed for the current column.
	 *
	 * @since 1.8.0
	 *
	 * @param array $column_id The current column to handle (skip when saving).
	 * @param int   $post_id   The ID of the current post (skip when saving).
	 * @param array $columns   The custom columns to work with.
	 *
	 * @return array The updated columns list.
	 */
	public function _do_columns( $column_id, $post_id, $columns ) {
		// Get the arguments for the current column
		if ( isset( $columns[ $column_id ] ) ) {
			$args = $columns[ $column_id ];
		} else{
			// Nothing to do
			return;
		}

		// Skip non-associative and arg-less entries
		if ( is_int( $column_id ) || empty( $args ) || ! is_array( $args ) ) return;

		// First, check for an all-purpose output callback
		if ( isset( $args['output'] ) && is_callable( $args['output'] ) ) {
			/**
			 * Output the content for the column
			 *
			 * @since 1.8.0
			 *
			 * @param int    $post_id The id of the post to output for.
			 * @param string $column_id The id of the column to output for.
			 */
			call_user_func( $args['output'], $post_id, $column_id );
		} elseif ( $args['meta_key'] ) {
			// Output the meta_value for this posts meta_key
			echo get_post_meta( $post_id, $args['meta_key'], true );
		} elseif ( $args['post_field'] ) {
			// Output the post_filed for this post
			echo get_post( $post_id )->{ $args['meta_key'] };
		}

		// No output otherwise
	}

	// =========================
	// !Theme Setups
	// =========================

	/**
	 * Proccess the theme setups; registering the various features and supports.
	 *
	 * @since 1.1.0 'menus' is now 'nav_menus', $defaults['sidebars'] is now $defaults['sidebar'].
	 * @since 1.0.0
	 *
	 * @param array &$configs Optional The features and supports for the theme.
	 */
	public function run_theme_setups( $configs = null ) {
		// If no $configs is passed, load the locally stored ones.
		if ( is_null( $configs ) ) {
			$configs = &$this->configs;
		}

		// Theme supports
		if ( isset( $configs['supports'] ) ) {
			csv_array_ref( $configs['supports'] );
			foreach ( $configs['supports'] as $key => $value ) {
				make_associative( $key, $value );
				// Pass just $key or $key & $value depending on $value
				if ( empty( $value ) ) {
					add_theme_support( $key );
				} else {
					add_theme_support( $key, $value );
				}
			}
		}

		// Custom image sizes(s)
		if ( isset( $configs['image_sizes'] ) ) {
			foreach( $configs['image_sizes'] as $name => $specs ) {
				list( $width, $height, $crop ) = $specs + array( 0, 0, false );
				add_image_size( $name, $width, $height, $crop );
			}
		}

		// Editor style(s)
		if ( isset( $configs['editor_style'] ) ) {
			add_editor_style( $configs['editor_style'] );
		}

		// Navigation menus
		if ( isset( $configs['nav_menus'] ) ) {
			register_nav_menus( $configs['nav_menus'] );
		}

		// Sidebars
		if ( isset( $configs['sidebars'] ) ) {
			$defaults = null;

			// Prep defaults, if present
			if ( isset( $this->defaults['sidebar'] ) ) {
				$defaults = $this->defaults['sidebar'];
				$find = '/.*<(\w+).*>.*/';
				$replace = '$1';

				if ( isset( $defaults['before_widget'] ) && ! isset( $defaults['after_widget'] ) ) {
					$defaults['after_widget'] = '</' . preg_replace( $find, $replace, $defaults['before_widget'] ) . '>';
				}
				if ( isset( $defaults['before_title'] ) && ! isset( $defaults['after_title'] ) ) {
					$defaults['after_title'] = '</' . preg_replace( $find, $replace, $defaults['before_title'] ) . '>';
				}
			}

			foreach ( $configs['sidebars'] as $id => $args ) {
				make_associative( $id, $args );

				// If just a string is passed for $args,
				// assume it's to be the name of the sidebar
				if ( is_string( $args ) ) {
					$args = array(
						'name' => $args,
					);
				}
				// If no args are passed,
				// Auto create name from $id
				elseif ( is_array( $args ) && empty( $args ) ) {
					$args = array(
						'name' => make_legible( $id ),
					);
				}

				$args['id'] = $id;

				// Process args with defaults, it present
				if ( $defaults ) {
					// Set default before_widget if default exists
					if ( ! isset( $args['before_widget'] ) && isset( $defaults['before_widget'] ) ) {
						$args['before_widget'] = $defaults['before_widget'];
					}

					// Set default before_title if default exists
					if ( ! isset( $args['before_title'] ) && isset( $defaults['before_title'] ) ) {
						$args['before_title'] = $defaults['before_title'];
					}

					// Auto set after_widget if not set but before_widget is
					if ( isset( $args['before_widget'] ) && ! isset( $args['after_widget'] ) ) {
						$args['after_widget'] = $defaults['after_widget'];
					}

					// Auto set after_title if not set but before_title is
					if ( isset( $args['before_title'] ) && ! isset( $args['after_title'] ) ) {
						$args['after_title'] = $defaults['after_title'];
					}
				}

				// Finally, register the sidebar
				register_sidebar( $args );
			}
		}
	}

	// =========================
	// !MCE Setups
	// =========================

	/**
	 * Add buttons for MCE.
	 *
	 * This simply adds them; there must be associated JavaScript to display them.
	 *
	 * @since 1.0.0
	 *
	 * @param array        $buttons        The currently enabled buttons. (skip when saving)
	 * @param array|string $buttons_to_add A list of buttons to enable.
	 * @param int          $position       Optional An exact position to inser the button.
	 */
	public function _add_mce_buttons( $buttons, $buttons_to_add, $position = null ) {
		csv_array_ref( $buttons_to_add );

		// Go through each button and remove them if they are already present;
		// We'll be re-adding them in the new desired position.
		foreach ( $buttons_to_add as $button ) {
			if ( ( $i = array_search( $button, $buttons ) ) && false !== $i ) {
				unset( $buttons[ $i ] );
			}
		}

		if ( is_int( $position ) ) {
			// Insert at desired position
			array_splice( $buttons, $position, 0, $buttons_to_add );
		} else {
			// Just append to the end
			$buttons = array_merge( $buttons, $buttons_to_add );
		}

		return $buttons;
	}

	/**
	 * Add buttons for MCE (specifically the second row).
	 *
	 * @see Setup::_enable_mce_buttons()
	 */
	public function _add_mce_buttons_2( $buttons, $buttons_to_add, $position = null ) {
		return $this->_add_mce_buttons( $buttons, $buttons_to_add, $position );
	}

	/**
	 * Add buttons for MCE (specifically the third row).
	 *
	 * @see Setup::_enable_mce_buttons()
	 */
	public function _add_mce_buttons_3( $buttons, $buttons_to_add, $position = null ) {
		return $this->_add_mce_buttons( $buttons, $buttons_to_add, $position );
	}

	/**
	 * Add a plugin to the MCE plugins list.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $plugins The current list of plugins. (skip when saving)
	 * @param string $plugin  The slug/key of the plugin to add.
	 * @param string $src     The URL to the javascript file for the plugin.
	 *
	 * @return $plugins The modified plugins array.
	 */
	public function _add_mce_plugin( $plugins, $plugin, $src ) {
		$plugins[ $plugin ] = $src;
		return $plugins;
	}

	/**
	 * Register an MCE Plugin/Button
	 *
	 * @since 1.2.0 Removed separator before each button.
	 * @since 1.0.0
	 *
	 * @param string $plugin The slug of the MCE plugin to be registered.
	 * @param string $src    The URL of the plugin.
	 * @param string $button Optional the ID of the button to be added to the toolbar.
	 * @param int    $row    Optional the row number of the toolbar (1, 2, or 3) to add the button to.
	 */
	public function register_mce_plugin( $plugin, $src, $button = true, $row = 1 ) {
		// Skip if the current use can't edit posts/pages
		if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Only bother if rich editing is true
		if ( get_user_option( 'rich_editing' ) == 'true' ) {
			if ( $button ) {
				// If $button is literal true, make it the same as the plugin slug
				if ( true == $button ) {
					$button = $plugin;
				}

				// Add the button to the appropriate row
				$method = 'add_mce_buttons' . ( $row > 1 ? "_$row" : '' );
				$this->$method( $button );
			}

			$this->add_mce_plugin( $plugin, $src );
		}
	}

	/**
	 * Register multiple MCE Plugins/Buttons.
	 *
	 * @since 1.2.0 Revised $args logic and flexibility.
	 * @since 1.0.0
	 *
	 * @param array $plugins The list of MCE plugins to be registered.
	 */
	public function register_mce_plugins( $plugins ) {
		if ( is_array( $plugins ) ) {
			foreach( $plugins as $plugin => $args ) {
				$src = $button = $row = null;

				// $args can be a source string or an arguments array
				if ( ! is_array( $args ) ) {
					$button = true; // By default, any plugin will have a button by the same name
					$src = $args;
				} elseif ( is_assoc( $args ) ) {
					extract( $args );
				} else {
					list( $src, $button, $row ) = fill_array( $args, 3 );
				}

				// Default value for row
				if ( ! $row ) $row = 1;

				$this->register_mce_plugin( $plugin, $src, $button, $row );
			}
		}
	}

	/**
	 * Helper; add style formats to the MCE settings.
	 *
	 * @since 1.0.0
	 *
	 * @param array $settings The TinyMCE settings array to alter. (skip when saving)
	 * @param array $styles   An array of styles to register.
	 */
	public function _add_mce_style_formats( $settings, $styles ) {
		$style_formats = array();

		if ( isset( $settings['style_formats'] ) ) {
			$style_formats = json_decode( $settings['style_formats'] );
		}

		$style_formats = array_merge( $style_formats, $styles );

		$settings['style_formats'] = json_encode( $style_formats );

		return $settings;
	}

	/**
	 * Register custom styles for MCE.
	 *
	 * @since 1.0.0
	 *
	 * @param array $styles An array of styles to register.
	 */
	public function register_mce_styles( $styles ) {
		// Add the styleselect item to the second row of buttons.
		$this->add_mce_buttons_2( 'styleselect', 1 );

		// Actually add the styles
		$this->add_mce_style_formats( $styles );
	}

	// =========================
	// !Settings Setups
	// =========================

	/**
	 * Register and build a setting.
	 *
	 * @since 1.8.0 Added use of Tools::build_settings_field().
	 * @since 1.7.1 Added use of Setup::maybe_load_media_manager().
	 * @since 1.4.0 Added 'source' to build_fields $args.
	 * @since 1.3.0 Added 'wrap' to build_fields $args.
	 * @since 1.1.0 Dropped stupid $args['fields'] processing.
	 * @since 1.0.0
	 *
	 * @param string       $setting The id of the setting to register.
	 * @param array|string $args    Optional The setting configuration (string accepted for name or html).
	 * @param string       $group   Optional The id of the group this setting belongs to.
	 * @param string       $page    Optional The id of the page this setting belongs to.
	 */
	public function _register_setting( $setting, $args = null, $section = null, $page = null ) {
		make_associative( $setting, $args );

		// Default arguments
		$default_args = array(
			'title'    => make_legible( $setting ),
			'sanitize' => null,
		);

		// Default $section to 'default'
		if ( is_null( $section ) ) {
			$section = 'default';
		}

		// Default $page to 'general'
		if ( is_null( $page ) ) {
			$page = 'general';
		}

		// Parse the arguments with the defaults
		$args = wp_parse_args( $args, $default_args );

		// Build the $fields array based on provided data
		if ( isset( $args['field'] ) ) {
			// A single field is provided, the name of the setting is also the name of the field

			// Default the wrap_with_label argument to false if applicable
			if ( ! is_callable( $args['field'] ) && is_array( $args['field'] ) && ! isset( $args['field']['wrap_with_label'] ) ) {
				// Auto set wrap_with_label to false if not present already
				$args['field']['wrap_with_label'] = false;
			}

			// Create a fields entry
			$args['fields'] = array(
				$setting => $args['field'],
			);
		} elseif ( ! isset( $args['fields'] ) ) {
			// Assume $args is the literal arguments for the field,
			// create a fields entry, default wrap_with_label to false

			if ( ! isset( $args['wrap_with_label'] ) ) {
				$args['wrap_with_label'] = false;
			}

			$args['fields'] = array(
				$setting => $args,
			);
		}

		// Check if media_manager helper needs to be loaded
		self::maybe_load_media_manager( $args['fields'] );

		// Set the current arguments
		$_args = array(
			'setting' => $setting,
			'fields' => $args['fields'],
			'__extract',
		);

		// Register the setting
		register_setting( $page, $setting, $args['sanitize'] );

		// Add the field
		add_settings_field(
			$setting,
			'<label for="' . $setting . '">' . $args['title'] . '</label>',
			array( __NAMESPACE__ . '\Tools', 'build_settings_field' ),
			$page,
			$section,
			$_args
		);
	}

	/**
	 * Register multiple settings.
	 *
	 * @since 1.0.0
	 * @uses Setup::register_setting()
	 *
	 * @param array  $settings An array of settings to register.
	 * @param string $group    Optional The id of the group this setting belongs to.
	 * @param string $page     Optional The id of the page this setting belongs to.
	 */
	public function _register_settings( $settings, $section = null, $page = null ) {
		// If page is provided, rebuild $settings to be in $page => $settings format
		if ( $page ) {
			$settings = array(
				$page => $settings,
			);
		}

		// $settings should now be in page => settings format

		if ( is_array( $settings ) ) {
			foreach ( $settings as $page => $_settings ) {
				foreach ( $_settings as $id => $setting ) {
					$this->_register_setting( $id, $setting, $section, $page );
				}
			}
		}
	}

	// =========================
	// !Menu Pages Setups
	// =========================

	/**
	 * Register and build a page.
	 *
	 * @since 1.4.1 Fixed child page registration.
	 * @since 1.2.0 Added child page registration from other methods.
	 * @since 1.0.0
	 *
	 * @uses Setup::register_page_settings()
	 * @uses Setup::add_page_to_menu()
	 *
	 * @param string $setting The id of the page to register.
	 * @param array  $args    The page configuration.
	 * @param string $parent  Optional The slug of the parent page.
	 */
	public function register_page( $page, $args, $parent = null ) {
		// Add settings for the page
		$this->register_page_settings( $page, $args );

		// Now, add this page to the admin menu
		$this->add_page_to_menu( $page, $args, $parent );

		// Run through any submenus in this page and set them up
		if ( isset( $args['children'] ) ) {
			$this->register_pages( $args['children'], $page );
		}
	}

	/**
	 * Register multiple pages.
	 *
	 * @since 1.0.0
	 *
	 * @uses Setup::register_page()
	 *
	 * @param array  $settings An array of pages to register.
	 * @param string $parent   Optional The id of the page these are children of.
	 */
	public function register_pages( $pages, $parent = null ) {
		foreach ( $pages as $page => $args ) {
			$this->register_page( $page, $args, $parent );
		}
	}

	/**
	 * Register the settings for this page.
	 *
	 * @since 1.6.0 Allow registering just settings, no fields, in name => sanitize format.
	 * @since 1.3.0 Reordered so bare fields go before sections.
	 * @since 1.2.0 Moved child page registration to Setup::register_page().
	 * @since 1.0.0
	 *
	 * @uses Setup::register_settings()
	 *
	 * @param string $setting The id of the page to register.
	 * @param array  $args    The page configuration.
	 */
	public function _register_page_settings( $page, $args ) {
		// Register any settings (not fields).
		if ( isset( $args['settings'] ) ) {
			foreach ( $args['settings'] as $setting => $sanitize ) {
				make_associative( $setting, $sanitize, null );

				// Register the setting with the sanitize callback
				register_setting( $page, $setting, $sanitize );
			}
		}

		// Run through any bare fields (assume belonging to default, which will be added automatically)
		if ( isset( $args['fields'] ) ) {
			add_settings_section( 'default', null, null, $page );
			$this->_register_settings( $args['fields'], 'default', $page );
		}

		// Run through each section, add them, and register the settings for them
		if ( isset( $args['sections'] ) ) {
			foreach ( $args['sections'] as $id => $section ) {
				add_settings_section( $id, $section['title'], $section['callback'], $page );
				if ( isset( $section['fields'] ) ) {
					$this->_register_settings( $section['fields'], $id, $page );
				}
			}
		}
	}

	/**
	 * Register the settings for this page.
	 *
	 * @since 1.3.3 Fixed submenu registration for custom post types.
	 * @since 1.3.0 Reworked processing, now supports passing a file and no callback/function.
	 * @since 1.2.0 Moved child page registration to Setup::register_page().
	 * @since 1.1.0 'submenus' is now 'children'.
	 * @since 1.0.0
	 *
	 * @param string $setting The id of the page to register.
	 * @param array  $args    The page configuration.
	 * @param string $parent  Optional The parent the page belongs to.
	 */
	public function _add_page_to_menu( $page, $args, $parent = null ) {
		$default_args = array(
			'type'       => 'menu',
			'title'      => make_legible( $page ),
			'slug'       => $page,
			'capability' => 'manage_options',
			'callback'   => array( __NAMESPACE__ . '\Callbacks', 'default_admin_page' ),
			'icon'       => '',
			'position'   => null,
		);

		// Parse the arguments with the defaults
		$args = wp_parse_args( $args, $default_args );

		// Set the menu and page titles if not set, based on the title and menu title, respectively
		if ( ! isset( $args['menu_title'] ) ) {
			$args['menu_title'] = $args['title'];
		}
		if ( ! isset( $args['page_title'] ) ) {
			$args['page_title'] = $args['menu_title'];
		}

		// Override the parent if provided
		if ( ! empty( $args['parent'] ) ) {
			$parent = $args['parent'];
		}

		// Defaut the type to menu if not a level type
		$levels = array( 'object', 'utility' );
		if ( ! in_array( $args['type'], $levels ) ) {
			$args['type'] == 'menu';
		}

		// Extract $args
		extract( $args, EXTR_SKIP );

		// Determine function name and arguments...
		if ( empty( $parent ) ) {
			// Top level page, call add_{type}_page
			$function = 'add_' . $type . '_page';
			$func_args = array( $page_title, $menu_title, $capability, $slug, $callback, $icon );

			// Add $position for add_menu_page
			if ( $type == 'menu' ) {
				$func_args[] = $position;
			}
		} else {
			// Submenu page, see if it's one of the builtin menus
			$builtin = array( 'dashboard', 'posts', 'media', 'links', 'pages', 'comments', 'theme', 'plugins', 'users', 'management', 'options' );
			if ( in_array( $parent, $builtin ) ) {
				$function = 'add_' . $parent . '_page';
				$func_args = array( $page_title, $menu_title, $capability, $slug, $callback );
			} else {
				$function = 'add_submenu_page';

				if ( post_type_exists( $parent ) ) {
					if ( $parent == 'post' ) {
						$parent = 'edit.php';
					} else {
						$parent = "edit.php?post_type=$parent";
					}
				}

				$func_args = array( $parent, $page_title, $menu_title, $capability, $slug, $callback );
			}
		}

		// Call the determined function with the determined arguments
		call_user_func_array( $function, $func_args );
	}

	// =========================
	// !Feature Setups
	// =========================

	/**
	 * Setup the requested feature.
	 *
	 * @since 1.8.0 Added use of add_theme_support for plugin capabilities.
	 * @since 1.0.0
	 *
	 * @param string $feature The slug of the taxonomy to register.
	 * @param array  $args     The arguments for registration.
	 */
	public function _setup_feature( $feature, $args ) {
		// Call the appropriate setup method.

		$method = "setup_{$feature}";
		if ( method_exists( $this, $method ) ) {
			$this->$method( $args );
		}

		// Also register it as a theme support for plugin purposes
		add_theme_support( "quickstart-$feature" );
	}

	/**
	 * Sets up the requested features.
	 *
	 * Simply loops through and calls Setup::_register_feature().
	 *
	 * @since 1.6.0 Handle features added numerically via run_content_setups().
	 * @since 1.0.0
	 *
	 * @param array $feature The list of features to register.
	 */
	public function _setup_features( array $features ) {
		foreach ( $features as $feature => $args ) {
			if ( ! make_associative( $feature, $args ) ) {
				// Feature was added numerically, assume id, args format.
				$feature = $args['id'];
				$args = $args['args'];
			}
			$this->_setup_feature( $feature, $args );
		}
	}

	// =========================
	// !Feature: Order Manager
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
			$callback = array( $this, 'save_menu_order' );
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
			$this->register_page( "$post_type-order", array(
				'title'      => sprintf( __( '%s Order' ), make_legible( $post_type ) ),
				'capability' => get_post_type_object( $post_type )->cap->edit_posts,
				'callback'   => array( __NAMESPACE__ . '\Callbacks', 'menu_order_admin_page' ),
			), $post_type );
		}
	}

	/**
	 * Default save callback for order manager.
	 *
	 * @since 1.0.0
	 */
	public function save_menu_order() {
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

	// =========================
	// !Feature: Custom Index Pages
	// =========================

	/**
	 * Setup index page setting/hook for certain post types.
	 *
	 * @since 1.8.0 Restructured to use a hooks once for all post_types.
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

		// Make sure the index helper is loaded
		Tools::load_helpers( 'index' );

		foreach ( $post_types as $post_type ) {
			// Make sure the post type is registered
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			if ( is_admin() ) {
				$option = "page_for_{$post_type}_posts";

				// Register the setting on the backend
				$this->register_setting( $option , array(
					'title' => sprintf( __( 'Page for %s' ) , get_post_type_object( $post_type )->labels->name ),
					'field' => function( $value ) use ( $post_type ) {
						wp_dropdown_pages( array(
							'name'              => $option,
							'echo'              => 1,
							'show_option_none'  => __( '&mdash; Select &mdash;' ),
							'option_none_value' => '0',
							'selected'          => $value,
						) );
					}
				), 'default', 'reading' );
			}
		}

		// Setup the frontend hooks if needed
		if ( ! is_admin() ) {
			// Build the array of available index pages to use
			$index_pages = array();
			foreach ( $post_types as $post_type ) {
				$index_pages[ $post_type ] = get_index( $post_type );
			}

			// Add the query/link/title hooks on the frontend
			$this->index_page_query( $index_pages );
			$this->index_page_link( $index_pages );
			$this->index_page_title_part( $index_pages );
		}
	}

	/**
	 * Check if the page is a custom post type's index page.
	 *
	 * @since 1.8.0 Restructured to handle all post_types at once.
	 * @since 1.6.0
	 *
	 * @param WP_Query $query       The query object (skip when saving).
	 * @param string   $index_pages An associative array of post type index pages.
	 */
	protected function _index_page_query( $query, $index_pages ) {
		$qv =& $query->query_vars;

		// Make sure this is a page
		if ( '' != $qv['pagename'] ) {
			// Check if this page is a post type index page
			$post_type = array_search( $query->queried_object_id, $index_pages );
			if ( $post_type !== false ) {
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
	 * Rewrite the post type archive link to be that of the index page.
	 *
	 * @since 1.8.0
	 *
	 * @param string $link      The original link.
	 * @param string $post_type The post type this link is for.
	 *
	 * @return string The new link.
	 */
	protected function _index_page_link( $link, $post_type ) {
		if ( $index = get_index( $post_type ) ) {
			$link = get_permalink( $index );
		}
		return $link;
	}

	/**
	 * Modify the title to display the index page's title.
	 *
	 * @since 1.8.0 Restructured to handle all post_types at once.
	 * @since 1.6.0
	 *
	 * @param string|array $title       The page title or parts (skip when saving).
	 * @param string       $index_pages An associative array of post type index pages.
	 *
	 * @return string|array The modified title.
	 */
	protected function _index_page_title_part( $title, $index_pages ) {
		// Skip if not an archive
		if ( ! is_post_type_archive() ) {
			return $title;
		}

		// Get the queried post type
		$post_type = get_query_var( 'post_type' );

		// Abort if not a post type with an index
		if ( ! isset( $index_pages[ $post_type ] ) ) {
			return $title;
		}

		// Get the index page for this post type
		$index_page = $index_pages[ $post_type ];

		// Replace the first part of the title with the index page's title
		$title[0] = get_the_title( $index_page );

		return $title;
	}
}
