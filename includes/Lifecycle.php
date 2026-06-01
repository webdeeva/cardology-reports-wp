<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Activation / deactivation / schema management.
 */
final class Lifecycle {

	public const DB_VERSION = '2';

	/**
	 * Runs once on plugin activation.
	 */
	public static function activate(): void {
		self::install_schema();
		Catalog::seed_defaults_if_missing();
		self::ensure_pages();

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
			self::ensure_pages();
			update_option( 'crwp_db_version', self::DB_VERSION );
		}
	}

	/**
	 * Make sure the customer-facing catalog and status pages exist and are
	 * recorded in the settings. Without a stored status page the post-payment
	 * redirect falls back to the site home, which both loses the status view
	 * and can trip host WAFs (e.g. ModSecurity) on the bare ?session_id URL.
	 *
	 * Runs on activation and on the v1 -> v2 upgrade so existing installs heal
	 * themselves after updating.
	 */
	private static function ensure_pages(): void {
		self::ensure_page( 'crwp_catalog_page_id', __( 'Cardology Reports', 'cardology-reports' ), '[cardology_reports]' );
		self::ensure_page( 'crwp_status_page_id', __( 'Report Status', 'cardology-reports' ), '[cardology_report_status]' );
	}

	/**
	 * Ensure a single shortcode-backed page exists and its ID is stored.
	 *
	 * @param string $option    Option key that stores the page ID.
	 * @param string $title     Title for a newly created page.
	 * @param string $shortcode Shortcode the page must contain.
	 */
	private static function ensure_page( string $option, string $title, string $shortcode ): void {
		// Already configured with a live page? Leave it alone.
		$existing = (int) get_option( $option, 0 );
		if ( $existing > 0 && 'page' === get_post_type( $existing ) ) {
			$status = get_post_status( $existing );
			if ( $status && 'trash' !== $status ) {
				return;
			}
		}

		// Reuse an existing page that already holds the shortcode, if any.
		$page_id    = 0;
		$candidates = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
				'numberposts'      => 5,
				's'                => $shortcode,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		foreach ( $candidates as $candidate_id ) {
			if ( false !== strpos( (string) get_post_field( 'post_content', $candidate_id ), $shortcode ) ) {
				$page_id = (int) $candidate_id;
				break;
			}
		}

		// Otherwise create it.
		if ( 0 === $page_id ) {
			$result = wp_insert_post(
				array(
					'post_title'   => $title,
					'post_content' => $shortcode,
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( $result && ! is_wp_error( $result ) ) {
				$page_id = (int) $result;
			}
		}

		if ( $page_id > 0 ) {
			update_option( $option, $page_id );
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
