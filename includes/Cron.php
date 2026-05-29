<?php
namespace CRWP;

defined( 'ABSPATH' ) || exit;

/**
 * Periodic poller that catches reports that finished while the customer's tab
 * was closed.
 */
final class Cron {

	private Orders $orders;
	private Report_Writer_Client $report_writer;
	private Mailer $mailer;
	private Catalog $catalog;

	public function __construct( Orders $orders, Report_Writer_Client $report_writer, Mailer $mailer, Catalog $catalog ) {
		$this->orders        = $orders;
		$this->report_writer = $report_writer;
		$this->mailer        = $mailer;
		$this->catalog       = $catalog;
	}

	public function register_hooks(): void {
		add_action( 'crwp_poll_pending_reports', array( $this, 'tick' ) );
	}

	public function tick(): void {
		$rows = $this->orders->find_in_flight( 25 );
		foreach ( $rows as $order ) {
			if ( empty( $order['job_id'] ) ) {
				continue;
			}
			$upstream = $this->report_writer->status( $order['job_id'] );
			if ( is_wp_error( $upstream ) ) {
				continue;
			}
			if ( ( $upstream['status'] ?? '' ) === 'completed' && ! empty( $upstream['reportUrl'] ) ) {
				$url    = (string) $upstream['reportUrl'];
				$report = $this->catalog->get( $order['report_slug'] );
				$email_sent = empty( $order['email_sent_at'] )
					? $this->mailer->send_report_ready(
						array(
							'customer_name'  => $order['customer_name'],
							'customer_email' => $order['customer_email'],
							'report_title'   => $report['title'] ?? 'Cardology Report',
						),
						$url
					)
					: true;
				$this->orders->update_by_session(
					$order['session_id'],
					array(
						'status'        => 'completed',
						'report_url'    => $url,
						'completed_at'  => current_time( 'mysql', true ),
						'email_sent_at' => $email_sent ? current_time( 'mysql', true ) : ( $order['email_sent_at'] ?? null ),
					)
				);
			} elseif ( ( $upstream['status'] ?? '' ) === 'failed' ) {
				$this->orders->update_by_session( $order['session_id'], array( 'status' => 'failed' ) );
			}
		}
	}
}
