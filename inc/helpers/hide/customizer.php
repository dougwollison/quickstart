<?php
/**
 * Hide Helper - Customizer: Disable Customizer via capability.
 *
 * @package QuickStart
 * @subpackage Hide
 * @since 1.11.0
 */

// Remove capability
function qs_helper_hide_customizer_cap( $caps, $cap ) {
	if ( $cap == 'customize' ) {
		$caps = array( 'qs-disabled' );
	}
	return $caps;
}
add_filter( 'map_meta_cap', 'qs_helper_hide_customizer_cap', 10, 2 );