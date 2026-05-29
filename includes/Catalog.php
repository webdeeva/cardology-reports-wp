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
			// Default to enabled; admins can opt-out per report.
			$row['enabled']           = array_key_exists( 'enabled', $override )
				? (bool) $override['enabled']
				: true;
			$row['slug']              = $slug;
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
			$cleaned[ $slug ] = array(
				'title'             => sanitize_text_field( wp_unslash( $input[ $slug ]['title'] ?? '' ) ),
				'short_description' => sanitize_text_field( wp_unslash( $input[ $slug ]['short_description'] ?? '' ) ),
				'description'       => wp_kses_post( wp_unslash( $input[ $slug ]['description'] ?? '' ) ),
				'price_cents'       => max( 0, (int) ( $input[ $slug ]['price_cents'] ?? 0 ) ),
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
}
