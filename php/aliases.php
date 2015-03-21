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
function QuickStart( $configs, $defaults = array(), $global = false ) {
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
 * Alias to the viewport template tag
 *
 * @see QuickStart\Template::viewport()
 */
function qs_viewport( $settings = array() ) {
	QuickStart\Template::viewport( $settings );
}

/**
 * Alias to the favicon template tag
 *
 * @see QuickStart\Template::favicon()
 */
function qs_favicon( $settings = null ) {
	QuickStart\Template::favicon( $settings );
}

/**
 * Alias to the ie_css template tag
 *
 * @see QuickStart\Template::ie_css()
 */
function qs_ie_css( $settings = null ) {
	QuickStart\Template::ie_css( $settings );
}

/**
 * Alias to the html5shiv template tag
 *
 * @see QuickStart\Template::html5shiv()
 */
function qs_html5shiv( $shiv_url = null ) {
	QuickStart\Template::html5shiv( $shiv_url );
}

/**
 * Alias to the ajaxurl template tag
 *
 * @see QuickStart\Template::ajaxurl()
 */
function qs_ajaxurl() {
	QuickStart\Template::ajaxurl();
}

/**
 * Alias to the template_url template tag
 *
 * @see QuickStart\Template::template_url()
 */
function qs_template_url() {
	QuickStart\Template::template_url();
}

/**
 * Alias to the ga_code template tag
 *
 * @see QuickStart\Template::ga_code()
 */
function qs_ga_code( $account, $production = null ) {
	QuickStart\Template::ga_code( $account, $production );
}

/**
 * Alias to the the_head template tag
 *
 * @see QuickStart\Template::the_head()
 */
function qs_the_head( array $features = array() ) {
	QuickStart\Template::the_head( $features );
}

// =========================
// !Tools aliases
// =========================

/**
 * Alias to the relabel_posts tool
 *
 * @see QuickStart\Tools::relabel_posts()
 */
function qs_relabel_posts( $label = null ) {
	QuickStart\Tools::relabel_posts( $file, $label );
}

/**
 * Alias to the enqueue tool
 *
 * @see QuickStart\Tools::enqueue()
 */
function qs_enqueue( array $enqueues = array() ) {
	QuickStart\Tools::enqueue( $enqueues );
}

/**
 * Alias to the quick_enqueue tool
 *
 * @see QuickStart\Tools::quick_enqueue()
 */
function qs_quick_enqueue( $type, $files ) {
	QuickStart\Tools::quick_enqueue( $type, $files );
}

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

/**
 * Alias to the register_shortcodes tool
 *
 * @see QuickStart\Tools::register_shortcodes()
 */
function qs_register_shortcodes( $shortcodes ) {
	QuickStart\Tools::register_shortcodes( $shortcodes );
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

/**
 * Alias to the extra_editor hook
 *
 * @see QuickStart\Hooks::extra_editor()
 */
function qs_extra_editor( $name, $settings = array() ) {
	QuickStart\Hooks::extra_editor( $name, $settings );
}
