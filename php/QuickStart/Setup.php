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
	 * =========================
	 * Method Overloading
	 * =========================
	 */

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
		'run_theme_setups' => 'after_theme_setup'
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
		$this->defaults = array_merge_recursive( $this->defaults, $defaults );
		
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
						$tx_args['post_types'] = array( $post_type );
						
						// Add this taxonomy to $taxonomies, remove from this post type
						$taxonomies[ $taxonomy ] = $tx_args;
						unset( $pt_args['taxonomies'][ $taxonomy ] );
					}
				}
			}
			
			if ( isset( $pt_args['meta_boxes'] ) ) {
				foreach ( $pt_args['meta_boxes'] as $meta_box => $mb_args ) {
					// Fix if dumb metabox was passed (numerically, not associatively)
					make_associative( $meta_box, $mb_args );
					
					// Add this post type to the post_types argument to this meta box
					$mb_args['post_types'] = array( $post_type );
					
					// Add this taxonomy to $taxonomies, remove from this post type
					$meta_boxes[ $meta_box ] = $mb_args;
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
	public function _register_post_type( $post_type, $args ) {
		
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
	public function _register_post_types( $post_types ) {
		
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
	public function _register_taxonomies( $taxonomies ) {
		
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
	public function register_meta_boxes( $meta_boxes ) {
		
	}
}