<?php
/**
 * Uninstall handler — runs when the plugin is deleted from the WordPress UI.
 *
 * We respect a stored opt-in flag so admins don't lose order history by accident.
 * Set the option `crwp_delete_data_on_uninstall` to `1` from the settings screen
 * to fully purge data.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( (int) get_option( 'crwp_delete_data_on_uninstall', 0 ) !== 1 ) {
	return;
}

global $wpdb;

// Drop our custom orders table.
$table = $wpdb->prefix . 'crwp_orders';
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$table}" );

// Remove options.
$option_keys = array(
	'crwp_db_version',
	'crwp_stripe_keys',
	'crwp_report_api',
	'crwp_email_settings',
	'crwp_catalog',
	'crwp_delete_data_on_uninstall',
);
foreach ( $option_keys as $key ) {
	delete_option( $key );
}

// Clear scheduled cron.
wp_clear_scheduled_hook( 'crwp_poll_pending_reports' );
