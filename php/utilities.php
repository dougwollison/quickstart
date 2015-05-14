<?php
/**
 * QuickStart External Utility Functions
 *
 * @package QuickStart
 * @subpackage Functions
 *
 * @since 1.9.0 Removed fill_array(), now using native array_pad() instead.
 * @since 1.0.0
 */

/**
 * Check if currently doing AJAX.
 *
 * @since 1.10.1
 *
 * @return bool Wether or not we're doing ajax.
 */
function is_ajax() {
	return defined( 'DOING_AJAX' ) && DOING_AJAX;
}

/**
 * Check if on the login page.
 *
 * @since 1.10.1
 *
 * @return bool Wether or not we're on the login page
 */
function is_login() {
	global $pagenow;
	return in_array( $pagenow, array( 'wp-login.php', 'wp-register.php' ) );
}

/**
 * Check if on the front end of the site.
 *
 * Will return true if not in the admin or otherwise doing a non-admin AJAX request.
 *
 * @since 1.10.1
 *
 * @return bool Wether or not we're on the frontend.
 */
function is_frontend() {
	if ( is_ajax() ) {
		// Check if the referrer is from the admin
		return strpos( $_SERVER['HTTP_REFERER'], admin_url() ) !== 0;
	} else {
		return ! is_admin() && ! is_login();
	}
}

/**
 * Test if  an array is associative or numeric.
 *
 * @since 1.0.0
 *
 * @param array	$array The array to be tested.
 *
 * @return bool The result of the test.
 */
function is_assoc( $array ) {
	return array_values( (array) $array ) !== $array;
}

/**
 * Check if the $key is an associative key (non numeric),
 * Swap with $value and make new value empty array if so.
 *
 * @since 1.6.0 Only if $value is string, and return true/false if key is string.
 * @since 1.0.0
 *
 * @param mixed &$key   The key being tested.
 * @param mixed &$value The value that may be swapped.
 * @param mixed $fill   The new value for $value.
 */
function make_associative( &$key, &$value, $fill = array() ) {
	if ( is_int( $key ) && is_string( $value ) ) {
		$key = $value;
		$value = $fill;
	}
	return is_string( $key );
}

/**
 * Convert a string to a more legible form... or at least try to.
 *
 * @since 1.0.0
 *
 * @param string $string The string that is to be converted to legible form.
 *
 * @return string The input string ( hopefully ) converted to legible form.
 */
function make_legible( $string ) {
	return ucwords( str_replace( array( '_', '-' ), ' ', $string ) );
}

/**
 * Utility for pluralize and singularize
 *
 * @since 1.0.0
 *
 * @param $string The string to process.
 * @param $rules  The list of find/replace rules to test with.
 *
 * @return string The processed string.
 */
function _process_n_form( $string, $rules ) {
	foreach ( $rules as $rule ) {
		if ( preg_match( $rule[0], $string ) ) {
			return preg_replace( $rule[0], $rule[1], $string );
		}
	}

	return $string;
}

/**
 * Convert a string to plural form... or at least try to.
 *
 * @since 1.10.0 Added condition for words like series
 * @since 1.6.0 Fixed missing e on ch/x/s-es
 * @since 1.0.0
 *
 * @param string $string The string that is to be converted to plural form.
 *
 * @return string The input string ( hopefully ) converted to plural form.
 */
function pluralize( $string ) {
	// The find/replace rules, ordered most specialised to most generic
	$plurals = array(
 		array( '/([^aeiou])ies$/', '$1ies' ), // series => series
 		array( '/erson$/', 'eople' ), // person => people
 		array( '/man$/', 'men' ), // woman => women
		array( '/(fe?)$/i', '$1ves' ), // half => halves, knife > knives
		array( '/([^aeiou])y$/', '$1ies' ), // baby => babies
		array( '/(ch|x|s)$/', '$1es' ), // batch => batches, box => boxes, bus => buses
		array( '/$/', 's' ), // thing => things
	);

	return _process_n_form( $string, $plurals );
}

/**
 * Convert a string to singular form... or at least try to.
 *
 * @since 1.10.0 Added condition for words like series
 * @since 1.0.0
 *
 * @param string $string The string that is to be converted to singular form.
 *
 * @return string The input string ( hopefully ) converted to singular form.
 */
function singularize( $string ) {
	// The find/replace rules, ordered most specialised to most generic
	$singulars = array(
 		array( '/([^aeiou])ies$/', '$1ies' ), // series => series
 		array( '/eople$/', 'erson' ), // people => person
 		array( '/men$/', 'man' ), // women => woman
		array( '/ives$/i', 'ife' ), // knives => knife
		array( '/ves$/i', 'f' ), // halves => half
		array( '/([^aeiou])ies$/', '$1y' ), // babies => baby
		array( '/(ch|x|s)es$/', '$1' ), // batches => batch, boxes => box, buses => bus
 		array( '/s$/i', '' ), // things => thing
	);

	return _process_n_form( $string, $singulars );
}

/**
 * Parse a format string and replace the placeholders with the matched values.
 *
 * Formatting is similar to in vprintf; placeholders are prefixed with a %,
 * %% results in a literal %. Placeholder names must be alphanumeric + underscore,
 * and can be prematurely terminated with a $ (e.g. %myfield$name matches myfield).
 *
 * @since 1.0.0
 *
 * @param string $format The format string to parse.
 * @param array  $values The values to parse with.
 *
 * @return string The formated string.
 */
function sprintp( $format, $values ) {
	$result = preg_replace_callback( '/(?:(?<!%)%(\w+)\$?)/', function ( $matches ) use ( $values ) {
		$var = $matches[1];

		$result = $matches[0];
		if ( isset( $values[ $var ] ) ) {
			$result = $values[ $var ];
		}

		return $result;
	}, $format );

	// Fix double %
	$result = str_replace( '%%', '%', $result );

	return $result;
}

/**
 * Echo's the output of  sprintp().
 *
 * @see sprintp()
 */
function printp( $format, $values ) {
	echo sprintp( $format, $values );
}

/**
 * Take a comma/whitespace separated string and split it into an array.
 *
 * Will return an array of one value if no commas are found.
 *
 * @since 1.0.0
 *
 * @param string $var The string to split.
 *
 * @return array The split array.
 */
function csv_array( $var ) {
	if ( is_array( $var ) ) {
		return $var;
	}
	return preg_split( '/[\s,]+/', $var, 0, PREG_SPLIT_NO_EMPTY );
}

/**
 * Calls csv_array on the passed variable if it's not already an array.
 *
 * @since 1.0.0
 *
 * @param mixed &$var The variable to process, passed by reference.
 */
function csv_array_ref( &$var ) {
	if ( ! is_array( $var ) ) {
		$var = csv_array( $var );
	}
}

/**
 * Given an array, extract the disired value defined like so: myvar[mykey][0].
 *
 * @since 1.6.0 Overhauled and simplified.
 * @since 1.0.0
 *
 * @param array        $array The array to extract from.
 * @param array|string $map   The map to follow, in myvar[mykey] or [myvar, mykey] form.
 *
 * @return mixed The extracted value.
 */
function extract_value( $array, $map ) {
	// Abort if not an array
	if ( ! is_array( $array ) ) return $array;

	// If $map is a string, turn it into an array
	if ( ! is_array( $map ) ) {
		$map = trim( $map, ']' ); // Get rid of last ] so we don't have an empty value at the end
		$map = preg_split( '/[\[\]]+/', $map );
	}

	// Extract the first key to look for
	$key = array_shift( $map );

	// See if it exists
	if ( isset( $array[ $key ] ) ) {
		// See if we need to go deeper
		if ( $map ) {
			return extract_value( $array[ $key ], $map );
		} else {
			return $array[ $key ];
		}
	} else {
		// Nothing found.
		return null;
	}
}

/**
 * Get the values of the array, whitelisted.
 *
 * @since 1.9.0
 *
 * @param array $array The array of values.
 * @param array $keys  The whitelist of keys (can also pass as individual arguments).
 *
 * @return array The whitelisted values.
 */
function get_array_values( array $array, $whitelist ) {
	// Get the full arguments, make the list (sans the first one)
	// the $whitelist if it's not already an array.
	$args = func_get_args();
	array_shift( $args );
	if ( ! is_array( $whitelist ) ) {
		$whitelist = $args;
	}

	$values = array();
	if ( is_assoc( $array ) ) {
		// Associative array, add only keys that are whitelisted
		foreach ( $whitelist as $key ) {
			if ( isset( $array[ $key ] ) ) {
				$values[ $key ] = $array[ $key ];
			}
		}
	} else {
		// Numeric array, assume values are in proper order
		foreach ( $array as $i => $value ) {
			$values[ $whitelist[ $i ] ] = $value;
		}
	}

	return $values;
}

/**
 * Restructure an array into a more logical layout.
 *
 * Best exable is the $_FILES array when you have multiple file fields with an array name.
 *
 * <input type="file" name="import[something]">
 * This would restructure $_FILES so instead of $_FILES['import']['name']['something'],
 * we get $_FILES['import']['something']['name']
 *
 * @since 1.0.0
 *
 * @param array $array The array that is to be restructured.
 *
 * @return array The restructured array.
 */
function diverse_array( $array ) {
    $result = array();
    foreach ( $array as $key1 => $value1 ) {
        foreach ( $value1 as $key2 => $value2 ) {
            $result[ $key2 ][ $key1 ] = $value2;
        }
    }

   return $result;
}

/**
 * Replace a string within all array values.
 *
 * @since 1.0.0
 *
 * @param mixed $find    The string( s ) to find in the array.
 * @param mixed $replace The string( s ) to replace in the array.
 * @param array &$array  The array to be processed, passed by reference.
 */
function str_replace_in_array( $find, $replace, &$array ) {
	array_walk_recursive( $array, function( &$item ) use ( $find, $replace ) {
		$item = str_replace( $find, $replace, $item );
	} );
}

/**
 * Convert an array of objects into an associative array of scalars,
 * using provided properies as the key and value data.
 *
 * @since 1.5.0
 *
 * @param array  $objects    The array of objects.
 * @param string $key_prop   The property to use for the key.
 * @param string $value_prop The property to use for the value.
 *
 * @return array The simplified array.
 */
function simplify_object_array( $objects, $key_prop, $value_prop ) {
	$array = array();
	foreach ( $objects as $object ) {
		$array[ $object->$key_prop ] = $object->$value_prop;
	}
	return $array;
}