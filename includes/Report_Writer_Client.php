<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * HTTP client for the upstream Report Writer API.
 *
 * Generation is async — POST /reports/generate returns a jobId and the report
 * URL becomes available on GET /reports/status/{jobId} ~10 minutes later.
 */
final class Report_Writer_Client {

	public const SETTINGS_KEY = 'crwp_report_api';

	public function settings(): array {
		$defaults = array(
			'api_key' => '',
			'api_url' => 'https://report-writer-qt7cc.ondigitalocean.app/api/v1',
		);
		$saved    = get_option( self::SETTINGS_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( $defaults, $saved );
	}

	public function api_key(): string {
		return (string) $this->settings()['api_key'];
	}

	public function api_url(): string {
		return untrailingslashit( (string) $this->settings()['api_url'] );
	}

	/**
	 * Kick off generation. Returns the upstream jobId.
	 *
	 * @param array<string,mixed> $payload
	 * @return string|\WP_Error
	 */
	public function generate( array $payload ) {
		$key = $this->api_key();
		if ( '' === $key ) {
			return new \WP_Error( 'crwp_no_report_key', __( 'Report Writer API key is not configured.', 'cardology-reports' ) );
		}
		$res = wp_remote_post(
			$this->api_url() . '/reports/generate',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $json ) || empty( $json['jobId'] ) ) {
			$msg = is_array( $json ) && isset( $json['message'] ) ? (string) $json['message'] : (string) wp_remote_retrieve_body( $res );
			return new \WP_Error( 'crwp_generate_failed', $msg ?: __( 'Report Writer rejected the request.', 'cardology-reports' ) );
		}
		return (string) $json['jobId'];
	}

	/**
	 * Polls status. Returns ['status' => ..., 'reportUrl' => ...|null].
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function status( string $job_id ) {
		$key = $this->api_key();
		if ( '' === $key ) {
			return new \WP_Error( 'crwp_no_report_key', __( 'Report Writer API key is not configured.', 'cardology-reports' ) );
		}
		$res = wp_remote_get(
			$this->api_url() . '/reports/status/' . rawurlencode( $job_id ),
			array(
				'headers' => array( 'Authorization' => 'Bearer ' . $key ),
				'timeout' => 20,
			)
		);
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $json ) ) {
			return new \WP_Error( 'crwp_status_failed', (string) wp_remote_retrieve_body( $res ) );
		}
		// Normalize upstream URL.
		if ( ! empty( $json['reportUrl'] ) && is_string( $json['reportUrl'] ) ) {
			$json['reportUrl'] = self::normalize_url( $json['reportUrl'] );
		}
		return $json;
	}

	public static function normalize_url( string $url ): string {
		$url = preg_replace( '#https?://localhost(:\d+)?#', 'https://report-writer-qt7cc.ondigitalocean.app', $url );
		$url = str_replace( 'report-writer-qt7cc.ondigitalocean.app:8080', 'report-writer-qt7cc.ondigitalocean.app', (string) $url );
		return (string) $url;
	}
}
