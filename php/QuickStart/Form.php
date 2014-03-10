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
	 * Convert a field name to a legible Label
	 *
	 * @since 1.3.3
	 *
	 * @param string $name The name of the field
	 *
	 * @return string The legible label
	 */
	public static function make_label( $name ) {
		return make_legible( static::make_id( $name ) );
	}

	/**
	 * Generate the format string to use in sprintp
	 *
	 * @since 1.4.0
	 *
	 * @param string $side Which side the label should appear on (left/right).
	 * @param string $tag  The tag name to use in the format.
	 *
	 * @return string The generated format string.
	 */
	public static function build_field_wrapper( $side = 'left', $tag = 'div' ) {
		$format = '<' . $tag . ' class="qs-field %type %wrapper_class %id-field">';

		$label = '<label for="%id" class="qs-label">%label</label>';
		if ( $side == 'right' ) {
			$format .= "%input $label";
		} else {
			$format .= "$label %input";
		}

		$format .= '</' . $tag . '>';

		return $format;
	}

	/**
	 * Wrap the field in a label, if wrap_with_label is true.
	 *
	 * @since 1.4.0 Renamed $html to $input, revised $format handling
	 * @since 1.0.0
	 *
	 * @param string $input    The html of the input to wrap.
	 * @param array  $settings The settings array for the field.
	 * @param string $format   The format to use.
	 *
	 * @return string The processed HTML.
	 */
	public static function maybe_wrap_field( $input, $settings, $format = null ) {
		// If format setting exists, overwrite $format with it
		if ( isset( $settings['format'] ) ) {
			$format = $settings['format'];
		}

		// If no format provided, make it an empty array
		if ( is_null( $format ) ) {
			$format = array();
		}

		// If $format is an array, run through build_field_wrapper()
		if ( is_array( $format ) ) {
			$format = call_user_func_array( 'static::build_field_wrapper', $format );
		}

		if ( isset( $settings['wrap_with_label'] ) && $settings['wrap_with_label'] ) {
			$settings['input'] = $input;

			/**
			 * Filter the format string to be used.
			 *
			 * @since 1.0.0
			 *
			 * @param string $format   The format string being used.
			 * @param array  $settings The settings array used by the field.
			 */
			$format = apply_filters( 'qs_form_field_wrap_format', $format, $settings );

			return sprintp( $format, $settings );
		} else {
			return $input;
		}
	}

	/**
	 * Get the value to use for the field.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed  $data The raw data source.
	 * @param string $type The type of source to expect. e.g. "post", "option", "array", or "raw".
	 * @param string $key  The field to extract from the source.
	 *
	 * @return mixed The extracted value.
	 */
	public static function get_value( $data, $type, $key ) {
		// Proceed based on what $type is
		switch ( $type ) {
			case 'post':
				// Get the matching meta value for this post
				if ( is_object( $data ) ) {
					$data = $data->ID;
				}
				return get_post_meta( $data, $key, true );
			case 'option':
				// Get the matching option value
				return get_option( $key );
			case 'array':
				// Get the matching entry if present
				return isset( $data[ $key ] ) ? $data[ $key ] : null;
			default:
				// No processing required
				return $data;
		}
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
			$accepted = static::$accepted_attrs;
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
	 * @since 1.4.0 Added 'source' argument.
	 * @since 1.3.3 Added use of new make_label() method.
	 * @since 1.3.0 Added $wrap argument for setting default wrap_with_label value,
	 *				also merged filters into one, and added 'build' callback.
	 * @since 1.1.0 Added check if $settings is a callback.
	 * @since 1.0.0
	 *
	 * @param string $field    The name/id of the field.
	 * @param array  $settings The settings to use in creating the field.
	 * @param mixed  $data     The source for the value; use $type argument to specify.
	 * @param string $source   The type of value source; see static::get_value().
	 * @param bool   $wrap     Default value for wrap_with_label option.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_field( $field, $settings = array(), $data = null, $source = 'raw', $wrap = true ) {
		// Check if $settings is a callback, call and return it's result if so
		if ( is_callable( $settings ) ) {
			/**
			 * Build the HTML of the field
			 *
			 * @since 1.1.0
			 *
			 * @param mixed  $data  The source for the value.
			 * @param string $field The name of the field to build.
			 *
			 * @return string The HTML for the field.
			 */
			return call_user_func( $settings, $data, $field );
		}

		$default_settings = array(
			'type'            => 'text',
			'id'              => static::make_id( $field ),
			'name'            => $field,
			'label'           => static::make_label( $field ),
			'data_name'       => $field, // The name of the postmeta or option to retrieve
			'wrap_with_label' => $wrap, // Wether or not to wrap the field in a label
			'wrapper_class'   => '', // The class to apply to the wrapper
		);

		// Parse the passed settings with the defaults
		$settings = wp_parse_args( $settings, $default_settings );

		// Get the value to use, based on $source and data_name
		$value = static::get_value( $data, $source, $settings['data_name'] );

		// Set a default value for the class setting;
		// otherwise, make sure it's an array
		if ( ! isset( $settings['class'] ) ) {
			$settings['class'] = array();
		} elseif ( ! is_array($settings['class'] ) ) {
			$settings['class'] = (array) $settings['class'];
		}

		// Check if the "get_values" key is present (and a callback),
		// Apply it and replace "values" key with the returned value.
		if ( isset ( $settings['get_values'] ) && is_callable( $settings['get_values'] ) ) {
			/**
			 * Custom callback for getting the values setting for the field.
			 *
			 * @since 1.3.0
			 *
			 * @param string $field    The name of the field to build.
			 * @param array  $settings The settings for the field.
			 * @param mixed  $data     The original data passed to this function.
			 *
			 * @return mixed The values setting for the field.
			 */
			$settings['values'] = call_user_func( $settings['get_values'], $field, $settings, $data );
		}

		// Build the field by calling the appropriate method
		$method = 'build_' . $settings['type'];
		if ( isset( $settings['build'] ) && is_callable( $settings['build'] ) ) {
			/**
			 * Custom callback for building the field's HTML.
			 *
			 * @since 1.4.0 Argument order is now $settings, $value, $field.
			 * @since 1.3.0
			 *
			 * @param array  $settings The settings for the field.
			 * @param mixed  $value    The retrieved value of the field.
			 * @param string $field    The name of the field to build.
			 *
			 * @return string The HTML of the field.
			 */
			$html = call_user_func( $settings['build'], $settings, $value, $field );
		} elseif ( $method != __FUNCTION__ && method_exists( get_called_class(), $method ) ) {
			// Matches one of the specialized internal field builders
			$html = static::$method( $settings, $value );
		} else {
			// Assume a text-like input, use the generic field builder
			$html = static::build_generic( $settings, $value );
		}

		/**
		 * Filter the HTML of the field.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed  $value    The original html for this field.
		 * @param string $field    The field this html is for.
		 * @param mixed  $settings The settings for this field.
		 * @param mixed  $value    The processed value for this field.
		 * @param mixed  $data     The source of the value for this field.
		 * @param string $source   The type of value source; see static::get_value().
		 *
		 * @return string The HTML of the field.
		 */
		$html = apply_filters( 'qs_form_field', $html, $field, $settings, $value, $data, $source );

		return $html;
	}

	/**
	 * Build a single field, based on the passed configuration data.
	 *
	 * @since 1.4.0 Added 'source' argument.
	 * @since 1.3.0 Added 'wrap' argument.
	 * @since 1.0.0
	 *
	 * @param string $fields The name/id of the field.
	 * @param array  $data   The source for the values; see static::build_field() for details.
	 * @param string $source Identifies the type of values source; see static::build_field() for details.
	 * @param mixed  $echo   Wether or not to echo the output.
	 * @param bool   $wrap   Default value for wrap_with_label option.
	 *
	 * @return string The HTML for the fields.
	 */
	public static function build_fields( $fields, $data = null, $source = 'raw', $echo = false, $wrap = true ) {
		$html = '';

		// If $fields is actually meant to be an array of all arguments for this
		// method, it should include the __extract value, extract if so.
		if ( in_array( '__extract', $fields ) ) {
			extract( $fields );
		}

		// Check if $fields is a callback, run it if so.
		if ( is_callable( $fields ) ) {
			$html .= call_user_func( $fields, $data );
		} else {
			csv_array_ref( $fields );

			// Run through each field; key is the field name, value is the settings
			foreach ( $fields as $field => $settings ) {
				make_associative( $field, $settings );
				$html .= static::build_field( $field, $settings, $data, $wrap );
			}
		}

		// Echo the output if desired
		if ( $echo ) {
			echo $html;
		}

		return $html;
	}

	/**
	 * Build a generic field (e.g. text, number, email, etc.)
	 *
	 * @since 1.4.0 Dropped $field arg, added $wrapper arg, revised wrapping usage.
	 * @since 1.0.0
	 *
	 * @param array  $settings The settings to use in creating the field.
	 * @param mixed  $value    The value to fill the field with.
	 * @param string $wrapper  The format string to use when wrapping the field.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_generic( $settings, $value, $wrapper = null ) {
		// Load the value attribute with the field value
		$settings['value'] = $value;

		// Build the <input>
		$input = static::build_tag( 'input', $settings );

		// Add the generic class to the wrapper classes
		$settings['wrapper_class'] .= ' generic';

		// Wrap the input in the html if needed
		$html = static::maybe_wrap_field( $input, $settings, $wrapper );

		return $html;
	}

	/**
	 * Build a textarea field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_textarea( $settings, $value, $wrapper = null ) {
		// Build the <input>
		$input = static::build_tag( 'textarea', $settings, $value );

		// Wrap the input in the html if needed
		$html = static::maybe_wrap_field( $input, $settings, $wrapper );

		return $html;
	}

	/**
	 * Build a checkbox field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_checkbox( $settings, $value, $wrapper = null ) {
		// Default the value to 1 if it's a checkbox
		if ( $settings['type'] == 'checkbox' && ! isset( $settings['value'] ) ) {
			$settings['value'] = 1;
		}

		// If the values match, mark as checked
		if ( $value == $settings['value'] || ( is_array( $value ) && in_array( $settings['value'], $value ) ) ) {
			$settings[] = 'checked';
		}

		// Build the <input>
		$input = static::build_tag( 'input', $settings );

		// Wrap the input in the html if needed
		$html = static::maybe_wrap_field( $input, $settings, $wrapper );

		return $html;
	}

	/**
	 * Build a radio field.
	 *
	 * This uses build_checkbox wrather than build_generic,
	 * since it's not a text-style input.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_checkbox()
	 */
	public static function build_radio( $settings, $value, $wrapper = null ) {
		return static::build_checkbox( $settings, $value, $wrapper );
	}

	/**
	 * Build a select field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_select( $settings, $value, $wrapper = null ) {
		$options = '';

		if ( ! isset( $settings['values'] ) ) {
			throw new Exception( 'Select fields MUST have a values parameter.' );
		}

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
		$input = static::build_tag( 'select', $settings, $options );

		$html = static::maybe_wrap_field( $input, $settings, $wrapper );

		return $html;
	}

	/**
	 * Build a checklist or radio list.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	protected static function build_inputlist( $type, $settings, $value, $wrapper = null ) {
		if ( ! isset( $settings['values'] ) ) {
			throw new Exception( 'Checklist/radiolist fieldsets MUST have a values parameter.' );
		}

		// If no value exists, and there is a default value set, use it.
		if ( is_null( $value ) && isset( $settings['default'] ) ) {
			$value = $settings['default'];
		}

		csv_array_ref( $settings['values'] );
		$is_assoc = is_assoc( $settings['values'] );

		$items = '';
		// Run through the values and build the input list
		foreach ( $settings['values'] as $val => $label ) {
			if ( ! $is_assoc ) {
				$val = $label;
			}

			// Build the settings for the item
			$item_settings = array(
				'type'            => $type,
				'id'              => $settings['id'] . '__' . sanitize_key( $val ),
				'name'            => $settings['name'],
				'value'           => $val,
				'label'           => $label,
				'wrap_with_label' => true,
				'wrapper_class'   => '',
			);

			if ( $type == 'checkbox' ) {
				// Append brackets to name attribute
				$item_settings['name'] .= '[]';
			}

			$build = "build_$type";

			$items .= static::$build( $field, $item_settings, $value, array('right', 'li') );
		}

		$settings['class'][] = 'inputlist';

		// Build the list
		$list = static::build_tag( 'ul', $settings, $items, array( 'class', 'id', 'style', 'title' ) );

		if ( is_null( $wrapper ) ) {
			$wrapper = '<div class="qs-fieldset inputlist %type %wrapper_class %id"><p class="qs-legend">%label</p> %input</div>';
		}

		// Optionally wrap the fieldset
		$html = static::maybe_wrap_field( $list, $settings, $wrapper );

		return $html;
	}

	/**
	 * Alias to build_inputlist; build a checklist.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_inputlist()
	 */
	public static function build_checklist( $settings, $value, $wrapper = null ) {
		return static::build_inputlist( 'checkbox', $settings, $value, $wrapper );
	}

	/**
	 * Alias to build_inputlist; build a radiolist.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_inputlist()
	 */
	public static function build_radiolist( $settings, $value, $wrapper = null ) {
		return static::build_inputlist( 'radio', $settings, $value, $wrapper );
	}

	/**
	 * Build a file adder field.
	 *
	 * @since 1.3.3
	 *
	 * @see Form::build_generic()
	 */
	public static function build_addfile( $settings, $value ) {
		$html = '<div class="qs-field qs-media qs-addfile">';
			$html .= '<div class="qs-preview">';
				$html .= basename(wp_get_attachment_url($value));
			$html .= '</div>';
			$html .= '<button type="button" class="button qs-button">' . $settings['label'] . '</button>';
			$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-value">', $settings['name'], $value );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build an image setter field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_setimage( $settings, $value ) {
		$html = '<div class="qs-field qs-media qs-setimage">';
			$html .= '<div class="qs-preview">';
				$html .= wp_get_attachment_image( $value, 'thumbnail' );
			$html .= '</div>';
			$html .= '<button type="button" class="button qs-button">' . $settings['label'] . '</button>';
			$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-value">', $settings['name'], $value );
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
	public static function build_editgallery( $settings, $value ) {
		$html = '<div class="qs-field qs-media qs-editgallery">';
			$html .= '<div class="qs-preview">';
			foreach ( explode( ',', $value ) as $image ) {
				$html .= wp_get_attachment_image( $image, 'thumbnail' );
			}
			$html .= '</div>';
			$html .= '<button type="button" class="button qs-button">' . $settings['label'] . '</button>';
			$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-value">', $settings['name'], $value );
		$html .= '</div>';

		return $html;
	}
}