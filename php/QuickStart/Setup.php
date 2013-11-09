<?php
namespace QuickStart;

/**
 * The Setup Class: The dynamic class that processes the configuration instructions.
 *
 * @package QuickStart
 * @subpackage Setup
 * @since 1.0.0
 */

class Setup{
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
	 * The stored callbacks.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected $callbacks = array();

	/**
	 * A count of all the callbacks made.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected $callback_counts = 0;

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
		'register_supports' => 'after_theme_setup',
		'frontend_enqueue' => 'wp_enqueue_scripts',
		'backend_enqueue' => 'admin_enqueue_scripts',
	);

	/**
	 * Method overloader; handle hook setup/callback for the true method.
	 *
	 * @since 1.0
	 *
	 * @param string $name The name of the method being called
	 * @param array $args The arguments passed to said method
	 */
	public function __call( $method, $args ) {
		/**
		 * Check if the method name is a callback alias,
		 * or a real method to setup the hook for.
		 * Abort if neither.
		 */
		if ( method_exists( $this, "_$method" ) ) {
			return $this->save_callback( $method, $args );
		} elseif ( preg_match( '/^callback-(.+)/', $method, $matches ) ) {
			return $this->load_callback( $matches[1], $args );
		} else {
			return;
		}
	}

	/**
	 * Save a callback for the requested method
	 *
	 * @since 1.0
	 *
	 * @param string $method The name of the method to setup the hook for.
	 * @param array  $args   The arguments for the method.
	 */
	protected function save_callback( $method, $args ) {
		$hook = 'init';
		if ( isset( $this->method_hooks[ $method ] ) )
			$hook = $this->method_hooks[ $method ];

		++$this->callback_counts;
		$id = $this->callback_counts;

		$this->callbacks[ $id ] = array( $method, $args );

		add_action( $hook, array( $this, "callback-$id" ) );

		return $id;
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @since 1.0
	 *
	 * @param string $id    The ID of the callback to load.
	 * @param array  $_args The additional arguments for the method.
	 */
	protected function load_callback( $id, $_args ) {
		// First, make sure the callback exists, abort if not
		if ( ! isset( $this->callbacks[ $id ] ) ) return;

		// Fetch the method name and saved arguments
		list( $method, $args ) = $this->callbacks[ $id ];

		// Apply the method with the saved arguments
		return call_user_func_array( array( $this, "_$method" ), $args );
	}

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
			// Proceed based on what $key is
			switch ( $key ) {
				case 'hide':
					// Hide certain aspects of the backend
					Tools::these( $value );
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
					foreach ( (array) $helpers as $helper ) {
						$file = QS_DIR."/php/helpers/$helper.php";
						if ( file_exists( $file ) ){
							require_once( $file );
						}
					}
				break;
				case 'enqueue':
					// Enqueue frontend scripts/styles if set
					if ( isset( $value['frontend'] ) ) {
						$this->frontend_enqueue( $value['frontend'] );
					}
					// Enqueue backend scripts/styles if set
					if ( isset( $value['backend'] ) ) {
						$this->backend_enqueue( $value['frontend'] );
					}
				break;
			}
		}
	}

	/**
	 * Alias to Utilities::enqueue(), for the frontend
	 *
	 * @since 1.0
	 * @uses Utilities::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css)
	 */
	protected function _frontend_enqueue($enqueues){
		Tools::enqueue( $enqueues );
	}

	/**
	 * Alias to Utilities::enqueue() for the backend
	 *
	 * @since 1.0
	 * @uses Utilities::enqueue()
	 *
	 * @param array $enqueues An array of the scripts/styles to enqueue, sectioned by type (js/css)
	 */
	protected function _backend_enqueue($enqueues){
		Tools::enqueue( $enqueues );
	}
}