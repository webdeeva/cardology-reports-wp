<?php
/**
 * @var array<string,array<string,mixed>> $reports
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="crwp-catalog">
	<h2 class="crwp-catalog__title"><?php echo esc_html__( 'Personalized Cardology Reports', 'cardology-reports' ); ?></h2>
	<p class="crwp-catalog__lede">
		<?php echo esc_html__( 'Choose a report. Share your birth details. Receive a deep personalized reading by email and on this site.', 'cardology-reports' ); ?>
	</p>

	<?php
	/**
	 * Format cents as a price, dropping the decimals on whole-dollar amounts.
	 *
	 * @param int $cents Amount in cents.
	 * @return string Formatted amount without the currency symbol.
	 */
	$crwp_money = static function ( $cents ) {
		return 0 === $cents % 100
			? number_format( $cents / 100, 0 )
			: number_format( $cents / 100, 2 );
	};
	?>
	<div class="crwp-grid">
		<?php
		foreach ( $reports as $report ) :
			$regular_cents   = (int) $report['price_cents'];
			$on_sale         = ! empty( $report['on_sale'] );
			$effective_cents = $on_sale ? (int) $report['sale_price_cents'] : $regular_cents;
			?>
			<article class="crwp-card" data-slug="<?php echo esc_attr( $report['slug'] ); ?>">
				<div class="crwp-card__frame">
					<div class="crwp-card__inner">
						<span class="crwp-card__hairline" aria-hidden="true"></span>

						<?php foreach ( array( 'tl', 'tr', 'bl', 'br' ) as $corner ) : ?>
							<span class="crwp-card__corner crwp-card__corner--<?php echo esc_attr( $corner ); ?>" aria-hidden="true">
								<svg width="22" height="22" viewBox="0 0 22 22" fill="none" focusable="false">
									<path d="M2 2L8 2M2 2L2 8M2 2L7 7" stroke="currentColor" stroke-width="1" stroke-linecap="round" />
								</svg>
							</span>
						<?php endforeach; ?>

						<div class="crwp-card__body">
							<div class="crwp-card__emblem" aria-hidden="true">
								<span class="crwp-card__halo"></span>
								<svg class="crwp-card__doc" viewBox="0 0 48 48" fill="none" focusable="false">
									<rect x="13" y="9" width="22" height="28" rx="2.5" fill="currentColor" opacity="0.12" />
									<path d="M16 13.5A2.5 2.5 0 0 1 18.5 11H27l8 8v16.5a2.5 2.5 0 0 1-2.5 2.5h-14A2.5 2.5 0 0 1 16 35.5z" fill="var(--crwp-card-bg)" stroke="currentColor" stroke-width="1.6" />
									<path d="M27 11v6.5A1.5 1.5 0 0 0 28.5 19H35" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
									<path d="M20 25h9M20 29h9M20 33h6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
								</svg>
							</div>

							<div class="crwp-card__divider" aria-hidden="true">
								<span class="crwp-card__divider-line"></span>
								<span class="crwp-card__divider-star">&#10022;</span>
								<span class="crwp-card__divider-gem"></span>
								<span class="crwp-card__divider-star">&#10022;</span>
								<span class="crwp-card__divider-line"></span>
							</div>

							<h3 class="crwp-card__title"><?php echo esc_html( $report['title'] ); ?></h3>
							<p class="crwp-card__kicker"><?php echo esc_html__( 'A Cardology Reading', 'cardology-reports' ); ?></p>
							<p class="crwp-card__desc"><?php echo esc_html( $report['short_description'] ); ?></p>

							<div class="crwp-card__tags">
								<?php if ( $report['requires_partner'] ) : ?>
									<span class="crwp-tag"><?php echo esc_html__( 'Partner info', 'cardology-reports' ); ?></span>
								<?php endif; ?>
								<?php if ( $report['requires_birth_time_and_place'] ) : ?>
									<span class="crwp-tag"><?php echo esc_html__( 'Birth time + place', 'cardology-reports' ); ?></span>
								<?php endif; ?>
								<?php if ( $report['supports_age'] ) : ?>
									<span class="crwp-tag"><?php echo esc_html__( 'Age-targeted', 'cardology-reports' ); ?></span>
								<?php endif; ?>
							</div>

							<div class="crwp-card__footer">
								<div class="crwp-card__price">
									<?php if ( $on_sale ) : ?>
										<span class="crwp-card__price-strike">$<?php echo esc_html( $crwp_money( $regular_cents ) ); ?></span>
									<?php endif; ?>
									<span class="crwp-card__seal<?php echo $on_sale ? ' crwp-card__seal--sale' : ''; ?>">
										<span class="crwp-card__seal-cur">$</span><span class="crwp-card__seal-amt"><?php echo esc_html( $crwp_money( $effective_cents ) ); ?></span>
									</span>
									<?php if ( $on_sale ) : ?>
										<span class="crwp-card__sale-badge"><?php echo esc_html__( 'SALE', 'cardology-reports' ); ?></span>
									<?php endif; ?>
								</div>
								<button type="button" class="crwp-btn crwp-btn--cover" data-crwp-open-form data-slug="<?php echo esc_attr( $report['slug'] ); ?>">
									<?php echo esc_html__( 'Order this report', 'cardology-reports' ); ?>
									<span class="crwp-btn__arrow" aria-hidden="true">&rarr;</span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="crwp-form-host" data-crwp-form-host>
		<?php foreach ( $reports as $slug => $report ) : ?>
			<div class="crwp-form-wrapper" data-crwp-form-wrapper="<?php echo esc_attr( $slug ); ?>" hidden>
				<?php include CRWP_PLUGIN_DIR . 'templates/front/single.php'; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
