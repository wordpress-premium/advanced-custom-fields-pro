<?php
/**
 * Fires when the plugin is uninstalled.
 *
 * @link    https://rankmath.com
 * @since   2.6.0
 * @package RANK_MATH_PRO
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Set rank_math_clear_data_on_uninstall to TRUE to delete all data on uninstall.
if ( true === apply_filters( 'rank_math_clear_data_on_uninstall', false ) ) {

	if ( ! is_multisite() ) {
		rank_math_pro_drop_tables();
		return;
	}

	global $wpdb;

	$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE archived = '0' AND spam = '0' AND deleted = '0'" );
	if ( ! empty( $blog_ids ) ) {
		foreach ( $blog_ids as $blog_id ) {
			switch_to_blog( $blog_id );
			rank_math_pro_drop_tables();
			restore_current_blog();
		}
	}
}

/**
 * Drop tables from database.
 */
function rank_math_pro_drop_tables() {
	global $wpdb;

	foreach ( [ 'analytics_ga', 'analytics_adsense', 'analytics_keyword_manager' ] as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rank_math_{$table}" ); // phpcs:ignore
	}
}
