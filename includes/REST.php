<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * REST routes under /wp-json/cardology-reports/v1/...
 *
 * - POST /checkout       Creates Stripe Checkout Session for a paid order.
 * - POST /claim-free     Creates the order + kicks off generation for a 100%-off code.
 * - POST /validate-promo Validates a promo code against Stripe.
 * - GET  /status         Returns the current state of an order (polled by the success page).
 * - POST /webhook        Stripe webhook target.
 */
final class REST {

	public const NS = 'cardology-reports/v1';

	private Catalog $catalog;
	private Stripe_Client $stripe;
	private Orders $orders;
	private Report_Writer_Client $report_writer;
	private Mailer $mailer;

	public function __construct(
		Catalog $catalog,
		Stripe_Client $stripe,
		Orders $orders,
		Report_Writer_Client $report_writer,
		Mailer $mailer
	) {
		$this->catalog       = $catalog;
		$this->stripe        = $stripe;
		$this->orders        = $orders;
		$this->report_writer = $report_writer;
		$this->mailer        = $mailer;
	}

	public function register_hooks(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		register_rest_route(
			self::NS,
			'/checkout',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_checkout' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/claim-free',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'claim_free' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/validate-promo',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate_promo' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			self::NS,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'status' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'sessionId' => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/* -------------------- Helpers -------------------- */

	private static function clean_customer( array $in ): array {
		return array(
			'name'        => sanitize_text_field( wp_unslash( $in['name'] ?? '' ) ),
			'email'       => sanitize_email( wp_unslash( $in['email'] ?? '' ) ),
			'birthdate'   => sanitize_text_field( wp_unslash( $in['birthdate'] ?? '' ) ),
			'birthTime'   => isset( $in['birthTime'] ) ? sanitize_text_field( wp_unslash( $in['birthTime'] ) ) : null,
			'birthPlace'  => isset( $in['birthPlace'] ) ? sanitize_text_field( wp_unslash( $in['birthPlace'] ) ) : null,
		);
	}

	private static function clean_partner( ?array $in ): ?array {
		if ( ! is_array( $in ) ) {
			return null;
		}
		$out = array(
			'name'       => sanitize_text_field( wp_unslash( $in['name'] ?? '' ) ),
			'birthdate'  => sanitize_text_field( wp_unslash( $in['birthdate'] ?? '' ) ),
			'birthTime'  => isset( $in['birthTime'] ) ? sanitize_text_field( wp_unslash( $in['birthTime'] ) ) : null,
			'birthPlace' => isset( $in['birthPlace'] ) ? sanitize_text_field( wp_unslash( $in['birthPlace'] ) ) : null,
		);
		return ( '' === $out['name'] || '' === $out['birthdate'] ) ? null : $out;
	}

	private function discount_cents( array $coupon, int $original ): int {
		if ( isset( $coupon['percent_off'] ) && is_numeric( $coupon['percent_off'] ) ) {
			return (int) max( 0, round( $original * ( 1 - $coupon['percent_off'] / 100 ) ) );
		}
		if ( isset( $coupon['amount_off'] ) && is_numeric( $coupon['amount_off'] ) ) {
			return (int) max( 0, $original - (int) $coupon['amount_off'] );
		}
		return $original;
	}

	private function generate_payload( array $report, array $customer, ?array $partner, ?int $age ): array {
		$customer_payload = array(
			'name'      => $customer['name'],
			'email'     => $customer['email'],
			'birthdate' => $customer['birthdate'],
		);
		if ( $customer['birthTime'] ) {
			$customer_payload['birthTime'] = $customer['birthTime'];
		}
		if ( $customer['birthPlace'] ) {
			$customer_payload['birthPlace'] = $customer['birthPlace'];
		}
		if ( 'yearly' === $report['slug'] && null !== $age ) {
			$customer_payload['age'] = $age;
		}

		$payload = array(
			'reportType' => $report['slug'],
			'customer'   => $customer_payload,
		);

		if ( $partner && ( 'relationship' === $report['slug'] || 'marriage' === $report['slug'] ) ) {
			$p = array(
				'name'      => $partner['name'],
				'birthdate' => $partner['birthdate'],
			);
			if ( $partner['birthTime'] ) {
				$p['birthTime'] = $partner['birthTime'];
			}
			if ( $partner['birthPlace'] ) {
				$p['birthPlace'] = $partner['birthPlace'];
			}
			$payload['partner'] = $p;
		}

		return $payload;
	}

	private function status_url( string $session_id ): string {
		$page = (int) get_option( 'crwp_status_page_id', 0 );
		$base = $page ? get_permalink( $page ) : home_url( '/' );
		return add_query_arg( 'session_id', $session_id, $base );
	}

	private function shape_status( array $order ): array {
		return array(
			'status'      => $order['status'],
			'reportSlug'  => $order['report_slug'],
			'reportUrl'   => $order['report_url'] ?: null,
			'emailSent'   => ! empty( $order['email_sent_at'] ),
			'completedAt' => $order['completed_at'] ?? null,
			'createdAt'   => $order['created_at'] ?? null,
		);
	}

	/* -------------------- Endpoints -------------------- */

	public function validate_promo( \WP_REST_Request $req ): \WP_REST_Response {
		$code = sanitize_text_field( wp_unslash( $req->get_param( 'code' ) ?? '' ) );
		$slug = sanitize_text_field( wp_unslash( $req->get_param( 'reportSlug' ) ?? '' ) );
		if ( '' === $code ) {
			return new \WP_REST_Response( array( 'valid' => false, 'error' => __( 'Code is required.', 'cardology-reports' ) ), 400 );
		}
		$report = $this->catalog->get( $slug );
		if ( ! $report ) {
			return new \WP_REST_Response( array( 'valid' => false, 'error' => __( 'Unknown report.', 'cardology-reports' ) ), 400 );
		}

		if ( empty( $report['enabled'] ) ) {
			return new \WP_REST_Response( array( 'valid' => false, 'error' => __( 'This report is not currently available.', 'cardology-reports' ) ), 200 );
		}
		$promo = $this->stripe->lookup_promotion_code( $code );
		if ( is_wp_error( $promo ) ) {
			return new \WP_REST_Response( array( 'valid' => false, 'error' => $promo->get_error_message() ), 200 );
		}
		$coupon = is_array( $promo['coupon'] ?? null ) ? $promo['coupon'] : array();
		if ( empty( $coupon ) || empty( $coupon['valid'] ) ) {
			return new \WP_REST_Response( array( 'valid' => false, 'error' => __( 'This code is no longer valid.', 'cardology-reports' ) ), 200 );
		}
		if ( ! empty( $promo['expires_at'] ) && (int) $promo['expires_at'] < time() ) {
			return new \WP_REST_Response( array( 'valid' => false, 'error' => __( 'This code has expired.', 'cardology-reports' ) ), 200 );
		}
		if ( ! empty( $promo['max_redemptions'] ) && (int) $promo['times_redeemed'] >= (int) $promo['max_redemptions'] ) {
			return new \WP_REST_Response( array( 'valid' => false, 'error' => __( 'This code has been fully redeemed.', 'cardology-reports' ) ), 200 );
		}

		$original_cents   = (int) $report['price_cents'];
		$discounted_cents = $this->discount_cents( $coupon, $original_cents );
		$min_cents        = 50; // Stripe minimum.
		$free             = $discounted_cents < $min_cents;
		return new \WP_REST_Response(
			array(
				'valid'             => true,
				'code'              => $promo['code'] ?? $code,
				'promotionCodeId'   => $promo['id'] ?? '',
				'discountPercent'   => $coupon['percent_off'] ?? null,
				'discountAmountCents' => $coupon['amount_off'] ?? null,
				'originalCents'     => $original_cents,
				'discountedCents'   => $free ? 0 : $discounted_cents,
				'freeReport'        => $free,
			),
			200
		);
	}

	public function create_checkout( \WP_REST_Request $req ): \WP_REST_Response {
		$slug   = sanitize_text_field( wp_unslash( $req->get_param( 'reportSlug' ) ?? '' ) );
		$report = $this->catalog->get( $slug );
		if ( ! $report ) {
			return new \WP_REST_Response( array( 'error' => __( 'Unknown report slug.', 'cardology-reports' ) ), 400 );
		}
		if ( empty( $report['enabled'] ) ) {
			return new \WP_REST_Response( array( 'error' => __( 'This report is not currently available.', 'cardology-reports' ) ), 400 );
		}
		$customer = self::clean_customer( (array) $req->get_param( 'customer' ) );
		if ( '' === $customer['name'] || '' === $customer['email'] || '' === $customer['birthdate'] ) {
			return new \WP_REST_Response( array( 'error' => __( 'Customer name, email, and birth date are required.', 'cardology-reports' ) ), 400 );
		}
		if ( $report['requires_birth_time_and_place'] && ( ! $customer['birthTime'] || ! $customer['birthPlace'] ) ) {
			return new \WP_REST_Response( array( 'error' => __( 'Birth time and place are required for this report.', 'cardology-reports' ) ), 400 );
		}
		$partner = self::clean_partner( $req->get_param( 'partner' ) );
		if ( $report['requires_partner'] && ! $partner ) {
			return new \WP_REST_Response( array( 'error' => __( 'Partner info is required for this report.', 'cardology-reports' ) ), 400 );
		}
		$age = $req->get_param( 'age' );
		$age = is_numeric( $age ) ? (int) $age : null;

		// Metadata (string-only).
		$metadata = array(
			'report_slug'      => $report['slug'],
			'customer_name'    => $customer['name'],
			'customer_email'   => $customer['email'],
			'customer_birthdate' => $customer['birthdate'],
		);
		if ( $customer['birthTime'] ) {
			$metadata['customer_birth_time'] = $customer['birthTime'];
		}
		if ( $customer['birthPlace'] ) {
			$metadata['customer_birth_place'] = $customer['birthPlace'];
		}
		if ( $partner ) {
			$metadata['partner_name']      = $partner['name'];
			$metadata['partner_birthdate'] = $partner['birthdate'];
			if ( $partner['birthTime'] ) {
				$metadata['partner_birth_time'] = $partner['birthTime'];
			}
			if ( $partner['birthPlace'] ) {
				$metadata['partner_birth_place'] = $partner['birthPlace'];
			}
		}
		if ( null !== $age ) {
			$metadata['age'] = (string) $age;
		}

		$promotion_code_id = sanitize_text_field( wp_unslash( $req->get_param( 'promotionCodeId' ) ?? '' ) );

		$origin = $req->get_header( 'origin' );
		if ( ! $origin ) {
			$origin = home_url();
		}
		$success_url = add_query_arg( 'session_id', '{CHECKOUT_SESSION_ID}', $this->status_url( '__placeholder__' ) );
		// Replace the placeholder back to Stripe's token after building.
		$success_url = str_replace( '__placeholder__', '{CHECKOUT_SESSION_ID}', $success_url );

		$session = $this->stripe->create_checkout_session(
			array(
				'slug'             => $report['slug'],
				'title'            => $report['title'],
				'amount_cents'     => (int) $report['price_cents'],
				'currency'         => $this->stripe->currency(),
				'customer_email'   => $customer['email'],
				'metadata'         => $metadata,
				'success_url'      => $success_url,
				'cancel_url'       => $origin,
				'promotion_code'   => $promotion_code_id ?: null,
			)
		);
		if ( is_wp_error( $session ) ) {
			return new \WP_REST_Response( array( 'error' => $session->get_error_message() ), 500 );
		}

		return new \WP_REST_Response(
			array(
				'sessionId' => $session['id'],
				'url'       => $session['url'],
			),
			200
		);
	}

	public function claim_free( \WP_REST_Request $req ): \WP_REST_Response {
		$slug   = sanitize_text_field( wp_unslash( $req->get_param( 'reportSlug' ) ?? '' ) );
		$report = $this->catalog->get( $slug );
		if ( ! $report ) {
			return new \WP_REST_Response( array( 'error' => __( 'Unknown report slug.', 'cardology-reports' ) ), 400 );
		}
		if ( empty( $report['enabled'] ) ) {
			return new \WP_REST_Response( array( 'error' => __( 'This report is not currently available.', 'cardology-reports' ) ), 400 );
		}
		$customer = self::clean_customer( (array) $req->get_param( 'customer' ) );
		if ( '' === $customer['name'] || '' === $customer['email'] || '' === $customer['birthdate'] ) {
			return new \WP_REST_Response( array( 'error' => __( 'Customer info incomplete', 'cardology-reports' ) ), 400 );
		}
		if ( $report['requires_birth_time_and_place'] && ( ! $customer['birthTime'] || ! $customer['birthPlace'] ) ) {
			return new \WP_REST_Response( array( 'error' => __( 'Birth time and place are required', 'cardology-reports' ) ), 400 );
		}
		$partner = self::clean_partner( $req->get_param( 'partner' ) );
		if ( $report['requires_partner'] && ! $partner ) {
			return new \WP_REST_Response( array( 'error' => __( 'Partner info is required', 'cardology-reports' ) ), 400 );
		}
		$age  = $req->get_param( 'age' );
		$age  = is_numeric( $age ) ? (int) $age : null;
		$code = sanitize_text_field( wp_unslash( $req->get_param( 'promoCode' ) ?? '' ) );
		if ( '' === $code ) {
			return new \WP_REST_Response( array( 'error' => __( 'Promo code is required', 'cardology-reports' ) ), 400 );
		}

		// Revalidate code: must drop below Stripe minimum.
		$promo = $this->stripe->lookup_promotion_code( $code );
		if ( is_wp_error( $promo ) ) {
			return new \WP_REST_Response( array( 'error' => $promo->get_error_message() ), 400 );
		}
		$coupon = is_array( $promo['coupon'] ?? null ) ? $promo['coupon'] : array();
		if ( empty( $coupon ) || empty( $coupon['valid'] ) ) {
			return new \WP_REST_Response( array( 'error' => __( 'Promo code is not valid', 'cardology-reports' ) ), 400 );
		}
		if ( ! empty( $promo['expires_at'] ) && (int) $promo['expires_at'] < time() ) {
			return new \WP_REST_Response( array( 'error' => __( 'Promo code has expired', 'cardology-reports' ) ), 400 );
		}
		if ( ! empty( $promo['max_redemptions'] ) && (int) $promo['times_redeemed'] >= (int) $promo['max_redemptions'] ) {
			return new \WP_REST_Response( array( 'error' => __( 'Promo code has been fully redeemed', 'cardology-reports' ) ), 400 );
		}
		$final = $this->discount_cents( $coupon, (int) $report['price_cents'] );
		if ( $final >= 50 ) {
			return new \WP_REST_Response( array( 'error' => __( 'This code does not make the report free. Use checkout.', 'cardology-reports' ) ), 400 );
		}

		// Insert order.
		$session_id = 'free_' . wp_generate_uuid4();
		$order_id   = $this->orders->insert(
			array(
				'session_id'           => $session_id,
				'report_slug'          => $report['slug'],
				'amount_cents'         => 0,
				'currency'             => $this->stripe->currency(),
				'customer_name'        => $customer['name'],
				'customer_email'       => $customer['email'],
				'customer_birthdate'   => $customer['birthdate'],
				'customer_birth_time'  => $customer['birthTime'],
				'customer_birth_place' => $customer['birthPlace'],
				'partner_name'         => $partner['name']      ?? null,
				'partner_birthdate'    => $partner['birthdate'] ?? null,
				'partner_birth_time'   => $partner['birthTime'] ?? null,
				'partner_birth_place'  => $partner['birthPlace'] ?? null,
				'age'                  => $age,
				'status'               => 'paid',
			)
		);
		if ( ! $order_id ) {
			return new \WP_REST_Response( array( 'error' => __( 'Could not record the order', 'cardology-reports' ) ), 500 );
		}

		// Fire generate.
		$payload = $this->generate_payload( $report, $customer, $partner, $age );
		$job_id  = $this->report_writer->generate( $payload );
		if ( is_wp_error( $job_id ) ) {
			$this->orders->update_by_session( $session_id, array( 'status' => 'failed' ) );
			return new \WP_REST_Response( array( 'error' => $job_id->get_error_message() ), 500 );
		}
		$this->orders->update_by_session(
			$session_id,
			array(
				'job_id' => $job_id,
				'status' => 'processing',
			)
		);

		// Confirmation email (best-effort).
		$this->mailer->send_order_received(
			array(
				'customer_name'  => $customer['name'],
				'customer_email' => $customer['email'],
				'report_title'   => $report['title'],
				'amount_cents'   => 0,
			),
			$this->status_url( $session_id )
		);

		return new \WP_REST_Response( array( 'sessionId' => $session_id ), 200 );
	}

	public function status( \WP_REST_Request $req ): \WP_REST_Response {
		$session_id = sanitize_text_field( $req->get_param( 'sessionId' ) );
		$order      = $this->orders->find_by_session( $session_id );
		if ( ! $order ) {
			return new \WP_REST_Response(
				array(
					'status'      => 'pending',
					'reportSlug'  => null,
					'reportUrl'   => null,
					'emailSent'   => false,
					'completedAt' => null,
					'createdAt'   => current_time( 'mysql', true ),
					'message'     => __( 'Awaiting payment confirmation.', 'cardology-reports' ),
				),
				200
			);
		}
		if ( 'completed' === $order['status'] || 'failed' === $order['status'] ) {
			return new \WP_REST_Response( $this->shape_status( $order ), 200 );
		}
		if ( empty( $order['job_id'] ) ) {
			$payload                = $this->shape_status( $order );
			$payload['message']     = __( 'Preparing your report…', 'cardology-reports' );
			return new \WP_REST_Response( $payload, 200 );
		}

		$upstream = $this->report_writer->status( $order['job_id'] );
		if ( is_wp_error( $upstream ) ) {
			$payload            = $this->shape_status( $order );
			$payload['message'] = __( 'Status check temporarily unavailable.', 'cardology-reports' );
			return new \WP_REST_Response( $payload, 200 );
		}
		if ( ( $upstream['status'] ?? '' ) === 'completed' && ! empty( $upstream['reportUrl'] ) ) {
			$url = (string) $upstream['reportUrl'];
			$this->mark_completed( $order, $url );
			$order['status']         = 'completed';
			$order['report_url']     = $url;
			$order['completed_at']   = current_time( 'mysql', true );
			$order['email_sent_at']  = current_time( 'mysql', true );
			return new \WP_REST_Response( $this->shape_status( $order ), 200 );
		}
		if ( ( $upstream['status'] ?? '' ) === 'failed' ) {
			$this->orders->update_by_session( $order['session_id'], array( 'status' => 'failed' ) );
			$order['status'] = 'failed';
			return new \WP_REST_Response( $this->shape_status( $order ), 200 );
		}
		return new \WP_REST_Response( $this->shape_status( $order ), 200 );
	}

	private function mark_completed( array $order, string $report_url ): void {
		$report = $this->catalog->get( $order['report_slug'] );
		$title  = $report['title'] ?? 'Cardology Report';

		$email_sent = $this->mailer->send_report_ready(
			array(
				'customer_name'  => $order['customer_name'],
				'customer_email' => $order['customer_email'],
				'report_title'   => $title,
			),
			$report_url
		);

		$this->orders->update_by_session(
			$order['session_id'],
			array(
				'status'        => 'completed',
				'report_url'    => $report_url,
				'completed_at'  => current_time( 'mysql', true ),
				'email_sent_at' => $email_sent ? current_time( 'mysql', true ) : ( $order['email_sent_at'] ?? null ),
			)
		);
	}

	public function webhook( \WP_REST_Request $req ): \WP_REST_Response {
		$payload   = $req->get_body();
		$signature = $req->get_header( 'stripe-signature' ) ?? '';
		if ( ! $this->stripe->verify_webhook( $payload, $signature ) ) {
			return new \WP_REST_Response( array( 'error' => 'invalid signature' ), 400 );
		}
		$event = json_decode( $payload, true );
		if ( ! is_array( $event ) || ( $event['type'] ?? '' ) !== 'checkout.session.completed' ) {
			return new \WP_REST_Response( array( 'received' => true, 'ignored' => true ), 200 );
		}
		$session  = is_array( $event['data']['object'] ?? null ) ? $event['data']['object'] : array();
		$metadata = is_array( $session['metadata'] ?? null ) ? $session['metadata'] : array();
		$slug     = sanitize_text_field( $metadata['report_slug'] ?? '' );
		$report   = $this->catalog->get( $slug );
		if ( ! $report ) {
			return new \WP_REST_Response( array( 'received' => true, 'ignored' => 'not_a_report_order' ), 200 );
		}
		$session_id = sanitize_text_field( $session['id'] ?? '' );
		if ( '' === $session_id ) {
			return new \WP_REST_Response( array( 'error' => 'missing session id' ), 400 );
		}

		// Idempotency: if we already created the order for this session, exit.
		$existing = $this->orders->find_by_session( $session_id );
		if ( $existing && ! empty( $existing['job_id'] ) ) {
			return new \WP_REST_Response( array( 'received' => true, 'alreadyQueued' => true ), 200 );
		}

		$amount = (int) ( $session['amount_total'] ?? 0 );
		$age    = isset( $metadata['age'] ) ? (int) $metadata['age'] : null;

		if ( ! $existing ) {
			$this->orders->insert(
				array(
					'session_id'           => $session_id,
					'report_slug'          => $slug,
					'amount_cents'         => $amount,
					'currency'             => $this->stripe->currency(),
					'customer_name'        => sanitize_text_field( $metadata['customer_name'] ?? '' ),
					'customer_email'       => sanitize_email( $metadata['customer_email'] ?? ( $session['customer_details']['email'] ?? '' ) ),
					'customer_birthdate'   => sanitize_text_field( $metadata['customer_birthdate'] ?? '' ),
					'customer_birth_time'  => isset( $metadata['customer_birth_time'] ) ? sanitize_text_field( $metadata['customer_birth_time'] ) : null,
					'customer_birth_place' => isset( $metadata['customer_birth_place'] ) ? sanitize_text_field( $metadata['customer_birth_place'] ) : null,
					'partner_name'         => isset( $metadata['partner_name'] ) ? sanitize_text_field( $metadata['partner_name'] ) : null,
					'partner_birthdate'    => isset( $metadata['partner_birthdate'] ) ? sanitize_text_field( $metadata['partner_birthdate'] ) : null,
					'partner_birth_time'   => isset( $metadata['partner_birth_time'] ) ? sanitize_text_field( $metadata['partner_birth_time'] ) : null,
					'partner_birth_place'  => isset( $metadata['partner_birth_place'] ) ? sanitize_text_field( $metadata['partner_birth_place'] ) : null,
					'age'                  => $age,
					'status'               => 'paid',
				)
			);
		}

		$customer = array(
			'name'      => sanitize_text_field( $metadata['customer_name'] ?? '' ),
			'email'     => sanitize_email( $metadata['customer_email'] ?? '' ),
			'birthdate' => sanitize_text_field( $metadata['customer_birthdate'] ?? '' ),
			'birthTime' => isset( $metadata['customer_birth_time'] ) ? sanitize_text_field( $metadata['customer_birth_time'] ) : null,
			'birthPlace' => isset( $metadata['customer_birth_place'] ) ? sanitize_text_field( $metadata['customer_birth_place'] ) : null,
		);
		$partner = null;
		if ( ! empty( $metadata['partner_name'] ) && ! empty( $metadata['partner_birthdate'] ) ) {
			$partner = array(
				'name'       => sanitize_text_field( $metadata['partner_name'] ),
				'birthdate'  => sanitize_text_field( $metadata['partner_birthdate'] ),
				'birthTime'  => isset( $metadata['partner_birth_time'] ) ? sanitize_text_field( $metadata['partner_birth_time'] ) : null,
				'birthPlace' => isset( $metadata['partner_birth_place'] ) ? sanitize_text_field( $metadata['partner_birth_place'] ) : null,
			);
		}
		$payload = $this->generate_payload( $report, $customer, $partner, $age );
		$job_id  = $this->report_writer->generate( $payload );
		if ( is_wp_error( $job_id ) ) {
			$this->orders->update_by_session( $session_id, array( 'status' => 'failed' ) );
			return new \WP_REST_Response( array( 'received' => true, 'generateFailed' => true ), 200 );
		}
		$this->orders->update_by_session(
			$session_id,
			array(
				'job_id' => $job_id,
				'status' => 'processing',
			)
		);

		// Order-received email.
		$this->mailer->send_order_received(
			array(
				'customer_name'  => $customer['name'],
				'customer_email' => $customer['email'],
				'report_title'   => $report['title'],
				'amount_cents'   => $amount,
			),
			$this->status_url( $session_id )
		);

		return new \WP_REST_Response( array( 'received' => true, 'jobId' => $job_id ), 200 );
	}
}
