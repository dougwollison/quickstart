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
		$array = ( array ) $array;
	}

	for( $i = 0; $i < $length; $i++ ) {
		if ( ! isset( $array[ $i ] ) ) {
			$array[$i] = null;
		}
	}

	return $array;
}

/**
 * "Serialize" an array into PHP code
 *
 * @since 1.0.0
 *
 * @param array	$array The array to be serialized
 *
 * @return string The resulting code
 */
function phpize( $array ) {
	if ( is_string( $array ) ) return json_encode( $array );

	$code = array();
	$assoc = is_assoc( $array );
	foreach ( $array as $key => $value ) {
		$_code = '';
		if ( $assoc ) {
			$key = json_encode( $key );
			$_code .= "$key=>";
		}
		if ( is_array( $value ) ) {
			$_code .= phpize( $value );
		} elseif ( is_string( $value ) ) {
			$_code .= json_encode( $value );
		} elseif ( is_int( $value ) || is_float( $value ) ) {
			$_code .= $value;
		} elseif ( is_null( $value ) ) {
			$_code .= "null";
		} elseif ( is_bool( $value ) ) {
			$_code .= $value ? 'true' : 'false';
		}
		$code[] = $_code;
	}
	$code = 'array( ' . implode( ',', $code ) . ' )';
	return $code;
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
	return array_values( (array ) $array ) !== $array;
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
function legible( $string ) {
	return ucwords( str_replace( array( '_','-' ), ' ', $string ) );
}

/**
 * Convert a string to a machine safe form
 *
 * @since 1.0.0
 *
 * @param string $string The string that is to be converted to machine form
 *
 * @return string The input string ( hopefully ) converted to legible form
 */
function machine( $string ) {
	return strtolower( preg_replace( '/[^\w-]+/', '-', $string ) );
}

/**
 * Convert a field name to a valid ID
 *
 * @since 1.0.0
 *
 * @param string $name The name of the field
 *
 * @return string The valid ID
 */
function name_to_id( $name ) {
	return preg_replace( '/\[(.+)\]/', '_$1', $name );
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
    foreach ( $array as $key1 => $value1 )
        foreach ( $value1 as $key2 => $value2 )
            $result[$key2][$key1] = $value2;

   return $result;
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
	$plurals = array(
		array( '/( x|ch|ss|sh )$/i', '$1es' ),
		array( '/( [^aeiou] )y$/i', '$1es' ),
		array( '/( ?:( [^f] )fe|( [lr] )f )$/i', '$1$2ves' ),
		array( '/sis$/i', 'ses' ),
 		array( '/( [ti] )um$/i', '$1a' ),
 		array( '/man$/','men' ),
 		array( '/erson$/','eople' ),
 		array( '/s$/i', 's' ),
		array( '/$/', 's' )
	);

	foreach ( $plurals as $plural ) {
		if ( preg_match( $plural[0], $string ) )
			return preg_replace( $plural[0], $plural[1], $string );
	}

	return $string;
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
	$singulars = array(
		array( '/ies$/i', '$1y' ),
		array( '/ses$/i', 'sis' ),
 		array( '/( [ti] )a$/i', '$1um' ),
 		array( '/men$/','man' ),
 		array( '/eople$/','erson' ),
 		array( '/s$/i', '' )
	);

	foreach ( $singulars as $singular ) {
		if ( preg_match( $singular[0], $string ) )
			return preg_replace( $singular[0], $singular[1], $string );
	}

	return $string;
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
			if ( is_array( $value ) && isset( $value[$key] ) ) {
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