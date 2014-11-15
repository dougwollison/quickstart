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
	 * Convert a field name to a valid ID.
	 *
	 * @since 1.6.0 Fixed to catch all brackets.
	 * @since 1.0.0
	 *
	 * @param string $name The name of the field.
	 *
	 * @return string The valid ID.
	 */
	public static function make_id( $name ) {
		return preg_replace( '/\[(.+?)\]/', '_$1', $name );
	}

	/**
	 * Convert a field name to a legible label.
	 *
	 * @since 1.3.3
	 *
	 * @param string $name The name of the field.
	 *
	 * @return string The legible label.
	 */
	public static function make_label( $name ) {
		return make_legible( static::make_id( $name ) );
	}

	/**
	 * Generate the format string to use in sprintp.
	 *
	 * @since 1.6.3 Added %description placeholder and qs-field-[side] class.
	 * @since 1.5.0 Added %id-field id.
	 * @since 1.4.0
	 *
	 * @param string $side Which side the label should appear on (left/right).
	 * @param string $tag  The tag name to use in the format.
	 *
	 * @return string The generated format string.
	 */
	public static function build_field_wrapper( $side = 'left', $tag = 'div' ) {
		$format = '<' . $tag . ' class="qs-field %type %wrapper_class" id="%id-field">';

		$label = '<label for="%id" class="qs-label qs-label-' . $side . '">%label</label>';
		if ( $side == 'right' ) {
			$format .= "%input $label";
		} else {
			$format .= "$label %input";
		}
		
		$format .= '%description';

		$format .= '</' . $tag . '>';

		return $format;
	}

	/**
	 * Wrap the field in a label, if wrap_with_label is true.
	 *
	 * @since 1.6.3 Added handling of description option, mild restructuring.
	 * @since 1.4.0 Renamed $html to $input, revised $format handling.
	 * @since 1.0.0
	 *
	 * @param string $input    The html of the input to wrap.
	 * @param array  $settings The settings array for the field.
	 * @param string $format   Optional The format to use.
	 *
	 * @return string The processed HTML.
	 */
	public static function maybe_wrap_field( $input, $settings, $format = null ) {
		// If a description is set, prep it
		if ( isset( $settings['description'] ) && ! empty( $settings['description'] ) ) {
			$settings['description'] = sprintf( '<p class="description">%s</p>', $settings['description'] );
		} else {
			// Make sure it's set but blank if not
			$settings['description'] = '';
		}

		if ( isset( $settings['wrap_with_label'] ) && $settings['wrap_with_label'] ) {
			// If format setting exists, overwrite $format with it
			if ( isset( $settings['format'] ) ) {
				$format = $settings['format'];
			}
	
			// If no format is provided, make it an empty array
			if ( is_null( $format ) ) {
				$format = array();
			}
	
			// If $format is an array, run through build_field_wrapper()
			if ( is_array( $format ) ) {
				$format = call_user_func_array( 'static::build_field_wrapper', $format );
			}

			/**
			 * Filter the format string to be used.
			 *
			 * @since 1.0.0
			 *
			 * @param string $format   The format string being used.
			 * @param array  $settings The settings array used by the field.
			 */
			$format = apply_filters( 'qs_form_field_wrap_format', $format, $settings );
			
			// Store the input HTML in the 'input' setting for sprintp()
			$settings['input'] = $input;

			return sprintp( $format, $settings );
		} else {
			// Manually append the description
			$input .= $settings['description'];
			return $input;
		}
	}

	/**
	 * Get the value to use for the field.
	 *
	 * @since 1.6.0 Added use of extract_value().
	 * @since 1.4.0
	 *
	 * @uses extract_value()
	 *
	 * @param mixed  $data The raw data source.
	 * @param string $type The type of source to expect (e.g. "post", "option", "array", or "raw").
	 * @param string $key  The field to extract from the source.
	 *
	 * @return mixed The extracted value.
	 */
	public static function get_value( $data, $type, $key ) {
		if ( preg_match( '/([\w-]+)\[([\w-]+)\](.*)/', $key, $matches ) ) {
			// Field is an array map, get the actual key...
			$key = $matches[1];
			// ... and the map to use.
			$map = $matches[2] . $matches[3];
		}

		// Proceed based on what $type is
		switch ( $type ) {
			case 'post':
				// Get the matching meta value for this post
				if ( is_object( $data ) ) {
					$data = $data->ID;
				}
				$value = get_post_meta( $data, $key, true );
				break;
			case 'option':
				// Get the matching option value
				$value = get_option( $key );
				break;
			case 'array':
				// Get the matching entry if present
				$value = isset( $data[ $key ] ) ? $data[ $key ] : null;
				break;
			default:
				// No processing required
				$value = $data;
				break;
		}

		if ( $map ) {
			$value = extract_value( $value, $map );
		}

		return $value;
	}

	/**
	 * Build a single field, based on the passed configuration data.
	 *
	 * @since 1.6.0 Added qs_field_ prefix to field id, get_value() use for callback.
	 * @since 1.5.0 Added "taxonomy" option handling.
	 * @since 1.4.2 Added "get_value" and "post_field" option handling.
	 * @since 1.4.0 Added $source argument.
	 * @since 1.3.3 Added use of new make_label() method.
	 * @since 1.3.0 Added $wrap argument for setting default wrap_with_label value,
	 *				also merged filters into one, and added 'build' callback.
	 * @since 1.1.0 Added check if $settings is a callback.
	 * @since 1.0.0
	 *
	 * @param string $field    The name/id of the field.
	 * @param array  $settings Optional The settings to use in creating the field.
	 * @param mixed  $data     Optional The source for the value; use $source argument to specify.
	 * @param string $source   Optional The type of value source; see static::get_value().
	 * @param bool   $wrap     Optional Default value for wrap_with_label option.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_field( $field, $settings = array(), $data = null, $source = 'raw', $wrap = true ) {
		// Check if $settings is a callback, call and return it's result if so
		if ( is_callable( $settings ) ) {
			// Get the value
			$value = static::get_value( $data, $source, $field );

			/**
			 * Build the HTML of the field.
			 *
			 * @since 1.1.0
			 *
			 * @param mixed  $data  The source for the value.
			 * @param string $field The name of the field to build.
			 *
			 * @return string The HTML for the field.
			 */
			return call_user_func( $settings, $value, $field );
		}

		$default_settings = array(
			'type'            => 'text',
			'id'              => 'qs_field_' . static::make_id( $field ),
			'name'            => $field,
			'label'           => static::make_label( $field ),
			'data_name'       => $field, // The name of the postmeta or option to retrieve
			'wrap_with_label' => $wrap, // Wether or not to wrap the field in a label
			'wrapper_class'   => '', // The class to apply to the wrapper
		);

		// Parse the passed settings with the defaults
		$settings = wp_parse_args( $settings, $default_settings );

		// Get the value to use, first by checking if the "get_value" callback is present
		if ( isset( $settings['get_value'] ) && is_callable( $settings['get_value'] ) ) {
			/**
			 * Custom callback for getting the value to use for building the field.
			 *
			 * @since 1.4.2
			 *
			 * @param mixed  $source   The source for the value.
			 * @param array  $settings The settings for the field.
			 * @param string $field    The name of the field being built.
			 * @param string $source   The type of value source.
			 *
			 * @return mixed The value to use for building the field.
			 */
			$value = call_user_func( $settings['get_value'], $data, $source, $settings, $field );
		} elseif ( isset( $settings['post_field'] ) && $settings['post_field'] && $source == 'post' ) {
			// Alternately, if "post_field" is present (and the source is a post), get the matching field
			$value = $data->{$settings['post_field']};
		} elseif ( isset( $settings['taxonomy'] ) && $settings['taxonomy'] && $source == 'post' ) {
			// Alternately, if "taxonomy" is present (and the source is a post), get the matching terms

			// Get the post_terms for $value
			$post_terms = get_the_terms( $data->ID, $settings['taxonomy'] );
			$value = array_map( function( $term ) {
				return $term->term_id;
			}, $post_terms );

			// Get the available terms for the values list
			$tax_terms = get_terms( $settings['taxonomy'], 'hide_empty=0' );
			$settings['values'] = simplify_object_array( $tax_terms, 'term_id', 'name' );
		} else {
			// Otherwise, use the built in get_value method
			$value = static::get_value( $data, $source, $settings['data_name'] );
		}

		// Set a default value for the class setting;
		// otherwise, make sure it's an array
		if ( ! isset( $settings['class'] ) ) {
			$settings['class'] = array();
		} elseif ( ! is_array( $settings['class'] ) ) {
			$settings['class'] = (array) $settings['class'];
		}

		// Check if the "get_values" callback is present,
		// Run it and replace "values" key with the returned value.
		if ( isset( $settings['get_values'] ) && is_callable( $settings['get_values'] ) ) {
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
	 * @since 1.4.1 Updated build_field call to include $source argument.
	 * @since 1.4.0 Added 'source' argument.
	 * @since 1.3.0 Added 'wrap' argument.
	 * @since 1.0.0
	 *
	 * @param string $fields The name/id of the field.
	 * @param array  $data   Optional The source for the values; see static::build_field() for details.
	 * @param string $source Optional Identifies the type of values source; see static::build_field() for details.
	 * @param mixed  $echo   Optional Wether or not to echo the output.
	 * @param bool   $wrap   Optional Default value for wrap_with_label option.
	 *
	 * @return string The HTML for the fields.
	 */
	public static function build_fields( $fields, $data = null, $source = 'raw', $echo = false, $wrap = true ) {
		// If $fields is actually meant to be an array of all arguments for this
		// method, it should include the __extract value, extract if so.
		if ( in_array( '__extract', $fields ) ) {
			extract( $fields );
		}

		// Check if $fields is a callback, run it if so.
		if ( is_callable( $fields ) ) {
			/**
			 * Build the HTML of the fields.
			 *
			 * @since 1.1.0
			 *
			 * @param mixed $data The data source.
			 *
			 * @return string The HTML of the fields.
			 */
			$html = call_user_func( $fields, $data );
		} else {
			$html = '';
			csv_array_ref( $fields );

			// Run through each field; key is the field name, value is the settings
			foreach ( $fields as $field => $settings ) {
				make_associative( $field, $settings );
				$html .= static::build_field( $field, $settings, $data, $source, $wrap );
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
	 * @param string $wrapper  Optional The format string to use when wrapping the field.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_generic( $settings, $value, $wrapper = null ) {
		// Load the value attribute with the field value
		$settings['value'] = $value;

		// Build the <input>
		$input = Tools::build_tag( 'input', $settings );

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
		$input = Tools::build_tag( 'textarea', $settings, $value );

		// Wrap the input in the html if needed
		$html = static::maybe_wrap_field( $input, $settings, $wrapper );

		return $html;
	}

	/**
	 * Build a select field.
	 *
	 * @since 1.5.0 Add "null" option handling.
	 * @since 1.4.2 Added [] to field name when multiple is true.
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_select( $settings, $value, $wrapper = null ) {
		$options = '';

		// Ensure a values setting has been passed
		if ( ! isset( $settings['values'] ) ) {
			throw new Exception( 'Select fields MUST have a values parameter.' );
		}

		// If multiple, add [] to the field name
		if ( isset( $settings['multiple'] ) && $settings['multiple'] ) {
			$settings['name'] .= '[]';
		}

		csv_array_ref( $settings['values'] );

		$is_assoc = is_assoc( $settings['values'] );

		// Add a null option if requested
		if ( isset( $settings['null'] ) ) {
			$options .= sprintf( '<option value="">%s</option>', $settings['null'] );
		}

		// Run through the values and build the options list
		foreach ( $settings['values'] as $val => $label ) {
			if ( ! $is_assoc ) {
				$val = $label;
			}

			$options .= sprintf(
				'<option value="%s" %s>%s</option>',
				$val,
				in_array( $val, (array) $value ) ? 'selected' : '',
				$label
			);
		}

		// Build the <select>
		$input = Tools::build_tag( 'select', $settings, $options );

		$html = static::maybe_wrap_field( $input, $settings, $wrapper );

		return $html;
	}

	/**
	 * Build a checkbox field.
	 *
	 * @since 1.4.2 Added $dummy argument and printing of dummy input for null value.
	 * @since 1.4.1 Added modified default value for $wrapper.
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 *
	 * @param bool $dummy Wether or not to print a hidden null input first.
	 */
	public static function build_checkbox( $settings, $value, $wrapper = null, $dummy = true ) {
		// Default the value to 1 if it's a checkbox
		if ( $settings['type'] == 'checkbox' && ! isset( $settings['value'] ) ) {
			$settings['value'] = 1;
		}

		// Default the wrapper to right sided
		if ( is_null( $wrapper ) ) {
			$wrapper = array( 'right' );
		}

		// If the values match, mark as checked
		if ( $value == $settings['value'] || ( is_array( $value ) && in_array( $settings['value'], $value ) ) ) {
			$settings[] = 'checked';
		}

		// Build the dummy <input> if enabled
		$hidden = '';
		if ( $dummy ) {
			$hidden = Tools::build_tag( 'input', array(
				'type' => 'hidden',
				'name' => $settings['name'],
				'value' => null,
			) );
		}

		// Build the actual <input>
		$input = Tools::build_tag( 'input', $settings );

		// Wrap the inputs in the html if needed
		$html = static::maybe_wrap_field( $hidden . $input, $settings, $wrapper );

		return $html;
	}

	/**
	 * Build a radio field.
	 *
	 * This uses build_checkbox rather than build_generic,
	 * since it's not a text-style input.
	 *
	 * @since 1.4.0
	 *
	 * @see Form::build_checkbox()
	 */
	public static function build_radio( $settings, $value, $wrapper = null, $dummy = true ) {
		return static::build_checkbox( $settings, $value, $wrapper, $dummy );
	}

	/**
	 * Build a checklist or radio list.
	 *
	 * @since 1.6.0 Added checked_first support.
	 * @since 1.5.0 Added %id-fieldset id.
	 * @since 1.4.2 Added dummy input for null value.
	 * @since 1.4.0 Overhauled item building and wrapper handling.
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

		$items = array();
		$i = 0;
		// Run through the values and prep the input list
		foreach ( $settings['values'] as $val => $label ) {
			if ( ! $is_assoc ) {
				$val = $label;
			}

			// Build the settings for the item
			$item_settings = array(
				'qs-order'        => $i,
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

			// If the values match, mark as checked
			$item_settings['checked'] = $value == $item_settings['value'] || ( is_array( $value ) && in_array( $item_settings['value'], $value ) );

			// Add the settings to the item list
			$items[] = $item_settings;

			$i++;
		}

		// Sort the items with the checked ones first unless not wanted
		if ( ! isset( $settings['checked_first'] ) || $settings['checked_first'] ) {
			usort( $items, function( $a, $b ) {
				$a_checked = $a['checked'] ? 1 : 0;
				$b_checked = $b['checked'] ? 1 : 0;

				if ( $a_checked == $b_checked ) {
					// Maintain original order otherwise
					return $a['qs-order'] - $b['qs-order'];
				}

				return $a_checked > $b_checked ? -1 : 1;
			});
		}

		// Now actually build the input list
		foreach ( $items as &$item ){
			// Get the type to determine the callback to use
			$type = $item['type'];
			$build = "build_$type";

			// Add the input, wrapped in a list item (and sans the dummy input)
			$item = static::$build( $item, null, array( 'right', 'li' ), false );
		}

		$items = implode( '', $items );

		$settings['class'][] = 'inputlist';

		// Build the list
		$list = Tools::build_tag( 'ul', $settings, $items, array( 'class', 'id', 'style', 'title' ) );

		if ( is_null( $wrapper ) ) {
			$wrapper = '<div class="qs-fieldset inputlist %type %wrapper_class" id="%id-fieldset"><p class="qs-legend">%label</p> %input</div>';
		}

		// Build a dummy <input>
		$hidden = Tools::build_tag( 'input', array(
			'type' => 'hidden',
			'name' => $settings['name'],
			'value' => null,
		) );

		// Optionally wrap the fieldset
		$html = static::maybe_wrap_field( $hidden . $list, $settings, $wrapper );

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
	 * Build a single file adder item.
	 *
	 * @since 1.6.0 Added quick sort support.
	 * @since 1.4.0
	 *
	 * @param int    $id       The ID of the attachment to use.
	 * @param string $name     The name of the file adder field.
	 * @param bool   $is_image Wether or not this is for images or any file.
	 * @param bool   $is_multi Wether or not this supports multiple files.
	 * @param bool   $use_sort Wether or not quick sort is desired.
	 * @param string $show     What to display of the non-image file (title|filename).
	 *
	 * @return string The markup fo the item.
	 */
	public static function build_addfile_item( $id, $name, $is_image, $is_multi, $use_sort, $show ) {
		if ( $use_sort && ! is_null( $id ) ) {
			// Setup item for quick sort support
			$item_name = sanitize_title( basename( wp_get_attachment_url( $id ) ) );
			$item_date = get_the_date( 'U' );
			$html = sprintf( '<div class="qs-item" data-name="%s" data-date="%s">', $item_name, $item_date );
		} else {
			$html = '<div class="qs-item">';
		}

		if ( is_null( $id ) ) {
			// No id passed, print a blank
			$html .= $is_image ? '<img class="qs-preview" />' : '<span class="qs-preview"></span>';
		} elseif ( $is_image ) {
			// Image mode, print the thumbnail
			$html .= wp_get_attachment_image( $id, 'thumbnail', false, array(
				'class' => 'qs-preview',
			) );
		} else {
			// Any kind of file, print the attachment title or filename
			$preview = basename( wp_get_attachment_url( $id ) );
			if ( $show == 'title' ) {
				$preview = get_the_title( $id );
			}
			$html .= '<span class="qs-preview">' . $preview . '</span>';
		}

		// Add delete button and field name brackets if in mulitple mode
		if ( $is_multi ) {
			$html .= '<button type="button" class="button qs-delete">Delete</button>';
			$name .= '[]';
		}

		// Add the input field for this item
		$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-value">', $name, $id );

		$html .= '</div>';
		return $html;
	}

	/**
	 * Build a file adder field.
	 *
	 * @since 1.6.2 Fixed template output to include $show option.
	 * @since 1.6.0 Added qs-sortable class with data-axis attribute, quick sort support, "show" option.
	 * @since 1.4.0 Overhauled markup/functionality.
	 * @since 1.3.3
	 *
	 * @see Form::build_generic()
	 */
	public static function build_addfile( $settings, $value ) {
		// Get the field name
		$name = $settings['name'];

		// Determine if this is a muti-item adder
		$is_multi = isset( $settings['multiple'] ) && $settings['multiple'];

		// Determine if use of quick sort buttons is desired
		$use_sort = $is_multi && isset( $settings['quicksort'] ) && $settings['quicksort'];

		// Determine the media type
		$media = isset( $settings['media'] ) ? $settings['media'] : null;

		// Determine what to display for plain files (default to filename)
		$show = isset( $settings['show'] ) ? $settings['show'] : 'filename';

		// Flag for if we're using images only or not
		$is_image = $media == 'image';

		// If the label seems auto generated, modify the label text to Add/Choose
		if ( $settings['label'] == make_legible( $name ) ) {
			$settings['label'] = ( $is_multi ? 'Add' : 'Choose' ) . ' ' . $settings['label'];
		}

		// Setup the classes for the container
		$classes = array( 'qs-field', 'qs-media', 'qs-addfile' );
		if ( $is_multi ) {
			$classes[] = 'multiple';
		}
		if ( $media ) {
			$classes[] = 'media-' . $media;
		}

		// Begin the markup for this component
		$html = sprintf( '<div class="%s" data-type="%s" data-show="%s">', implode( ' ', $classes ), $media, $show );
			// The button to open the media manager
			$html .= '<button type="button" class="button button-primary qs-button">' . $settings['label'] . '</button>';

			// A button to clear all items currently loaded
			$html .= ' <button type="button" class="button qs-clear">Clear</button>';

			// Start the preview list container, adding sortable class and axis if needed
			$html .= sprintf( '<div class="qs-container %s" %s>', $is_multi ? 'qs-sortable' : '', $is_image ? '' : 'data-axis="y"' );
			// Print the items if present
			if ( $value ) {
				// Process into an appropriate array
				$value = (array) $value;

				// Loop through each image and print an item
				foreach ( $value as $file ) {
					// Add an item for the current file
					$html .= static::build_addfile_item( $file, $name, $is_image, $is_multi, $use_sort, $show );

					// If we're only to do a single item, break now.
					if ( ! $is_multi ) {
						break;
					}
				}
			}
			$html .= '</div>';

			// Add quick sort buttons if enabled
			if ( $use_sort ) {
				$html .= '<div class="qs-sort">
					<label>Quick Sort:</label>
					<button type="button" class="button-secondary" value="name">Alphabetical</button>
					<button type="button" class="button-secondary" value="date">Date</button>
					<button type="button" class="button-secondary" value="flip">Reverse</button>
				</div>';
			}

			// Print the template so javascript knows how to add new items
			$html .= '<template class="qs-template">';
				$html .= static::build_addfile_item( null, $name, $is_image, $is_multi, $use_sort, $show );
			$html .= '</template>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build an image setter field.
	 *
	 * @since 1.4.0 Reduced to alias of build_addfile
	 * @since 1.0.0
	 *
	 * @see Form::build_addfile()
	 */
	public static function build_setimage( $settings, $value ) {
		// Force the media type to image
		$settings['media'] = 'image';

		return static::build_addfile( $settings, $value );
	}

	/**
	 * Build a gallery editor field.
	 *
	 * @since 1.6.0 Added clear button, data-id to image for inline sortability.
	 * @since 1.4.0 Added semi-intelligent button text guessing.
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_editgallery( $settings, $value ) {
		// If the label seems auto generated, modify the label text to Edit [label]
		if ( $settings['label'] == make_legible( $settings['name'] ) ) {
			$settings['label'] = 'Edit ' . $settings['label'];
		}

		$html = '<div class="qs-field qs-media qs-editgallery">';
			$html .= '<button type="button" class="button-primary qs-button">' . $settings['label'] . '</button>';
			$html .= ' <button type="button" class="button qs-clear">Clear</button>';
			$html .= '<div class="qs-preview">';
			foreach ( explode( ',', $value ) as $image ) {
				$html .= wp_get_attachment_image( $image, 'thumbnail', false, array( 'data-id' => $image ) );
			}
			$html .= '</div>';
			$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-value">', $settings['name'], $value );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a single repeater item.
	 *
	 * @since 1.5.0
	 *
	 * @param array $repeater The settings of the repeater.
	 * @param array $item     Optional The item data.
	 * @param int   $i        Optional The item's number (-1 for template).
	 */
	private static function build_repeater_item( $repeater, $item = null, $i = -1 ) {
		$fields = csv_array( $repeater['template'] );

		$html = '<div class="qs-item">';
			if ( is_callable( $fields ) ) {
				/**
				 * Custom callback for building a repeater item.
				 *
				 * @since 1.5.0
				 *
				 * @param mixed  $item The data for this item.
				 * @param int    $i The index of this item.
				 * @param string $name The name of this repeater's field.
				 * @param array  $settings The settings for this repeater.
				 *
				 * @return string The HTML of the repeater item.
				 */
				$html .= call_user_func( $fields, $item, $i );
			} elseif ( is_array( $fields ) ) {
				// Loop through each field in the template, and build them
				foreach ( $fields as $field => $settings ) {
					make_associative( $field, $settings );

					// Create the name for the field
					$settings['name'] = sprintf( '%s[%d][%s]', $repeater['name'], $i, $field );

					// Create the ID for the field
					$settings['id'] = static::make_id( $field ) . '-';

					// Add a unique string to the end of the ID or a % placeholder for the blank
					$settings['id'] .= $i == -1 ? '%' : substr( md5( $field.$i ), 0, 6 );

					// Set the value for the field
					if ( is_null( $item ) || ! isset( $item[ $field ] ) ) {
						$value = '';
					} else {
						$value = $item[ $field ];
					}

					// Finally, build the field
					$html .= static::build_field( $field, $settings, $value );
				}
			}
			$html .= '<button type="button" class="button qs-delete">Delete</button>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a repeater interface.
	 *
	 * @since 1.5.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_repeater( $settings, $data ) {
		if ( ! isset( $settings['template'] ) ) {
			throw new Exception( 'Repeater fields MUST have a template parameter.' );
		}

		// Get the field name
		$name = $settings['name'];

		// Get the value to use, based on $source and the data_name
		$values = static::get_value( $data, $source, $field );

		// If the label seems auto generated, modify the label text to Add/Choose
		if ( $settings['label'] == make_legible( $name ) ) {
			$settings['label'] = 'Add ' . $settings['label'];
		}

		// Write the repeater container
		$html = sprintf( '<div class="qs-repeater" id="%s-repeater">', $name );
			// The button to open the media manager
			$html .= '<button type="button" class="button button-primary qs-button">' . $settings['label'] . '</button>';

			// A button to clear all items currently loaded
			$html .= ' <button type="button" class="button qs-clear">Clear</button>';

			// Write the repeater item template
			$html .= '<template class="qs-template">';
				$html .= static::build_repeater_item( $settings );
			$html .= '</template>';


			// Write the existing items if present
			$html .= '<div class="qs-container">';
			if ( $values ) {
				// Loop through each entry in the data, write the items
				foreach ( $values as $i => $item ) {
					$html .= static::build_repeater_item( $settings, $item, $i );
				}
			}
			$html .= '</div>';
		$html .= '</div>';

		return $html;
	}
}
