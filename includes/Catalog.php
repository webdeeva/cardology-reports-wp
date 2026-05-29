<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * The 10 default reports + persisted admin overrides.
 *
 * Stored as a single autoloaded option `crwp_catalog` so the admin can edit
 * names, descriptions, and prices without code changes.
 */
final class Catalog {

	public const OPTION_KEY = 'crwp_catalog';

	/**
	 * Default catalog. Keys are upstream Report Writer report types.
	 */
	public static function defaults(): array {
		return array(
			'childrens_life'   => array(
				'title'                          => "Children's Life Report",
				'short_description'              => "A parent's guide to a child's cards, gifts, and life path.",
				'description'                    => "An in-depth look at your child's Birth Card, life cards, and the energetic patterns shaping their early years.",
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 12,
			),
			'yearly'           => array(
				'title'                          => 'Yearly Report',
				'short_description'              => 'A 52-week forecast based on your age and yearly spread.',
				'description'                    => 'Your personal year ahead, mapped through the yearly Cardology spread. Includes 13 weekly cycles, key dates, and themes.',
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => true,
				'estimated_minutes'              => 12,
			),
			'singles'          => array(
				'title'                          => 'Singles Report',
				'short_description'              => 'Insight into your romantic patterns and what attracts you.',
				'description'                    => 'For unattached readers ready to meet the right partner. Explores what you bring to a relationship and call in.',
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 12,
			),
			'life'             => array(
				'title'                          => 'Life Report',
				'short_description'              => 'A complete portrait of who you are and why you are here.',
				'description'                    => 'A deep-dive into your Birth Card, Planetary Ruling Card, life-path cards, and major karmic themes.',
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 12,
			),
			'financial'        => array(
				'title'                          => 'Financial Report',
				'short_description'              => 'Money, work, and the cards that shape your finances.',
				'description'                    => 'How your cards influence the way you earn, spend, save, and invest.',
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 12,
			),
			'wealth'           => array(
				'title'                          => 'Wealth Report',
				'short_description'              => 'Long-game prosperity: build, protect, multiply.',
				'description'                    => 'A higher-altitude reading on the cards of abundance, opportunity windows, and durable wealth.',
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 12,
			),
			'relationship'     => array(
				'title'                          => 'Relationship Report',
				'short_description'              => 'Two-card compatibility for any partnership.',
				'description'                    => "A side-by-side reading of you and your partner's cards: connections, tensions, and timing of relationship cycles.",
				'price_cents'                    => 3400,
				'requires_partner'               => true,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 14,
			),
			'marriage'         => array(
				'title'                          => 'Marriage Report',
				'short_description'              => 'Long-term partnership compatibility, in depth.',
				'description'                    => 'A long-form reading for committed and married couples. Covers shared karmic cards and cycles ahead.',
				'price_cents'                    => 2500,
				'requires_partner'               => true,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 14,
			),
			'blueprint'        => array(
				'title'                          => 'Blueprint Report',
				'short_description'              => 'The master map of your soul, mind, body, and life cycles.',
				'description'                    => 'Our most comprehensive single-person reading. Combines life-path cards, planetary periods, and karmic patterns.',
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => false,
				'supports_age'                   => false,
				'estimated_minutes'              => 15,
			),
			'astro_cardology'  => array(
				'title'                          => 'Astro-Cardology Report',
				'short_description'              => 'Cardology + your full astrological chart, woven together.',
				'description'                    => 'A premium reading that synthesizes your Cardology spread with a full natal astrology chart. Birth time and place required.',
				'price_cents'                    => 2500,
				'requires_partner'               => false,
				'requires_birth_time_and_place'  => true,
				'supports_age'                   => false,
				'estimated_minutes'              => 15,
			),
		);
	}

	/**
	 * Returns the catalog, merging admin overrides with defaults.
	 * Structural flags (requires_partner, etc.) always come from defaults — admins
	 * can only override the editable fields (title, descriptions, price, enabled).
	 *
	 * Disabled reports are still returned here so the admin catalog editor can
	 * display them. Customer-facing code paths should use {@see enabled()}.
	 */
	public function all(): array {
		$defaults  = self::defaults();
		$overrides = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $overrides ) ) {
			$overrides = array();
		}

		$out = array();
		foreach ( $defaults as $slug => $default ) {
			$row             = $default;
			$override        = isset( $overrides[ $slug ] ) && is_array( $overrides[ $slug ] ) ? $overrides[ $slug ] : array();
			$row['title']             = isset( $override['title'] ) && '' !== $override['title']
				? $override['title']
				: $default['title'];
			$row['short_description'] = isset( $override['short_description'] ) && '' !== $override['short_description']
				? $override['short_description']
				: $default['short_description'];
			$row['description']       = isset( $override['description'] ) && '' !== $override['description']
				? $override['description']
				: $default['description'];
			$row['price_cents']       = isset( $override['price_cents'] ) && (int) $override['price_cents'] > 0
				? (int) $override['price_cents']
				: $default['price_cents'];
			$row['sale_price_cents']  = isset( $override['sale_price_cents'] ) && (int) $override['sale_price_cents'] > 0
				? (int) $override['sale_price_cents']
				: 0;
			$row['sale_start_date']   = isset( $override['sale_start_date'] ) ? (string) $override['sale_start_date'] : '';
			$row['sale_end_date']     = isset( $override['sale_end_date'] ) ? (string) $override['sale_end_date'] : '';
			// Default to enabled; admins can opt-out per report.
			$row['enabled']           = array_key_exists( 'enabled', $override )
				? (bool) $override['enabled']
				: true;
			$row['slug']              = $slug;
			// Derive sale state at read-time so it stays in sync with the clock.
			$row['on_sale']           = self::is_sale_active( $row );
			$row['effective_price_cents'] = $row['on_sale'] ? $row['sale_price_cents'] : $row['price_cents'];
			$out[ $slug ]             = $row;
		}
		return $out;
	}

	/**
	 * Catalog filtered to only enabled reports. Use this for the customer-facing grid.
	 */
	public function enabled(): array {
		return array_filter( $this->all(), static fn( $r ) => ! empty( $r['enabled'] ) );
	}

	public function get( string $slug ): ?array {
		$all = $this->all();
		return $all[ $slug ] ?? null;
	}

	public function is_enabled( string $slug ): bool {
		$row = $this->get( $slug );
		return $row && ! empty( $row['enabled'] );
	}

	/**
	 * The price to actually charge: sale price when active, regular otherwise.
	 */
	public function effective_price_cents( string $slug ): int {
		$row = $this->get( $slug );
		return $row ? (int) $row['effective_price_cents'] : 0;
	}

	/**
	 * True when sale_price_cents is positive, strictly less than the regular price,
	 * and (if dates are set) the current site-time falls inside the window.
	 *
	 * @param array<string,mixed> $row Already-merged row from {@see all()}.
	 */
	private static function is_sale_active( array $row ): bool {
		$sale = (int) ( $row['sale_price_cents'] ?? 0 );
		$reg  = (int) ( $row['price_cents'] ?? 0 );
		if ( $sale <= 0 || $sale >= $reg ) {
			return false;
		}
		$start = (string) ( $row['sale_start_date'] ?? '' );
		$end   = (string) ( $row['sale_end_date'] ?? '' );
		try {
			$now = current_datetime();
		} catch ( \Exception $e ) {
			$now = new \DateTimeImmutable( 'now' );
		}
		if ( '' !== $start ) {
			$start_dt = self::parse_date_boundary( $start, '00:00:00' );
			if ( $start_dt && $now < $start_dt ) {
				return false;
			}
		}
		if ( '' !== $end ) {
			$end_dt = self::parse_date_boundary( $end, '23:59:59' );
			if ( $end_dt && $now > $end_dt ) {
				return false;
			}
		}
		return true;
	}

	private static function parse_date_boundary( string $date, string $time ): ?\DateTimeImmutable {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}
		try {
			return new \DateTimeImmutable( $date . 'T' . $time, wp_timezone() );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Persists the editable subset of the catalog. Called from the admin save handler.
	 *
	 * @param array<string,array<string,mixed>> $input Untrusted input.
	 */
	public function save_overrides( array $input ): void {
		$defaults  = self::defaults();
		$cleaned   = array();
		foreach ( $defaults as $slug => $_default ) {
			if ( ! isset( $input[ $slug ] ) || ! is_array( $input[ $slug ] ) ) {
				continue;
			}
			$sale_price = max( 0, (int) ( $input[ $slug ]['sale_price_cents'] ?? 0 ) );
			$start_raw  = sanitize_text_field( wp_unslash( $input[ $slug ]['sale_start_date'] ?? '' ) );
			$end_raw    = sanitize_text_field( wp_unslash( $input[ $slug ]['sale_end_date'] ?? '' ) );
			$cleaned[ $slug ] = array(
				'title'             => sanitize_text_field( wp_unslash( $input[ $slug ]['title'] ?? '' ) ),
				'short_description' => sanitize_text_field( wp_unslash( $input[ $slug ]['short_description'] ?? '' ) ),
				'description'       => wp_kses_post( wp_unslash( $input[ $slug ]['description'] ?? '' ) ),
				'price_cents'       => max( 0, (int) ( $input[ $slug ]['price_cents'] ?? 0 ) ),
				'sale_price_cents'  => $sale_price,
				// Store dates as YYYY-MM-DD or empty. Anything else is dropped.
				'sale_start_date'   => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_raw ) ? $start_raw : '',
				'sale_end_date'     => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_raw ) ? $end_raw : '',
				// Unchecked checkboxes don't submit, so the form sends a hidden
				// `enabled_present=1` for every row and we treat absence of
				// `enabled` as "disabled".
				'enabled'           => ! empty( $input[ $slug ]['enabled'] ),
			);
		}
		update_option( self::OPTION_KEY, $cleaned, false );
	}

	public static function seed_defaults_if_missing(): void {
		if ( false === get_option( self::OPTION_KEY, false ) ) {
			add_option( self::OPTION_KEY, array(), '', false );
		}
	}

	/**
	 * Bulk operations exposed to the admin. Each one mutates the override option
	 * in-place and returns the count of affected reports.
	 */
	public function bulk_apply_sale_percent( float $percent_off, string $start_date = '', string $end_date = '' ): int {
		if ( $percent_off <= 0 || $percent_off >= 100 ) {
			return 0;
		}
		$overrides = $this->get_raw_overrides();
		$count     = 0;
		foreach ( $this->all() as $slug => $row ) {
			$regular   = (int) $row['price_cents'];
			$sale      = (int) round( $regular * ( 1 - $percent_off / 100 ) );
			if ( $sale < 50 || $sale >= $regular ) {
				continue;
			}
			$overrides[ $slug ] = array_merge(
				$overrides[ $slug ] ?? array(),
				array(
					'sale_price_cents' => $sale,
					'sale_start_date'  => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ? $start_date : '',
					'sale_end_date'    => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ? $end_date : '',
				)
			);
			$count++;
		}
		update_option( self::OPTION_KEY, $overrides, false );
		return $count;
	}

	public function bulk_clear_sales(): int {
		$overrides = $this->get_raw_overrides();
		$count     = 0;
		foreach ( $overrides as $slug => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$was_on_sale = ! empty( $row['sale_price_cents'] );
			$overrides[ $slug ]['sale_price_cents'] = 0;
			$overrides[ $slug ]['sale_start_date']  = '';
			$overrides[ $slug ]['sale_end_date']    = '';
			if ( $was_on_sale ) {
				$count++;
			}
		}
		update_option( self::OPTION_KEY, $overrides, false );
		return $count;
	}

	public function bulk_set_enabled( bool $enabled ): int {
		$overrides = $this->get_raw_overrides();
		$count     = 0;
		foreach ( self::defaults() as $slug => $_default ) {
			$current = $overrides[ $slug ]['enabled'] ?? true;
			if ( (bool) $current !== $enabled ) {
				$overrides[ $slug ]              = $overrides[ $slug ] ?? array();
				$overrides[ $slug ]['enabled']   = $enabled;
				$count++;
			}
		}
		update_option( self::OPTION_KEY, $overrides, false );
		return $count;
	}

	private function get_raw_overrides(): array {
		$o = get_option( self::OPTION_KEY, array() );
		return is_array( $o ) ? $o : array();
	}
}
