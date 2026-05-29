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

		<div class="crwp-status__card">
			<div class="crwp-status__icon crwp-status__icon--warning" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
					<line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
				</svg>
			</div>
			<h2><?php echo esc_html__( 'Missing checkout session', 'cardology-reports' ); ?></h2>
			<p><?php echo esc_html__( 'We could not find an active order on this URL. If you just completed a purchase, check your email for the link.', 'cardology-reports' ); ?></p>
		</div>

	<?php else : ?>

		<!-- ============ Processing state (default) ============ -->
		<div class="crwp-status__card" data-crwp-status-loading>
			<div class="crwp-status__icon crwp-status__icon--pulse" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M12 2L9 9l-7 1 5 5-1 7 6-3 6 3-1-7 5-5-7-1z"/>
				</svg>
			</div>
			<div class="crwp-status__pill" data-crwp-status-pill>
				<span class="crwp-status__dot"></span>
				<span data-crwp-status-label><?php echo esc_html__( 'Generating your report…', 'cardology-reports' ); ?></span>
			</div>

			<h2><?php echo esc_html__( 'Payment received — crafting your reading', 'cardology-reports' ); ?></h2>
			<p class="crwp-status__lede">
				<?php echo esc_html__( 'Your personalized Cardology reading is being written specifically for you. This typically takes 10–15 minutes.', 'cardology-reports' ); ?>
			</p>

			<div class="crwp-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100">
				<div class="crwp-progress__bar" data-crwp-progress-bar></div>
				<div class="crwp-progress__shine"></div>
			</div>
			<div class="crwp-status__meta">
				<span data-crwp-progress-pct>8%</span>
				<span class="crwp-status__sep">·</span>
				<span data-crwp-progress-eta><?php echo esc_html__( 'Estimating…', 'cardology-reports' ); ?></span>
			</div>

			<ol class="crwp-status__steps">
				<li class="is-done">
					<span class="crwp-status__step-dot" aria-hidden="true"></span>
					<span class="crwp-status__step-label"><?php echo esc_html__( 'Payment confirmed', 'cardology-reports' ); ?></span>
				</li>
				<li class="is-active" data-crwp-step="generating">
					<span class="crwp-status__step-dot" aria-hidden="true"></span>
					<span class="crwp-status__step-label"><?php echo esc_html__( 'Generating your reading', 'cardology-reports' ); ?></span>
				</li>
				<li data-crwp-step="delivered">
					<span class="crwp-status__step-dot" aria-hidden="true"></span>
					<span class="crwp-status__step-label"><?php echo esc_html__( 'Delivered to your inbox', 'cardology-reports' ); ?></span>
				</li>
			</ol>

			<div class="crwp-status__panel" data-crwp-order-panel hidden>
				<h3><?php echo esc_html__( 'Order details', 'cardology-reports' ); ?></h3>
				<dl>
					<dt><?php echo esc_html__( 'Report', 'cardology-reports' ); ?></dt>
					<dd data-crwp-order-title>—</dd>
					<dt><?php echo esc_html__( 'Order', 'cardology-reports' ); ?></dt>
					<dd><code data-crwp-order-id>—</code></dd>
				</dl>
			</div>

			<div class="crwp-status__tips">
				<div class="crwp-status__tip">
					<span aria-hidden="true">✉️</span>
					<span><?php echo esc_html__( "We'll email you the moment it's ready — no need to wait here.", 'cardology-reports' ); ?></span>
				</div>
				<div class="crwp-status__tip">
					<span aria-hidden="true">🔖</span>
					<span><?php echo esc_html__( 'Bookmark this page — the link in your email works too.', 'cardology-reports' ); ?></span>
				</div>
			</div>
		</div>

		<!-- ============ Ready state ============ -->
		<div class="crwp-status__card crwp-status__card--ready" hidden data-crwp-status-ready>
			<div class="crwp-status__icon crwp-status__icon--success" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
					<polyline points="22 4 12 14.01 9 11.01"/>
				</svg>
			</div>
			<div class="crwp-status__pill crwp-status__pill--success">
				<span class="crwp-status__dot crwp-status__dot--success"></span>
				<?php echo esc_html__( 'Ready', 'cardology-reports' ); ?>
			</div>
			<h2><?php echo esc_html__( 'Your report is ready', 'cardology-reports' ); ?></h2>
			<p class="crwp-status__lede" data-crwp-ready-message>
				<?php echo esc_html__( 'Your personalized reading is complete. We also sent the link to your email.', 'cardology-reports' ); ?>
			</p>
			<a class="crwp-btn crwp-btn--primary crwp-btn--xl" data-crwp-status-link target="_blank" rel="noopener">
				<?php echo esc_html__( 'Open your report', 'cardology-reports' ); ?>
				<span class="crwp-btn__arrow" aria-hidden="true">→</span>
			</a>
			<p class="crwp-status__small">
				<?php echo esc_html__( 'The link is permanent — save this page or the email if you want to revisit your reading.', 'cardology-reports' ); ?>
			</p>
		</div>

		<!-- ============ Failed state ============ -->
		<div class="crwp-status__card crwp-status__card--failed" hidden data-crwp-status-failed>
			<div class="crwp-status__icon crwp-status__icon--warning" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
					<circle cx="12" cy="12" r="10"/>
					<line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
				</svg>
			</div>
			<h2><?php echo esc_html__( 'Something went wrong', 'cardology-reports' ); ?></h2>
			<p data-crwp-status-error>
				<?php echo esc_html__( "We couldn't generate your report automatically. Your payment is safe.", 'cardology-reports' ); ?>
			</p>
			<a class="crwp-btn crwp-btn--primary" href="mailto:<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
				<?php echo esc_html__( 'Contact support', 'cardology-reports' ); ?>
			</a>
		</div>

	<?php endif; ?>
</div>
