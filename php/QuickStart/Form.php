<?php
namespace QuickStart;

/**
 * The Form Kit: A collection of form related utilities.
 *
 * @package QuickStart
 * @subpackage Form
 * @since 1.0.0
 */

class Form{
	public static $accepted_attrs = array( 'accesskey', 'autocomplete', 'checked', 'class', 'cols', 'disabled', 'id', 'max', 'maxlength', 'min', 'multiple', 'name', 'placeholder', 'readonly', 'required', 'rows', 'size', 'style', 'tabindex', 'title', 'type', 'value' );

	/**
	 * Build an HTML tag.
	 *
	 * @since 1.0
	 *
	 * @param string $tag     The tag name.
	 * @param array  $atts    The tag attributes.
	 * @param string $content The tag content.
	 *
	 * @return string The html of the tag.
	 */
	public static function build_tag( $tag, $atts, $content = null, $accepted = null ) {
		if ( is_null( $accepted ) ) {
			$accepted = self::$accepted_attrs;
		}
	
		$html = "<$tag";

		foreach ( $atts as $attr => $value ) {
			if ( is_numeric ( $attr ) ) {
				$html .= " $value";
			} else {
				// Make sure it's a registerd attribute (or data- attribute)
				if ( ! in_array( $attr, $accepted ) && strpos( $attr, 'data-' ) !== 0 ) continue;

				if ( is_array( $value ) ) {
					// Implode into a space separated list
					$value = implode( ' ', $value );
				}
				$html .= " $attr=\"$value\"";
			}
		}

		if ( is_null( $content ) ) {
			$html .= '/>';
		} else {
			$html .= ">$content</$tag>";
		}

		return $html;
	}
	
	public static function build_field( $field, $settings = array(), $post = null ) {
	}
	
	public static function build_fields($fields, $post = null, $echo = false){
	}
}