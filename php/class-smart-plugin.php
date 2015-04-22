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
	 * @since 1.10.0 Now supports saving as just a callback without hooking, storing $hook
	 *               in the callbacks list.
	 * @since 1.8.0  Now supports passing a custom hook to attach the callback to,
	 *               also supports multiple hooks for the same callback setup.
	 * @since 1.0.0
	 *
	 * @param string $method The name of the method to setup the hook for.
	 * @param array  $args   The arguments for the method.
	 * @param mixed  $hook   Optional The hook to attach this to (defaults
	 *                       to init or any hook registered for the method).
	 *                       Pass false skip hook setup.
	 *
	 * @return array $callback The callback array that was created.
	 */
	protected function save_callback( $method, $args, $hook = null ) {
		if ( ! method_exists( $this, $method )
		  && ! method_exists( $this, "_$method" ) ) {
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

		$callback = array( $this, "cb$id" );

		// Check if a hook is specified, add it
		if ( $hook !== false ) {
			// Ensure the hook is in the proper form (name, priority, arguments)
			$hook = (array) $hook + array( 'init', 10, 0 );

			// Get the hook details
			list( $tags, $priority, $accepted_args ) = $hook;

			// Multiple tag names may be used, treat as array and loop
			$tags = (array) $tags;
			foreach ( $tags as $tag ) {
				// Register the hook for this tag
				add_filter( $tag, $callback, $priority, $accepted_args );
			}
		}

		// Save the callback information
		$this->callbacks[ $id ] = array( $method, $args, $hook );

		return $callback;
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @since 1.10.0 Now checks hook data before merging arguments
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
		list( $method, $args, $hook ) = $this->callbacks[ $id ];

		// Make sure the method exists, including _-prefixed version
		if ( ! method_exists( $this, $method ) ) {
			$method = "_$method";
			if ( ! method_exists( $this, $method ) ) {
				return;
			}
		}

		// Prepend the arguments list with the ones passed now
		// ONLY if the accepted arguments value isn't 0
		if ( is_array( $hook ) && $hook[2] > 0 ) {
			$args = array_merge( $_args, $args );
		}

		// Apply the method with the saved arguments
		return call_user_func_array( array( $this, $method ), $args );
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
	 * @see SmartPlugin::__call() for details and change log.
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
	 * @see SmartPlugin::save_callback() for details and change log.
	 */
	protected static function save_static_callback( $method, $args, $hook = null ) {
		if ( ! method_exists( get_called_class(), $method )
		  && ! method_exists( get_called_class(), "_$method" ) ) {
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

		$callback = array( get_called_class(), "cb$id" );

		// Check if a hook is specified, add it
		if ( $hook !== false ) {
			// Ensure the hook is in the proper form (name, priority, arguments)
			$hook = (array) $hook + array( 'init', 10, 0 );

			// Get the hook details
			list( $tags, $priority, $accepted_args ) = $hook;

			// Multiple tag names may be used, treat as array and loop
			$tags = (array) $tags;
			foreach ( $tags as $tag ) {
				// Register the hook for this tag
				add_filter( $tag, $callback, $priority, $accepted_args );
			}
		}

		// Save the callback information
		static::$static_callbacks[ $id ] = array( $method, $args, $hook );

		return $callback;
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @see SmartPlugin::load_callback() for details and change log.
	 */
	protected static function load_static_callback( $id, $_args ) {
		// First, make sure the callback exists, abort if not
		if ( ! isset( static::$static_callbacks[ $id ] ) ) {
			return;
		}

		// Fetch the method name and saved arguments
		list( $method, $args, $hook ) = static::$static_callbacks[ $id ];

		// Make sure the method exists, including _-prefixed version
		if ( ! method_exists( get_called_class(), $method ) ) {
			$method = "_$method";
			if ( ! method_exists( get_called_class(), $method ) ) {
				return;
			}
		}

		// Prepend the arguments list with the ones passed now
		// ONLY if the accepted arguments value isn't 0
		if ( is_array( $hook ) && $hook[2] > 0 ) {
			$args = array_merge( $_args, $args );
		}

		// Apply the method with the saved arguments
		return call_user_func_array( array( get_called_class(), $method ), $args );
	}
}