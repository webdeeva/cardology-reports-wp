<?php
namespace CRWP\Admin;

use CRWP\Catalog;
use CRWP\Appearance;
use CRWP\Stripe_Client;
use CRWP\Report_Writer_Client;
use CRWP\Mailer;
use CRWP\REST;

defined( 'ABSPATH' ) || exit;

/**
 * Admin menu, screens, and Settings API registration.
 */
final class Admin {

	public const CAP = 'manage_options';
	public const MENU_SLUG = 'cardology-reports';

	private Catalog $catalog;
	private Appearance $appearance;

	public function __construct( Catalog $catalog, Appearance $appearance ) {
		$this->catalog    = $catalog;
		$this->appearance = $appearance;
	}

	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'maybe_save_catalog' ) );
		add_action( 'admin_init', array( $this, 'maybe_run_bulk_action' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_settings_notices' ) );
	}

	public function register_menu(): void {
		add_menu_page(
			__( 'Cardology Reports', 'cardology-reports' ),
			__( 'Cardology Reports', 'cardology-reports' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-tickets-alt',
			56
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Dashboard', 'cardology-reports' ),
			__( 'Dashboard', 'cardology-reports' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' )
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Reports Catalog', 'cardology-reports' ),
			__( 'Reports Catalog', 'cardology-reports' ),
			self::CAP,
			self::MENU_SLUG . '-catalog',
			array( $this, 'render_catalog' )
		);
		add_submenu_page(
			self::MENU_SLUG,
			__( 'Settings', 'cardology-reports' ),
			__( 'Settings', 'cardology-reports' ),
			self::CAP,
			self::MENU_SLUG . '-settings',
			array( $this, 'render_settings' )
		);
	}

	public function enqueue_admin_assets( string $hook ): void {
		if ( strpos( $hook, self::MENU_SLUG ) === false ) {
			return;
		}
		wp_enqueue_style(
			'crwp-admin',
			CRWP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			CRWP_VERSION
		);
	}

	/* -------------------- Settings API -------------------- */

	public function register_settings(): void {
		// Stripe keys.
		register_setting(
			'crwp_stripe',
			Stripe_Client::SETTINGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_stripe' ),
				'default'           => array(),
			)
		);
		// Report Writer.
		register_setting(
			'crwp_report_api',
			Report_Writer_Client::SETTINGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_report_api' ),
				'default'           => array(),
			)
		);
		// Email.
		register_setting(
			'crwp_email',
			Mailer::SETTINGS_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_email' ),
				'default'           => array(),
			)
		);
		// Appearance.
		register_setting(
			'crwp_appearance',
			Appearance::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( Appearance::class, 'sanitize' ),
				'default'           => array(),
			)
		);
		// Pages.
		register_setting(
			'crwp_pages',
			'crwp_catalog_page_id',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
		register_setting(
			'crwp_pages',
			'crwp_status_page_id',
			array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
		// Uninstall opt-in.
		register_setting(
			'crwp_advanced',
			'crwp_delete_data_on_uninstall',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => static fn( $v ) => (int) (bool) $v,
				'default'           => 0,
			)
		);
	}

	public function sanitize_stripe( $input ): array {
		$input = is_array( $input ) ? $input : array();
		return array(
			'mode'                 => in_array( $input['mode'] ?? 'test', array( 'test', 'live' ), true ) ? $input['mode'] : 'test',
			'test_publishable_key' => sanitize_text_field( $input['test_publishable_key'] ?? '' ),
			'test_secret_key'      => sanitize_text_field( $input['test_secret_key'] ?? '' ),
			'test_webhook_secret'  => sanitize_text_field( $input['test_webhook_secret'] ?? '' ),
			'live_publishable_key' => sanitize_text_field( $input['live_publishable_key'] ?? '' ),
			'live_secret_key'      => sanitize_text_field( $input['live_secret_key'] ?? '' ),
			'live_webhook_secret'  => sanitize_text_field( $input['live_webhook_secret'] ?? '' ),
			'currency'             => preg_match( '/^[a-zA-Z]{3}$/', $input['currency'] ?? 'usd' )
				? strtolower( $input['currency'] )
				: 'usd',
		);
	}

	public function sanitize_report_api( $input ): array {
		$input = is_array( $input ) ? $input : array();
		return array(
			'api_key' => sanitize_text_field( $input['api_key'] ?? '' ),
			'api_url' => esc_url_raw( $input['api_url'] ?? 'https://report-writer-qt7cc.ondigitalocean.app/api/v1' ),
		);
	}

	public function sanitize_email( $input ): array {
		$input        = is_array( $input ) ? $input : array();
		$allowed_modes = array(
			Mailer::FROM_MODE_SITE_DEFAULT,
			Mailer::FROM_MODE_ADMIN,
			Mailer::FROM_MODE_NOREPLY,
			Mailer::FROM_MODE_CUSTOM,
		);
		$mode = isset( $input['from_email_mode'] ) && in_array( $input['from_email_mode'], $allowed_modes, true )
			? $input['from_email_mode']
			: Mailer::FROM_MODE_NOREPLY;
		return array(
			'from_name'       => sanitize_text_field( $input['from_name'] ?? get_bloginfo( 'name' ) ),
			'from_email_mode' => $mode,
			'from_email'      => sanitize_email( $input['from_email'] ?? '' ),
		);
	}

	public function render_settings_notices(): void {
		if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::MENU_SLUG . '-catalog' ) {
			return;
		}
		if ( isset( $_GET['crwp_saved'] ) && '1' === $_GET['crwp_saved'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'Reports catalog saved.', 'cardology-reports' ) . '</p></div>';
		}
		if ( isset( $_GET['crwp_bulk'] ) ) {
			$action = sanitize_key( $_GET['crwp_bulk'] );
			$count  = isset( $_GET['count'] ) ? (int) $_GET['count'] : 0;
			$msg    = '';
			switch ( $action ) {
				case 'sale':
					$msg = sprintf(
						/* translators: %d count of reports */
						_n( 'Sale price applied to %d report.', 'Sale price applied to %d reports.', $count, 'cardology-reports' ),
						$count
					);
					break;
				case 'clear-sales':
					$msg = sprintf(
						/* translators: %d count of reports */
						_n( 'Cleared the sale on %d report.', 'Cleared the sale on %d reports.', $count, 'cardology-reports' ),
						$count
					);
					break;
				case 'enable-all':
					$msg = sprintf(
						/* translators: %d count of reports */
						_n( 'Enabled %d report.', 'Enabled %d reports.', $count, 'cardology-reports' ),
						$count
					);
					break;
				case 'disable-all':
					$msg = sprintf(
						/* translators: %d count of reports */
						_n( 'Disabled %d report.', 'Disabled %d reports.', $count, 'cardology-reports' ),
						$count
					);
					break;
			}
			if ( '' !== $msg ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
			}
		}
	}

	/* -------------------- Catalog bulk actions -------------------- */

	public function maybe_run_bulk_action(): void {
		if ( ! isset( $_POST['crwp_bulk_action'] ) ) {
			return;
		}
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to edit settings.', 'cardology-reports' ) );
		}
		check_admin_referer( 'crwp_bulk_action', 'crwp_bulk_nonce' );

		$action = sanitize_key( wp_unslash( $_POST['crwp_bulk_action'] ) );
		$count  = 0;
		switch ( $action ) {
			case 'sale':
				$percent = isset( $_POST['crwp_bulk_percent'] ) ? (float) wp_unslash( $_POST['crwp_bulk_percent'] ) : 0;
				$start   = isset( $_POST['crwp_bulk_start'] ) ? sanitize_text_field( wp_unslash( $_POST['crwp_bulk_start'] ) ) : '';
				$end     = isset( $_POST['crwp_bulk_end'] ) ? sanitize_text_field( wp_unslash( $_POST['crwp_bulk_end'] ) ) : '';
				$count   = $this->catalog->bulk_apply_sale_percent( $percent, $start, $end );
				break;
			case 'clear-sales':
				$count = $this->catalog->bulk_clear_sales();
				break;
			case 'enable-all':
				$count = $this->catalog->bulk_set_enabled( true );
				break;
			case 'disable-all':
				$count = $this->catalog->bulk_set_enabled( false );
				break;
			default:
				return;
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::MENU_SLUG . '-catalog',
					'crwp_bulk' => $action,
					'count'     => $count,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/* -------------------- Catalog save -------------------- */

	public function maybe_save_catalog(): void {
		if ( ! isset( $_POST['crwp_catalog_save'] ) ) {
			return;
		}
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to edit settings.', 'cardology-reports' ) );
		}
		check_admin_referer( 'crwp_catalog_save', 'crwp_catalog_nonce' );
		$rows = isset( $_POST['crwp_catalog'] ) && is_array( $_POST['crwp_catalog'] ) ? wp_unslash( $_POST['crwp_catalog'] ) : array();
		$this->catalog->save_overrides( $rows );
		wp_safe_redirect(
			add_query_arg(
				array( 'page' => self::MENU_SLUG . '-catalog', 'crwp_saved' => '1' ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/* -------------------- Screen renderers -------------------- */

	public function render_dashboard(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$rest_url = esc_url( rest_url( REST::NS . '/webhook' ) );
		include CRWP_PLUGIN_DIR . 'templates/admin/dashboard.php';
	}

	public function render_catalog(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$reports = $this->catalog->all();
		include CRWP_PLUGIN_DIR . 'templates/admin/catalog.php';
	}

	public function render_settings(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$stripe     = ( new Stripe_Client() )->settings();
		$report     = ( new Report_Writer_Client() )->settings();
		$email      = ( new Mailer( $this->appearance ) )->settings();
		$appearance = $this->appearance->settings();
		$presets    = Appearance::presets();
		$tokens     = Appearance::TOKENS;
		$pages      = array(
			'catalog' => (int) get_option( 'crwp_catalog_page_id', 0 ),
			'status'  => (int) get_option( 'crwp_status_page_id', 0 ),
		);
		$advanced   = array(
			'delete_on_uninstall' => (int) get_option( 'crwp_delete_data_on_uninstall', 0 ),
		);
		$webhook_url = esc_url( rest_url( REST::NS . '/webhook' ) );
		include CRWP_PLUGIN_DIR . 'templates/admin/settings.php';
	}
}
