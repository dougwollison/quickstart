<?php
/**
 * The SmartPlugin base; a base class for handling the hook setup of methods.
 *
 * @package QuickStart
 * @subpackage SmartHooks
 * @since 1.0.0
 */

abstract class SmartPlugin{
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
	 * A list of internal methods and their hooks names.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected $method_hooks = array();

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
		if ( ! method_exists( $this, "_$method" ) ) return;
	
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
	
	/* ============================================== */
	/* =============== Static Version =============== */
	/* ============================================== */
	
	/**
	 * The stored static callbacks.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $static_callbacks = array();

	/**
	 * A count of all the static callbacks made.
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $static_callback_counts = 0;

	/**
	 * A list of internal methods and their hooks names (static version).
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array();

	/**
	 * Static version of the method overloader
	 *
	 * @since 1.0
	 * @see SmartPlugin::__call()
	 *
	 * @param string $name The name of the method being called
	 * @param array $args The arguments passed to said method
	 */
	public static function __callStatic( $method, $args ) {
		/**
		 * Check if the method name is a callback alias,
		 * or a real method to setup the hook for.
		 * Abort if neither.
		 */
		if ( method_exists( get_called_class(), "_$method" ) ) {
			return self::save_static_callback( $method, $args );
		} elseif ( preg_match( '/^callback-(.+)/', $method, $matches ) ) {
			return self::load_static_callback( $matches[1], $args );
		} else {
			return;
		}
	}

	/**
	 * Save a callback for the requested method
	 *
	 * @since 1.0
	 * @see SmartPlugin::save_callback()
	 *
	 * @param string $method The name of the method to setup the hook for.
	 * @param array  $args   The arguments for the method.
	 */
	protected function save_static_callback( $method, $args ) {
		if ( ! method_exists( get_called_class(), "_$method" ) ) return;
		
		$hook = 'init';
		if ( isset( self::$static_method_hooks[ $method ] ) )
			$hook = self::$static_method_hooks[ $method ];

		++self::$static_callback_counts;
		$id = self::$static_callback_counts;

		self::$static_callbacks[ $id ] = array( $method, $args );

		add_action( $hook, array( get_called_class(), "callback-$id" ) );

		return $id;
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @since 1.0
	 * @see SmartPlugin::load_callback()
	 *
	 * @param string $id    The ID of the callback to load.
	 * @param array  $_args The additional arguments for the method.
	 */
	protected function load_static_callback( $id, $_args ) {
		// First, make sure the callback exists, abort if not
		if ( ! isset( self::$static_callbacks[ $id ] ) ) return;

		// Fetch the method name and saved arguments
		list( $method, $args ) = self::$static_callbacks[ $id ];

		// Apply the method with the saved arguments
		return call_user_func_array( array( get_called_class(), "_$method" ), $args );
	}
}