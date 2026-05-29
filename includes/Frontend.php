<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Customer-facing render: shortcodes for the catalog, order form, and status page.
 *
 * Usage in posts/pages:
 *   [cardology_reports]               — catalog grid
 *   [cardology_report slug="life"]    — single report order form
 *   [cardology_report_status]         — status / download page (uses ?session_id=)
 */
final class Frontend {

	private Catalog $catalog;

	public function __construct( Catalog $catalog ) {
		$this->catalog = $catalog;
	}

	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_shortcodes(): void {
		add_shortcode( 'cardology_reports', array( $this, 'shortcode_catalog' ) );
		add_shortcode( 'cardology_report', array( $this, 'shortcode_single' ) );
		add_shortcode( 'cardology_report_status', array( $this, 'shortcode_status' ) );
	}

	public function register_assets(): void {
		wp_register_style(
			'crwp-front',
			CRWP_PLUGIN_URL . 'assets/css/front.css',
			array(),
			CRWP_VERSION
		);
		wp_register_script(
			'crwp-front',
			CRWP_PLUGIN_URL . 'assets/js/front.js',
			array( 'wp-i18n' ),
			CRWP_VERSION,
			true
		);
		$plugin     = Plugin::instance();
		$publishable = $plugin->stripe->active_publishable_key();
		wp_localize_script(
			'crwp-front',
			'CRWP_CFG',
			array(
				'restRoot'        => esc_url_raw( rest_url( REST::NS ) ),
				'nonce'           => wp_create_nonce( 'wp_rest' ),
				'stripeKey'       => $publishable,
				'catalogUrl'      => $this->catalog_permalink(),
				'currency'        => $plugin->stripe->currency(),
				'i18n'            => array(
					'redirecting' => __( 'Redirecting to checkout…', 'cardology-reports' ),
					'claiming'    => __( 'Claiming your report…', 'cardology-reports' ),
					'applied'     => __( 'applied', 'cardology-reports' ),
					'free'        => __( 'FREE', 'cardology-reports' ),
					'apply'       => __( 'Apply', 'cardology-reports' ),
					'invalidCode' => __( 'That code is not valid.', 'cardology-reports' ),
				),
			)
		);
	}

	private function catalog_permalink(): string {
		$page = (int) get_option( 'crwp_catalog_page_id', 0 );
		return $page ? get_permalink( $page ) : home_url( '/' );
	}

	private function enqueue(): void {
		wp_enqueue_style( 'crwp-front' );
		wp_enqueue_script( 'crwp-front' );
		// Load Stripe.js when this shortcode renders so checkout works.
		wp_enqueue_script( 'stripe-js', 'https://js.stripe.com/v3/', array(), null, true );
	}

	/* -------------------- Shortcodes -------------------- */

	public function shortcode_catalog(): string {
		$this->enqueue();
		$reports = $this->catalog->enabled();
		ob_start();
		include CRWP_PLUGIN_DIR . 'templates/front/catalog.php';
		return (string) ob_get_clean();
	}

	public function shortcode_single( $atts ): string {
		$atts   = shortcode_atts( array( 'slug' => '' ), (array) $atts, 'cardology_report' );
		$report = $this->catalog->get( (string) $atts['slug'] );
		if ( ! $report ) {
			return '<p>' . esc_html__( 'Unknown report.', 'cardology-reports' ) . '</p>';
		}
		if ( empty( $report['enabled'] ) ) {
			return '<p>' . esc_html__( 'This report is not currently available.', 'cardology-reports' ) . '</p>';
		}
		$this->enqueue();
		ob_start();
		include CRWP_PLUGIN_DIR . 'templates/front/single.php';
		return (string) ob_get_clean();
	}

	public function shortcode_status(): string {
		$this->enqueue();
		$session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';
		ob_start();
		include CRWP_PLUGIN_DIR . 'templates/front/status.php';
		return (string) ob_get_clean();
	}
}
