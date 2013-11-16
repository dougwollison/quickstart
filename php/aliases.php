<?php
/**
 * QuickStart External Alias Functions
 *
 * @package QuickStart
 * @subpackage Aliases
 * @since 1.0.0
 */

/**
 * Setup function, process the theme configurations and defaults
 *
 * @since 1.0.0
 *
 * @param array	$configs	An associative array of theme configuration options
 * @param array	$default	Optional an associative array of default values to use
 * @param bool	$global		Optional Wether or not to assign this new instance to the $QuickStart global variable
 * @return \QuickStart $QS The class instance
 */
function QuickStart( $configs, $defaults = array(), $global = true ) {
	$obj = new QuickStart\Setup( $configs, $defaults );

	if ( $global ) {
		global $QuickStart;
		$QuickStart = $obj;
	}

	return $obj;
}