<?php
/**
 * QuickStart External Utility Functions
 *
 * @package QuickStart
 * @subpackage Utilities
 * @since 1.0.0
 */

/**
 * Fill up an array to a set length, setting null values for entries that aren't set.
 *
 * @since 1.0.0
 *
 * @param array $array The array of arguments
 * @param int   $lenth The length to fill the array to
 *
 * @return $array The newly filled array
 */
function fill_array( &$array, $length ) {
	if ( ! is_array( $array ) ) {
		$array = (array) $array;
	}

	$array += array_fill( 0, $length, null );

	return $array;
}

/**
 * Test if  an array is associative or numeric
 *
 * @since 1.0.0
 *
 * @param array	$array The array to be tested
 *
 * @return bool The result of the test
 */
function is_assoc( $array ) {
	return array_values( (array) $array ) !== $array;
}

/**
 * Check if the $key is an associative key (non numeric),
 * Swap with $value and make new value empty array if so.
 *
 * @since 1.0.0
 *
 * @param mixed &$key   The key being tested.
 * @param mixed &$value The value that may be swapped.
 */
function make_associative( &$key, &$value ) {
	if ( is_int( $key ) ) {
		$key = $value;
		$value = array();
	}
}

/**
 * Convert a string to a more legible form... or at least try to
 *
 * @since 1.0.0
 *
 * @param string $string The string that is to be converted to legible form
 *
 * @return string The input string ( hopefully ) converted to legible form
 */
function make_legible( $string ) {
	return ucwords( str_replace( array( '_', '-' ), ' ', $string ) );
}

/**
 * Restructure an array into a more logical layout
 *
 * Best exable is the $_FILES array when you have multiple file fields with an array name
 * <input type="file" name="import[something]">
 * This would restructure $_FILES so instead of $_FILES['import']['name']['something'] we get $_FILES['import']['something']['name']
 *
 * @since 1.0.0
 *
 * @param array $array The array that is to be restructured
 *
 * @return array The restructured array
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
 * Utility for pluralize and singularize
 *
 * @since 1.0.0
 *
 * @param $string The string to process.
 * @param $rules  The list of find/replace rules to test with
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
 * Convert a string to plural form... or at least try to
 *
 * @since 1.0.0
 *
 * @param string $string The string that is to be converted to plural form
 * @return string The input string ( hopefully ) converted to plural form
 */
function pluralize( $string ) {
	// The find/replace rules, ordered most specialised to most generic
	$plurals = array(
 		array( '/erson$/', 'eople' ), // person => people
 		array( '/man$/', 'men' ), // woman => women
		array( '/(fe?)$/i', '$1ves' ), // half => halves, knife > knives
		array( '/([^aeiou])y$/', '$1ies' ),  // baby => babies
		array( '/(ch|x|s)$/', '$1s' ), // batch => batches, box => boxes, bus => buses
		array( '/$/', 's' ) // thing => things
	);

	return _process_n_form( $string, $plurals );
}

/**
 * Convert a string to singular form... or at least try to
 *
 * @since 1.0.0
 *
 * @param string $string The string that is to be converted to singular form
 * @return string The input string ( hopefully ) converted to singular form
 */
function singularize( $string ) {
	// The find/replace rules, ordered most specialised to most generic
	$singulars = array(
 		array( '/eople$/', 'erson' ), // people => person
 		array( '/men$/', 'man' ), // women => woman
		array( '/ives$/i', 'ife' ), // knives => knife
		array( '/ves$/i', 'f' ), // halves => half
		array( '/([^aeiou])ies$/', '$1y' ), // babies => baby
		array( '/(ch|x|s)es$/', '$1' ), // batches => batch, boxes => box, buses => bus
 		array( '/s$/i', '' ) // things => thing
	);

	return _process_n_form( $string, $singulars );
}

/**
 * Given an array, extract the disired value defined like so: myvar[mykey][0]
 *
 * @since 1.0.0
 *
 * @param array $array The array to extract from
 * @param string $map The array map representation to work from
 * @return mixed The extracted value
 */
function extract_value( $array, $map ) {
	if ( ! is_array( $array ) ) return $array;

	// Break $map into the starting key and the subsequent map
	preg_match( '/^(.+?)(\[.+\])?$/', $map, $matches );
	$key = $matches[1];
	$map = $matches[2];
	$array = $array[ $key ];

	if ( ! is_array( $array ) ) {
		// Resulting $array not an array, return as the value
		return $array;
	} elseif ( preg_match_all( '/\[(.+?)\]/', $map, $matches ) ) {
		// Keys can be extracted from the map, loop through them
		$value = $array;
		foreach ( $matches[1] as $key ) {
			// Check if  the current $value has that key
			if ( is_array( $value ) && isset( $value[ $key ] ) ) {
				// Reassign that value to $value
				$value = $value[ $key ];
			} else {
				// No dice, return null
				return null;
			}
		}
		// Done!  return the extracted value
		return $value;
	} elseif ( isset( $array[ $map ] ) ) {
		// Map isn't an actual map, but actual key of the array, return the value
		return $array[ $map ];
	} else {
		// Otherwise, return null
		return null;
	}
}

/**
 * Replace a string within all array values
 *
 * @since 1.0.0
 *
 * @param mixed $find The string( s ) to find in the array
 * @param mixed $replace The string( s ) to replace in the array
 * @param array &$array The array to be processed, passed by reference
 */
function str_replace_in_array( $find, $replace, &$array ) {
	array_walk_recursive( $array, function( &$item ) use ( $find, $replace ) {
		$item = str_replace( $find, $replace, $item );
	} );
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
 * Echo's the output of  sprintp()
 *
 * @see sprintp()
 */
function printp( $format, $values ) {
	echo sprintp( $format, $values );
}