<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * All outbound transactional email lives here. Uses wp_mail by default so
 * any SMTP plugin (WP Mail SMTP, Postmark, Resend-WP etc.) can take over.
 */
final class Mailer {

	public const SETTINGS_KEY = 'crwp_email_settings';

	/** From-address selection modes shown in the admin. */
	public const FROM_MODE_SITE_DEFAULT = 'site_default';
	public const FROM_MODE_ADMIN        = 'admin';
	public const FROM_MODE_NOREPLY      = 'noreply';
	public const FROM_MODE_CUSTOM       = 'custom';

	private Appearance $appearance;

	public function __construct( Appearance $appearance ) {
		$this->appearance = $appearance;
	}

	public function settings(): array {
		$defaults = array(
			'from_name'       => get_bloginfo( 'name' ),
			'from_email_mode' => self::FROM_MODE_NOREPLY,
			'from_email'      => '',
			'notify_owner'    => 1,
			'notify_email'    => '',
		);
		$saved    = get_option( self::SETTINGS_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( $defaults, $saved );
	}

	/**
	 * Resolve the actual From address based on the selected mode.
	 */
	public function resolve_from_email(): string {
		$s    = $this->settings();
		$mode = (string) $s['from_email_mode'];
		switch ( $mode ) {
			case self::FROM_MODE_ADMIN:
				$email = (string) get_option( 'admin_email' );
				break;
			case self::FROM_MODE_CUSTOM:
				$email = sanitize_email( (string) $s['from_email'] );
				if ( '' === $email ) {
					$email = (string) get_option( 'admin_email' );
				}
				break;
			case self::FROM_MODE_NOREPLY:
				$email = 'noreply@' . self::site_host();
				break;
			case self::FROM_MODE_SITE_DEFAULT:
			default:
				// Mirrors WordPress core's default from address (wordpress@<host>).
				$email = 'wordpress@' . self::site_host();
				break;
		}
		return $email;
	}

	public static function site_host(): string {
		$host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
		// Strip leading www. so the address looks cleaner.
		return preg_replace( '/^www\./i', '', $host );
	}

	private function headers(): array {
		$s    = $this->settings();
		$from = $this->resolve_from_email();
		return array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $s['from_name'], $from ),
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
				'palette'    => $this->appearance->email_palette(),
				'site_name'  => get_bloginfo( 'name' ),
				'site_url'   => home_url(),
			)
		);
		return (bool) wp_mail( $order['customer_email'], $subject, $body, $this->headers() );
	}

	/**
	 * Where owner/sale notifications are delivered. Falls back to the WP admin
	 * email when no dedicated address is set.
	 */
	public function notify_recipient(): string {
		$email = sanitize_email( (string) ( $this->settings()['notify_email'] ?? '' ) );
		return '' !== $email ? $email : (string) get_option( 'admin_email' );
	}

	/**
	 * Notify the site owner that a sale happened. Reply-To is set to the
	 * customer so the owner can respond directly. No-op when disabled.
	 *
	 * @param array<string,mixed> $order Keys: customer_name, customer_email, report_title, amount_cents.
	 */
	public function send_owner_sale_notification( array $order ): bool {
		if ( empty( $this->settings()['notify_owner'] ) ) {
			return false;
		}
		$to = $this->notify_recipient();
		if ( '' === $to ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s report title */
			__( 'New report sale: %s', 'cardology-reports' ),
			$order['report_title']
		);
		$body = $this->render_template(
			'owner-sale',
			array(
				'order'     => $order,
				'palette'   => $this->appearance->email_palette(),
				'site_name' => get_bloginfo( 'name' ),
				'site_url'  => home_url(),
				'admin_url' => admin_url( 'admin.php?page=cardology-reports-customers' ),
			)
		);

		$headers = $this->headers();
		if ( ! empty( $order['customer_email'] ) ) {
			$headers[] = sprintf( 'Reply-To: %s <%s>', $order['customer_name'] ?? '', $order['customer_email'] );
		}

		return (bool) wp_mail( $to, $subject, $body, $headers );
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
				'palette'    => $this->appearance->email_palette(),
				'site_name'  => get_bloginfo( 'name' ),
				'site_url'   => home_url(),
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
