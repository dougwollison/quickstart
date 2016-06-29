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
	 * @since 1.8.0 Fixed to catch empty brackets.
	 * @since 1.6.0 Fixed to catch all brackets.
	 * @since 1.0.0
	 *
	 * @param string $name The name of the field.
	 *
	 * @return string The valid ID.
	 */
	public static function make_id( $name ) {
		return preg_replace( '/\[(.+?)\]/', '_$1', $name );
		$name = str_replace( '[]', '', $name );
		return $name;
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
	 * @since 1.10.0 Changed wrapper class/id scheme.
	 * @since 1.7.0  Added %description placeholder and qs-field-[side] class.
	 * @since 1.5.0  Added %id-field id.
	 * @since 1.4.0
	 *
	 * @param string $side Which side the label should appear on (left/right).
	 * @param string $tag  The tag name to use in the format.
	 *
	 * @return string The generated format string.
	 */
	public static function build_field_wrapper( $side = 'left', $tag = 'div' ) {
		$format = '<' . $tag . ' class="%wrapper_class" id="%id-wrapper">';

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
	 * @since 1.10.0 Dropped $wrapper argument; use 'format' setting.
	 * @since 1.7.0  Added handling of description option, mild restructuring.
	 * @since 1.4.0  Renamed $html to $input, revised $format handling.
	 * @since 1.0.0
	 *
	 * @param string $input    The html of the input to wrap.
	 * @param array  $settings The settings array for the field.
	 *
	 * @return string The processed HTML.
	 */
	public static function maybe_wrap_field( $input, $settings ) {
		// If a description is set, prep it
		if ( isset( $settings['description'] ) && ! empty( $settings['description'] ) ) {
			$settings['description'] = sprintf( '<p class="description">%s</p>', $settings['description'] );
		} else {
			// Make sure it's set but blank if not
			$settings['description'] = '';
		}

		$format = null;
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
				$format = call_user_func_array( array( get_called_class(), 'build_field_wrapper' ), $format );
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
	 * @since 1.10.0 Added handling of term meta.
	 * @since 1.8.0  Added $single param.
	 * @since 1.6.0  Added use of extract_value().
	 * @since 1.4.0
	 *
	 * @uses extract_value()
	 *
	 * @param mixed  $data   The raw data source.
	 * @param string $type   The type of source to expect (e.g. "post", "option", "array", or "raw").
	 * @param string $key    The field to extract from the source.
	 * @param bool   $single Wether the data is stored in a single or multiple entries (postmeta only).
	 *
	 * @return mixed The extracted value.
	 */
	public static function get_value( $data, $type, $key, $single = true ) {
		$map = null;
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
				$value = get_post_meta( $data, $key, $single );
				break;

			case 'term':
				// Get the matching meta value for this term
				if ( is_object( $data ) ) {
					$data = $data->term_id;
				}
				$value = get_term_meta( $data, $key, $single );
				break;

			case 'user':
				// Get the matching meta value for this post
				if ( is_object( $data ) ) {
					$data = $data->ID;
				}
				$value = get_user_meta( $data, $key, $single );
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
	 * Utility function for get_objects_list, for getting posts hierachically.
	 *
	 * @since 1.11.0
	 *
	 * @param array &$list   The list ove posts to add to (passed by reference).
	 * @param array  $args   The get_posts arguments.
	 * @param int    $parent Optional The parent to look under (defaults to 0 to start, pass false to disable hierachy).
	 * @param int    $level  Optional The current level in the hierarchy (defaults to 0 to start).
	 *
	 * @return array The complete list, with labels indented based on level.
	 */
	protected static function add_post_hierarchy( &$list, $args, $parent = 0, $level = 0 ) {
		$args['post_parent'] = $parent;

		// Get and loop through the posts found
		$posts = get_posts( $args );
		foreach ( $posts as $post ) {
			$list[ $post->ID ] = str_repeat( '&nbsp;', $level * 3 ) . apply_filters( 'the_title', $post->post_title );

			// Add any child posts if $parent isn't false
			if ( $parent !== false ) {
				static::add_post_hierarchy( $list, $args, $post->ID, $level + 1 );
			}
		}
	}

	/**
	 * Get a list of objects for use in a fields 'values' setting.
	 *
	 * @since 1.13.0 Added query_args setting.
	 * @since 1.11.0
	 *
	 * @param string $object_type The type of object to get.
	 * @param array  $settings    The settings to use in creating the field.
	 * @param mixed  $value       The value to fill the field with.
	 * @param mixed  $data        The original source of the data.
	 * @param string $source      The data source's type.
	 *
	 * @return array The list of objects in ID => Name format.
	 */
	protected static function get_objects_list( $object_type, $settings, $value, $data = null, $source = null ) {
		$values = array();

		switch ( $object_type ) {
			case 'post':
				// Handle the _type_options if set; one of the values could be the post type
				if ( isset( $settings['_type_options'] ) ) {
					// Loop through all options and see if one is a post type
					foreach ( $settings['_type_options'] as $option ) {
						if ( post_type_exists( $option ) ) {
							$settings['post_type'] = $option;
						}
					}
				}

				// Default Settings
				$default_settings = array(
					'post_type'   => null,
					'post_status' => array( 'publish', 'private', 'draft' ),
					'exclude'     => null,
					'none_option' => '&mdash; None &mdash;',
					'orderby'     => array( 'menu_order', 'post_title' ),
					'order'       => 'asc',
				);

				// Parse the passed settings with the defaults
				$settings = wp_parse_args( $settings, $default_settings );

				if ( is_null( $settings['post_type'] ) ) {
					// Determine default post type
					if ( $data && $source == 'post' ) {
						// Default to this posts post type
						$settings['post_type'] = $data->post_type;
					} else {
						// Default to page
						$settings['post_type'] = 'page';
					}
				}

				// If it's for a post and no exclude is passed, set it to the post ID
				if ( is_null( $settings['exclude'] ) && $data && $source == 'post' ) {
					$settings['exclude'] = $data->ID;
				}

				// Make sure exclude is an array
				csv_array_ref( $settings['exclude'] );

				// Create the arguments for wp_dropdown_pages()
				$args = array(
					'post_type'      => $settings['post_type'],
					'post_status'    => $settings['post_status'],
					'post__not_in'   => $settings['exclude'],
					'orderby'        => $settings['orderby'],
					'order'          => $settings['order'],
					'posts_per_page' => -1,
				);

				// Add a none option value if desired
				if ( ! empty( $settings['none_option'] ) ) {
					$values[0] = $settings['none_option'];
				}

				// Default parent value
                if ( is_null( $settings['parent'] ) ) {
	                // Set parent to 0 if post type is hierachical, or false to disable it
					$settings['parent'] = is_post_type_hierarchical( $settings['post_type'] ) ? 0 : false;
				}

				// Merge in the query_args if present
				if ( isset( $settings['query_args'] ) ) {
					$args = array_merge( $args, $settings['query_args'] );
				}

				// Add posts to the values list
				static::add_post_hierarchy( $values, $args, $settings['parent'] );

				break;

			case 'menu':
				// Get the menus
				$menus = wp_get_nav_menus();

				// Convert it to a values array and update $settings
				$values = simplify_object_array( $menus, 'term_id', 'name' );

				break;

			case 'template':
				// Default Settings
				$default_settings = array(
					'default' => 'default',
					'default_name' => 'Default Template',
				);

				// Parse the passed settings with the defaults
				$settings = wp_parse_args( $settings, $default_settings );

				// Build the options list array
				$options = array( $settings['default'] => $settings['default_name'] );

				// Get the temlates, sort them
				$templates = get_page_templates();
				ksort( $templates );

				// Flip it into a proper values list
				$templates = array_flip( $templates );

				// Merge with default option and update $settings
				$values = array_merge( $options, $templates );

				break;
		}

		/**
		 * Filter the values list for the field.
		 *
		 * @since 1.11.0
		 *
		 * @param array  $values      The array of object values.
		 * @param string $object_type The type of objects being for the list.
		 * @param array  $settings    The settings for the field.
		 * @param mixed  $data        The source of the value.
		 * @param string $source      The type of value source.
		 *
		 * @return array The filtered values list.
		 */
		$values = apply_filters( 'qs_form_objects_list', $values, $object_type, $settings, $data, $source );

		return $values;
	}

	/**
	 * Build a single field, based on the passed configuration data.
	 *
	 * @since 1.12.0 Updated to use external handle_shorthand().
	 * @since 1.11.0 Added use of static::handle_shorthand().
	 * @since 1.10.0 Added use of Tools::maybe_prefix_post_field() when handling post_field values.
	 *				 Also added/tweaked default input/wrapper classes/id values.
	 *               Dropped use of absent $wrapper argument in function calls.
	 * @since 1.8.0  Added use of "save_single" option, "default" value option,
	 * 				 "data_name" value now defaults to the same as "name".
	 * @since 1.6.0  Added qs_field_ prefix to field id, get_value() use for callback.
	 * @since 1.5.0  Added "taxonomy" option handling.
	 * @since 1.4.2  Added "get_value" and "post_field" option handling.
	 * @since 1.4.0  Added $source argument.
	 * @since 1.3.3  Added use of new make_label() method.
	 * @since 1.3.0  Added $wrap argument for setting default wrap_with_label value,
	 *				 also merged filters into one, and added 'build' callback.
	 * @since 1.1.0  Added check if $settings is a callback.
	 * @since 1.0.0
	 *
	 * @param string $field    The name/id of the field.
	 * @param array  $settings Optional The settings to use in creating the field.
	 * @param mixed  $data     Optional The source for the value; use $source argument to specify.
	 * @param string $source   Optional The type of value source; see Form::get_value().
	 * @param bool   $wrap     Optional Default value for wrap_with_label option.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_field( $field, $settings = array(), $data = null, $source = 'raw', $wrap = true ) {
		// Handle any shorthand
		handle_shorthand( 'field', $field, $settings );

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

		$field_id = static::make_id( $field );

		$default_settings = array(
			'type'            => 'text',
			'field_id'        => $field_id,
			'id'              => 'qs_field_' . $field_id,
			'name'            => $field,
			'label'           => static::make_label( $field ),
			'data_name'       => $field, // The name of the postmeta or option to retrieve
			'wrap_with_label' => $wrap, // Wether or not to wrap the field in a label
			'wrapper_class'   => '', // The class to apply to the wrapper
			'save_single'     => true, // Wether or not to save multiple values in a single entry
		);

		// Parse the passed settings with the defaults
		$settings = wp_parse_args( $settings, $default_settings );

		// If no data_name is set, make it the same as name
		if ( ! isset( $settings['data_name'] ) ) {
			$settings['data_name'] = $settings['name'];
		}

		// Check if condition callback exists; test it before proceeding
		if ( isset( $settings['condition'] ) && is_callable( $settings['condition'] ) ) {
			/**
			 * Test if the field should be printed.
			 *
			 * @since 1.8.0
			 *
			 * @param mixed  $data     The source for the value.
			 * @param string $source   The type of value source.
			 * @param string $field    The name/id of the field.
			 * @param array  $settings The settings to use in creating the field.
			 *
			 * @return bool The result of the test.
			 */
			$result = call_user_func( $settings['condition'], $data, $source, $field, $settings );

			// If test fails, don't print the field
			if ( ! $result ) return;
		}

		// Get the value to use, first by checking if the "get_value" callback is present
		if ( isset( $settings['get_value'] ) && is_callable( $settings['get_value'] ) ) {
			/**
			 * Custom callback for getting the value to use for building the field.
			 *
			 * @since 1.4.2
			 *
			 * @param mixed  $data     The source for the value.
			 * @param array  $settings The settings for the field.
			 * @param string $field    The name of the field being built.
			 * @param string $source   The type of value source.
			 *
			 * @return mixed The value to use for building the field.
			 */
			$value = call_user_func( $settings['get_value'], $data, $source, $settings, $field );
		} elseif ( isset( $settings['post_field'] ) && $settings['post_field'] && $source == 'post' ) {
			// Alternately, if "post_field" is present (and the source is a post), get the matching field

			// Prefix the field if necessary
			$field = Tools::maybe_prefix_post_field( $settings['post_field'] );

			$value = $data->$field;
		} elseif ( isset( $settings['taxonomy'] ) && $settings['taxonomy'] && $source == 'post' ) {
			// Alternately, if "taxonomy" is present (and the source is a post), get the matching terms

			// Get the post_terms for $value
			$post_terms = get_the_terms( $data->ID, $settings['taxonomy'] );
			$value = array_map( function( $term ) {
				return $term->term_id;
			}, (array) $post_terms );

			// Get the query args for get_terms
			if ( isset( $settings['term_query'] ) ) {
				$term_query = $settings['term_query'];
			} else {
				// Default to just show empty terms
				$term_query = array( 'hide_empty' => false );
			}

			// Get the available terms for the values list
			$tax_terms = get_terms( $settings['taxonomy'], $term_query );
			$settings['values'] = simplify_object_array( $tax_terms, 'term_id', 'name' );
		} else {
			// Otherwise, use the built in get_value method
			$value = static::get_value( $data, $source, $settings['data_name'], $settings['save_single'] );
		}

		// If the value is empty, and a default value has been provided, use that
		if ( ( is_null( $value ) || $value === '' ) && isset( $settings['default'] ) ){
			$value = $settings['default'];
		}

		// Make sure class is set and in array form
		if ( ! isset( $settings['class'] ) ) {
			$settings['class'] = array();
		} elseif ( ! is_array( $settings['class'] ) ) {
			$settings['class'] = (array) $settings['class'];
		}

		// Add the default qs-input and qs-input-$field_id classes
		$settings['class'][] = 'qs-input';
		$settings['class'][] = 'qs-input-' . $field_id;

		// Make sure wrapper_class is set
		if ( ! isset( $settings['wrapper_class'] ) ) {
			$settings['wrapper_class'] = '';
		}

		// Updated the wrapper_class, adding qs-field, qs-field-$field_id, and $type classes
		$settings['wrapper_class'] = implode( ' ', array(
			$settings['wrapper_class'],
			'qs-field',
			'qs-field-' . $field_id,
			$settings['type'],
		) );

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
			 * @param mixed  $data     The source of the value.
			 * @param string $source   The source's type.
			 * @param string $field    The name of the field to build.
			 *
			 * @return string The HTML of the field.
			 */
			$html = call_user_func( $settings['build'], $settings, $value, $data, $source, $field );
		} elseif ( $method != __FUNCTION__ && method_exists( get_called_class(), $method ) ) {
			// Matches one of the specialized internal field builders
			$html = static::$method( $settings, $value, $data, $source );
		} else {
			// Assume a text-like input, use the generic field builder
			$html = static::build_generic( $settings, $value, $data, $source );
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
		 * @param string $source   The type of value source; see Form::get_value().
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
	 * @param array  $data   Optional The source for the values; see Form::build_field() for details.
	 * @param string $source Optional Identifies the type of values source; see Form::build_field() for details.
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
	 * @since 1.10.0 Dropped use of $wrapper argument (just pass 'format' in $settings).
	 * @since 1.4.0  Dropped $field arg, added $wrapper arg, revised wrapping usage.
	 * @since 1.0.0
	 *
	 * @param array  $settings The settings to use in creating the field.
	 * @param mixed  $value    The value to fill the field with.
	 * @param mixed  $data     The original source of the data.
	 * @param string $source   The data source's type.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_generic( $settings, $value, $data = null, $source = null ) {
		// Load the value attribute with the field value
		$settings['value'] = $value;

		// Build the <input>
		$input = Tools::build_tag( 'input', $settings );

		// Add the generic class to the wrapper classes
		$settings['wrapper_class'] .= ' generic';

		// Wrap the input in the html if needed
		$html = static::maybe_wrap_field( $input, $settings );

		return $html;
	}

	/**
	 * Build a hidden field.
	 *
	 * @since 1.10.0
	 *
	 * @param array  $settings The settings to use in creating the field.
	 * @param mixed  $value    The value to fill the field with.
	 *
	 * @return string The HTML for the field.
	 */
	public static function build_hidden( $settings, $value ) {
		// Ensure the type is set in case this is called directly
		$settings['type'] = 'hidden';

		// Load the value attribute with the field value
		$settings['value'] = $value;

		// Build the <input>
		$html = Tools::build_tag( 'input', $settings );

		return $html;
	}

	/**
	 * Build a textarea field.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_textarea( $settings, $value, $data = null, $source = null ) {
		// Handle the _type_option if set; only accepted option is used to sepcify rows and columns in *x* format
		if ( isset( $settings['_type_options'] ) && preg_match( '/^(\d+)(?:x(\d+))?$/', $settings['_type_options'][0], $matches ) ) {
			$settings['rows'] = $matches[1];
			if ( isset( $matches[2] ) ) {
				$settings['cols'] = $matches[2];
			}
		}

		// Build the <input>
		$input = Tools::build_tag( 'textarea', $settings, $value );

		// Wrap the input in the html if needed
		$html = static::maybe_wrap_field( $input, $settings );

		return $html;
	}

	/**
	 * Build a select field.
	 *
	 * @since 1.12.0 Added "numeric_values" option handling.
	 * @since 1.8.0  Added support for option groups.
	 * @since 1.5.0  Add "null" option handling.
	 * @since 1.4.2  Added [] to field name when multiple is true.
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_select( $settings, $value, $data = null, $source = null ) {
		// Handle the _type_options if set; only accepted value is used to specify the multiple flag
		if ( isset( $settings['_type_options'] ) && $settings['_type_options'][0] == 'multiple' ) {
			$settings['multiple'] = true;
		}

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

		// Check if numeric values are desired
		$numeric_values = ( isset( $settings['numeric_values'] ) && $settings['numeric_values'] );

		$is_assoc = is_assoc( $settings['values'] ) || $numeric_values;

		// Add a null option if requested
		if ( isset( $settings['null'] ) ) {
			$options .= sprintf( '<option value="">%s</option>', $settings['null'] );
		}

		// Run through the values and build the options list
		foreach ( $settings['values'] as $val => $label ) {
			if ( ! $is_assoc ) {
				$val = $label;
			}

			// If $label is an array, handle as an optgroup
			if ( is_array( $label ) ) {
				$suboptions = '';
				$is_sub_assoc = is_assoc( $label ) || $numeric_values;
				foreach ( $label as $subval => $sublabel ) {
					if ( ! $is_sub_assoc ) {
						$subval = $sublabel;
					}
					$suboptions .= sprintf(
						'<option value="%s" %s>%s</option>',
						$subval,
						in_array( $subval, (array) $value ) ? 'selected' : '',
						$sublabel
					);
				}
				$options .= sprintf( '<optgroup label="%s">%s</optgroup>', $val, $suboptions );
			} else {
				$options .= sprintf(
					'<option value="%s" %s>%s</option>',
					$val,
					in_array( $val, (array) $value ) ? 'selected' : '',
					$label
				);
			}
		}

		// Build the <select>
		$input = Tools::build_tag( 'select', $settings, $options );

		$html = static::maybe_wrap_field( $input, $settings );

		return $html;
	}

	/**
	 * Build an post/menu/template select field.
	 *
	 * @since 1.11.0
	 *
	 */
	protected static function build_objectselect( $type, $settings, $value, $data = null, $source = null ) {
		// Get the values
		$settings['values'] = static::get_objects_list( $type, $settings, $value, $data, $source );

		// Update the wrapper_class so to include the select class
		$settings['wrapper_class'] .= ' select';

		// Pass it over to build_select
		return static::build_select( $settings, $value, $data, $source );
	}

	/**
	 * Build a hierarchical post select field.
	 *
	 * @since 1.11.0 Made alias of build_objectselect
	 * @since 1.10.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_postselect( $settings, $value, $data = null, $source = null ) {
		return static::build_objectselect( 'post', $settings, $value, $data, $source );
	}

	/**
	 * Build a menu select field.
	 *
	 * @since 1.11.0 Made alias of build_objectselect
	 * @since 1.10.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_menuselect( $settings, $value, $data = null, $source = null ) {
		return static::build_objectselect( 'menu', $settings, $value, $data, $source );
	}

	/**
	 * Build a template select field.
	 *
	 * @since 1.11.0 Made alias of build_objectselect
	 * @since 1.10.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_templateselect( $settings, $value, $data = null, $source = null ) {
		return static::build_objectselect( 'template', $settings, $value, $data, $source );
	}

	/**
	 * Build a checkbox field.
	 *
	 * @since 1.11.0 Added use of 'default' setting for what the dummy input's value should be.
	 * @since 1.10.0 Updated handling of default wrapper format, added use of build_hidden.
	 * @since 1.8.0  Fixed dummy field to have a 0 value, not null.
	 * @since 1.4.2  Added $dummy argument and printing of dummy input for null value.
	 * @since 1.4.1  Added modified default value for $wrapper.
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 *
	 * @param bool $dummy Wether or not to print a hidden null input first.
	 */
	public static function build_checkbox( $settings, $value, $data = null, $source = null, $dummy = true ) {
		// Default the value to 1 if it's a checkbox
		if ( $settings['type'] == 'checkbox' && ! isset( $settings['value'] ) ) {
			$settings['value'] = 1;
		}

		// Value for dummy input
		if ( ! isset( $settings['default'] ) ) {
			$settings['default'] = null;
		}

		// Default the wrapper to right sided
		if ( ! isset( $settings['format'] ) ) {
			$settings['format'] = array( 'right' );
		}

		// If the values match, mark as checked
		if ( $value == $settings['value'] || ( is_array( $value ) && in_array( $settings['value'], $value ) ) ) {
			$settings[] = 'checked';
		}

		// Build the dummy <input> if enabled
		$hidden = '';
		if ( $dummy ) {
			$hidden = static::build_hidden( array(
				'name'  => $settings['name'],
			), $settings['default'] );
		}

		// Build the actual <input>
		$input = Tools::build_tag( 'input', $settings );

		// Wrap the inputs in the html if needed
		$html = static::maybe_wrap_field( $hidden . $input, $settings );

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
	public static function build_radio( $settings, $value, $data = null, $source = null, $dummy = true ) {
		return static::build_checkbox( $settings, $value, $data, $source, $dummy );
	}

	/**
	 * Build a checklist or radio list.
	 *
	 * @since 1.12.0 Added "numeric_values" option handling.
	 * @since 1.11.0 Added "inputlist-item" to item class list.
	 * @since 1.10.0 Updated handling of default wrapper format, added use of build_hidden.
	 * @since 1.6.0  Added checked_first support.
	 * @since 1.5.0  Added %id-fieldset id.
	 * @since 1.4.2  Added dummy input for null value.
	 * @since 1.4.0  Overhauled item building and wrapper handling.
	 * @since 1.0.0
	 *
	 * @see Form::build_generic()
	 */
	protected static function build_inputlist( $type, $settings, $value, $data = null, $source = null ) {
		if ( ! isset( $settings['values'] ) ) {
			throw new Exception( 'Checklist/radiolist fieldsets MUST have a values parameter.' );
		}

		// Handle the _type_options if set; only accepted value is used to specify the checked_first flag
		if ( isset( $settings['_type_options'] ) && $settings['_type_options'][0] == 'checked_first' ) {
			$settings['checked_first'] = true;
		}

		// If no value exists, and there is a default value set, use it.
		if ( is_null( $value ) && isset( $settings['default'] ) ) {
			$value = $settings['default'];
		}

		csv_array_ref( $settings['values'] );

		// Check if numeric values are desired
		$numeric_values = ( isset( $settings['numeric_values'] ) && $settings['numeric_values'] );

		$is_assoc = is_assoc( $settings['values'] ) || $numeric_values;

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
				'wrapper_class'   => 'qs-field inputlist-item',
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

			// Setup the wrapper format for this item
			$item['format'] = array( 'right', 'li' );

			// Add the input, wrapped in a list item (and sans the dummy input)
			$item = static::$build( $item, null, $data, $source, false );
		}

		$items = implode( '', $items );

		$settings['class'][] = 'inputlist-items';

		// Build the list
		$list = Tools::build_tag( 'ul', $settings, $items, array( 'class', 'id', 'style', 'title' ) );

		if ( ! isset( $settings['format'] ) ) {
			$settings['format'] = '<div class="qs-fieldset inputlist %type %wrapper_class" id="%id-fieldset"><p class="qs-legend">%label</p> %input</div>';
		}

		// Build a dummy <input>
		$hidden = static::build_hidden( array(
			'name' => $settings['name'],
		), null );

		// Optionally wrap the fieldset
		$html = static::maybe_wrap_field( $hidden . $list, $settings );

		return $html;
	}

	/**
	 * Alias to build_inputlist; build a checklist.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_inputlist()
	 */
	public static function build_checklist( $settings, $value, $data = null, $source = null ) {
		return static::build_inputlist( 'checkbox', $settings, $value, $data, $source );
	}

	/**
	 * Alias to build_inputlist; build a radiolist.
	 *
	 * @since 1.0.0
	 *
	 * @see Form::build_inputlist()
	 */
	public static function build_radiolist( $settings, $value, $data = null, $source = null ) {
		return static::build_inputlist( 'radio', $settings, $value, $data, $source );
	}

	/**
	 * Build an post/menu/template checklist or radio list.
	 *
	 * @since 1.11.0
	 *
	 * @see Form::build_generic()
	 */
	protected static function build_objectlist( $type, $settings, $value, $data = null, $source = null ) {
		// Handle the _type_options if set; only accepted value is used to specify the multiple flag
		if ( isset( $settings['_type_options'] ) && $settings['_type_options'][0] == 'multiple' ) {
			$settings['multiple'] = true;
		}

		// Get the values
		$settings['values'] = static::get_objects_list( $type, $settings, $value, $data, $source );

		// Radiolist by default, checklist if multiple is true
		$method = 'radio';
		$class = 'radiolist';
		if ( isset( $settings['multiple'] ) && $settings['multiple'] ) {
			// Update the wrapper_class so to include the checklist class
			$method = 'checkbox';
			$class = 'checklist';

			// No need for the None entry in values
			unset( $settings['values'][0] );
		}

		$settings['wrapper_class'] .= " $class";

		// Pass it over to build_inputlist
		return static::build_inputlist( $method, $settings, $value, $data, $source );
	}

	/**
	 * Build a hierarchical post select field.
	 *
	 * @since 1.11.0 Made alias of build_objectlist
	 * @since 1.10.0
	 *
	 * @see Form::build_objectlist()
	 */
	public static function build_postlist( $settings, $value, $data = null, $source = null ) {
		return static::build_objectlist( 'post', $settings, $value, $data, $source );
	}

	/**
	 * Build a menu select field.
	 *
	 * @since 1.11.0 Made alias of build_objectlist
	 * @since 1.10.0
	 *
	 * @see Form::build_objectlist()
	 */
	public static function build_menulist( $settings, $value, $data = null, $source = null ) {
		return static::build_objectlist( 'menu', $settings, $value, $data, $source );
	}

	/**
	 * Build a template select field.
	 *
	 * @since 1.11.0 Made alias of build_objectlist
	 * @since 1.10.0
	 *
	 * @see Form::build_objectlist()
	 */
	public static function build_templatelist( $settings, $value, $data = null, $source = null ) {
		return static::build_objectlist( 'template', $settings, $value, $data, $source );
	}

	/**
	 * Build a media attachment manager.
	 *
	 * Replaces addfile, setimage and editgallery.
	 *
	 * @since 1.10.0 Now uses value-(filled|empty) classes.
	 * @since 1.8.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_media( $settings, $value, $data = null, $source = null ) {
		// Handle the _type_option if set; used to specify the mode or media
		if ( isset( $settings['_type_options'] ) && $options = $settings['_type_options'] ) {
			// Loop through options and handle according to value
			foreach ( $options as $option ) {
				if ( in_array( $option, array( 'gallery', 'multiple', 'quicksort' ) ) ) {
					// Matches a flag, update $settings
					$settings[ $option ] = true;
				} else {
					// Assume media setting value
					$settings['media'] = $option;
				}
			}
		}

		// Get the field name
		$field_name = $settings['name'];

		// Determine gallery mode support
		$is_gallery = isset( $settings['gallery'] ) ? $settings['gallery'] : false;

		// Determine multiple file support (not the same as gallery)
		$is_multi = ! $is_gallery && isset( $settings['multiple'] ) && $settings['multiple'];

		// Determine quicksort utility support
		$use_sort = $is_multi && isset( $settings['quicksort'] ) && $settings['quicksort'];

		// Determine media type support (defaults to any, or image if gallery)
		$media = isset( $settings['media'] ) ? $settings['media'] : ( $is_gallery ? 'image' : null );

		// Determine display mode for text (title, filename, or none, default none for image type)
		$show = isset( $settings['display'] ) ? $settings['display'] : ( $media == 'image' ? false : 'filename' );

		// Determin icon support (for multiple, non-image items)
		$icon = isset( $settings['icon'] ) ? $settings['icon'] : false;

		// Build the Add/Remove Labels
		if ( ! isset( $settings['add_label'] ) ) {
			$settings['add_label'] = ( $is_multi ? 'Add' : 'Set' ) . ' ' . $settings['label'];
		}
		if ( ! isset( $settings['remove_label'] ) ) {
			$settings['remove_label'] = $is_multi ? 'Clear' : 'Remove ' . $settings['label'];
		}

		// Setup the classes for the container
		$classes = array( 'qs-field', 'qs-media', $value ? 'value-filled' : 'value-empty' );
		if ( $media != 'image' ) {
			$classes[] = 'media-file';
		}
		if ( $media ) {
			$classes[] = 'media-' . sanitize_title( $media );
		}
		if ( $is_gallery ) {
			$classes[] = 'gallery';
		} elseif ( $is_multi ) {
			$classes[] = 'multiple';
		} else {
			$classes[] = 'single';
		}

		// Begin the markup for this component
		$html = sprintf( '<div id="%s" class="%s" data-type="%s" data-show="%s" data-mode="%s">', $settings['id'], implode( ' ', $classes ), $media, $show, $is_gallery ? 'gallery' : 'normal' );

		// Special output for certain conditions
		if ( $is_gallery ) {
			// If the label seems auto generated, modify the label text to Edit [label]
			if ( $settings['label'] == make_legible( $settings['name'] ) ) {
				$settings['label'] = 'Edit ' . $settings['label'];
			}

			// The button to open the gallery editor
			$html .= '<button type="button" class="button-primary qs-button">' . $settings['label'] . '</button>';

			// The button to clear all existing items
			$html .= ' <button type="button" class="button qs-clear">Clear</button>';

			// The preview container with the list of images
			$html .= '<div class="qs-preview">';
			if ( $value ) {
				foreach ( explode( ',', $value ) as $image ) {
					$html .= wp_get_attachment_image( $image, 'thumbnail', true, array( 'data-id' => $image ) );
				}
			}
			$html .= '</div>';

			// The value input to hold the ID list
			$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-value">', $field_name, $value );
		} elseif ( $is_multi ) {
			// The button to open the media manager
			$html .= '<button type="button" class="button button-primary qs-button">' . $settings['add_label'] . '</button>';

			// The button to clear all existing items
			$html .= ' <button type="button" class="button qs-clear">' . $settings['remove_label'] . '</button>';

			// Start the preview list container, adding axis setting for non-image media types
			$html .= sprintf( '<div class="qs-container qs-sortable" %s>', $media == 'image' ? '' : 'data-axis="y"' );
			// Print the items if present
			if ( $value ) {
				// Ensure value is in the form of an array
				csv_array_ref( $value );

				// Loop through each image and print an item
				foreach ( $value as $attachment_id ) {
					// Add an item for the current file
					$html .= static::build_media_item( $attachment_id, $field_name, $show, $icon );
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
				$html .= static::build_media_item( null, $field_name, $show, $icon );
			$html .= '</template>';
		} else {
			// Build a simple version similar to the Featured Image box
			$html .= '<div class="qs-container">';
				$html .= '<a href="#" class="qs-preview qs-button" title="' . $settings['add_label'] . '">';
				if ( $value ) {
					$preview = rawurldecode( basename( wp_get_attachment_url( $value ) ) );
					if ( $show == 'title' ) {
						$preview = get_the_title( $value );
					}

					$html .= wp_get_attachment_image( $value, 'medium', true, array(
						'title' => $preview,
					) );
				} else {
					$html .= $settings['add_label'];
				}
				$html .= '</a>';

				// The value input to hold the ID
				$html .= sprintf( '<input type="hidden" name="%s" value="%s" class="qs-value">', $field_name, $value );
			$html .= '</div>';

			// Add the remove button
			$html .= '<a href="#" class="qs-clear">' . $settings['remove_label'] . '</a>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a single media manager item.
	 *
	 * @since 1.8.0
	 *
	 * @param int            $attachment_id The ID of the attachment to use.
	 * @param string         $field_name    The name of the file adder field.
	 * @param string|boolean $show          What text to display with the image/icon (title|filename|FALSE).
	 * @param boolean        $icon          Wether or not to display the icon for non-image files.
	 *
	 * @return string The markup fo the item.
	 */
	protected static function build_media_item( $attachment_id, $field_name, $show, $icon ) {
		// Setup item for quicksort support
		$item_name = sanitize_title( basename( wp_get_attachment_url( $attachment_id ) ) );
		$item_date = get_the_date( 'U', $attachment_id );
		$html = sprintf( '<div class="qs-item" data-name="%s" data-date="%s">', $item_name, $item_date );

		$html .= '<div class="qs-preview">';
		if ( $attachment_id ) {
			$html .= wp_get_attachment_image( $attachment_id, 'thumbnail', $icon );

			if ( $show ) {
				$preview = rawurldecode( basename( wp_get_attachment_url( $attachment_id ) ) );
				if ( $show == 'title' ) {
					$preview = get_the_title( $attachment_id );
				}
				$html .= '<span class="qs-preview-text">' . $preview . '</span>';
			}
		}
		$html .= '</div>';

		// Add delete button and field name brackets if in mulitple mode
		$html .= '<button type="button" class="button qs-delete">Delete</button>';

		// Add the input field for this item (and append [] to the field name)
		$html .= sprintf( '<input type="hidden" name="%s[]" value="%s" class="qs-value">', $field_name, $attachment_id );

		$html .= '</div>';
		return $html;
	}

	/**
	 * Build a file adder field.
	 *
	 * @deprecated 1.8.0 Use build_media() instead (now an alias).
	 *
	 * @since 1.6.2 Fixed template output to include $show option.
	 * @since 1.6.0 Added qs-sortable class with data-axis attribute, quick sort support, "show" option.
	 * @since 1.4.0 Overhauled markup/functionality.
	 * @since 1.3.3
	 *
	 * @see Form::build_media()
	 */
	public static function build_addfile( $settings, $value, $data = null, $source = null ) {
		return self::build_media( $settings, $value );
	}

	/**
	 * Build an image setter field.
	 *
	 * @deprecated 1.8.0 Use build_media() instead (now an alias).
	 *
	 * @since 1.4.0 Reduced to alias of build_addfile
	 * @since 1.0.0
	 *
	 * @see Form::build_media()
	 */
	public static function build_setimage( $settings, $value, $data = null, $source = null ) {
		// Force the media type to image
		$settings['media'] = 'image';

		return self::build_media( $settings, $value );
	}

	/**
	 * Build a gallery editor field.
	 *
	 * @deprecated 1.8.0 Use build_media() instead (now an alias).
	 *
	 * @since 1.6.0 Added clear button, data-id to image for inline sortability.
	 * @since 1.4.0 Added semi-intelligent button text guessing.
	 * @since 1.0.0
	 *
	 * @see Form::build_gallery()
	 */
	public static function build_editgallery( $settings, $value, $data = null, $source = null ) {
		// Force the media type to image
		$settings['gallery'] = true;

		return self::build_media( $settings, $value );
	}

	/**
	 * Build a repeater interface.
	 *
	 * @since 1.8.0 Modified handling of template option for simpler templates.
	 *              Also update default labeling to use singular form.
	 * @since 1.5.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_repeater( $settings, $value, $data = null, $source = null ) {
		if ( ! isset( $settings['template'] ) ) {
			// Default to single generic text field
			$settings['template'] = array(
				'class' => 'widefat',
			);
		}

		// Get the field name
		$name = $settings['name'];

		// If the label seems auto generated, modify the label text to Add
		if ( $settings['label'] == make_legible( $name ) ) {
			$settings['label'] = 'Add ' . singularize( $settings['label'] );
		}

		// Write the repeater container
		$html = sprintf( '<div class="qs-repeater" id="%s-repeater">', $name );
			// The button to open the media manager
			$html .= '<button type="button" class="button button-primary qs-add">' . $settings['label'] . '</button>';

			// A button to clear all items currently loaded
			$html .= ' <button type="button" class="button qs-clear">Clear</button>';

			// Write the repeater item template
			$html .= '<template class="qs-template">';
				$html .= static::build_repeater_item( $settings );
			$html .= '</template>';


			// Write the existing items if present
			$html .= '<div class="qs-container qs-sortable" data-axis="y">';
			if ( $value ) {
				// Loop through each entry in the data, write the items
				foreach ( $value as $i => $item ) {
					$html .= static::build_repeater_item( $settings, $item, $i );
				}
			}
			$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a single repeater item.
	 *
	 * @since 1.8.0 Revised handling of template settings; now uses build_repeater_item_field().
	 * @since 1.5.0
	 *
	 * @param array $repeater The settings of the repeater.
	 * @param array $item     Optional The item data.
	 * @param int   $i        Optional The item's number (-1 for template).
	 */
	private static function build_repeater_item( $repeater, $item = null, $i = -1 ) {
		$name = $repeater['name'];
		$template = $repeater['template'];

		$html = '<div class="qs-item">';
			if ( is_callable( $template ) ) {
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
				$html .= call_user_func( $template, $item, $i );
			} elseif ( is_array( $template ) ) {
				// Detect if multiple fields
				$multiple = isset( $template['fields'] );

				// Build the fields wrapper; identify it as containing multiple or a single field
				$html .= sprintf( '<div class="qs-item-fields %s">', $multiple ? 'multiple-fields' : 'single-field' );
				if ( $multiple ) {
					// Loop through each field for the template, and build them
					foreach ( $template['fields'] as $field => $settings ) {
						make_associative( $field, $settings );

						$html .= static::build_repeater_item_field( $name, $settings, $item, $i, $field );
					}
				} else {
					// Default wrap_with_label to false
					if ( ! isset( $template['wrap_with_label'] ) ) {
						$template['wrap_with_label'] = false;
					}
					$html .= static::build_repeater_item_field( $name, $template, $item, $i );
				}
				$html .= '</div>';

				// Add the delete button
				$html .= '<button type="button" class="button qs-delete">Delete</button>';
			}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Build a single field for a single repeater item.
	 *
	 * @since 1.8.0
	 *
	 * @param string $field    The name of the field this item is for.
	 * @param array  $settings The settings for the field.
	 * @param mixed  $item     Optional The item data.
	 * @param int    $i        Optional The item's number (-1 for template).
	 * @param string $subfield Optional The name of this specific field.
	 */
	private static function build_repeater_item_field( $field, $settings, $item = null, $i = -1, $subfield = null) {
		// Create the name for the field
		if ( ! is_null( $subfield ) ) {
			$settings['name'] = sprintf( '%s[%d][%s]', $field, $i, $subfield );
		} else {
			$settings['name'] = sprintf( '%s[]', $field );
		}

		$id = ! is_null( $subfield ) ? $subfield : $field;

		// Create the ID for the field
		$settings['id'] = static::make_id( $id ) . '-';

		// Add a unique string to the end of the ID or a % placeholder for the blank
		$settings['id'] .= $i == -1 ? '%' : substr( md5( $id . $i ), 0, 6 );

		// Set the value for the field
		$value = null;
		if ( ! is_null( $subfield ) ) {
			// Must get a specific value from the item data
			if ( is_array( $item ) && isset( $item[ $id ] ) ) {
				$value = $item[ $id ];
			}
		} else {
			// The item data is the value itself
			$value = $item;
		}

		// Finally, build the field
		return static::build_field( $id, $settings, $value );
	}

	/**
	 * Build a rich text editor.
	 *
	 * @since 1.8.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_editor( $settings, $value, $data = null, $source = null ) {
		// Get the field name
		$name = $settings['name'];

		// Create the editor_id from the name if not present or valid
		if ( ! isset( $settings['id'] ) || preg_match( '/\W+/', $settings['id'] ) ) {
			$settings['id'] = preg_replace( '/\W+/', '', strtolower( $name ) ) . '_qseditor';
		}

		// Make the textarea_name setting that of the name setting
		$settings['textarea_name'] = $name;

		// Make the textarea_class setting that of the class setting if present
		if ( isset( $settings['class'] ) ) {
			$settings['textarea_class'] = $settings['class'];
		}

		// Make the textarea_rows setting that of the rows setting if present
		if ( isset( $settings['rows'] ) ) {
			$settings['textarea_rows'] = $settings['rows'];
		}

		// Handle any QuickTags settings if present
		if ( isset( $settings['quicktags'] ) ) {
			// Make sure it's an array
			csv_array_ref( $settings['quicktags'] );

			Tools::do_quicktags( $settings['quicktags'], $settings['id'] );

			// Also format into proper from
			$settings['quicktags'] = array(
				'buttons' => implode( ',', $settings['quicktags'] ),
			);
		}

		// Write the editor container
		$input = sprintf( '<div class="qs-editor" id="%s-editor">', $name );
			ob_start();
			// Print out the editor
			wp_editor( $value, $settings['id'], $settings );
			$input .= ob_get_clean();
		$input .= '</div>';

		// Wrap the input in the html if needed
		$html = static::maybe_wrap_field( $input, $settings );

		return $html;
	}

	/**
	 * Build a google map field for setting coordinates.
	 *
	 * @since 1.11.0 Removed use of key, will now be geocoding via AJAX.
	 * @since 1.8.0
	 *
	 * @see Form::build_generic()
	 */
	public static function build_map( $settings, $value, $data = null, $source = null ) {
		// Get the field name
		$name = $settings['name'];

		// Setup data attributes (default values)
		$data_atts = array();
		$usable_atts = array( 'lat', 'lng', 'zoom' );
		foreach ( $usable_atts as $attr ) {
			if ( isset( $settings[ $attr ] ) ) {
				$data_atts[] = 'data-' . $attr . '="' . $settings[ $attr ] . '"';
			}
		}

		// Default values for the data
		$value = wp_parse_args( $value, array(
			'lat' => null,
			'lng' => null,
			'zoom' => null,
		) );

		// Write the map container
		$html = sprintf( '<div class="qs-map" id="%s-map" %s>', $name, implode( ' ', $data_atts ) );
			// Print the hidden fields (lat, lng, zoom)
			$field = '<input type="hidden" name="%2$s[%1$s]" class="qs-value-%1$s" value="%3$s" />';
			$html .= sprintf( $field, 'lat', $name, $value['lat'] );
			$html .= sprintf( $field, 'lng', $name, $value['lng'] );
			$html .= sprintf( $field, 'zoom', $name, $value['zoom'] );

			// Add the address search option if available
			if ( isset( $settings['search'] ) && $settings['search'] ) {
				$html .= '<div class="qs-map-field">';
					$html .= '<label>Search for Address: <input type="text" class="qs-map-search regular-text" /></label>';
					$html .= '<button type="button" class="button qs-search">Find</button>';
				$html .= '</div>';
			}

			// Add the canvas
			$html .= '<div class="qs-map-canvas"></div>';

			// A button to clear all the map/coordinates
			$html .= ' <button type="button" class="button qs-clear">Clear</button>';
		$html .= '</div>';

		return $html;
	}
}
