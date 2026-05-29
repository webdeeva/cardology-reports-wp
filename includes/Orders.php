<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Read/write access to the custom orders table.
 *
 * All values that originate from external systems (Stripe metadata, Report
 * Writer responses) are still sanitised here as a defense-in-depth measure —
 * never trust upstream.
 */
final class Orders {

	private string $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'crwp_orders';
	}

	public function table(): string {
		return $this->table;
	}

	/**
	 * @param array<string,mixed> $data
	 * @return int|false Inserted ID or false on failure.
	 */
	public function insert( array $data ) {
		global $wpdb;
		$defaults = array(
			'session_id'           => '',
			'report_slug'          => '',
			'amount_cents'         => 0,
			'currency'             => 'usd',
			'customer_name'        => '',
			'customer_email'       => '',
			'customer_birthdate'   => '',
			'customer_birth_time'  => null,
			'customer_birth_place' => null,
			'partner_name'         => null,
			'partner_birthdate'    => null,
			'partner_birth_time'   => null,
			'partner_birth_place'  => null,
			'age'                  => null,
			'job_id'               => null,
			'status'               => 'pending',
			'report_url'           => null,
			'created_at'           => current_time( 'mysql', true ),
			'updated_at'           => current_time( 'mysql', true ),
		);
		$row     = array_intersect_key( array_merge( $defaults, $data ), $defaults );

		$ok = $wpdb->insert( $this->table, $row );
		if ( false === $ok ) {
			return false;
		}
		return (int) $wpdb->insert_id;
	}

	public function find_by_session( string $session_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table} WHERE session_id = %s LIMIT 1",
				$session_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public function update_by_session( string $session_id, array $patch ): bool {
		global $wpdb;
		if ( empty( $patch ) ) {
			return true;
		}
		$patch['updated_at'] = current_time( 'mysql', true );
		$ok                  = $wpdb->update( $this->table, $patch, array( 'session_id' => $session_id ) );
		return false !== $ok;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public function find_in_flight( int $limit = 50 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT * FROM {$this->table} WHERE status IN ('paid','processing') ORDER BY created_at ASC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}
}
