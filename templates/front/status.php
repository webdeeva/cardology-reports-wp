<?php
/**
 * @var string $session_id
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="crwp-status" data-crwp-status data-session-id="<?php echo esc_attr( $session_id ); ?>">
	<?php if ( '' === $session_id ) : ?>
		<h2><?php echo esc_html__( 'Missing checkout session', 'cardology-reports' ); ?></h2>
		<p><?php echo esc_html__( 'We could not find an active order on this URL. If you just completed a purchase, check your email for the link.', 'cardology-reports' ); ?></p>
	<?php else : ?>
		<div data-crwp-status-loading>
			<h2><?php echo esc_html__( 'Payment received — generating your report', 'cardology-reports' ); ?></h2>
			<p><?php echo esc_html__( 'Your report is being written specifically for you. This typically takes 10–15 minutes.', 'cardology-reports' ); ?></p>
			<div class="crwp-progress"><div class="crwp-progress__bar" data-crwp-progress-bar></div></div>
			<p class="crwp-status__note"><?php echo esc_html__( "You'll get an email when it's ready. You can also leave this page open — it will update automatically.", 'cardology-reports' ); ?></p>
		</div>
		<div hidden data-crwp-status-ready>
			<h2><?php echo esc_html__( 'Your report is ready', 'cardology-reports' ); ?></h2>
			<a class="crwp-btn crwp-btn--gold" data-crwp-status-link target="_blank" rel="noopener"><?php echo esc_html__( 'Open your report', 'cardology-reports' ); ?></a>
		</div>
		<div hidden data-crwp-status-failed>
			<h2><?php echo esc_html__( 'Something went wrong', 'cardology-reports' ); ?></h2>
			<p data-crwp-status-error></p>
		</div>
	<?php endif; ?>
</div>
