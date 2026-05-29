<?php
/**
 * Plugin Name:       Cardology Reports
 * Plugin URI:        https://aquariusmaximus.com/reports
 * Description:       Sell personalized Cardology reports on your WordPress site. Stripe Checkout, automatic report generation via the Report Writer API, and email delivery.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Aquarius Maximus
 * Author URI:        https://aquariusmaximus.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cardology-reports
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'CRWP_VERSION', '1.0.0' );
define( 'CRWP_PLUGIN_FILE', __FILE__ );
define( 'CRWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CRWP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// PSR-4-ish autoloader for our own classes only.
spl_autoload_register(
	static function ( $class ) {
		if ( strpos( $class, 'CRWP\\' ) !== 0 ) {
			return;
		}
		$relative = substr( $class, strlen( 'CRWP\\' ) );
		$path     = CRWP_PLUGIN_DIR . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( file_exists( $path ) ) {
			require_once $path;
		}
	}
);

// Optional Composer autoload (Stripe SDK).
if ( file_exists( CRWP_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once CRWP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Lifecycle hooks must be registered at top level (not inside other hooks).
register_activation_hook( __FILE__, array( 'CRWP\\Lifecycle', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CRWP\\Lifecycle', 'deactivate' ) );

// Bootstrap the plugin once all plugins are loaded.
add_action(
	'plugins_loaded',
	static function () {
		CRWP\Plugin::instance()->init();
	}
);
