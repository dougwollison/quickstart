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
	// !Shared Logic
	// =========================

	/**
	 * A list of internal methods and their hooks names.
	 *
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $method_hooks = array();

	/**
	 * Save the callback.
	 *
	 * @since 1.10.0 Moved to _save_callback() utility.
	 *               Now supports saving as just a callback without hooking,
	 *               storing $hook in the callbacks list as well.
	 * @since 1.8.0  Now supports passing a custom hook to attach the callback to,
	 *               also supports multiple hooks for the same callback setup.
	 * @since 1.0.0
	 *
	 * @param string $method The name of the method to setup the hook for.
	 * @param array  $args   The arguments for the method.
	 * @param mixed  $hook   Optional The hook to attach this to (defaults
	 *                       to init or any hook registered for the method).
	 *                       Pass integer to skip hook setup (specifies accepted args).
	 * @param array &$list   The callbacks list to use (passed by reference).
	 * @param int   &$count  The callback counter to use (passed by reference).
	 * @param object $object Either $this for instantiated or get_called_class() for static.
	 *
	 * @return array $callback The callback array that was created.
	 */
	protected static function _do_save_callback( $method, $args, $hook, &$list, &$count, $object ) {
		if ( ! method_exists( $object, $method )
		  && ! method_exists( $object, "_$method" ) ) {
			return;
		}

		if ( is_null( $hook ) ) {
			// Default to init hook or a defined on if set for this method
			if ( isset( static::$method_hooks[ $method ] ) ) {
				$hook = static::$method_hooks[ $method ];
			} else {
				$hook = array( 'init', 10, 0 );
			}
		} elseif ( is_int( $hook ) ) {
			// $hook is accepted arguments value, build empty hook set
			$hook = array( false, null, $hook );
		}

		++$count;
		$id = $count;

		$callback = array( $object, "cb$id" );

		// Ensure the hook is in the proper form (name, priority, arguments)
		$hook = (array) $hook + array( 'init', 10, 0 );

		// Get the hook details
		list( $tags, $priority, $accepted_args ) = $hook;

		// Proceed if $tags is set
		if ( $tags ) {
			// Multiple tag names may be used, treat as array and loop
			$tags = (array) $tags;
			foreach ( $tags as $tag ) {
				// Register the hook for this tag
				add_filter( $tag, $callback, $priority, $accepted_args );
			}
		}

		// Save the callback information
		$list[ $id ] = array( $method, $args, $hook );

		return $callback;
	}

	/**
	 * Load the callback.
	 *
	 * @since 1.10.0 Moved to _load_callback() utility.
	 *               Now checks hook data before merging arguments.
	 * @since 1.0.0
	 *
	 * @param string $id     The ID of the callback to load.
	 * @param array  $_args  The additional arguments passed to the method.
	 * @param array  $list   The callbacks list to use.
	 * @param object $object Either $this for instantiated or get_called_class() for static.
	 *
	 * @return array $callback The callback array that was created.
	 */
	protected static function _do_load_callback( $id, $_args, $list, $object ){
		// First, make sure the callback exists, abort if not
		if ( ! isset( $list[ $id ] ) ) {
			return;
		}

		// Fetch the method name and saved arguments
		list( $method, $args, $hook ) = $list[ $id ];

		// Make sure the method exists, including _-prefixed version
		if ( ! method_exists( $object, $method ) ) {
			$method = "_$method";
			if ( ! method_exists( $object, $method ) ) {
				return;
			}
		}

		// If there is hook data, make sure to trim $_args to the proper length
		if ( is_array( $hook ) ) {
			$_args = array_slice( $_args, 0, (int) $hook[2] );
			$args = array_merge( $_args, $args );
		}

		// Apply the method with the saved arguments
		return call_user_func_array( array( $object, $method ), $args );
	}

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
	 * @since 1.10.0 Renamed.
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected $callback_count = 0;

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
	 * Save a callback for the requested method.
	 *
	 * @see Smart_Plugin::_do_save_callback()
	 */
	public function save_callback( $method, $args, $hook = null ) {
		return static::_do_save_callback( $method, $args, $hook, $this->callbacks, $this->callback_count, $this );
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @see Smart_Plugin::_do_load_callback()
	 */
	public function load_callback( $id, $_args ) {
		return static::_do_load_callback( $id, $_args, $this->callbacks, $this );
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
	 * @since 1.10.0 Renamed.
	 * @since 1.0.0
	 * @access protected
	 * @var array
	 */
	protected static $static_callback_count = 0;

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
	 * @see SmartPlugin::do_save_callback() for details and change log.
	 */
	public static function save_static_callback( $method, $args, $hook = null ) {
		return static::_do_save_callback( $method, $args, $hook, static::$static_callbacks, static::$static_callback_count, get_called_class() );
	}

	/**
	 * Load the requested callback and apply it.
	 *
	 * @see SmartPlugin::do_load_callback() for details and change log.
	 */
	public static function load_static_callback( $id, $_args ) {
		return static::_do_load_callback( $id, $_args, static::$static_callbacks, get_called_class() );
	}
}