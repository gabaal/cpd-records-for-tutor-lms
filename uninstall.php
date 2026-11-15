<?php
/**
 * Uninstall routine.
 *
 * Data is kept unless the administrator opted in to deletion.
 *
 * @package CPD_Records
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( '1' !== get_option( 'cpd_records_delete_on_uninstall', '0' ) ) {
	return;
}

global $wpdb;

$table = $wpdb->prefix . 'cpd_records';

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is built from the prefix.
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static meta keys.
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('_cpd_records_gdc_role', '_cpd_records_cycle_start')" );

// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static meta keys.
$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_cpd_records_hours', '_cpd_records_outcomes')" );

delete_option( 'cpd_records_version' );
delete_option( 'cpd_records_delete_on_uninstall' );
