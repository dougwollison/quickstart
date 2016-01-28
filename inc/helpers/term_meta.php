<?php
/**
 * Term Meta helpers; support setup and utilities for term meta data.
 *
 * @package QuickStart
 * @subpackage Term_Meta
 *
 * @since 1.10.0
 */
global $wpdb;

// Check for native termmeta support based on presence of termmeta table.
define('QS_CHECK_TERMMETA_SUPPORT', in_array( 'termmeta', $wpdb->tables ) );

// =========================
// ! Setup & Hooks
// =========================

// Register the new termmeta table if not already present
if ( ! QS_CHECK_TERMMETA_SUPPORT ) :

$wpdb->tables[] = 'termmeta';
$wpdb->termmeta = $wpdb->prefix . 'termmeta';

// Version number for update purposes
define( 'QS_TERMMETA_VERSION', '1.0' );

/**
 * Register the termmeta table in the database.
 *
 * @since 1.10.0
 */
function qs_helper_termmeta_installtable() {
	// Don't bother if not in the admin
	if ( ! is_admin() ) {
		return;
	}

	global $wpdb;

	// Skip if the version number is up to date and logged
	if ( get_option( 'qs_termmeta_version' ) === QS_TERMMETA_VERSION ) {
		return;
	}

	$charset_collate = '';

	if ( ! empty($wpdb->charset) ) {
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	}
	if ( ! empty($wpdb->collate) ) {
		$charset_collate .= " COLLATE $wpdb->collate";
	}

	$sql = "CREATE TABLE $wpdb->termmeta (
	  meta_id bigint(20) unsigned NOT NULL auto_increment,
	  term_id bigint(20) unsigned NOT NULL default '0',
	  meta_key varchar(255) default NULL,
	  meta_value longtext,
	  PRIMARY KEY  (meta_id),
	  KEY comment_id (term_id),
	  KEY meta_key (meta_key)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	// Log the version number for updating and checking
	update_option( 'qs_termmeta_version', QS_TERMMETA_VERSION );
}

// Add install hook at most immediate point
if ( did_action( 'plugins_loaded' ) ) {
	// Was loaded by a theme
	add_action( 'after_setup_theme', 'qs_helper_termmeta_installtable' );
} else {
	// Was loaded by a plugin
	add_action( 'plugins_loaded', 'qs_helper_termmeta_installtable' );
}

endif; // QS_CHECK_TERMMETA_SUPPORT

/**
 * Filters the term query clauses to add support for basic filtering and ordering by meta data.
 *
 * @since 1.11.0 Added interal fix for including non-ordered terms on the order manager.
 * @since 1.10.0
 *
 * @param array $clauses    The clauses for the SQL.
 * @param array $taxonomies The taxonomies requested when get_terms was called.
 * @param array $args       The arguments passed to get_terms.
 *
 * @return array The modified clauses.
 */
function qs_helper_termmeta_clauses( $clauses, $taxonomies, $args ) {
	global $wpdb;

	// Check if meta key is set, add additional WHERE clause
	if ( isset( $args['meta_key'] ) ) {
		// Update the JOIN clause to include the term meta table
		$clauses['join'] .= " LEFT JOIN $wpdb->termmeta AS tm ON t.term_id = tm.term_id";

		// Update the WHERE clause to match entries with the meta
		$clause = " AND tm.meta_key = %s";
		if ( isset( $args['qs-context'] ) && $args['qs-context'] == 'order-manager' ) {
			// For the order manager, include terms that have NO meta data set.
			$clause = " AND ( tm.meta_key = %s OR tm.term_id IS NULL )";
		}
		$clauses['where'] .= $wpdb->prepare( $clause, $args['meta_key'] );

		// Check if there was a request to filter by meta_value
		if ( isset( $args['meta_value'] ) || isset( $args['meta_value_num'] ) ) {
			// Get the compare operator (must be a valid A/B operator)
			if ( isset( $args['meta_compare'] )
			  && in_array( $args['meta_compare'], array( '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE' ) ) ) {
				$compare = $args['meta_compare'];
			} else {
				// Default to equals
				$compare = '=';
			}

			// Determine handling of field and value
			$field = 'tm.meta_value';
			$value = '%s';
			if ( isset( $args['meta_value_num'] ) ) {
				$field .= '+0';
				$value = '%d';
			}

			$clauses['where'] .= $wpdb->prepare( " AND $field $compare $value", $args['meta_value'] );
		}

		// Check if the request was to order by a meta value
		if ( in_array( $args['orderby'], array( 'meta_value', 'meta_value_num' ) ) ) {
			// Replace the ORDER BY clause, converting to integer if needed
			$clauses['orderby'] = "ORDER BY tm.meta_value";
			if ( $args['orderby'] == 'meta_value_num' ) {
				$clauses['orderby'] .= '+0';
			}
		}
	}

	return $clauses;
}
add_filter( 'terms_clauses', 'qs_helper_termmeta_clauses', 10, 3 );

/**
 * Add default value for menu_order term meta key.
 *
 * @since 1.11.0
 *
 * @param int $term_id The ID of the term being deleted.
 */
function qs_helper_termmeta_insertterm( $term_id ) {
	add_term_meta( $term_id, 'menu_order', 0, true );
}
add_action( 'create_term', 'qs_helper_termmeta_insertterm' );

/**
 * Deletes all meta data tied to the term being deleted.
 *
 * @since 1.10.0
 *
 * @param int $term_id The ID of the term being deleted.
 */
function qs_helper_termmeta_deleteterm( $term_id ) {
	global $wpdb;

	$wpdb->delete( $wpdb->termmeta, array(
		'term_id' => $term_id,
	) );
}
add_action( 'delete_term', 'qs_helper_termmeta_deleteterm' );

// =========================
// ! Utilities
// =========================

// Add term meta utility functions if not already present
if ( ! QS_CHECK_TERMMETA_SUPPORT ) :

/**
 * Add meta data field to a term.
 *
 * @since 1.10.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Metadata value.
 * @param bool   $unique     Optional, default is false. Whether the same key should not be added.
 *
 * @return int|bool Meta ID on success, false on failure.
 */
function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata( 'term', $term_id, $meta_key, $meta_value, $unique );
}

/**
 * Remove metadata matching criteria from a term.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @since 1.10.0
 *
 * @param int    $term_id    The term ID
 * @param string $meta_key   Metadata name.
 * @param mixed  $meta_value Optional. Metadata value.
 *
 * @return bool True on success, false on failure.
 */
function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
	return delete_metadata( 'term', $term_id, $meta_key, $meta_value );
}

/**
 * Retrieve term meta field for a term.
 *
 * @since 1.10.0
 *
 * @param int    $term_id Term ID.
 * @param string $key     Optional. The meta key to retrieve. By default, returns data for all keys.
 * @param bool   $single  Whether to return a single value.
 *
 * @return mixed Will be an array if $single is false.
 *               Will be value of meta data field if $single is true.
 */
function get_term_meta( $term_id, $key = '', $single = false ) {
	return get_metadata( 'term', $term_id, $key, $single );
}

/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @since 1.10.0
 *
 * @param int    $term_id    Term ID.
 * @param string $meta_key   Metadata key.
 * @param mixed  $meta_value Metadata value.
 * @param mixed  $prev_value Optional. Previous value to check before removing.
 *
 * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'term', $term_id, $meta_key, $meta_value, $prev_value );
}

endif; // QS_CHECK_TERMMETA_SUPPORT