<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Stripe API access. Uses the Stripe SDK when available (Composer install)
 * but otherwise falls back to plain HTTP calls so the plugin still works on
 * shared hosts that don't allow Composer.
 */
final class Stripe_Client {

	public const SETTINGS_KEY = 'crwp_stripe_keys';

	public function settings(): array {
		$defaults = array(
			'mode'                 => 'test', // 'test' | 'live'
			'test_publishable_key' => '',
			'test_secret_key'      => '',
			'test_webhook_secret'  => '',
			'live_publishable_key' => '',
			'live_secret_key'      => '',
			'live_webhook_secret'  => '',
			'currency'             => 'usd',
		);
		$saved    = get_option( self::SETTINGS_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( $defaults, $saved );
	}

	public function active_secret_key(): string {
		$s = $this->settings();
		return 'live' === $s['mode'] ? (string) $s['live_secret_key'] : (string) $s['test_secret_key'];
	}

	public function active_publishable_key(): string {
		$s = $this->settings();
		return 'live' === $s['mode'] ? (string) $s['live_publishable_key'] : (string) $s['test_publishable_key'];
	}

	public function active_webhook_secret(): string {
		$s = $this->settings();
		return 'live' === $s['mode'] ? (string) $s['live_webhook_secret'] : (string) $s['test_webhook_secret'];
	}

	public function currency(): string {
		$s = $this->settings();
		return $s['currency'] ?: 'usd';
	}

	/**
	 * Creates a Stripe Checkout Session in payment mode for a report order.
	 *
	 * @param array{
	 *   slug:string,
	 *   title:string,
	 *   amount_cents:int,
	 *   currency:string,
	 *   customer_email:string,
	 *   metadata:array<string,string>,
	 *   success_url:string,
	 *   cancel_url:string,
	 *   promotion_code?:string|null,
	 * } $params
	 * @return array{ id:string, url:string } | \WP_Error
	 */
	public function create_checkout_session( array $params ) {
		$secret = $this->active_secret_key();
		if ( '' === $secret ) {
			return new \WP_Error( 'crwp_no_stripe_key', __( 'Stripe is not configured.', 'cardology-reports' ) );
		}

		$body = array(
			'mode'                         => 'payment',
			'payment_method_types[]'       => 'card',
			'customer_email'               => $params['customer_email'],
			'line_items[0][quantity]'      => 1,
			'line_items[0][price_data][currency]'                  => $params['currency'],
			'line_items[0][price_data][unit_amount]'               => (int) $params['amount_cents'],
			'line_items[0][price_data][product_data][name]'        => $params['title'],
			'line_items[0][price_data][product_data][description]' => sprintf(
				/* translators: %s customer name */
				__( 'Personalized Cardology reading for %s', 'cardology-reports' ),
				$params['metadata']['customer_name'] ?? ''
			),
			'success_url' => $params['success_url'],
			'cancel_url'  => $params['cancel_url'],
		);

		foreach ( $params['metadata'] as $k => $v ) {
			$body[ 'metadata[' . $k . ']' ] = $v;
		}

		if ( ! empty( $params['promotion_code'] ) ) {
			$body['discounts[0][promotion_code]'] = $params['promotion_code'];
		} else {
			$body['allow_promotion_codes'] = 'true';
		}

		$res = wp_remote_post(
			'https://api.stripe.com/v1/checkout/sessions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $secret,
					'Content-Type'  => 'application/x-www-form-urlencoded',
				),
				'body'    => $body,
				'timeout' => 20,
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $json ) || empty( $json['id'] ) ) {
			$msg = is_array( $json ) && isset( $json['error']['message'] ) ? $json['error']['message'] : (string) wp_remote_retrieve_body( $res );
			return new \WP_Error( 'crwp_stripe_error', $msg );
		}
		return array(
			'id'  => (string) $json['id'],
			'url' => (string) ( $json['url'] ?? '' ),
		);
	}

	/**
	 * Look up an active promotion code by its visible code value.
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function lookup_promotion_code( string $code ) {
		$secret = $this->active_secret_key();
		if ( '' === $secret ) {
			return new \WP_Error( 'crwp_no_stripe_key', __( 'Stripe is not configured.', 'cardology-reports' ) );
		}
		$res = wp_remote_get(
			add_query_arg(
				array( 'code' => $code, 'active' => 'true', 'limit' => '1' ),
				'https://api.stripe.com/v1/promotion_codes'
			),
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $secret ),
				'timeout' => 15,
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $json ) || empty( $json['data'][0] ) ) {
			return new \WP_Error( 'crwp_promo_not_found', __( 'Invalid or expired code', 'cardology-reports' ) );
		}
		$promo = $json['data'][0];
		// Expand the coupon.
		if ( ! empty( $promo['coupon'] ) && is_string( $promo['coupon'] ) ) {
			$cres = wp_remote_get(
				'https://api.stripe.com/v1/coupons/' . rawurlencode( $promo['coupon'] ),
				array(
					'headers' => array( 'Authorization' => 'Bearer ' . $secret ),
					'timeout' => 15,
				)
			);
			if ( ! is_wp_error( $cres ) ) {
				$promo['coupon'] = json_decode( (string) wp_remote_retrieve_body( $cres ), true );
			}
		}
		return $promo;
	}

	/**
	 * Verify a Stripe webhook signature using the t/v1 scheme.
	 * Tolerance: 5 minutes.
	 */
	public function verify_webhook( string $payload, string $signature_header, ?string $secret_override = null ): bool {
		$secret = $secret_override ?? $this->active_webhook_secret();
		if ( '' === $secret || '' === $signature_header ) {
			return false;
		}
		$parts = array();
		foreach ( explode( ',', $signature_header ) as $kv ) {
			$kv = trim( $kv );
			[ $k, $v ] = array_pad( explode( '=', $kv, 2 ), 2, '' );
			$parts[ $k ][] = $v;
		}
		$timestamp = isset( $parts['t'][0] ) ? (int) $parts['t'][0] : 0;
		$sigs      = $parts['v1'] ?? array();
		if ( ! $timestamp || empty( $sigs ) ) {
			return false;
		}
		if ( abs( time() - $timestamp ) > 5 * MINUTE_IN_SECONDS ) {
			return false;
		}
		$signed_payload = $timestamp . '.' . $payload;
		$expected       = hash_hmac( 'sha256', $signed_payload, $secret );
		foreach ( $sigs as $sig ) {
			if ( hash_equals( $expected, $sig ) ) {
				return true;
			}
		}
		return false;
	}
}
