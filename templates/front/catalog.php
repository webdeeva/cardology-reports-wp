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

	<div class="crwp-grid">
		<?php foreach ( $reports as $report ) : ?>
			<article class="crwp-card" data-slug="<?php echo esc_attr( $report['slug'] ); ?>">
				<h3 class="crwp-card__title"><?php echo esc_html( $report['title'] ); ?></h3>
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
					<?php if ( ! empty( $report['on_sale'] ) ) : ?>
						<span class="crwp-price">
							<span class="crwp-price__strike">$<?php echo esc_html( number_format( $report['price_cents'] / 100, 2 ) ); ?></span>
							<span class="crwp-price__sale">$<?php echo esc_html( number_format( $report['sale_price_cents'] / 100, 2 ) ); ?></span>
							<span class="crwp-price__badge"><?php echo esc_html__( 'SALE', 'cardology-reports' ); ?></span>
						</span>
					<?php else : ?>
						<span class="crwp-price">$<?php echo esc_html( number_format( $report['price_cents'] / 100, 2 ) ); ?></span>
					<?php endif; ?>
					<button type="button" class="crwp-btn crwp-btn--gold" data-crwp-open-form data-slug="<?php echo esc_attr( $report['slug'] ); ?>">
						<?php echo esc_html__( 'Order this report', 'cardology-reports' ); ?>
					</button>
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
