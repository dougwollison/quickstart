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
	 * A list of internal methods and their hooks configurations are.
	 *
	 * @since 1.9.0 Fixed run_theme_setups hook, added init hooks for explicitness.
	 * @since 1.8.0 Added hooks from Setup/Feature merge.
	 * @since 1.1.4 Added regster_page_setting(s) entries.
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $method_hooks = array(
		// Content Hooks
		'register_post_type'     => array( 'init', 10, 0 ),
		'register_post_types'    => array( 'init', 10, 0 ),
		'register_taxonomy'      => array( 'init', 10, 0 ),
		'register_taxonomies'    => array( 'init', 10, 0 ),

		// Theme Hooks
		'run_theme_setups'       => array( 'after_setup_theme', 10, 0 ),
		'register_sidebars'      => array( 'widgets_init', 10, 0 ),

		// Admin Hooks
		'edit_columns'           => array( 'admin_init', 10, 0 ),
		'do_columns'             => array( 'admin_init', 10, 0 ),

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
		'register_page_settings' => array( 'admin_init', 10, 0 ),
		'add_page_to_menu'       => array( 'admin_menu', 0 ),

		// Feature Hooks
		'order_manager_pages'    => array( 'init', 10, 0 ),
		'order_manager_save'     => array( 'admin_init', 10, 0 ),
		'index_page_settings'    => array( 'init', 10, 0 ),
		'index_page_post_states' => array( 'display_post_states', 10, 2 ),
		'index_page_request'     => array( 'parse_request', 0, 1 ),
		'index_page_query'       => array( 'parse_query', 0, 1 ),
		'index_page_link'        => array( 'post_type_archive_link', 10, 2 ),
		'index_page_title_part'  => array( 'wp_title_parts', 10, 1 ),
		'index_page_admin_bar'   => array( 'admin_bar_menu', 85, 1 ),
		'parent_filtering_input' => array( 'restrict_manage_posts', 10, 0 ),
		'section_manager_ajax'   => array( 'wp_ajax_qs-new_section', 10, 0 ),
	);

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

	// =========================
	// !Main Setup Function
	// =========================

	/**
	 * Processes configuration options and sets up necessary hooks/callbacks.
	 *
	 * @since 1.11.0 Remove helper enqueue; relocated to Hooks include.
	 * @since 1.9.2  Added jquery-ui-sortable dependency for qs-helpers script.
	 * @since 1.9.0  Moving register_pages() to new run_admin_setups().
	 * @since 1.8.0  Added quick-enqueue handling.
	 * @since 1.4.0  Added helpers css/js backend enqueue.
	 * @since 1.1.0  Added tinymce key; mce is deprecated.
	 * @since 1.0.0
	 *
	 * @param array $configs  The theme configuration options.
	 * @param array $defaults Optional The default values.
	 */
	public function __construct( array $configs, $defaults = array() ) {
		// Set default entries for the configuration array
		$configs = array_merge( array(
			// Content stuff
			'post_types'    => array(), // Custom post types
			'taxonomies'    => array(), // Custom taxonomies
			'meta_boxes'    => array(), // Meta boxes

			// Theme stuff
			'supports'      => array(), // Theme supports

			// Admin stuff
			'settings'      => array(), // Admin settings
			'pages'         => array(), // Admin pages
			'features'      => array(), // Custom QuickStart features
			'columns'       => array(), // Custom manager columns
			'user_meta'     => array(), // Custom user meta fields

			// Miscellaneous stuff
			'hide'          => null,    // Default things to hide/disable
			'helpers'       => null,    // QuickStart helpers to load
			'relabel_posts' => null,    // Rename the Posts post type
			'shortcodes'    => null,    // Custom shortcodes to register
			'enqueue'       => array(), // Styles/scripts to eneuque
		), $configs );

		// Store the configuration array
		$this->configs = $configs;

		// Merge default_args with passed defaults
		$this->defaults = wp_parse_args( $defaults, $this->defaults );

		// Run the content setups
		$this->run_content_setups();

		// Run the theme setups
		$this->run_theme_setups();

		// Run the admin setups, ONLY if in the backend
		if ( ! is_frontend() ) {
			$this->run_admin_setups();
		}

		// Run the miscellaneous setups
		$this->run_misc_setups();
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
				'name'          => _x( $plural, 'post type general name' ),
				'singular_name' => _x( $singular, 'post type singular name' ),
				'menu_name'     => _x( $menu_name, 'post type menu name' ),
				'add_new'       => _x( 'Add New', $object ),
			);

			$template = wp_parse_args( $template, array(
				'add_new_item'          => 'Add New %S',
				'edit_item'             => 'Edit %S',
				'new_item'              => 'New %S',
				'view_item'             => 'View %S',
				'all_items'             => 'All %P',
				'search_items'          => 'Search %P',
				'parent_item_colon'     => 'Parent %S:',
				'not_found'             => 'No %p found.',
				'items_list_navigation' => '%P list navigation',
				'items_list'            => '%P list',
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
	 * @since 1.8.1 Added 'media' to dependants list.
	 * @since 1.7.1
	 *
	 * @param array $fields The list of fields to check through.
	 */
	protected static function maybe_load_media_manager( $fields ) {
		// Skip if media manager already loaded
		if ( defined( 'QS_LOADED_MEDIA_MANAGER' ) && QS_LOADED_MEDIA_MANAGER ) {
			return;
		}

		// Also skip if fields isn't an array
		if ( ! is_array( $fields ) ) {
			return;
		}

		foreach ( $fields as $field ) {
			$dependants = array( 'media', 'addfile', 'editgallery', 'setimage' );
			if ( is_array( $field ) && isset( $field['type'] ) && in_array( $field['type'], $dependants ) ) {
				// Make sure the media_manager helper is loaded
				Tools::load_helpers( 'media_manager' );
				break;
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
	 * Proccess the content setups; extracting any local setups defined
	 * within each post_type configuration.
	 *
	 * @since 1.13.0 Post types supports as boolean now allowed.
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand(),
	 *               Moved meta box registration to run_admin_setups(), added conditions
	 *               To no process meta boxes, pages or columns unless in the admin.
	 * @since 1.9.0  Now protected, no longer accepts external $configs argument.
	 *				 Also added handling of pages passed in the post type.
	 *				 Also moved setup_features and setup_columns to new run_admin_setups().
	 * @since 1.8.0  Tweaked check for post-thumbnails support.
	 * @since 1.6.0  Add meta boxes/features numerically to prevent overwriting.
	 * @since 1.3.3  Removed callback check on feature args.
	 * @since 1.2.0  Added check for dumb meta box setup
	 * @since 1.0.0
	 */
	protected function run_content_setups() {
		// Load the configurations array
		$configs = &$this->configs;

		// Make sure supports is an array
		csv_array_ref( $configs['supports'] );

		// Loop through each post_type, check for supports, taxonomies or meta_boxes
		foreach ( $configs['post_types'] as $post_type => &$pt_args ) {
			make_associative( $post_type, $pt_args );

			// Handle any shorthand in this post type
			handle_shorthand( 'post_type', $meta_box, $args );

			// Make sure supports is an array or boolean
			if ( ! is_bool( $pt_args['supports'] ) ) {
				csv_array_ref( $pt_args['supports'] );
			}

			// Check if this post type uses thumbnails, and
			// make sure the theme supports includes it
			if ( is_array( $pt_args['supports'] ) && in_array( 'thumbnail', $pt_args['supports'] ) && ! in_array( 'post-thumbnails', $configs['supports'] ) && ! isset( $config['supports']['post-thumbnails'] ) ) {
				$configs['supports'][] = 'post-thumbnails';
			}

			// Check for taxonomies to register for the post type
			if ( isset( $pt_args['taxonomies'] ) ) {
				// Loop through each taxonomy, move it to $taxonomies if not registered yet
				foreach ( $pt_args['taxonomies'] as $taxonomy => $tx_args ) {
					// Fix if dumb taxonomy was passed (numerically, not associatively)
					make_associative( $taxonomy, $tx_args );

					// Handle any shorthand in this taxonomy
					handle_shorthand( 'taxonomy', $taxonomy, $args );

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
				unset( $pt_args['taxonomies'] );
			}

			// Check for features to register for the post type
			if ( isset( $pt_args['features'] ) ) {
				csv_array_ref( $pt_args['features'] );
				foreach ( $pt_args['features'] as $feature => $ft_args ) {
					// Fix if dumb feature was passed (numerically, not associatively)
					make_associative( $feature, $ft_args );

					// Add this post type to the post_types argument to this feature
					$ft_args['post_type'] = array( $post_type );

					// Add this feauture to features list
					$configs['features'][] = array(
						'id' => $feature,
						'args' => $ft_args,
					);
					//and remove from this post type
					unset( $pt_args['features'][ $feature ] );
				}
				unset( $pt_args['features'] );
			}

			// Stop here if not in the admin
			if ( is_frontend() ) {
				continue;
			}

			// Check for meta boxes to register for the post type
			if ( isset( $pt_args['meta_boxes'] ) ) {
				foreach ( $pt_args['meta_boxes'] as $meta_box => $mb_args ) {
					// Fix if dumb meta box was passed (numerically, not associatively)
					make_associative( $meta_box, $mb_args );

					// Handle any shorthand in this meta box
					handle_shorthand( 'meta_box', $taxonomy, $args );

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
				unset( $pt_args['meta_boxes'] );
			}

			// Check for pages to register under this post type
			if ( isset( $pt_args['pages'] ) ) {
				csv_array_ref( $pt_args['pages'] );
				foreach ( $pt_args['pages'] as $page => $pg_args ) {
					// Fix if dumb page was passed (numerically, not associatively)
					make_associative( $page, $pg_args );

					// Set the paren to this post type
					$pg_args['parent'] = $post_type;

					// Add this page to pages list
					$configs['pages'][ $page ] = $pg_args;

					//and remove from this post type
					unset( $pt_args['pages'][ $page ] );
				}
				unset( $pt_args['pages'] );
			}

			// Check for columns to register for the post type
			if ( isset( $pt_args['columns'] ) ) {
				// Add this column for this post type to the columns section of $config
				$configs['columns'][ $post_type ] = $pt_args['columns'];
				unset( $pt_args['columns'] );
			}
		}

		// Loop through each taxonomy, check if meta_fields are being used and load term_meta helper if so
		foreach ( $configs['taxonomies'] as $taxonomy => $tx_args ) {
			if ( isset( $tx_args['meta_fields'] ) ) {
				// Ensure the term meta helper is loaded
				Tools::load_helpers( 'term_meta' );
			}
		}

		// Run the content setups
		$this->register_post_types( $configs['post_types'] ); // Will run during "init"
		$this->register_taxonomies( $configs['taxonomies'] ); // Will run during "init"
	}

	// =========================
	// !- Post Type Setups
	// =========================

	/**
	 * Register the requested post_type.
	 *
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand().
	 * @since 1.10.1 Added day/month/year rewrites to post types with has_archive support.
	 * @since 1.9.0  Now protected.
	 * @since 1.6.0  Modified handling of save_post callback to Tools::post_type_save().
	 * @since 1.2.0  Added use of save argument for general save_post callback.
	 * @since 1.0.0
	 *
	 * @param string $post_type The slug of the post type to register.
	 * @param array  $args      Optional The arguments for registration.
	 */
	protected function _register_post_type( $post_type, array $args = array() ) {
		// Handle any shorthand in this post type
		handle_shorthand( 'post_type', $post_type, $args );

		// Make sure the post type doesn't already exist
		if ( post_type_exists( $post_type ) ) {
			return;
		}

		// Setup the labels if needed
		self::maybe_setup_labels( $post_type, $args, array(
			'new_item'           => 'New %S',
			'not_found_in_trash' => 'No %p found in Trash.',
			'filter_items_list'  => 'Filter %p list',
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

		// If icon is present instead of menu_icon, reassing
		if ( isset( $args['icon'] ) && ! $args['menu_icon'] ) {
			$args['menu_icon'] = $args['icon'];
		}

		// Now, register the post type
		register_post_type( $post_type, $args );

		// If a save hook is passed, register it
		if ( isset( $args['save'] ) ) {
			Tools::post_type_save( $post_type, $args['save'] );
			unset( $args['save'] );
		}

		// Get the registered post type
		$post_type_obj = get_post_type_object( $post_type );

		// Check the show_in_menu argument,
		// and add the post_type_count hook if true
		if ( $post_type_obj->show_in_menu ) {
			Tools::post_type_count( $post_type );
		}

		// Check the has_archive argument,
		// setup day/month/year archive rewrites if true
		if ( $post_type_obj->has_archive ) {
			$slug = $post_type_obj->rewrite['slug'];

			// Add the custom rewrites
			Tools::add_rewrites( array(
				// Add day archive (and pagination)
				$slug . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/page/?([0-9]+)/?$' => 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]&paged=$matches[4]',
				$slug . '/([0-9]{4})/([0-9]{2})/([0-9]{2})/?$' => 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&day=$matches[3]',

				// Add month archive (and pagination)
				$slug . '/([0-9]{4})/([0-9]{2})/page/?([0-9]+)/?$' => 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]&paged=$matches[3]',
				$slug . '/([0-9]{4})/([0-9]{2})/?$' => 'index.php?post_type=' . $post_type . '&year=$matches[1]&monthnum=$matches[2]',

				// Add year archive (and pagination)
				$slug . '/([0-9]{4})/page/?([0-9]+)/?$' => 'index.php?post_type=' . $post_type . '&year=$matches[1]&paged=$matches[2]',
				$slug . '/([0-9]{4})/?$' => 'index.php?post_type=' . $post_type . '&year=$matches[1]',
			) );
		}
	}

	/**
	 * Register the requested post types.
	 *
	 * Simply loops through and calls Setup::_register_post_type().
	 *
	 * @since 1.9.0 Now protected.
	 * @since 1.0.0
	 *
	 * @param array $post_types The list of post types to register.
	 */
	protected function _register_post_types( array $post_types ) {
		foreach ( $post_types as $post_type => $args ) {
			make_associative( $post_type, $args );
			$this->_register_post_type( $post_type, $args );
		}
	}

	// =========================
	// !- Taxonomy Setups
	// =========================

	/**
	 * Register the requested taxonomy.
	 *
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand().
	 * @since 1.10.1 Prevent non-taxonomy args from being passed to register_taxonomy.
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0  Added enforcement of array for for post_type value.
	 * @since 1.6.0  Updated static replacement meta box title based on $multiple.
	 * @since 1.5.0  Added "static" option handling (custom taxonomy meta box).
	 * @since 1.3.1  Removed Tools::taxonomy_count call.
	 * @since 1.0.0
	 *
	 * @param string $taxonomy The slug of the taxonomy to register.
	 * @param array  $args     The arguments for registration.
	 */
	protected function _register_taxonomy( $taxonomy, array $args = array() ) {
		// Handle any shorthand in this taxonomy
		handle_shorthand( 'taxonomy', $taxonomy, $args );

		// Ensure post_type is in array form if set.
		if ( isset( $args['post_type'] ) ) {
			csv_array_ref( $args['post_type'] );
		}

		// Get the post_type, preload, meta_fields, and meta_box_term_query arguments,
		// And remove them so they aren't saved to the taxonomy
		$post_type = $preload = $meta_fields = $meta_box_term_query = array();
		if ( isset( $args['post_type'] ) ) {
			$post_type = csv_array( $args['post_type'] );
			unset( $args['post_type'] );
		}
		if ( isset( $args['preload'] ) ) {
			$preload = $args['preload'];
			unset( $args['preload'] );
		}
		if ( isset( $args['meta_fields'] ) ) {
			$meta_fields = $args['meta_fields'];
			unset( $args['meta_fields'] );
		}
		if ( isset( $args['meta_box_term_query'] ) ) {
			$meta_box_term_query = $args['meta_box_term_query'];
			unset( $args['meta_box_term_query'] );
		}

		// Register the taxonomy if it doesn't exist
		if ( ! taxonomy_exists( $taxonomy ) ) {
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
			register_taxonomy( $taxonomy, $post_type, $args );

			// Proceed with post-registration stuff, provided it was successfully registered.
			if ( ! ( $taxonomy_obj = get_taxonomy( $taxonomy ) ) ) {
				return;
			}

			// Now that it's registered, fetch the resulting show_ui argument,
			// and add the taxonomy_filter hooks if true
			if ( $taxonomy_obj->show_ui ){
				Tools::taxonomy_filter( $taxonomy );
			}

			// Finish setting up the static taxonomy meta box if needed
			if ( $static ) {
				$meta_box_args = array(
					'title'     => ( $multiple ? $taxonomy_obj->labels->name : $taxonomy_obj->labels->singular_name ),
					'post_type' => $taxonomy_obj->object_type,
					'context'   => 'side',
					'priority'  => 'core',
					'name'      => $taxonomy,
					'type'      => $multiple ? 'checklist' : 'select',
					'class'     => 'widefat static-terms',
					'null'      => '&mdash; None &mdash;',
					'taxonomy'  => $taxonomy,
				);

				// Add term_query if metabox_term_query arg is set
				if ( $meta_box_term_query ) {
					$meta_box_args['term_query'] = $meta_box_term_query;
				}

				$this->register_meta_box( "$taxonomy-terms", $meta_box_args );
			}
		} else {
			// Existing taxonomy, check if any additional post types should be attached to it.
			if ( isset( $args['post_type'] ) ) {
				foreach ( (array) $args['post_type'] as $post_type ) {
					register_taxonomy_for_object_type( $taxonomy, $post_type );
				}
			}
		}

		// Now that it's registered, see if there are preloaded terms to add
		if ( $preload && is_array( $preload ) ) {
			$is_assoc = is_assoc( $preload );

			foreach ( $preload as $term => $t_args ) {
				// Check if the term was added numerically on it's own
				if ( ! $is_assoc ) {
					$term = $t_args;
					$t_args = array();
				}

				// If $args is not an array, assume slug => name format
				if ( ! is_array( $t_args ) ) {
					$slug = $term;
					$term = $t_args;
					$t_args = array( 'slug' => $slug );
				}

				// Check if it exists, skip if so
				if ( get_term_by( 'name', $term, $taxonomy ) ) {
					continue;
				}

				// Insert the term
				wp_insert_term( $term, $taxonomy, $t_args );
			}
		}

		// Check if any meta fields were defined, set them up if so
		if ( $meta_fields ) {
			$this->setup_termmeta( $taxonomy, $meta_fields );
		}
	}

	/**
	 * Register the requested taxonomies.
	 *
	 * Simply loops through and calls Setup::_register_taxonomy().
	 *
	 * @since 1.9.0 Now protected.
	 * @since 1.0.0
	 *
	 * @param array $taxonomies The list of taxonomies to register.
	 */
	protected function _register_taxonomies( array $taxonomies ) {
		foreach ( $taxonomies as $taxonomy => $args ) {
			make_associative( $taxonomy, $args );
			$this->_register_taxonomy( $taxonomy, $args );
		}
	}

	// =========================
	// !-- Term Meta Field Setups
	// =========================

	/**
	 * Setup the hooks for adding/saving user meta fields.
	 *
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Made sure term_meta helper is loaded,
	 *               Added use of static::handle_shorthand().
	 *               Swapped arguments order. Now public.
	 * @since 1.10.1
	 *
	 * @param string $taxonomy The taxonomy to hook into.
	 * @param array  $fields   The fields to build/save.
	 */
	public function setup_termmeta( $taxonomy, $fields ) {
		// Make sure term_meta helper is loaded
		Tools::load_helpers( 'term_meta' );

		// Do nothing if not in the admin
		if ( is_frontend() ) {
			return;
		}

		// Handle any shorthand in the fields
		handle_shorthand( 'field', $fields );

		$this->setup_callback( 'build_term_meta_fields', array( $fields ), array( "{$taxonomy}_edit_form_fields", 10, 1 ) );
		$this->setup_callback( 'save_term_meta_fields', array( $fields ), array( "edited_{$taxonomy}", 10, 1 ) );
	}

	/**
	 * Build and print out a term meta field row.
	 *
	 * @since 1.10.0
	 *
	 * @param object $term     The term being edited. (skip when saving)
	 * @param string $field    The id/name meta field to build.
	 * @param array  $args     The arguments for the field.
	 */
	protected function _build_term_meta_field( $term, $field, $args ) {
		Tools::build_field_row( $field, $args, $term, 'term' );
	}

	/**
	 * Build and print a series of term meta fields.
	 *
	 * @since 1.10.0
	 *
	 * @param object $term     The term being edited. (skip when saving)
	 * @param array  $fields   The fields to register.
	 */
	protected function _build_term_meta_fields( $term, $fields ) {
		foreach ( $fields as $field => $args ) {
			make_associative( $field, $args );
			$this->_build_term_meta_field( $term, $field, $args );
		}
	}

	/**
	 * Save the data for a term meta field.
	 *
	 * @since 1.13.0 Added save_single support.
	 * @since 1.10.0
	 *
	 * @param object $term_id  The term being edited. (skip when saving)
	 * @param string $field    The id/name meta field to build.
	 * @param array  $args     The arguments for the field.
	 * @param bool   $_checked Wether or not a check has been taken care of.
	 */
	protected function _save_term_meta_field( $term_id, $field, $args, $_checked = false ) {
		$post_key = $meta_key = $field;

		// Check if an explicit $_POST key is set
		if ( isset( $args['post_key'] ) ) {
			$post_key = $args['post_key'];
		}

		// Check if an explicit meta key is set
		if ( isset( $args['meta_key'] ) ) {
			$meta_key = $args['meta_key'];
		}

		// Save the field if it's been passed
		if ( isset( $_POST[ $post_key ] ) ) {
			// Determine save_single rule
			$save_single = true;
			if ( isset( $args['save_single'] ) ) {
				$save_single = $args['save_single'];
			}

			$value = $_POST[ $post_key ];
			if ( is_array( $value ) && ! $save_single ) {
				delete_term_meta( $term_id, $meta_key );
				foreach ( $value as $val ) {
					add_term_meta( $term_id, $meta_key, $val );
				}
			} else {
				update_term_meta( $term_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Save multiple term meta fields.
	 *
	 * @since 1.10.0
	 *
	 * @param object $term_id The term being saved. (skip when saving)
	 * @param array  $fields  The fields to register.
	 */
	protected function _save_term_meta_fields( $term_id, $fields ) {
		// Check that there's a nonce and that it validates.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-tag_' . $term_id ) ) {
			return;
		}

		foreach ( $fields as $field => $args ) {
			make_associative( $field, $args );
			$this->_save_term_meta_field( $term_id, $field, $args, true );
		}
	}

	// =========================
	// !Theme Setups
	// =========================

	/**
	 * Proccess the theme setups; registering the various features and supports.
	 *
	 * @since 1.13.0 Added version tagging to editor stylesheet URL.
	 * @since 1.11.0 Sidebar registration moved to separate method to run on widgets_init.
	 * @since 1.9.0  Now protected, no longer accepts external $configs argument.
	 * @since 1.1.0  'menus' is now 'nav_menus', $defaults['sidebars'] is now $defaults['sidebar'].
	 * @since 1.0.0
	 */
	protected function _run_theme_setups() {
		// Load the configuration array
		$configs = &$this->configs;

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
			$local = str_replace( get_option( 'siteurl' ), $_SERVER['DOCUMENT_ROOT'], $configs['editor_style'] );
            if ( file_exists( $local ) ) {
                $configs['editor_style'] .= '?v=' . filemtime( $local );
            }
			add_editor_style( $configs['editor_style'] );
		}

		// Navigation menus
		if ( isset( $configs['nav_menus'] ) ) {
			register_nav_menus( $configs['nav_menus'] );
		}

		// Sidebars (handled later)
		$this->register_sidebars();
	}

	/**
	 * Register any sidebars.
	 *
	 * @since 1.11.0
	 */
	protected function _register_sidebars() {
		// Load the configuration array
		$configs = &$this->configs;

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
	// !Admin Setups
	// =========================

	/**
	 * Proccess the admin setups; settings, meta boxes, admin pages, columns, user meta.
	 *
	 * @since 1.11.0 Moved metabox registration here.
	 * @since 1.9.0
	 */
	protected function run_admin_setups() {
		// Load the configuration array
		$configs = &$this->configs;

		$this->register_settings( $configs['settings'] ); // Will run during admin_init

		$this->register_meta_boxes( $configs['meta_boxes'] ); // Will run now and setup various hooks

		$this->setup_pages( $configs['pages'] ); // Will run now and setup various hooks
		$this->setup_columns( $configs['columns'] ); // Will run now and setup various hooks
		$this->setup_usermeta( $configs['user_meta'] ); // Will run now and setup various hooks
	}

	// =========================
	// !- Settings Setups
	// =========================

	/**
	 * Register and build a setting.
	 *
	 * @since 1.13.0 Added support for settings part of an array (e.g. address[country]).
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Now accepts callback and callback_args options,
	 *               Also accepts callback as the $args themselves,
	 *               Added use of static::handle_shorthand().
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0  Added use of Tools::build_settings_field().
	 * @since 1.7.1  Added use of Setup::maybe_load_media_manager().
	 * @since 1.4.0  Added 'source' to build_fields $args.
	 * @since 1.3.0  Added 'wrap' to build_fields $args.
	 * @since 1.1.0  Dropped stupid $args['fields'] processing.
	 * @since 1.0.0
	 *
	 * @param string       $setting The id of the setting to register.
	 * @param array|string $args    Optional The setting configuration (string accepted for name or html).
	 * @param string       $group   Optional The id of the group this setting belongs to.
	 * @param string       $page    Optional The id of the page this setting belongs to.
	 */
	protected function _register_setting( $setting, $args = null, $section = null, $page = null ) {
		make_associative( $setting, $args );

		// Create the setting ID
		$field_id = Form::make_id( $setting );

		// Strip any array keys from setting name
		$setting_name = preg_replace( '/^([^\[]+).*$/', '$1', $setting );

		// Check for $args as callback
		if ( is_callable( $args ) ) {
			$args = array(
				'field' => $args,
			);
		}

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

		// Default callback is the build_settings_field tool.
		$callback = array( __NAMESPACE__ . '\Tools', 'build_settings_field' );

		if ( isset( $args['callback'] ) ) {
			// Override the callback with the passed one
			$callback = $args['callback'];

			// Empty array for the callback args unless ones were passed
			$callback_args = array();
			if ( isset( $args['callback_args'] ) ) {
				$callback_args = $args['callback_args'];
			}
		} else {
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

				// Update field_id if specified
				if ( is_array( $args['field'] ) && isset( $args['field']['id'] ) ) {
					$field_id = $args['field']['id'];
				}
			} elseif ( ! isset( $args['fields'] ) ) {
				// Assume $args is the literal arguments for the field,
				// create a fields entry, default wrap_with_label to false

				if ( ! isset( $args['wrap_with_label'] ) ) {
					$args['wrap_with_label'] = false;
				}

				$args['fields'] = array(
					$setting => $args,
				);

				// Update field_id if specified
				if ( isset( $args['id'] ) ) {
					$field_id = $args['id'];
				}
			}

			// Handle any shorthand in the fields
			handle_shorthand( 'field', $args['fields'] );

			// Check if media_manager helper needs to be loaded
			self::maybe_load_media_manager( $args['fields'] );

			// arguments for build_settings_field
			$callback_args = array(
				'field_id' => $field_id,
				'args' => $args,
			);
		}

		// Register the setting
		register_setting( $page, $setting_name, $args['sanitize'] );

		// Add the field
		add_settings_field(
			$field_id,
			'<label for="qs_field_' . $field_id . '">' . $args['title'] . '</label>',
			$callback,
			$page,
			$section,
			$callback_args
		);
	}

	/**
	 * Register multiple settings.
	 *
	 * @since 1.9.0 Now protected.
	 * @since 1.0.0
	 * @uses Setup::register_setting()
	 *
	 * @param array  $settings An array of settings to register.
	 * @param string $group    Optional The id of the group this setting belongs to.
	 * @param string $page     Optional The id of the page this setting belongs to.
	 */
	protected function _register_settings( $settings, $section = null, $page = null ) {
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
	// !- Meta Box Setups
	// =========================

	/**
	 * Register the requested meta box.
	 *
	 * @since 1.12.2 Removed type hinting on $args.
	 * @since 1.12.0 Updated to use external handle_shorthand(),
	 *               Also made sure fields arg was array before passing through maybe_load_media_manager()
	 *               or looping through to register meta fields.
	 * @since 1.11.1 Made sure fields arg was an array before passing through handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand().
	 *               Now public again.
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0  Added use of register_meta() for sanitizing and protection,
	 *               also added handling of condition setting, modified single
	 *				 field handling to account for callbacks and default to widefat.
	 * @since 1.7.1  Added use of maybe_load_media_manager()
	 * @since 1.3.5  Added use-args-as-field-args handling.
	 * @since 1.3.3  Fixed bug with single field expansion.
	 * @since 1.2.0  Moved dumb meta box logic to Setup::make_dumb_meta_box().
	 * @since 1.0.0
	 *
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments for registration.
	 */
	public function register_meta_box( $meta_box, $args = array() ) {
		// Handle any shorthand in this meta box
		handle_shorthand( 'meta_box', $meta_box, $args );

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

			// Default the class argument to widefat if applicable
			if ( ! is_callable( $args['field'] ) && is_array( $args['field'] ) && ! isset( $args['field']['class'] ) ) {
				// Auto set class to widefat if not present already
				$args['field']['class'] = 'widefat';
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

			// Reset shorthand handling status for the field
			unset( $args['fields'][ $meta_box ]['__handled_shorthand'] );
		}

		$defaults = array(
			'title'     => make_legible( $meta_box ),
			'context'   => 'normal',
			'post_type' => 'post',
		);

		// Prep $defaults and parse the $args
		$this->prep_defaults( 'meta_box', $defaults );
		$args = wp_parse_args( $args, $defaults );

		// Set the priority if it's not already set
		if ( ! isset( $args['priority'] ) ) {
	        // Normal meta boxes should be high priority by default, or default for side ones
	        $args['priority'] = $args['context'] == 'normal' ? 'high' : 'default';
		}

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

		// Handle any shorthand in the fields if it's an array
		if ( is_array( $args['fields'] ) ) {
			handle_shorthand( 'field', $args['fields'] );

			// Check if media_manager helper needs to be loaded
			self::maybe_load_media_manager( $args['fields'] );

			// Register all meta keys found
			foreach ( $args['fields'] as $field => $_args ) {
				// Skip if this field is for a post field or taxonomy
				if ( isset( $_args['post_field'] ) || isset( $_args['taxonomy'] ) ) {
					continue;
				}

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
	 * @since 1.11.0 Now public again.
	 * @since 1.9.0  Now protected.
	 * @since 1.6.0  Handle meta boxes added numerically via run_content_setups().
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
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand(), also added save callback support on individual fields.
	 * @since 1.10.0 Added use of Tools::maybe_prefix_post_field when handling post_field values.
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0  Added use of "save_single" option and support for foo[bar] style fields,
	 *				 $meta_key value now defaults to the same as $post_key.
	 * @since 1.6.0  Restructured for better handling.
	 * @since 1.5.0  Added taxonomy meta box saving.
	 * @since 1.4.2  Added "post_field" update handling.
	 * @since 1.2.0  Moved save check functionality to Tools::save_post_check().
	 * @since 1.1.1  Fixed typo causing $args['fields'] saving to save the $_POST key, not the value.
	 * @since 1.0.0
	 *
	 * @param int    $post_id  The ID of the post being saved. (skip when saving)
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments from registration.
	 */
	protected function _save_meta_box( $post_id, $meta_box, $args ) {
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

			// Handle any shorthand in the fields
			handle_shorthand( 'field', $fields );

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
					// Save callback for this specific field, run and move on
					if ( isset( $settings['save'] ) && is_callable( $settings['save'] ) ) {
						/**
						 * Desired processing to be done when saving this meta box
						 *
						 * @since 1.1.0
						 *
						 * @param int    $post_id  The ID of the post being saved.
						 * @param array  $settings The settings of the field.
						 * @param string $field    The name/ID of the field.
						 */
						call_user_func( $settings['save'], $post_id, $settings, $field );
						continue;
					}

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

						// Prefix the field if necessary
						$field = Tools::maybe_prefix_post_field( $settings['post_field'] );

						// Directly update the entry in the database
						$wpdb->update( $wpdb->posts, array(
							$field => $value,
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
	 * @since 1.9.0 Now protected.
	 * @since 1.6.0 Added qs_metabox_ prefix to meta box id.
	 * @since 1.0.0
	 *
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments from registration.
	 */
	protected function _add_meta_box( $meta_box, $args ) {
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
	// !- Menu Pages Setups
	// =========================

	/**
	 * Setup an admin page, registering settings and adding to the menu.
	 *
	 * @since 1.11.0 Now public again.
	 * @since 1.9.0  Now protected. Renamed from register_page().
	 * @since 1.4.1  Fixed child page registration.
	 * @since 1.2.0  Added child page registration from other methods.
	 * @since 1.0.0
	 *
	 * @uses Setup::register_page_settings()
	 * @uses Setup::add_page_to_menu()
	 *
	 * @param string $setting The id of the page to register.
	 * @param array  $args    The page configuration.
	 * @param string $parent  Optional The slug of the parent page.
	 */
	public function setup_page( $page, $args, $parent = null ) {
		// Add settings for the page
		$this->register_page_settings( $page, $args );

		// Now, add this page to the admin menu
		$this->add_page_to_menu( $page, $args, $parent );

		// Run through any submenus in this page and set them up
		if ( isset( $args['children'] ) ) {
			$this->setup_pages( $args['children'], $page );
		}
	}

	/**
	 * Setup multiple pages.
	 *
	 * @since 1.11.0 Now public again.
	 * @since 1.9.0  Now protected. Renamed from register_pages().
	 * @since 1.0.0
	 *
	 * @uses Setup::setup_page()
	 *
	 * @param array  $settings An array of pages to register.
	 * @param string $parent   Optional The id of the page these are children of.
	 */
	public function setup_pages( $pages, $parent = null ) {
		foreach ( $pages as $page => $args ) {
			$this->setup_page( $page, $args, $parent );
		}
	}

	/**
	 * Register the settings for this page.
	 *
	 * @since 1.13.0 Added get_fields option support.
	 *               Added filtering of capability for saving settings.
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand().
	 * @since 1.9.0  Now protected.
	 * @since 1.6.0  Allow registering just settings, no fields, in name => sanitize format.
	 * @since 1.3.0  Reordered so bare fields go before sections.
	 * @since 1.2.0  Moved child page registration to Setup::register_page().
	 * @since 1.0.0
	 *
	 * @uses Setup::register_settings()
	 *
	 * @param string $setting The id of the page to register.
	 * @param array  $args    The page configuration.
	 */
	protected function _register_page_settings( $page, $args ) {
		// Register any settings (not fields).
		if ( isset( $args['settings'] ) ) {
			foreach ( $args['settings'] as $setting => $sanitize ) {
				make_associative( $setting, $sanitize, null );

				// Register the setting with the sanitize callback
				register_setting( $page, $setting, $sanitize );
			}
		}

		// Check if fields need to be generated
        if ( isset( $args['get_fields'] ) && is_callable( $args['get_fields'] ) ) {
            /**
             * Dynamically generate the fields array.
             *
             * @since 1.13.0
             *
             * @param string $setting The id of the page to register.
             * @param array  $args    The page configuration.
             */
            $args['fields'] = call_user_func( $args['get_fields'], $page, $args );
        }

		// Run through any bare fields (assume belonging to default, which will be added automatically)
		if ( isset( $args['fields'] ) ) {
			add_settings_section( 'default', null, null, $page );

			// Handle any shorthand in the fields
			handle_shorthand( 'field', $args['fields'] );

			$this->_register_settings( $args['fields'], 'default', $page );
		}

		// Run through each section, add them, and register the settings for them
		if ( isset( $args['sections'] ) ) {
			foreach ( $args['sections'] as $id => $section ) {
				// Default title and callback to null
				$section = array_merge( array (
					'title' => null,
					'callback' => null,
				), $section );

				add_settings_section( $id, $section['title'], $section['callback'], $page );
				if ( isset( $section['fields'] ) ) {
					$this->_register_settings( $section['fields'], $id, $page );
				}
			}
		}

		// Setup a filter for the capability required for saving settings.
		if ( isset( $args['capability'] ) ) {
			static::setup_callback( '__return_new_value', array( $args['capability'] ), array( "option_page_capability_{$page}", 10, 1 ) );
		}
	}

	/**
	 * Register the settings for this page.
	 *
	 * @since 1.11.0 Dropped 'slug' argument support; unnecessary.
	 * @since 1.9.0  Now protected.
	 * @since 1.3.3  Fixed submenu registration for custom post types.
	 * @since 1.3.0  Reworked processing, now supports passing a file and no callback/function.
	 * @since 1.2.0  Moved child page registration to Setup::register_page().
	 * @since 1.1.0  'submenus' is now 'children'.
	 * @since 1.0.0
	 *
	 * @param string $page   The slug of the page to register.
	 * @param array  $args   The page configuration.
	 * @param string $parent Optional The parent the page belongs to.
	 */
	protected function _add_page_to_menu( $page, $args, $parent = null ) {
		$default_args = array(
			'type'       => 'menu',
			'title'      => make_legible( $page ),
			'capability' => 'manage_options',
			'icon'       => '',
			'position'   => null,
		);

		// Parse the arguments with the defaults
		$args = wp_parse_args( $args, $default_args );

		// If menu_icon is present instead of icon, reassing
		if ( isset( $args['menu_icon'] ) && ! $args['icon'] ) {
			$args['icon'] = $args['menu_icon'];
		}

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

		// Set the default callback if none is set
		if ( ! isset( $args['callback'] ) ) {
			// Setup the default_admin_page callback, passing the $page id
			$args['callback'] = Callbacks::setup_callback( 'default_admin_page', array( $page ) );
		}

		// Extract $args
		extract( $args, EXTR_SKIP );

		// Determine function name and arguments...
		if ( empty( $parent ) ) {
			// Top level page, call add_{type}_page
			$function = 'add_' . $type . '_page';
			$func_args = array( $page_title, $menu_title, $capability, $page, $callback, $icon );

			// Add $position for add_menu_page
			if ( $type == 'menu' ) {
				$func_args[] = $position;
			}
		} else {
			// Submenu page, see if it's one of the builtin menus
			$builtin = array( 'dashboard', 'posts', 'media', 'links', 'pages', 'comments', 'theme', 'plugins', 'users', 'management', 'options' );
			if ( in_array( $parent, $builtin ) ) {
				$function = 'add_' . $parent . '_page';
				$func_args = array( $page_title, $menu_title, $capability, $page, $callback );
			} else {
				$function = 'add_submenu_page';

				if ( post_type_exists( $parent ) ) {
					if ( $parent == 'post' ) {
						$parent = 'edit.php';
					} else {
						$parent = "edit.php?post_type=$parent";
					}
				}

				$func_args = array( $parent, $page_title, $menu_title, $capability, $page, $callback );
			}
		}

		// Call the determined function with the determined arguments
		call_user_func_array( $function, $func_args );
	}

	// =========================
	// !- Column Setups
	// =========================

	/**
	 * Setup the requested columns for the post type.
	 *
	 * @since 1.11.0 Revized hook names, now just uses manage_{$post_type}_posts_columns.
	 *               Now public again.
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0
	 *
	 * @param string $post_type The slug of the post type to setup for.
	 * @param array  $columnset The list of columns to use/register.
	 */
	public function setup_columnset( $post_type, $columnset ) {
		// Build the filter/action hook names
		$filter_hook = 'manage_' . $post_type . '_posts_columns';
		$action_hook = 'manage_' . $post_type . '_posts_custom_column';

		// Create the hook settings and arguments list
		$filter_hook = array( $filter_hook, 10, 1 );
		$action_hook = array( $action_hook, 10, 2 );
		$args = array( $columnset );

		// Save the callbacks
		$this->setup_callback( 'edit_columns', $args, $filter_hook );
		$this->setup_callback( 'do_columns', $args, $action_hook );
	}

	/**
	 * Sets up the requested columns for each post type.
	 *
	 * Simply loops through and calls Setup::_setup_columnset().
	 *
	 * @since 1.11.0 Now public again.
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0
	 *
	 * @param array $feature The list of features to register.
	 */
	public function setup_columns( array $columns ) {
		foreach ( $columns as $post_type => $columnset ) {
			$this->setup_columnset( $post_type, $columnset );
		}
	}

	/**
	 * Edit the list of columns using the passed columnset.
	 *
	 * @since 1.11.0 Updated to use make_associative
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0
	 *
	 * @param array $old_columns The current columns to edit (skip when saving).
	 * @param array $new_columns The list of desired columns.
	 *
	 * @return array The updated columns list.
	 */
	protected function _edit_columns( $old_columns, $new_columns ) {
		$columns = array();

		// Go through the columns, and add/modify as needed
		foreach ( $new_columns as $column_id => $args ) {
			// Handle non-associative entries
			make_associative( $column_id, $args );

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
	 * @since 1.9.0 Now protected.
	 * @since 1.8.0
	 *
	 * @param array $column_id The current column to handle (skip when saving).
	 * @param int   $post_id   The ID of the current post (skip when saving).
	 * @param array $columns   The custom columns to work with.
	 *
	 * @return array The updated columns list.
	 */
	protected function _do_columns( $column_id, $post_id, $columns ) {
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
	// !- User Meta Field Setups
	// =========================

	/**
	 * Setup the hooks for adding/saving user meta fields.
	 *
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand().
	 *               Now public again.
	 * @since 1.10.0
	 *
	 * @param array $fields The fields to build/save.
	 */
	public function setup_usermeta( $fields ) {
		// Do nothing if not in the admin
		if ( is_frontend() ) {
			return;
		}

		$user_id = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null;

		if ( ( defined( 'IS_PROFILE_PAGE' ) && IS_PROFILE_PAGE === true )
		|| ( wp_get_current_user()->ID == $user_id ) ) {
			$save_hook = 'personal_options_update';
		} else {
			$save_hook = 'edit_user_profile_update';
		}

		// Handle any shorthand in the fields
		handle_shorthand( 'field', $fields );

		$this->setup_callback( 'build_user_meta_fields', array( $fields ), array( 'personal_options', 10, 1 ) );
		$this->setup_callback( 'save_user_meta_fields', array( $fields ), array( $save_hook, 10, 1 ) );
	}

	/**
	 * Build and print out a user meta field row.
	 *
	 * @since 1.10.0
	 *
	 * @param object $user  The user being edited. (skip when saving)
	 * @param string $field The id/name meta field to build.
	 * @param array  $args  The arguments for the field.
	 */
	protected function _build_user_meta_field( $user, $field, $args ) {
		Tools::build_field_row( $field, $args, $user, 'user' );
	}

	/**
	 * Build and print a series of user meta fields.
	 *
	 * @since 1.10.0
	 *
	 * @param object $user   The user being edited. (skip when saving)
	 * @param array  $fields The fields to register.
	 */
	protected function _build_user_meta_fields( $user, $fields ) {
		foreach ( $fields as $field => $args ) {
			make_associative( $field, $args );
			$this->_build_user_meta_field( $user, $field, $args );
		}
	}

	/**
	 * Save the data for a user meta field.
	 *
	 * @since 1.13.0 Added save_single support.
	 * @since 1.10.0
	 *
	 * @param object $user_id  The user being edited. (skip when saving)
	 * @param string $field    The id/name meta field to build.
	 * @param array  $args     The arguments for the field.
	 * @param bool   $_checked Wether or not a check has been taken care of.
	 */
	protected function _save_user_meta_field( $user_id, $field, $args, $_checked = false ) {
		$post_key = $meta_key = $field;

		// Check if an explicit $_POST key is set
		if ( isset( $args['post_key'] ) ) {
			$post_key = $args['post_key'];
		}

		// Check if an explicit meta key is set
		if ( isset( $args['meta_key'] ) ) {
			$meta_key = $args['meta_key'];
		}

		// Save the field if it's been passed
		if ( isset( $_POST[ $post_key ] ) ) {
			// Determine save_single rule
			$save_single = true;
			if ( isset( $args['save_single'] ) ) {
				$save_single = $args['save_single'];
			}

			$value = $_POST[ $post_key ];
			if ( is_array( $value ) && ! $save_single ) {
				delete_user_meta( $user_id, $meta_key );
				foreach ( $value as $val ) {
					add_user_meta( $user_id, $meta_key, $val );
				}
			} else {
				update_user_meta( $user_id, $meta_key, $value );
			}
		}
	}

	/**
	 * Save multiple user meta fields.
	 *
	 * @since 1.10.0
	 *
	 * @param object $user_id The user being saved. (skip when saving)
	 * @param array  $fields  The fields to register.
	 */
	protected function _save_user_meta_fields( $user_id, $fields ) {
		// Check that there's a nonce and that it validates.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
			return;
		}

		foreach ( $fields as $field => $args ) {
			make_associative( $field, $args );
			$this->_save_user_meta_field( $user_id, $field, $args, true );
		}
	}

	// =========================
	// !Miscellaneous Setups
	// =========================

	/**
	 * Proccess various miscellaneous setups, including features
	 *
	 * @since 1.11.0 Moved features setup to here.
	 * @since 1.10.0
	 */
	protected function run_misc_setups() {
		// Load the configuration array
		$configs = &$this->configs;

		$this->setup_features( $configs['features'] ); // Will run now and setup various hooks

		// Handle any simple Tool configs if set
		if ( isset( $configs['hide'] ) ) {
			Tools::hide( $configs['hide'] );
		}
		if ( isset( $configs['helpers'] ) ) {
			Tools::load_helpers( $configs['helpers'] );
		}
		if ( isset( $configs['relabel_posts'] ) ) {
			Tools::relabel_posts( $configs['relabel_posts'] );
		}
		if ( isset( $configs['shortcodes'] ) ) {
			Tools::register_shortcodes( $configs['shortcodes'] );
		}

		// Handle the enqueues if set
		if ( $enqueue = $configs['enqueue'] ) {
			// Enqueue frontend scripts/styles if set
			if ( isset( $enqueue['frontend'] ) ) {
				Tools::frontend_enqueue( $enqueue['frontend'] );
			}
			// Enqueue backend scripts/styles if set
			if ( isset( $enqueue['backend'] ) ) {
				Tools::backend_enqueue( $enqueue['backend'] );
			}
		}

		// Run through the config and handle them based on $key
		// This is for sets of multiple configs sharing the same handling methods
		foreach ( $configs as $key => $value ) {
			switch ( $key ) {
				case 'css':
				case 'js':
					// Process quick enqueue scripts/styles for the frontend
					Tools::quick_frontend_enqueue( $key, $value );
				break;
				case 'admin_css':
				case 'admin_js':
					// Process quick enqueue scripts/styles for the backend
					$key = str_replace( 'admin_', '', $key );
					Tools::quick_backend_enqueue( $key, $value );
				break;
				case 'tinymce':
					// Deprecated, use MCE
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
			}
		}
	}

	// =========================
	// !- Feature Setups
	// =========================

	/**
	 * Setup the requested feature.
	 *
	 * @since 1.9.0 Now protected.
	 * @since 1.8.0 Added use of add_theme_support for plugin capabilities.
	 * @since 1.0.0
	 *
	 * @param string $feature The slug of the taxonomy to register.
	 * @param array  $args     The arguments for registration.
	 */
	protected function setup_feature( $feature, $args ) {
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
	 * @since 1.9.0 Now protected.
	 * @since 1.6.0 Handle features added numerically via run_content_setups().
	 * @since 1.0.0
	 *
	 * @param array $feature The list of features to register.
	 */
	protected function setup_features( array $features ) {
		foreach ( $features as $feature => $args ) {
			if ( ! make_associative( $feature, $args ) ) {
				// Feature was added numerically, assume id, args format.
				$feature = $args['id'];
				$args = $args['args'];
			}
			$this->setup_feature( $feature, $args );
		}
	}

	// =========================
	// !-- Feature: Order Manager
	// =========================

	/**
	 * Setup an order manager for certain post types.
	 *
	 * @since 1.10.0 Now supports term order, 'post_type' argument moved to 'objects'
	 * @since 1.9.0  Now protected.
	 * @since 1.8.0  Allowed passing of just the post_type list instead of args.
	 * @since 1.6.0  Added check if enqueues were already handled.
	 * @since 1.0.0
	 *
	 * @param array $args A list of options for the order manager.
	 */
	protected function setup_order_manager( $args ) {
		// If $args looks like it could be the list of objects, restructure
		if ( is_string( $args ) || ( is_array( $args ) && ! empty( $args ) && ! is_assoc( $args ) ) ) {
			$args = array( 'objects' => $args );
		}

		// Backwards compatability; rename post_type arg to object
		if ( isset( $args['post_type'] ) ) {
			$args['objects'] = $args['post_type'];
		}

		// If no object(s) are specified, default to page
		if ( ! isset( $args['objects'] ) ) {
			$args['objects'] = 'page';
		}

		$objects = csv_array( $args['objects'] );

		// Check if any of the objects are taxonomies (registered or configured), load term_meta helper if so
		foreach ( $objects as $object ) {
			if ( taxonomy_exists( $object ) || isset( $this->configs['taxonomies'][ $object ] ) ) {
				Tools::load_helpers( 'term_meta' );
				break;
			}
		}

		// Don't proceed with hooks if not on the admin side.
		if ( is_frontend() ) {
			return;
		}

		// Use the provided save callback if provided
		if ( isset( $args['save'] ) && is_callable( $args['save'] ) ) {
			add_action( 'admin_init', $args['save'] );
		} else {
			// Otherwise, use the built in one
			$this->order_manager_save();
		}

		// Enqueue the necessary scripts if not already
		if ( is_admin() && ( ! defined( 'QS_ORDER_ENQUEUED' ) || ! QS_ORDER_ENQUEUED ) ) {
			Tools::backend_enqueue( array(
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

		$this->order_manager_pages( $objects );
	}

	/**
	 * Register the order manager admin pages for the post types.
	 *
	 * @since 1.10.0 Reworked to support term order
	 * @since 1.9.0
	 *
	 * @param array $objects The list of post types and taxonomies to add the page for.
	 */
	protected function _order_manager_pages( $objects ) {
		// Setup the admin pages for each post type or taxonomy
		foreach ( $objects as $object ) {
			if ( taxonomy_exists( $object ) ) {
				// This is a taxonomy, get the associated post types
				$type = 'taxonomy';
				$the_object = get_taxonomy( $object );
				$post_types = $the_object->object_type;
				$capability = $the_object->cap->manage_terms;
			} elseif ( post_type_exists( $object ) ) {
				$type = 'post_type';
				$the_object = get_post_type_object( $object );
				$post_types = array( $object );
				$capability = $the_object->cap->publish_posts;
			} else {
				continue;
			}

			/**
			 * Filter the capability required to access the order manager page.
			 *
			 * @since 1.11.0
			 *
			 * @param string $capability The capability required.
			 * @param string $object     The object this is for.
			 * @param string $type       The object's type (post_type or taxonomy).
			 *
			 * @return string The capability to require.
			 */
			$capability = apply_filters( 'qs_setup_ordermanager_capability', $capability, $object, $type );

			foreach ( $post_types as $post_type ) {
				$this->setup_page( "$object-order", array(
					'title'      => sprintf( __( '%s Order' ), $the_object->labels->singular_name ),
					'capability' => $capability,
					'callback'   => Callbacks::setup_callback( 'menu_order_admin_page', array( $type, $object ) ),
				), $post_type );
			}
		}
	}

	/**
	 * Default save callback for order manager.
	 *
	 * @since 1.10.0 Reworked to support term order
	 * @since 1.9.0  Now protected, renamed to be auto-hooked.
	 * @since 1.0.0
	 */
	protected function _order_manager_save() {
		global $wpdb;
		if ( isset( $_POST['_qsnonce'] ) && wp_verify_nonce( $_POST['_qsnonce'], 'manage_menu_order' ) ) {
			$object_type = $_POST['object_type']; // post_type or taxonomy
			$object_slug = $_POST['object_slug']; // a post type or taxonomy slug

			// Loop through the list of posts and update
			foreach ( $_POST['menu_order'] as $order => $id ) {
				// Get the parent
				$parent = $_POST['parent'][ $id ];

				// Update the object
				if ( $object_type == 'taxonomy' ) {
					wp_update_term( $id, $object_slug, array(
						'parent' => $parent,
					) );
					update_term_meta( $id, 'menu_order', $order );
				} else {
					wp_update_post( array(
						'ID'          => $id,
						'menu_order'  => $order,
						'post_parent' => $parent,
					) );
				}
			}

			// Redirect back to the refering page
			header( 'Location: ' . $_POST['_wp_http_referer'] );
			exit;
		}
	}

	// =========================
	// !-- Feature: Custom Index Pages
	// =========================

	/**
	 * Setup index page setting/hook for certain post types.
	 *
	 * @since 1.11.0 Removed index_page_query; don't need query object rewriting.
	 * @since 1.10.1 Split index_page_query into index_page_request/query.
	 * @since 1.9.0  Now protected, dropped $index_pages array creation.
	 * @since 1.8.0  Restructured to use a hooks once for all post_types,
	 *				 Also allowed passing of just the post_type list instead of args.
	 * @since 1.6.0
	 *
	 * @param array $args A list of options for the custom indexes.
	 */
	protected function setup_index_page( $args ) {
		// If $args looks like it could be the list of post types, restructure
		if ( is_string( $args ) || ( is_array( $args ) && ! empty( $args ) && ! is_assoc( $args ) ) ) {
			$args = array( 'post_type' => $args );
		}

		// If no post type is specified, abort
		if ( ! isset( $args['post_type'] ) ) {
			return;
		}

		$post_types = csv_array( $args['post_type'] );

		// Make sure the index helper is loaded
		Tools::load_helpers( 'index' );

		// Setup appropriate hooks depending on where we are
		if ( is_admin() ) {
			// Register the settings and post states
			$this->index_page_settings( $post_types );
			$this->index_page_post_states( $post_types );
		}
		if ( is_frontend() ) {
			// Add the request/title/link/adminbar hooks on the frontend
			$this->index_page_request( $post_types );
			$this->index_page_title_part();
			$this->index_page_link();
			$this->index_page_admin_bar();
		}
	}

	/**
	 * Register the index page settings for the post types.
	 *
	 * @since 1.11.1 Tweaked field callback to use $option via arguments list.
	 * @since 1.10.1 Added check for has_archive support.
	 * @since 1.9.0
	 *
	 * @param array $post_types The list of post types.
	 */
	protected function _index_page_settings( $post_types ) {
		// Register a setting for each post type
		foreach ( $post_types as $post_type ) {
			// Make sure the post type is registered and supports archives
			if ( ! post_type_exists( $post_type ) || ! get_post_type_object( $post_type )->has_archive ) {
				continue;
			}

			$option = "page_for_{$post_type}_posts";

			// Register the setting on the backend
			$this->register_setting( $option , array(
				'title' => sprintf( __( 'Page for %s' ) , get_post_type_object( $post_type )->labels->name ),
				'field' => function( $value, $option ) {
					wp_dropdown_pages( array(
						'name'              => $option,
						'echo'              => 1,
						'show_option_none'  => __( '&mdash; Select &mdash;' ),
						'option_none_value' => '0',
						'selected'          => $value,
						'qs-context'        => 'index-page',
					) );
				}
			), 'default', 'reading' );
		}
	}

	/**
	 * Filter the post states to flag a page as a custom index page.
	 *
	 * @since 1.12.2 Store the post state in an explicit key.
	 * @since 1.10.0
	 *
	 * @param array  $post_states The list of post states. (skip when saving)
	 * @param object $post        The post in question. (skip when saving)
	 * @param array  $post_types  The list of post types for this feature.
	 *
	 * @return array The filter post states.
	 */
	protected function _index_page_post_states( $post_states, $post, $post_types ) {
		// Check if the post is the archive page for any post type
		foreach ( $post_types as $post_type ) {
			// Make sure the post type is registered
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}

			// Get the index for this post type, if it matches,
			// add the "POST_TYPE Page" state
			if ( get_index( $post_type ) == $post->ID ) {
				$post_type = get_post_type_object( $post_type );
				$post_states[ "page_for_{$post_type->name}_posts" ] = sprintf( '%s Page', $post_type->label );
			}
		}

		return $post_states;
	}

	/**
	 * Check if the path matches that of an index page (with optional date/paged arguments).
	 *
	 * @since 1.11.1 Fixed typo due to variable renaming.
	 * @since 1.11.0 Reworked to reparse requested URL to match an archive.
	 * @since 1.10.1
	 *
	 * @param WP    $wp         The request object (skip when saving).
	 * @param array $post_types The list of post types.
	 */
	protected function _index_page_request( $wp, $post_types ) {
		$qv =& $wp->query_vars;

		// Abort if a pagename wasn't matched at all
		if ( ! isset( $qv['pagename'] ) ) {
			return;
		}

		// Build an index of the index pages to reference
		$index_pages = array();
		foreach( $post_types as $post_type ) {
			$index_pages[ $post_type ] = get_index( $post_type );
		}

		// Build a RegExp to capture a page with date/paged arguments
		$pattern =
			'(.+?)'. // page name/path
			'(?:/([0-9]{4})'. // optional year...
				'(?:/([0-9]{2})'. // ...with optional month...
					'(?:/([0-9]{2}))?'. // ...and optional day
				')?'.
			')?'.
			'(?:/page/([0-9]+))?'. // and optional page number
		'/?$';

		// Proceed if the pattern checks out
		if ( preg_match( "#$pattern#", $wp->request, $matches ) ) {
			// Get the page using match 1 (pagename)
			$page = get_page_by_path( $matches[1] );

			// Abort if no page is found
			if ( is_null( $page ) ) {
				return;
			}

			// Check if this page is a post type index page
			$post_type = array_search( $page->ID, $index_pages );

			if ( $post_type !== false ) {
				// Modify the request into a post type archive instead
				$qv['post_type'] = $post_type;
				list( , , $qv['year'], $qv['monthnum'], $qv['day'], $qv['paged'] ) = array_pad( $matches, 6, null );

				// Make sure these are unset
				unset( $qv['pagename'] );
				unset( $qv['page'] );
				unset( $qv['name'] );
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
	 * @since 1.9.0 Reworked to not need $post_types at all.
	 * @since 1.8.0 Restructured to handle all post_types at once.
	 * @since 1.6.0
	 *
	 * @param string|array $title The page title or parts (skip when saving).
	 *
	 * @return string|array The modified title.
	 */
	protected function _index_page_title_part( $title ) {
		// Skip if not an archive
		if ( ! is_post_type_archive() ) {
			return $title;
		}

		// Get the queried post type
		$post_type = get_query_var( 'post_type' );

		// Get the index for this post type, update the title if found
		if ( $index_page = get_index( $post_type ) ) {
			$title[0] = get_the_title( $index_page );
		}

		return $title;
	}

	/**
	 * Modify the admin bar to add an edit button for the index page.
	 *
	 * @since 1.12.2
	 *
	 * @param WP_Admin_Bar $wp_admin_bar The admin bar for editing (skip when saving).
	 */
	protected function _index_page_admin_bar( $wp_admin_bar ) {
		// Abort if not an archive for the supported post types
		if ( ! is_post_type_archive() ) {
			return;
		}

		// Abort if an edit node already exists
		if ( $wp_admin_bar->get_node( 'edit' ) ) {
			return;
		}

		// Get the page post type object
		$post_type_object = get_post_type_object( 'page' );

		// If an index is found, is editable, and has an edit link, add the edit button.
		if ( ( $index_page = get_index() )
		&& current_user_can( 'edit_post', $index_page )
		&& $edit_post_link = get_edit_post_link( $index_page ) ) {
			$wp_admin_bar->add_menu( array(
				'id' => 'edit',
				'title' => $post_type_object->labels->edit_item,
				'href' => $edit_post_link
			) );
		}
	}

	// =========================
	// !-- Feature: Parent Filtering
	// =========================

	/**
	 * Setup parent filtering option in the admin for certain post types.
	 *
	 * @since 1.9.0 Now protected.
	 * @since 1.8.0
	 *
	 * @param array $args A list of options for the parent filter.
	 */
	protected function setup_parent_filtering( $args ) {
		// Don't bother if not on the admin side.
		if ( is_frontend() ) {
			return;
		}

		// If $args looks like it could be the list of post types, restructure
		if ( is_string( $args ) || ( is_array( $args ) && ! empty( $args ) && ! is_assoc( $args ) ) ) {
			$args = array( 'post_type' => $args );
		}

		// Get the post types if specified, default to "any" if not
		if ( isset( $args['post_type'] ) ) {
			$post_types = csv_array( $args['post_type'] );
		} else {
			$post_types = 'any';
		}

		// Setup the callback for restrict_manage_posts
		$this->parent_filtering_input( $post_types );

		// Register the post_parent query var for the filtering to work
		Tools::add_query_var( 'post_parent' );
	}

	/**
	 * Add the dropdown to the posts manager for filtering by parent.
	 *
	 * @since 1.8.0
	 *
	 * @param array|string $post_types The list of post types that require this.
	 */
	protected function _parent_filtering_input( $post_types = 'any' ) {
		$post_type = get_query_var('post_type');

		// Check if this is a match
		$match = $post_types == 'any' || is_array( $post_types ) && in_array( $post_type, $post_types );

		// Only proceed if it's one of the desired post types and it's hierarchical.
		if ( $match && get_post_type_object( $post_type )->hierarchical ) {
			// Build the query for the dropdown
			$request = array(
				'qs-context' => 'parent-filtering',
				'post_type' => $post_type,
				'post_parent' => '',
				'posts_per_page' => -1,
				'orderby' => array('menu_order', 'title'),
				'order' => 'asc',
				'selected' => null,
			);

			// Update the selected option if needed
			if ( isset( $_GET['post_parent'] ) ) {
				$request['selected'] = $_GET['post_parent'];
			}

			// Run the query
			$query = new \WP_Query( $request );

			// Print the dropdown
			echo '<select name="post_parent" id="parent_filter">';
				// Print the no filtering option
				echo '<option value="">Any Parent</option>';
				// Print the 0 option for showing only top level posts
				echo '<option value="0"' . ( $request['selected'] === '0' ? ' selected="selected"' : '' ) . '>&mdash; None/Root &mdash;</option>';
				// Print the queried items
				echo walk_page_dropdown_tree( $query->posts, 0, $request );
			echo '</select>';
		}
	}

	// =========================
	// !-- Feature: Sections Manager
	// =========================

	/**
	 * Setup section management for the desired post types.
	 *
	 * @since 1.11.0
	 *
	 * @param array $args A list of options for the parent filter.
	 */
	protected function setup_section_manager( $args ) {
		// Ensure the sections helper is loaded
		Tools::load_helpers( 'sections' );

		// If $args looks like it could be the list of post types, restructure
		if ( is_string( $args ) || ( is_array( $args ) && ! empty( $args ) && ! is_assoc( $args ) ) ) {
			$args = array( 'post_type' => $args );
		}

		// Default post type to page if not set
		if ( ! isset( $args['post_type'] ) ) {
			$args['post_type'] = 'page';
		}

		// Default arguments for the section post type
		$post_type_args = array(
			'singular'     => 'Section',
			'supports'     => array('title', 'editor', 'revisions'),
			'hierarchical' => true,
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'rewrite'      => false,
		);

		// Overwrite with passed post type args if set
		if ( isset( $args['post_type_args'] ) ) {
			$post_type_args = wp_parse_args( $args['post_type_args'], $post_type_args );
		}

		// Register the section post type
		$this->register_post_type( 'qs_section', $post_type_args );

		// Wrap it up here if we're only on the frontend
		if ( is_frontend() ) {
			return;
		}

		// Default arguments for the section meta box
		$meta_box_args = array(
			'title'       => 'Manage Sections',
			'post_type'   => $args['post_type'],
			'type'        => 'repeater',
			'label'       => 'Add Section',
			'name'        => 'qs_sections',
			'data_name'   => '_qs_section',
			'save_single' => false,
			'template'    => array(
				'label'       => 'Section',
				'type'        => 'postselect',
				// postselect arguments
				'post_type'   => 'qs_section',
				'sort_column' => 'post_title',
				'class'       => 'widefat',
				'parent'      => false,
			),
		);

		// Overwrite with passed meta box args if set
		if ( isset( $args['meta_box_args'] ) ) {
			$meta_box_args = wp_parse_args( $args['meta_box_args'], $meta_box_args );
		}

		// Register the section manager meta box
		$this->register_meta_box( 'qs-section_manager', $meta_box_args );

		// Setup the ajax callback
		$this->section_manager_ajax();
	}

	/**
	 * Handle the AJAX request for creating a new section.
	 *
	 * @since 1.11.0
	 */
	protected function _section_manager_ajax() {
		$title = $_GET['title'];
		$parent = $_GET['parent'];

		// Insert the new section
		$id = wp_insert_post( array(
			'post_type' => 'qs_section',
			'post_status' => 'draft',
			'post_title' => $title,
			'post_name' => sanitize_title( $title ),
		) );

		/**
		 * Fires after a section is created.
		 *
		 * @since 1.11.0
		 *
		 * @param WP_Post $section The section post object.
		 * @param int     $parent  The post this section is being added to.
		 */
		do_action( 'qs_section_create', get_post( $id ), $parent );

		// Print out the resulting ID
		echo $id;
		exit;
	}

	// =========================
	// !- MCE Setups
	// =========================

	/**
	 * Setup a button for MCE.
	 *
	 * Will setup the add_mce_button callback for the correct row/position.
	 *
	 * @since 1.10.1
	 *
	 * @param string $button The button to add.
	 * @param array  $args   The arguments for the button (e.g. row, position).
	 */
	public function setup_mce_button( $button, $args ) {
		// Default row and position
		$row = 1;
		$position = null;

		if ( is_int( $args ) ) {
			// Row number specified
			$row = $args;
		} elseif ( is_array( $args ) && ! empty( $args ) ) {
			if ( isset( $args['row'] ) ) {
				$row = $args['row'];
			}
			if ( isset( $args['position'] ) ) {
				$position = $args['position'];
			}
		}

		// Add the button to the proper hook
		$hook = $row > 1 ? "mce_buttons_$row" : 'mce_buttons';
		$this->setup_callback( 'add_mce_button', array( $button, $position ), array( $hook, 10, 1 ) );
	}

	/**
	 * Setup buttons for MCE.
	 *
	 * Will setup add_mce_button callbacks for the correct row and position.
	 *
	 * @since 1.10.1
	 *
	 * @param array|string $buttons A list of buttons to enable, with optional row and position values.
	 */
	public function setup_mce_buttons( $buttons ) {
		csv_array_ref( $buttons );

		// Loop through each button and add it
		foreach ( $buttons as $button => $args ) {
			make_associative( $button, $args );

			$this->setup_mce_button( $button, $args );
		}
	}

	/**
	 * Add a button for MCE.
	 *
	 * @since 1.10.1
	 *
	 * @param array  $buttons       The currently enabled buttons. (skip when saving)
	 * @param string $button_to_add A list of buttons to enable.
	 * @param int    $position      Optional An exact position to insert the button.
	 */
	protected function add_mce_button( $buttons, $button_to_add, $position ) {
		// Remove the button if already present; We'll be inserting it in the new position
		if ( ( $i = array_search( $button_to_add, $buttons ) ) && false !== $i ) {
			unset( $buttons[ $i ] );
		}

		if ( is_int( $position ) ) {
			// Insert at desired position
			array_splice( $buttons, $position, 0, $button_to_add );
		} else {
			// Just append to the end
			$buttons[] = $button_to_add;
		}

		return $buttons;
	}

	/**
	 * Add buttons for MCE.
	 *
	 * This simply adds them; there must be associated JavaScript to display them.
	 *
	 * @deprecated 1.10.1 Use setup_mce_buttons instead.
	 *
	 * @since 1.9.0 Now protected.
	 * @since 1.0.0
	 *
	 * @param array        $buttons        The currently enabled buttons. (skip when saving)
	 * @param array|string $buttons_to_add A list of buttons to enable.
	 * @param int          $position       Optional An exact position to inser the button.
	 */
	protected function _add_mce_buttons( $buttons, $buttons_to_add, $position = null ) {
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
	protected function _add_mce_buttons_2( $buttons, $buttons_to_add, $position = null ) {
		return $this->_add_mce_buttons( $buttons, $buttons_to_add, $position );
	}

	/**
	 * Add buttons for MCE (specifically the third row).
	 *
	 * @see Setup::_enable_mce_buttons()
	 */
	protected function _add_mce_buttons_3( $buttons, $buttons_to_add, $position = null ) {
		return $this->_add_mce_buttons( $buttons, $buttons_to_add, $position );
	}

	/**
	 * Add a plugin to the MCE plugins list.
	 *
	 * @since 1.9.0 Now protected.
	 * @since 1.0.0
	 *
	 * @param array  $plugins The current list of plugins. (skip when saving)
	 * @param string $plugin  The slug/key of the plugin to add.
	 * @param string $src     The URL to the javascript file for the plugin.
	 *
	 * @return $plugins The modified plugins array.
	 */
	protected function _add_mce_plugin( $plugins, $plugin, $src ) {
		$plugins[ $plugin ] = $src;
		return $plugins;
	}

	/**
	 * Register an MCE Plugin/Button
	 *
	 * @since 1.11.0 Now public again.
	 * @since 1.9.0  Now protected.
	 * @since 1.2.0  Removed separator before each button.
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
				$this->setup_mce_button( $button, array( 'row' => $row ) );
			}

			$this->add_mce_plugin( $plugin, $src );
		}
	}

	/**
	 * Register multiple MCE Plugins/Buttons.
	 *
	 * @since 1.11.0 Now public again.
	 * @since 1.9.0  Now protected. Updated argument handling to use.
	 * @since 1.2.0  Revised $args logic and flexibility.
	 * @since 1.0.0
	 *
	 * @param array $plugins The list of MCE plugins to be registered.
	 */
	public function register_mce_plugins( $plugins ) {
		if ( is_array( $plugins ) ) {
			foreach( $plugins as $plugin => $args ) {
				$src = $button = $row = null;

				// $args can be a source string or an arguments array
				if ( is_array( $args ) ) {
					extract( get_array_values( $args, 'src', 'button', 'row' ) );
				} else {
					$button = true; // By default, any plugin will have a button by the same name
					$src = $args;
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
	 * @since 1.9.0 Now protected.
	 * @since 1.0.0
	 *
	 * @param array $settings The TinyMCE settings array to alter. (skip when saving)
	 * @param array $styles   An array of styles to register.
	 */
	protected function _add_mce_style_formats( $settings, $styles ) {
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
	 * @since 1.11.0 Now public again.
	 * @since 1.10.1 Added use of setup_mce_button.
	 * @since 1.9.0  Now protected.
	 * @since 1.0.0
	 *
	 * @param array $styles An array of styles to register.
	 */
	public function register_mce_styles( $styles ) {
		// Add the styleselect item to the second row of buttons.
		$this->setup_mce_button( 'styleselect', array( 'row' => 2, 'position' => 1 ) );

		// Actually add the styles
		$this->add_mce_style_formats( $styles );
	}
}
