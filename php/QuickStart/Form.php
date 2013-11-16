<?php
namespace QuickStart;

/**
 * The Form Kit: A collection of form related utilities.
 *
 * @package QuickStart
 * @subpackage Form
 * @since 1.0.0
 */

class Form {
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
	 * Wrap the fields in a label, if $settings['_label'] is true.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html     The html to wrap.
	 * @param array  $settings The settings array for the field.
	 * @param string $format   The format to use.
	 *
	 * @return string The processed HTML.
	 */
	public static function maybe_wrap_field( $html, $settings, $format ) {

		if ( isset( $settings['wrap_with_label'] ) && $settings['wrap_with_label'] ) {
			$settings['html'] = $html;

			/**
			 * Filter the format string to be used.
			 *
			 * @since 1.0.0
			 *
			 * @param string $format   The format string being used.
			 * @param array  $settings The settings array used by the field.
			 */
			$format = apply_filters( 'qs_form_field_wrap_format', $format, $settings );

			$html = sprintp( $format, $settings );
		}

		return $html;
	}

	/**
	 * Build an HTML tag.
	 *
	 * @since 1.0.0
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
			'data_name' => $field, //The name of the postmeta or option to retrieve
			'wrap_with_label' => true //Wether or not to wrap the input in a label
		);

		// Parse the passed settings with the defaults
		$settings = wp_parse_args( $settings, $default_settings );

		/**
		 * Filter the settings array for this field.
		 *
		 * @since 1.0.0
		 *
		 * @param array  $settings The settings array for the field.
		 * @param string $field    The original field name.
		 */
		$settings = apply_filters( 'qs_form_field_settings', $settings, $field );

		// Get the value based on what $post is
		if ( is_null( $data ) ) {
			// Assume it's an option, retrieve it
			$value = get_option( $settings['data_name'] );
		} elseif ( is_object( $data ) ) {
			// Assume a post object, get the metadata for it
			$value = get_post_meta( $data->ID, $settings['data_name'], true );
		} else {
			// Assume literal value
			$value = $data;
		}

		// Check if the "get_values" key is present (and a callback),
		// Apply it and replace "values" key with the returned value.
		if ( isset ( $settings['get_values'] ) && is_callable( $settings['get_values'] ) ) {
			$settings['values'] = call_user_func( $settings['get_values'], $field, $settings, $data );
		}

		/**
		 * Filter the value to be used for the field.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $value The original value for this field.
		 * @param string $field The field this value is for.
		 * @param mixed  $data  The original data argument passed.
		 */
		$value = apply_filters( 'qs_form_field_value', $value, $field, $data );

		// Build the field by calling the appropriate method
		$method = 'build_' . $settings['type'];
		if ( $method != __FUNCTION__ && method_exists( get_called_class(), $method ) ) {
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
	 * @return string The HTML for the fields.
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
			$settings['class'] = array( 'regular-text' );
		} elseif ( ! is_array($settings['class'] ) ) {
			$settings['class'] = (array) $settings['class'];
		}

		$settings['value'] = $value;

		// Build the <input>
		$html = static::build_tag( 'input', $settings );

		$settings['class'] = array(
			$settings['type'] . '-field',
			$settings['id']
		);

		$html = static::maybe_wrap_field( $html, $settings, '<p class="field text-field %type-field %id"><label for="%id">%label:</label> %html</p>' );

		return $html;
	}

	/**
	 * Build a textarea field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_textarea( $field, $settings, $value ) {
		$html = self::build_tag( 'textarea', $settings, $value );

		$html = static::maybe_wrap_field( $html, $settings, '<p class="field textarea-field %id"><label for="%id">%label</label><br> %html</p>' );

		return $html;
	}

	/**
	 * Build a checkbox field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_checkbox( $field, $settings, $value ) {
		if ( ! isset( $settings['value'] ) ) {
			$settings['value'] = 1;
		}

		if ( $value == $settings['value'] ) {
			$settings[] = 'checked';
		}

		// Build the <input>
		$html = self::build_tag( 'input', $settings );

		$html = static::maybe_wrap_field( $html, $settings, '<p class="field checkbox-field %id"><label>%label %html</label></p>' );

		return $html;
	}

	/**
	 * Build a checklist or radio list.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	protected static function build_inputlist( $type, $field, $settings, $value ) {
		$settings['type'] = $type;

		$items = '';

		if ( ! isset( $settings['values'] ) )
			throw new Exception( 'Checklist/radiolist fields MUST have a values parameter.' );

		csv_array_ref( $settings['values'] );

		$is_assoc = is_assoc( $settings['values'] );

		// Run through the values and build the input list
		foreach ( $settings['values'] as $val => $label ) {
			if ( ! $is_assoc ) {
				$val = $label;
			}

			// Build the attributes for the <input>
			$atts = array(
				'type' => $type,
				'id' => $settings['id'] . '__' . sanitize_key( $val ),
				'name' => $settings['name'],
				'value' => $val
			);

			// Check if the value is present or the default one.
			if ( in_array( $val, (array) $value ) || ( ! $value && isset( $settings['default'] ) && $val == $settings['default'] ) ) {
				$atts[] = 'checked';
			}

			// Build the li > label > input markup
			$items .= sprintf(
				'<li class="%1$s %1$s-%2$s"><label>%3$s %4$s</label></li>',
				$settings['id'],
				sanitize_key( $val ),
				static::build_tag(
					'input',
					$atts
				),
				$label
			);
		}

		if ( ! isset( $settings['class'] ) ) {
			$settings['class'] = array();
		} elseif ( ! is_array($settings['class'] ) ) {
			$settings['class'] = (array) $settings['class'];
		}

		$settings['class'][] = $settings['type'] . '-list';

		// Build the <ul>
		$html = self::build_tag( 'ul', $settings, $items, array( 'class', 'id', 'style', 'title' ) );

		$html = static::maybe_wrap_field( $html, $settings, '<p class="field %type-field %id"><label>%label</label></p> %html' );

		return $html;
	}

	/**
	 * Alias to build_inputlist; build a checklist.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_inputlist()
	 */
	public static function build_checklist( $field, $settings, $value ) {
		return static::build_inputlist( 'checkbox', $field, $settings, $value );
	}

	/**
	 * Alias to build_inputlist; build a radiolist.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_inputlist()
	 */
	public static function build_radiolist( $field, $settings, $value ) {
		return static::build_inputlist( 'radio', $field, $settings, $value );
	}

	/**
	 * Build a select field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_select( $field, $settings, $value ) {
		$options = '';

		if ( ! isset( $settings['values'] ) )
			throw new Exception( 'Checklist/radiolist fields MUST have a values parameter.' );

		csv_array_ref( $settings['values'] );

		$is_assoc = is_assoc( $settings['values'] );

		// Run through the values and build the options list
		foreach ( $settings['values'] as $val => $label ) {
			if ( ! $is_assoc ) {
				$val = $label;
			}

			$options .= sprintf(
				'<option value="%s" %s> %s</option>',
				$val,
				in_array( $val, (array) $value ) ? 'selected' : '',
				$label
			);
		}

		// Build the <select>
		$html = self::build_tag( 'select', $settings, $options );

		$html = static::maybe_wrap_field( $html, $settings, '<p class="field select-field %id"><label>%label %html</label></p>' );

		return $html;
	}

	/**
	 * Build an image setter field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_setimage( $field, $settings, $value ) {
		$html = '<div class="qs-setimage">';
			$html .= '<div class="qs-preview">';
				$html .= wp_get_attachment_image( $value, 'thumbnail' );
			$html .= '</div>';
			$html .= '<button type="button" class="button qs-button">' . $settings['label'] . '</button>';
			$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-input">', $settings['name'], $value );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a gallery editor field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_editgallery( $field, $settings, $value ) {
		$html = '<div class="qs-editgallery">';
			$html .= '<div class="qs-preview">';
			foreach ( explode( ',', $value ) as $image ) {
				$html .= wp_get_attachment_image( $image, 'thumbnail' );
			}
			$html .= '</div>';
			$html .= '<button type="button" class="button qs-button">' . $settings['label'] . '</button>';
			$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-input">', $settings['name'], $value );
		$html .= '</div>';

		return $html;
	}
}