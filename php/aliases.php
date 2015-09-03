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
 * @return QuickStart The class instance.
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
 * @see QuickStart\Template::the_head()
 */
function qs_the_head() {
	call_user_func_array( array( 'QuickStart\Template', 'the_head' ), func_get_args() );
}

/**
 * @see QuickStart\Template::doc_start()
 */
function qs_doc_start() {
	call_user_func_array( array( 'QuickStart\Template', 'doc_start' ), func_get_args() );
}

/**
 * @see QuickStart\Template::viewport()
 */
function qs_viewport() {
	call_user_func_array( array( 'QuickStart\Template', 'viewport' ), func_get_args() );
}

/**
 * @see QuickStart\Template::title()
 */
function qs_title() {
	call_user_func_array( array( 'QuickStart\Template', 'title' ), func_get_args() );
}

/**
 * @see QuickStart\Template::title_filter()
 */
function qs_title_filter() {
	call_user_func_array( array( 'QuickStart\Template', 'title_filter' ), func_get_args() );
}

/**
 * @see QuickStart\Template::favicon()
 */
function qs_favicon() {
	call_user_func_array( array( 'QuickStart\Template', 'favicon' ), func_get_args() );
}

/**
 * @see QuickStart\Template::ie_css()
 */
function qs_ie_css() {
	call_user_func_array( array( 'QuickStart\Template', 'ie_css' ), func_get_args() );
}

/**
 * @see QuickStart\Template::html5shiv()
 */
function qs_html5shiv() {
	call_user_func_array( array( 'QuickStart\Template', 'html5shiv' ), func_get_args() );
}

/**
 * @see QuickStart\Template::ajaxurl()
 */
function qs_ajaxurl() {
	call_user_func_array( array( 'QuickStart\Template', 'ajaxurl' ), func_get_args() );
}

/**
 * @see QuickStart\Template::template_url()
 */
function qs_template_url() {
	call_user_func_array( array( 'QuickStart\Template', 'template_url' ), func_get_args() );
}

/**
 * @see QuickStart\Template::theme_url()
 */
function qs_theme_url() {
	call_user_func_array( array( 'QuickStart\Template', 'theme_url' ), func_get_args() );
}

/**
 * @see QuickStart\Template::ga_code()
 */
function qs_ga_code() {
	call_user_func_array( array( 'QuickStart\Template', 'ga_code' ), func_get_args() );
}

// =========================
// !Tools aliases
// =========================

/**
 * @see QuickStart\Tools::build_tag()
 */
function qs_build_tag() {
	call_user_func_array( array( 'QuickStart\Tools', 'build_tag' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::load_helpers()
 */
function qs_load_helpers() {
	call_user_func_array( array( 'QuickStart\Tools', 'load_helpers' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::upload()
 */
function qs_upload() {
	call_user_func_array( array( 'QuickStart\Tools', 'upload' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::maybe_prefix_post_field()
 */
function qs_maybe_prefix_post_field() {
	call_user_func_array( array( 'QuickStart\Tools', 'maybe_prefix_post_field' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::save_post_check()
 */
function qs_save_post_check() {
	call_user_func_array( array( 'QuickStart\Tools', 'save_post_check' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::build_meta_box()
 */
function qs_build_meta_box() {
	call_user_func_array( array( 'QuickStart\Tools', 'build_meta_box' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::build_settings_field()
 */
function qs_build_settings_field() {
	call_user_func_array( array( 'QuickStart\Tools', 'build_settings_field' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::build_field_row()
 */
function qs_build_field_row() {
	call_user_func_array( array( 'QuickStart\Tools', 'build_field_row' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::extra_editor()
 */
function qs_extra_editor() {
	call_user_func_array( array( 'QuickStart\Tools', 'extra_editor' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::geocode_address()
 */
function qs_geocode_address() {
	call_user_func_array( array( 'QuickStart\Tools', 'geocode_address' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::enqueue()
 */
function qs_enqueue() {
	call_user_func_array( array( 'QuickStart\Tools', 'enqueue' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::quick_enqueue()
 */
function qs_quick_enqueue() {
	call_user_func_array( array( 'QuickStart\Tools', 'quick_enqueue' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::add_hooks()
 */
function qs_add_hooks() {
	call_user_func_array( array( 'QuickStart\Tools', 'add_hooks' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::add_callbacks()
 */
function qs_add_callbacks() {
	call_user_func_array( array( 'QuickStart\Tools', 'add_callbacks' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::simple_shortcode()
 */
function qs_simple_shortcode() {
	call_user_func_array( array( 'QuickStart\Tools', 'simple_shortcode' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::register_shortcodes()
 */
function qs_register_shortcodes() {
	call_user_func_array( array( 'QuickStart\Tools', 'register_shortcodes' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::hide()
 */
function qs_hide() {
	call_user_func_array( array( 'QuickStart\Tools', 'hide' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::hide_posts()
 */
function qs_hide_posts() {
	call_user_func_array( array( 'QuickStart\Tools', 'hide_posts' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::hide_pages()
 */
function qs_hide_pages() {
	call_user_func_array( array( 'QuickStart\Tools', 'hide_pages' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::hide_comments()
 */
function qs_hide_comments() {
	call_user_func_array( array( 'QuickStart\Tools', 'hide_comments' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::hide_links()
 */
function qs_hide_links() {
	call_user_func_array( array( 'QuickStart\Tools', 'hide_links' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::hide_wp_head()
 */
function qs_hide_wp_head() {
	call_user_func_array( array( 'QuickStart\Tools', 'hide_wp_head' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::relabel_posts()
 */
function qs_relabel_posts() {
	call_user_func_array( array( 'QuickStart\Tools', 'relabel_posts' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_fix_shortcodes()
 */
function qs_fix_shortcodes() {
	call_user_func_array( array( 'QuickStart\Tools', 'fix_shortcodes' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_do_quicktags()
 */
function qs_do_quicktags() {
	call_user_func_array( array( 'QuickStart\Tools', 'do_quicktags' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_disable_quickedit()
 */
function qs_disable_quickedit() {
	call_user_func_array( array( 'QuickStart\Tools', 'disable_quickedit' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_frontend_enqueue()
 */
function qs_frontend_enqueue() {
	call_user_func_array( array( 'QuickStart\Tools', 'frontend_enqueue' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_backend_enqueue()
 */
function qs_backend_enqueue() {
	call_user_func_array( array( 'QuickStart\Tools', 'backend_enqueue' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_quick_frontend_enqueue()
 */
function qs_quick_frontend_enqueue() {
	call_user_func_array( array( 'QuickStart\Tools', 'quick_frontend_enqueue' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_quick_backend_enqueue()
 */
function qs_quick_backend_enqueue() {
	call_user_func_array( array( 'QuickStart\Tools', 'quick_backend_enqueue' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_edit_meta_box()
 */
function qs_edit_meta_box() {
	call_user_func_array( array( 'QuickStart\Tools', 'edit_meta_box' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_taxonomy_filter()
 */
function qs_taxonomy_filter() {
	call_user_func_array( array( 'QuickStart\Tools', 'taxonomy_filter' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_print_extra_editor()
 */
function qs_print_extra_editor() {
	call_user_func_array( array( 'QuickStart\Tools', 'print_extra_editor' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_print_extra_editor_above()
 */
function qs_print_extra_editor_above() {
	call_user_func_array( array( 'QuickStart\Tools', 'print_extra_editor_above' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_print_extra_editor_below()
 */
function qs_print_extra_editor_below() {
	call_user_func_array( array( 'QuickStart\Tools', 'print_extra_editor_below' ), func_get_args() );
}

/**
 * @see QuickStart\Tools::_add_query_var()
 */
function qs_add_query_var() {
	call_user_func_array( array( 'QuickStart\Tools', 'add_query_var' ), func_get_args() );
}