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
	 * Paged list of orders for the admin Customers screen, newest first.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function paged( int $limit = 25, int $offset = 0, string $search = '' ): array {
		global $wpdb;
		$limit  = max( 1, $limit );
		$offset = max( 0, $offset );

		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$this->table} WHERE customer_name LIKE %s OR customer_email LIKE %s OR report_slug LIKE %s ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$like,
					$like,
					$like,
					$limit,
					$offset
				),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
					$limit,
					$offset
				),
				ARRAY_A
			);
		}
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Count orders, optionally filtered by the same search as paged().
	 */
	public function count( string $search = '' ): int {
		global $wpdb;
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT COUNT(*) FROM {$this->table} WHERE customer_name LIKE %s OR customer_email LIKE %s OR report_slug LIKE %s",
					$like,
					$like,
					$like
				)
			);
		}
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
	}

	/**
	 * Headline totals for the Customers screen. Revenue excludes failed orders.
	 *
	 * @return array{orders:int,revenue_cents:int}
	 */
	public function totals(): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$revenue = (int) $wpdb->get_var( "SELECT COALESCE(SUM(amount_cents),0) FROM {$this->table} WHERE status <> 'failed'" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$orders = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
		return array(
			'orders'        => $orders,
			'revenue_cents' => $revenue,
		);
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
