<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin orchestrator. Holds singletons for each subsystem and wires hooks.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	public Catalog $catalog;
	public Appearance $appearance;
	public Stripe_Client $stripe;
	public Report_Writer_Client $report_writer;
	public Orders $orders;
	public Mailer $mailer;
	public Frontend $frontend;
	public Admin\Admin $admin;
	public REST $rest;
	public Cron $cron;

	private function __construct() {}

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function init(): void {
		// Load text domain for translations.
		load_plugin_textdomain(
			'cardology-reports',
			false,
			dirname( plugin_basename( CRWP_PLUGIN_FILE ) ) . '/languages'
		);

		// Run any pending schema upgrades.
		Lifecycle::maybe_upgrade();

		// Subsystems.
		$this->catalog       = new Catalog();
		$this->appearance    = new Appearance();
		$this->stripe        = new Stripe_Client();
		$this->report_writer = new Report_Writer_Client();
		$this->orders        = new Orders();
		$this->mailer        = new Mailer( $this->appearance );
		$this->cron          = new Cron( $this->orders, $this->report_writer, $this->mailer, $this->catalog );
		$this->rest          = new REST( $this->catalog, $this->stripe, $this->orders, $this->report_writer, $this->mailer );
		$this->frontend      = new Frontend( $this->catalog, $this->appearance );

		$this->cron->register_hooks();
		$this->rest->register_hooks();
		$this->frontend->register_hooks();

		// GitHub-backed auto-updates (runs in admin + wp-cron update checks).
		( new Updater( CRWP_PLUGIN_FILE ) )->register_hooks();

		if ( is_admin() ) {
			$this->admin = new Admin\Admin( $this->catalog, $this->appearance );
			$this->admin->register_hooks();
		}
	}
}
