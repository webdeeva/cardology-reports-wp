<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * All outbound transactional email lives here. Uses wp_mail by default so
 * any SMTP plugin (WP Mail SMTP, Postmark, Resend-WP etc.) can take over.
 */
final class Mailer {

	public const SETTINGS_KEY = 'crwp_email_settings';

	public function settings(): array {
		$defaults = array(
			'from_name'  => get_bloginfo( 'name' ),
			'from_email' => get_option( 'admin_email' ),
		);
		$saved    = get_option( self::SETTINGS_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( $defaults, $saved );
	}

	private function headers(): array {
		$s = $this->settings();
		return array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $s['from_name'], $s['from_email'] ),
		);
	}

	public function send_order_received( array $order, string $status_url ): bool {
		$subject = sprintf(
			/* translators: %s report title */
			__( 'Your %s is being prepared', 'cardology-reports' ),
			$order['report_title']
		);
		$body = $this->render_template(
			'order-received',
			array(
				'order'      => $order,
				'status_url' => $status_url,
			)
		);
		return (bool) wp_mail( $order['customer_email'], $subject, $body, $this->headers() );
	}

	public function send_report_ready( array $order, string $report_url ): bool {
		$subject = sprintf(
			/* translators: %s report title */
			__( 'Your %s is ready', 'cardology-reports' ),
			$order['report_title']
		);
		$body = $this->render_template(
			'report-ready',
			array(
				'order'      => $order,
				'report_url' => $report_url,
			)
		);
		return (bool) wp_mail( $order['customer_email'], $subject, $body, $this->headers() );
	}

	private function render_template( string $slug, array $vars ): string {
		$path = CRWP_PLUGIN_DIR . 'templates/emails/' . $slug . '.php';
		if ( ! file_exists( $path ) ) {
			return '';
		}
		ob_start();
		// phpcs:ignore WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER
		extract( $vars, EXTR_SKIP );
		include $path;
		return (string) ob_get_clean();
	}
}
