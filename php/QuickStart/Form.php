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
	/**
	 * A list of accepted attributes for tag building.
	 *
	 * @since 1.0.0
	 * @access public
	 * @var array
	 */
	public static $accepted_attrs = array( 'accesskey', 'autocomplete', 'checked', 'class', 'cols', 'disabled', 'id', 'max', 'maxlength', 'min', 'multiple', 'name', 'placeholder', 'readonly', 'required', 'rows', 'size', 'style', 'tabindex', 'title', 'type', 'value' );

	/**
	 * Convert a field name to a valid ID
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of the field
	 *
	 * @return string The valid ID
	 */
	public static function make_id( $name ) {
		return preg_replace( '/\[(.+)\]/', '_$1', $name );
	}

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

	/**
	 * Build a single field, based on the passed configuration data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field    The name/id of the field.
	 * @param array  $settings The settings to use in creating the field.
	 * @param mixed  $data     The source for the value; WP_Post/stdClass object for a post, null for an option, or anything else for the literal value.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_field( $field, $settings = array(), $data = null ) {
		$default_settings = array(
			'type' => 'text',
			'id' => static::make_id( $field ),
			'name' => $field,
			'label' => make_legible( static::make_id( $field ) ),
			'_name' => $field, //The name of the postmeta or option to retrieve
			'_label' => true //Wether or not to wrap the input in a label
		);

		// Parse the passed settings with the defaults
		$settings = wp_parse_args( $settings, $default_settings );

		// Get the value based on what $post is
		if ( is_null( $data ) ) {
			// Assume it's an option, retrieve it
			$value = get_option( $settings['_name'] );
		} elseif ( is_object( $data ) ) {
			// Assume a post object, get the metadata for it
			$value = get_post_meta( $data->ID, $settings['_name'], true );
		} else {
			// Assume literal value
			$value = $data;
		}


		// Build the field by calling the appropriate method
		if ( method_exists( get_called_class(), $settings['type'] ) ) {
			$method = 'build_' . $settings['type'];
			$html = static::$method( $field, $settings, $value );
		} else { // Meant for text and similar fields; pass to the generic field builder
			$html = static::build_generic( $field, $settings, $value );
		}

		return $html;
	}

	/**
	 * Build a single field, based on the passed configuration data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $fields The name/id of the field.
	 * @param array  $data   The source for the values; see self::build_field() for details.
	 * @param mixed  $echo   Wether or not to echo the output.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_fields( $fields, $data = null, $echo = false ) {
		$html = '';

		// If $fields is actually meant to be an array of all arguments for this
		// method, it should include the __extract value, extract if so.
		if ( in_array( '__extract', $fields ) ) {
			extract( $fields );
		}

		// Run through each field; key is the field name, value is the settings
		foreach ( $fields as $field => $settings ) {
			make_associative( $field, $settings );
			$html .= static::build_field( $field, $settings, $data );
		}

		// Echo the output if desired
		if ( $echo ) echo $html;

		return $html;
	}

	/**
	 * Build a generic field (e.g. text, number, email, etc.)
	 *
	 *
	 * @param string $field    The name/id of the field.
	 * @param array  $settings The settings to use in creating the field.
	 * @param mixed  $value    The value to fill the field with.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_generic( $field, $settings, $value ) {
		if ( ! isset( $settings['class'] ) ) {
			$settings['class'] = array();
		} elseif ( ! is_array($settings['class'] ) ) {
			$settings['class'] = (array) $settings['class'];
		}

		$settings['value'] = $value;

		// Build the <input>
		$html = static::build_tag(
			'input',
			$settings
		);

		$settings['class'] = array(
			$settings['type'] . '-field',
			$settings['id']
		);

		if ( $settings['_label'] ) { // Wrap in label
			$html = sprintf(
				'<p class="field text-field %1$s-field %2$s"><label for="%2$s">%3$s:</label> %4$s</p>',
				$settings['type'],
				$settings['id'],
				$settings['label'],
				$html
			);
		}
	}
}