<?php
/**
 * QuickStart External Alias Functions
 *
 * @package QuickStart
 * @subpackage Aliases
 * @since 1.0.0
 */

/**
 * Setup function, process the theme configurations and defaults.
 *
 * @since 1.0.0
 *
 * @param array	$configs	An associative array of theme configuration options.
 * @param array	$default	Optional an associative array of default values to use.
 * @param bool	$global		Optional Wether or not to assign this new instance to the $QuickStart global variable.
 *
 * @return QuickStart $QS The class instance.
 */
function QuickStart( $configs, $defaults = array(), $global = true ) {
	$obj = new QuickStart\Setup( $configs, $defaults );

	if ( $global ) {
		global $QuickStart;
		$QuickStart = $obj;
	}

	return $obj;
}

// =========================
// !Template aliases
// =========================

/**
 * Alias to the doc_start template tag
 *
 * @see QuickStart\Template::doc_start()
 */
function qs_doc_start() {
	QuickStart\Template::doc_start();
}

/**
 * Alias to the ga_code template tag
 *
 * @see QuickStart\Template::ga_code()
 */
function qs_ga_code() {
	QuickStart\Template::ga_code();
}

// =========================
// !Tools aliases
// =========================

/**
 * Alias to the upload tool
 *
 * @see QuickStart\Tools::upload()
 */
function qs_upload( $file, $attachment = array() ) {
	QuickStart\Tools::upload( $file, $attachment );
}

/**
 * Alias to the save_post_check tool
 *
 * @see QuickStart\Tools::save_post_check()
 */
function qs_save_post_check( $post_id, $post_type = null, $nonce_name = null, $nonce_value = null ) {
	QuickStart\Tools::save_post_check( $post_id, $post_type, $nonce_name, $nonce_value );
}

/**
 * Alias to the simple_shortcode tool
 *
 * @see QuickStart\Tools::simple_shortcode()
 */
function qs_simple_shortcode( $atts, $content, $tag ) {
	QuickStart\Tools::simple_shortcode( $atts, $content, $tag );
}

// =========================
// !Hooks aliases
// =========================

/**
 * Alias to the fix_shortcodes hook
 *
 * @see QuickStart\Hooks::_fix_shortcodes()
 */
function qs_fix_shortcodes( $tags ) {
	QuickStart\Hooks::fix_shortcodes( $tags );
}

/**
 * Alias to the disable_quickedit hook
 *
 * @see QuickStart\Hooks::_disable_quickedit()
 */
function qs_disable_quickedit( $post_types ) {
	QuickStart\Hooks::disable_quickedit( $post_types );
}

/**
 * Alias to the edit_meta_box hook
 *
 * @see QuickStart\Hooks::_edit_meta_box()
 */
function qs_edit_meta_box( $meta_box, $changes, $post_types = null ) {
	QuickStart\Hooks::edit_meta_box( $meta_box, $changes, $post_types );
}