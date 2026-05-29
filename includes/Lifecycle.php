<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Activation / deactivation / schema management.
 */
final class Lifecycle {

	public const DB_VERSION = '1';

	/**
	 * Runs once on plugin activation.
	 */
	public static function activate(): void {
		self::install_schema();
		Catalog::seed_defaults_if_missing();

		// Schedule the polling cron if not already.
		if ( ! wp_next_scheduled( 'crwp_poll_pending_reports' ) ) {
			wp_schedule_event( time() + 60, 'crwp_two_minutes', 'crwp_poll_pending_reports' );
		}

		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		// Don't drop data, but stop cron.
		$timestamp = wp_next_scheduled( 'crwp_poll_pending_reports' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'crwp_poll_pending_reports' );
		}
		wp_clear_scheduled_hook( 'crwp_poll_pending_reports' );

		flush_rewrite_rules();
	}

	/**
	 * Called from Plugin::init() on every load to catch missed activation/migrations.
	 */
	public static function maybe_upgrade(): void {
		$current = get_option( 'crwp_db_version', '0' );
		if ( version_compare( $current, self::DB_VERSION, '<' ) ) {
			self::install_schema();
			Catalog::seed_defaults_if_missing();
			update_option( 'crwp_db_version', self::DB_VERSION );
		}
	}

	private static function install_schema(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'crwp_orders';
		$charset_collate = $wpdb->get_charset_collate();

		// Note: dbDelta is strict about formatting (two spaces after PRIMARY KEY, etc.).
		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			session_id VARCHAR(191) NOT NULL,
			report_slug VARCHAR(64) NOT NULL,
			amount_cents INT NOT NULL DEFAULT 0,
			currency VARCHAR(8) NOT NULL DEFAULT 'usd',
			customer_name VARCHAR(255) NOT NULL,
			customer_email VARCHAR(191) NOT NULL,
			customer_birthdate VARCHAR(32) NOT NULL,
			customer_birth_time VARCHAR(16) DEFAULT NULL,
			customer_birth_place VARCHAR(255) DEFAULT NULL,
			partner_name VARCHAR(255) DEFAULT NULL,
			partner_birthdate VARCHAR(32) DEFAULT NULL,
			partner_birth_time VARCHAR(16) DEFAULT NULL,
			partner_birth_place VARCHAR(255) DEFAULT NULL,
			age TINYINT UNSIGNED DEFAULT NULL,
			job_id VARCHAR(191) DEFAULT NULL,
			status VARCHAR(32) NOT NULL DEFAULT 'pending',
			report_url TEXT DEFAULT NULL,
			email_sent_at DATETIME DEFAULT NULL,
			completed_at DATETIME DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY session_id (session_id),
			KEY status (status),
			KEY customer_email (customer_email)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		\dbDelta( $sql );

		update_option( 'crwp_db_version', self::DB_VERSION );
	}
}

// Register a custom cron interval used by the polling job.
add_filter(
	'cron_schedules',
	static function ( $schedules ) {
		if ( ! isset( $schedules['crwp_two_minutes'] ) ) {
			$schedules['crwp_two_minutes'] = array(
				'interval' => 2 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 2 minutes (Cardology Reports)', 'cardology-reports' ),
			);
		}
		return $schedules;
	}
);
