<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Front-end appearance: theme presets + custom palette.
 *
 * The customer-facing CSS uses CSS custom properties only — this class emits
 * the right `:root` overrides as a small <style> block on pages where our
 * shortcodes render. Admins pick a preset (grouped: Light / Dark / Bold) or
 * choose "Custom" and supply individual colors.
 */
final class Appearance {

	public const OPTION_KEY = 'crwp_appearance';

	/** Token keys that admins can edit in custom mode. */
	public const TOKENS = array(
		'--crwp-bg'           => array( 'label' => 'Section background', 'default' => '#ffffff' ),
		'--crwp-card-bg'      => array( 'label' => 'Card background', 'default' => '#ffffff' ),
		'--crwp-border'       => array( 'label' => 'Borders', 'default' => '#e5e7eb' ),
		'--crwp-text'         => array( 'label' => 'Text', 'default' => 'inherit' ),
		'--crwp-text-muted'   => array( 'label' => 'Muted text', 'default' => '#6b7280' ),
		'--crwp-accent'       => array( 'label' => 'Accent (tags, badges)', 'default' => '#111827' ),
		'--crwp-accent-soft'  => array( 'label' => 'Accent (soft fill)', 'default' => '#f3f4f6' ),
		'--crwp-btn-bg'       => array( 'label' => 'Button background', 'default' => '#111827' ),
		'--crwp-btn-bg-hover' => array( 'label' => 'Button hover', 'default' => '#1f2937' ),
		'--crwp-btn-text'     => array( 'label' => 'Button text', 'default' => '#ffffff' ),
		'--crwp-error-bg'     => array( 'label' => 'Error background', 'default' => '#fef2f2' ),
		'--crwp-error-text'   => array( 'label' => 'Error text', 'default' => '#991b1b' ),
		'--crwp-success-bg'   => array( 'label' => 'Success background', 'default' => '#ecfdf5' ),
		'--crwp-radius'       => array( 'label' => 'Corner radius', 'default' => '10px' ),
	);

	/**
	 * Grouped presets shown as swatches in the admin. Token values become CSS
	 * variables; missing tokens fall through to defaults above.
	 *
	 * @return array<string,array{label:string,options:array<string,array{label:string,colors:array<string,string>,swatches:array<int,string>}>}>
	 */
	public static function presets(): array {
		return array(
			'light' => array(
				'label'   => __( 'Light', 'cardology-reports' ),
				'options' => array(
					'default-white' => array(
						'label'    => __( 'Default White', 'cardology-reports' ),
						'swatches' => array( '#ffffff', '#e5e7eb', '#111827' ),
						'colors'   => array(
							// All defaults — explicit so customers see what's applied.
						),
					),
					'ivory' => array(
						'label'    => __( 'Soft Ivory', 'cardology-reports' ),
						'swatches' => array( '#fdf8f1', '#e7d8c2', '#2a1f12' ),
						'colors'   => array(
							'--crwp-bg'           => '#fdf8f1',
							'--crwp-card-bg'      => '#ffffff',
							'--crwp-border'       => '#e7d8c2',
							'--crwp-text'         => '#2a1f12',
							'--crwp-text-muted'   => '#7d6a52',
							'--crwp-accent'       => '#8b4513',
							'--crwp-accent-soft'  => '#f5ebd7',
							'--crwp-btn-bg'       => '#8b4513',
							'--crwp-btn-bg-hover' => '#6f3610',
							'--crwp-btn-text'     => '#ffffff',
						),
					),
					'slate' => array(
						'label'    => __( 'Slate', 'cardology-reports' ),
						'swatches' => array( '#f8fafc', '#cbd5e1', '#0f172a' ),
						'colors'   => array(
							'--crwp-bg'           => '#f8fafc',
							'--crwp-card-bg'      => '#ffffff',
							'--crwp-border'       => '#cbd5e1',
							'--crwp-text'         => '#0f172a',
							'--crwp-text-muted'   => '#475569',
							'--crwp-accent'       => '#0f172a',
							'--crwp-accent-soft'  => '#e2e8f0',
							'--crwp-btn-bg'       => '#0f172a',
							'--crwp-btn-bg-hover' => '#1e293b',
							'--crwp-btn-text'     => '#ffffff',
						),
					),
				),
			),
			'dark' => array(
				'label'   => __( 'Dark', 'cardology-reports' ),
				'options' => array(
					'burgundy-gold' => array(
						'label'    => __( 'Burgundy & Gold', 'cardology-reports' ),
						'swatches' => array( '#380611', '#f5c542', '#ffffff' ),
						'colors'   => array(
							'--crwp-bg'           => 'linear-gradient(to bottom, #380611, #0c0205)',
							'--crwp-card-bg'      => 'rgba(255,255,255,0.05)',
							'--crwp-border'       => 'rgba(255,255,255,0.12)',
							'--crwp-text'         => '#f5f5f5',
							'--crwp-text-muted'   => 'rgba(245,245,245,0.7)',
							'--crwp-accent'       => '#f5c542',
							'--crwp-accent-soft'  => 'rgba(245,197,66,0.1)',
							'--crwp-btn-bg'       => '#f5c542',
							'--crwp-btn-bg-hover' => '#e2b439',
							'--crwp-btn-text'     => '#000000',
						),
					),
					'midnight' => array(
						'label'    => __( 'Midnight', 'cardology-reports' ),
						'swatches' => array( '#0b1120', '#38bdf8', '#e0f2fe' ),
						'colors'   => array(
							'--crwp-bg'           => '#0b1120',
							'--crwp-card-bg'      => '#111c33',
							'--crwp-border'       => '#1e293b',
							'--crwp-text'         => '#e0f2fe',
							'--crwp-text-muted'   => '#93c5fd',
							'--crwp-accent'       => '#38bdf8',
							'--crwp-accent-soft'  => 'rgba(56,189,248,0.12)',
							'--crwp-btn-bg'       => '#38bdf8',
							'--crwp-btn-bg-hover' => '#0ea5e9',
							'--crwp-btn-text'     => '#0b1120',
						),
					),
					'forest' => array(
						'label'    => __( 'Forest', 'cardology-reports' ),
						'swatches' => array( '#06241b', '#84cc16', '#ecfccb' ),
						'colors'   => array(
							'--crwp-bg'           => '#06241b',
							'--crwp-card-bg'      => '#0a3528',
							'--crwp-border'       => '#1a5c46',
							'--crwp-text'         => '#ecfccb',
							'--crwp-text-muted'   => 'rgba(236,252,203,0.7)',
							'--crwp-accent'       => '#84cc16',
							'--crwp-accent-soft'  => 'rgba(132,204,22,0.12)',
							'--crwp-btn-bg'       => '#84cc16',
							'--crwp-btn-bg-hover' => '#65a30d',
							'--crwp-btn-text'     => '#06241b',
						),
					),
					'charcoal' => array(
						'label'    => __( 'Charcoal & Amber', 'cardology-reports' ),
						'swatches' => array( '#1c1917', '#f59e0b', '#f5f5f4' ),
						'colors'   => array(
							'--crwp-bg'           => '#1c1917',
							'--crwp-card-bg'      => '#292524',
							'--crwp-border'       => '#44403c',
							'--crwp-text'         => '#f5f5f4',
							'--crwp-text-muted'   => '#a8a29e',
							'--crwp-accent'       => '#f59e0b',
							'--crwp-accent-soft'  => 'rgba(245,158,11,0.12)',
							'--crwp-btn-bg'       => '#f59e0b',
							'--crwp-btn-bg-hover' => '#d97706',
							'--crwp-btn-text'     => '#1c1917',
						),
					),
				),
			),
			'bold' => array(
				'label'   => __( 'Bold', 'cardology-reports' ),
				'options' => array(
					'coral' => array(
						'label'    => __( 'Coral Sunrise', 'cardology-reports' ),
						'swatches' => array( '#fff7ed', '#fb923c', '#7c2d12' ),
						'colors'   => array(
							'--crwp-bg'           => '#fff7ed',
							'--crwp-card-bg'      => '#ffffff',
							'--crwp-border'       => '#fed7aa',
							'--crwp-text'         => '#7c2d12',
							'--crwp-text-muted'   => '#9a3412',
							'--crwp-accent'       => '#ea580c',
							'--crwp-accent-soft'  => '#fed7aa',
							'--crwp-btn-bg'       => '#ea580c',
							'--crwp-btn-bg-hover' => '#c2410c',
							'--crwp-btn-text'     => '#ffffff',
						),
					),
					'ocean' => array(
						'label'    => __( 'Ocean Teal', 'cardology-reports' ),
						'swatches' => array( '#ecfeff', '#06b6d4', '#0e7490' ),
						'colors'   => array(
							'--crwp-bg'           => '#ecfeff',
							'--crwp-card-bg'      => '#ffffff',
							'--crwp-border'       => '#a5f3fc',
							'--crwp-text'         => '#0e7490',
							'--crwp-text-muted'   => '#0891b2',
							'--crwp-accent'       => '#0e7490',
							'--crwp-accent-soft'  => '#cffafe',
							'--crwp-btn-bg'       => '#0e7490',
							'--crwp-btn-bg-hover' => '#155e75',
							'--crwp-btn-text'     => '#ffffff',
						),
					),
					'rose' => array(
						'label'    => __( 'Rose Pink', 'cardology-reports' ),
						'swatches' => array( '#fff1f2', '#f43f5e', '#881337' ),
						'colors'   => array(
							'--crwp-bg'           => '#fff1f2',
							'--crwp-card-bg'      => '#ffffff',
							'--crwp-border'       => '#fecdd3',
							'--crwp-text'         => '#881337',
							'--crwp-text-muted'   => '#9f1239',
							'--crwp-accent'       => '#e11d48',
							'--crwp-accent-soft'  => '#ffe4e6',
							'--crwp-btn-bg'       => '#e11d48',
							'--crwp-btn-bg-hover' => '#be123c',
							'--crwp-btn-text'     => '#ffffff',
						),
					),
				),
			),
		);
	}

	public function settings(): array {
		$defaults = array(
			'theme'  => 'default-white',
			'custom' => array(),
		);
		$saved    = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( $defaults, $saved );
	}

	public function active_theme(): string {
		return (string) $this->settings()['theme'];
	}

	/**
	 * Find the preset row by slug, regardless of group.
	 *
	 * @return array{label:string,colors:array<string,string>,swatches:array<int,string>}|null
	 */
	public static function find_preset( string $slug ): ?array {
		foreach ( self::presets() as $group ) {
			if ( isset( $group['options'][ $slug ] ) ) {
				return $group['options'][ $slug ];
			}
		}
		return null;
	}

	/**
	 * Resolve the CSS variable map to apply on the front-end.
	 *
	 * @return array<string,string>
	 */
	public function tokens(): array {
		$theme = $this->active_theme();
		if ( 'custom' === $theme ) {
			$custom = $this->settings()['custom'];
			return is_array( $custom ) ? $custom : array();
		}
		$preset = self::find_preset( $theme );
		return $preset ? $preset['colors'] : array();
	}

	/**
	 * Emits a small <style> block scoped to .crwp-root that overrides only the
	 * tokens the active theme actually changes. Defaults stay in front.css.
	 */
	public function inline_style(): string {
		$tokens = $this->tokens();
		if ( empty( $tokens ) ) {
			return '';
		}
		$lines = array();
		foreach ( $tokens as $key => $value ) {
			// Allow-list the keys; values are sanitised before save so we can trust them here.
			if ( ! preg_match( '/^--crwp-[a-z0-9-]+$/', $key ) ) {
				continue;
			}
			$lines[] = $key . ':' . $value . ';';
		}
		if ( empty( $lines ) ) {
			return '';
		}
		return ".crwp-root{" . implode( '', $lines ) . "}";
	}

	/**
	 * Sanitiser used by register_setting.
	 */
	public static function sanitize( $input ): array {
		$theme = isset( $input['theme'] ) ? sanitize_key( $input['theme'] ) : 'default-white';
		if ( 'custom' !== $theme && null === self::find_preset( $theme ) ) {
			$theme = 'default-white';
		}
		$custom = array();
		if ( isset( $input['custom'] ) && is_array( $input['custom'] ) ) {
			foreach ( $input['custom'] as $k => $v ) {
				if ( ! is_string( $k ) || ! preg_match( '/^--crwp-[a-z0-9-]+$/', $k ) ) {
					continue;
				}
				if ( ! is_string( $v ) ) {
					continue;
				}
				// Accept hex, rgb/rgba, hsl/hsla, named colors, simple gradients, "inherit".
				if ( ! preg_match( '#^(?:inherit|[a-zA-Z]+|#[0-9a-fA-F]{3,8}|rgba?\([0-9.,\s%]+\)|hsla?\([0-9.,\s%]+\)|linear-gradient\([^)]+\)|[0-9.]+px|[0-9.]+rem)$#', $v ) ) {
					continue;
				}
				$custom[ $k ] = $v;
			}
		}
		return array(
			'theme'  => $theme,
			'custom' => $custom,
		);
	}
}
