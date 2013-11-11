<?php
namespace QuickStart;

/**
 * The Setup Class: The dynamic class that processes the configuration instructions.
 *
 * @package QuickStart
 * @subpackage Setup
 * @since 1.0.0
 */

class Setup extends \SmartPlugin{
	/**
	 * The configuration array.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected $config = array();

	/**
	 * The defaults array.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected $defaults = array();

	/**
	 * A list of internal methods and their hooks configurations are.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected $method_hooks = array(
		'frontend_enqueue' => 'wp_enqueue_scripts',
		'backend_enqueue' => 'admin_enqueue_scripts',
		'run_theme_setups' => 'after_theme_setup',
		'save_meta_box' => array( 'save_post', 10, 1 ),
		'add_meta_box' => 'add_meta_boxes'
	);

	/**
	 * =========================
	 * Main Setup Function
	 * =========================
	 */

	/**
	 * Processes configuration options and sets up necessary hooks/callbacks.
	 *
	 * @since 1.0
	 *
	 * @param array $configs The theme configuration options.
	 * @param array $defaults The default values. Optional.
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
						$this->frontend_enqueue( $value['frontend'] );
					}
					// Enqueue backend scripts/styles if set
					if ( isset( $value['backend'] ) ) {
						$this->backend_enqueue( $value['backend'] );
					}
				break;
			}
		}

		// Run the content setups
		$this->run_content_setups();

		// Run the theme setups
		$this->run_theme_setups();
	}

	/**
	 * =========================
	 * Enqueue Related Methods
	 * =========================
	 */

	/**
	 * Alias to Utilities::enqueue(), for the frontend
	 *
	 * @since 1.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css)
	 */
	public function _frontend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}

	/**
	 * Alias to Utilities::enqueue() for the backend
	 *
	 * @since 1.0
	 * @uses Tools::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css)
	 */
	public function _backend_enqueue( $enqueues ) {
		Tools::enqueue( $enqueues );
	}

	/**
	 * =========================
	 * Custom Content Setups
	 * =========================
	 */

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
	protected function maybe_setup_labels( $object, &$args, $template ) {
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

			$args['labels'] = array(
				'name'               => _x( $plural, 'post type general name' ),
				'singular_name'      => _x( $singular, 'post type singular name' ),
				'menu_name'          => _x( $singular, 'post type menu name' ),
				'add_new'            => _x( 'Add New', $post_type ),
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
				$args['labels'][ $label ] = __( $text );
			}
		}
	}

	/**
	 * Proccess the content setups; extracting any taxonomies/meta_boxes defined
	 * within a post_type configuration.
	 *
	 * @since 1.0
	 *
	 * @param array &$configs Optional. The post types, taxonomies and meta boxes to setup.
	 */
	public function run_content_setups( $configs = null ) {
		// If no $configs is passed, load the locally stored ones.
		if ( is_null( $configs ) ) {
			$configs = &$this->configs;
		}

		$configs = array_merge( array(
			'post_types' => array(),
			'taxonomies' => array(),
			'meta_boxes' => array()
		), $configs );

		// Loop through each post_type, check for taxonomies or meta_boxes
		foreach ( $configs['post_types'] as $post_type => &$pt_args ) {
			make_associative( $post_type, $pt_args );
			if ( isset( $pt_args['taxonomies'] ) ) {
				// Loop through each taxonomy, move it to $taxonomies if not registered yet
				foreach ( $pt_args['taxonomies'] as $taxonomy => $tx_args ) {
					// Fix if dumb taxonomy was passed (numerically, not associatively)
					make_associative( $taxonomy, $tx_args );

					// Check if the taxonomy is registered yet
					if ( ! taxonomy_exists( $taxonomy ) ) {
						// Add this post type to the post_types argument to this taxonomy
						$tx_args['post_type'] = array( $post_type );

						// Add this taxonomy to $taxonomies, remove from this post type
						$configs['taxonomies'][ $taxonomy ] = $tx_args;
						unset( $pt_args['taxonomies'][ $taxonomy ] );
					}
				}
			}

			if ( isset( $pt_args['meta_boxes'] ) ) {
				foreach ( $pt_args['meta_boxes'] as $meta_box => $mb_args ) {
					// Fix if dumb metabox was passed (numerically, not associatively)
					make_associative( $meta_box, $mb_args );

					// Check if the arguments are a callable, restructure to proper form
					if ( is_callable( $mb_args ) ) {
						$mb_args = array(
							'fields' => $mb_args
						);
					}

					// Add this post type to the post_types argument to this meta box
					$mb_args['post_type'] = array( $post_type );

					// Add this taxonomy to $taxonomies, remove from this post type
					$configs['meta_boxes'][ $meta_box ] = $mb_args;
					unset( $pt_args['meta_boxes'][ $meta_box ] );
				}
			}
		}

		// Run the content setups
		$this->register_post_types( $configs['post_types'] ); // Will run during "init"
		$this->register_taxonomies( $configs['taxonomies'] ); // Will run during "init"
		$this->register_meta_boxes( $configs['meta_boxes'] ); // Will run now and setup various hooks
	}

	/**
	 * Register the requested post_type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_type The slug of the post type to register.
	 * @param array  $args      The arguments for registration.
	 */
	public function _register_post_type( $post_type, array $args = array() ) {
		// Make sure the post type doesn't already exist
		if ( post_type_exists( $post_type ) ) return;

		// Setup the labels if needed
		self::maybe_setup_labels( $post_type, $args, array(
			'new_item' => 'New %S',
			'not_found_in_trash' => 'No %p found in Trash.'
		) );

		// Default arguments for the post type
		$defaults = array(
			'public' => true,
			'has_archive' => true,
		);

		// Prep $defaults
		$this->prep_defaults( 'post_type', $defaults );

		// Parse the arguments with the defaults
		$args = wp_parse_args($args, $defaults);

		// Now, register the post type
		register_post_type( $post_type, $args );

		// Now that it's registered, fetch the resulting show_in_menu argument,
		// and add the post_type_count hook if true
		if ( get_post_type_object( $post_type )->show_in_menu ){
			Hooks::post_type_count( $post_type );
		}
	}

	/**
	 * Register the requested post types.
	 *
	 * Simply loops through and calls Setup::_register_post_type()
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

	/**
	 * Register the requested taxonomy.
	 *
	 * Simply loops through and calls Setup::_register_post_type()
	 *
	 * @since 1.0.0
	 *
	 * @param string $taxonomy The slug of the taxonomy to register.
	 * @param array  $args     The arguments for registration.
	 */
	public function _register_taxonomy( $taxonomy, $args ) {
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

		// Default arguments for the post type
		$defaults = array(
			'hierarchical' => true,
		);

		// Prep $defaults
		$this->prep_defaults( 'taxonomy', $defaults );

		// Parse the arguments with the defaults
		$args = wp_parse_args($args, $defaults);

		// Now, register the post type
		register_taxonomy( $taxonomy, $args['post_type'], $args );

		// Now that it's registered, fetch the resulting show_ui argument,
		// and add the taxonomy_count hook if true
		if ( get_taxonomy( $taxonomy )->show_ui ){
			Hooks::taxonomy_count( $taxonomy );
		}
	}

	/**
	 * Register the requested taxonomies
	 *
	 * Simply loops through and calls Setup::_register_taxonomy()
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

	/**
	 * Register the requested meta box.
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments for registration.
	 */
	public function register_meta_box( $meta_box, $args ) {
		if ( is_callable( $args ) ) { // A callback, recreate into proper array
			$args = array(
				'fields' => $args
			);
		} elseif ( empty( $args ) ) { // Empty array; make dumb meta box
			$args = array(
				'fields' => array(
					$meta_box => array(
						'class' => 'full-width-text',
						'_label' => false
					)
				)
			);
		}

		$defaults = array(
			'title' => make_legible( $meta_box ),
			'context' => 'normal',
			'priority' => 'high',
			'post_type' => 'post'
		);

		// Prep $defaults
		$this->prep_defaults( 'meta_box', $defaults );

		$args = wp_parse_args( $args, $defaults );

		$this->save_meta_box( $meta_box, $args );
		$this->add_meta_box( $meta_box, $args );
	}

	/**
	 * Register the requested meta boxes.
	 *
	 * Simply loops through and calls Setup::register_meta_box()
	 *
	 * @since 1.0.0
	 *
	 * @param array $meta_boxes The list of meta boxes to register.
	 */
	public function register_meta_boxes( array $meta_boxes ) {
		foreach ( $meta_boxes as $meta_box => $args ) {
			make_associative( $meta_box, $args );
			$this->register_meta_box( $meta_box, $args );
		}
	}

	/**
	 * Setup the save hook for the meta box
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id  The ID of the post being saved. (skip when saving the hook)
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments from registration.
	 */
	protected function _save_meta_box( $post_id, $meta_box, $args ) {
	}

	/**
	 * Add the meta box to WordPress
	 *
	 * @since 1.0.0
	 *
	 * @param string $meta_box The slug of the meta box to register.
	 * @param array  $args     The arguments from registration.
	 */
	protected function _add_meta_box( $meta_box, $args ) {
		foreach ( (array) $args['post_type'] as $post_type ) {
			add_meta_box(
				$meta_box,
				$args['title'],
				array( __NAMESPACE__.'\Tools', 'build_meta_box' ),
				$post_type,
				$args['context'],
				$args['priority'],
				array(
					'id' => $meta_box,
					'args' => $args
				)
			);
		}
	}
}