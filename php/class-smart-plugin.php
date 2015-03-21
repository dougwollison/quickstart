<?php
/**
 * The Smart_Plugin base; a base class for handling the hook setup of methods.
 *
 * @package QuickStart
 * @subpackage Smart_Plugin
 *
 * @since 1.6.1 Changed name for WordPress standards
 * @since 1.0.0
 */

abstract class Smart_Plugin{
	// =========================
	// !Instantiated Version
	// =========================

	/**
	 * The stored callbacks.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $callbacks = array();

	/**
	 * A count of all the callbacks made.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $callback_counts = 0;

	/**
	 * A list of internal methods and their hooks names.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $method_hooks = array();

	/**
	 * Method overloader; handle hook setup/callback for the true method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the method being called.
	 * @param array  $args The arguments passed to said method.
	 */
	public function __call( $method, $args ) {
		/**
		 * Check if the method name is a real method,
		 * or a callback alias.
		 * Abort if neither.
		 */
		if ( method_exists( $this, "_$method" ) ) {
			return $this->save_callback( $method, $args );
		} elseif ( preg_match( '/^cb(\d+)/', $method, $matches ) ) {
			return $this->load_callback( $matches[1], $args );
		} else {
			return;
		}
	}

	/**
	 * Save a callback for the requested method
	 *
	 * @since 1.8.0 Now supports passing a custom hook to attach the callback to,
	 *              also supports multiple hooks for the same callback setup.
	 * @since 1.0.0
	 *
	 * @param string       $method The name of the method to setup the hook for.
	 * @param array        $args   The arguments for the method.
	 * @param string|array $hook   Optional The hook to attach this to (defaults
	 *                             to init or any hook registered for the method).
	 */
	protected function save_callback( $method, $args, $hook = null ) {
		if ( ! method_exists( $this, "_$method" ) ) {
			return;
		}

		if ( is_null( $hook ) ) {
			// Default to the 'init' hook
			if ( isset( $this->method_hooks[ $method ] ) ) {
				$hook = $this->method_hooks[ $method ];
			} else {
				$hook = 'init';
			}
		}

		++$this->callback_counts;
		$id = $this->callback_counts;

		$this->callbacks[ $id ] = array( $method, $args );

		// Get the name, priority and number of args for the hook,
		// based on the hook plus default arguments if needed.
		list( $tags, $priority, $accepted_args ) = (array) $hook + array( 'init', 10, 0 );

		// Multiple tag names may be used, treat as array and loop
		$tags = (array) $tags;
		foreach ( $tags as $tag ) {
			// Register the hook for this tag
			add_filter( $tag, array( $this, "cb$id" ), $priority, $accepted_args );
		}

		return $id;
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id    The ID of the callback to load.
	 * @param array  $_args The additional arguments for the method.
	 */
	protected function load_callback( $id, $_args ) {
		// First, make sure the callback exists, abort if not
		if ( ! isset( $this->callbacks[ $id ] ) ) {
			return;
		}

		// Fetch the method name and saved arguments
		list( $method, $args ) = $this->callbacks[ $id ];

		// Append the saved arguments to the passed arguments
		$args = array_merge( $_args, $args );

		// Apply the method with the saved arguments
		return call_user_func_array( array( $this, "_$method" ), $args );
	}

	// =========================
	// !Static Version
	// =========================

	/**
	 * The stored static callbacks.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_callbacks = array();

	/**
	 * A count of all the static callbacks made.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_callback_counts = 0;

	/**
	 * A list of internal methods and their hooks names (static version).
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_method_hooks = array();

	/**
	 * Static version of the method overloader.
	 *
	 * @since 1.0.0
	 *
	 * @see SmartPlugin::__call()
	 */
	public static function __callStatic( $method, $args ) {
		/**
		 * Check if the method name is a real method,
		 * or a callback alias.
		 * Abort if neither.
		 */
		if ( method_exists( get_called_class(), "_$method" ) ) {
			return static::save_static_callback( $method, $args );
		} elseif ( preg_match( '/^cb(\d+)/', $method, $matches ) ) {
			return static::load_static_callback( $matches[1], $args );
		} else {
			return;
		}
	}

	/**
	 * Save a callback for the requested method.
	 *
	 * @since 1.8.0 SmartPlugin::save_callback() changes.
	 * @since 1.0.0
	 *
	 * @see SmartPlugin::save_callback()
	 */
	protected static function save_static_callback( $method, $args, $hook = null ) {
		if ( ! method_exists( get_called_class(), "_$method" ) ) {
			return;
		}

		if ( is_null( $hook ) ) {
			// Default to the 'init' hook
			if ( isset( static::$static_method_hooks[ $method ] ) ) {
				$hook = static::$static_method_hooks[ $method ];
			} else {
				$hook = 'init';
			}
		}

		++static::$static_callback_counts;
		$id = static::$static_callback_counts;

		static::$static_callbacks[ $id ] = array( $method, $args );

		// Get the name, priority and number of args for the hook,
		// based on the hook plus default arguments if needed.
		list( $tags, $priority, $accepted_args ) = (array) $hook + array( 'init', 10, 0 );

		// Multiple tag names may be used, treat as array and loop
		$tags = (array) $tags;
		foreach ( $tags as $tag ) {
			// Register the hook for this tag
			add_filter( $tag, array( get_called_class(), "cb$id" ), $priority, $accepted_args );
		}

		return $id;
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @since 1.0.0
	 *
	 * @see SmartPlugin::load_callback()
	 */
	protected static function load_static_callback( $id, $_args ) {
		// First, make sure the callback exists, abort if not
		if ( ! isset( static::$static_callbacks[ $id ] ) ) {
			return;
		}

		// Fetch the method name and saved arguments
		list( $method, $args ) = static::$static_callbacks[ $id ];

		// Append the saved arguments to the passed arguments
		$args = array_merge( $_args, $args );

		// Apply the method with the saved arguments
		return call_user_func_array( array( get_called_class(), "_$method" ), $args );
	}
}