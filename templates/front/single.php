<?php
/**
 * @var array<string,mixed> $report
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="crwp-single" data-slug="<?php echo esc_attr( $report['slug'] ); ?>">
	<header class="crwp-single__hero">
		<h2><?php echo esc_html( $report['title'] ); ?></h2>
		<p class="crwp-single__desc"><?php echo wp_kses_post( $report['description'] ); ?></p>
		<div class="crwp-single__price">$<?php echo esc_html( number_format( $report['price_cents'] / 100, 2 ) ); ?></div>
	</header>

	<form class="crwp-form" data-crwp-form data-slug="<?php echo esc_attr( $report['slug'] ); ?>">
		<div class="crwp-form__row">
			<label>
				<?php echo esc_html__( 'Full Name', 'cardology-reports' ); ?>
				<input type="text" name="name" required />
			</label>
			<label>
				<?php echo esc_html__( 'Email Address', 'cardology-reports' ); ?>
				<input type="email" name="email" required />
			</label>
		</div>

		<fieldset class="crwp-form__date">
			<legend><?php echo esc_html__( 'Birth Date', 'cardology-reports' ); ?></legend>
			<input type="number" name="month" min="1" max="12" placeholder="MM" required />
			<input type="number" name="day" min="1" max="31" placeholder="DD" required />
			<input type="number" name="year" min="1900" placeholder="YYYY" required />
		</fieldset>

		<?php if ( $report['requires_birth_time_and_place'] ) : ?>
		<div class="crwp-form__row">
			<label>
				<?php echo esc_html__( 'Birth Time (required)', 'cardology-reports' ); ?>
				<input type="time" name="birthTime" required />
			</label>
			<label>
				<?php echo esc_html__( 'Birth Place (required)', 'cardology-reports' ); ?>
				<input type="text" name="birthPlace" required placeholder="<?php echo esc_attr__( 'City, State, Country', 'cardology-reports' ); ?>" />
			</label>
		</div>
		<?php endif; ?>

		<?php if ( $report['supports_age'] ) : ?>
		<label class="crwp-form__age">
			<?php echo esc_html__( 'Age the report is for (optional)', 'cardology-reports' ); ?>
			<input type="number" name="age" min="1" max="120" />
		</label>
		<?php endif; ?>

		<?php if ( $report['requires_partner'] ) : ?>
		<fieldset class="crwp-form__partner">
			<legend><?php echo esc_html__( 'Partner Information', 'cardology-reports' ); ?></legend>
			<label><?php echo esc_html__( "Partner's Name", 'cardology-reports' ); ?>
				<input type="text" name="partnerName" required />
			</label>
			<div class="crwp-form__date">
				<input type="number" name="partnerMonth" min="1" max="12" placeholder="MM" required />
				<input type="number" name="partnerDay"   min="1" max="31" placeholder="DD" required />
				<input type="number" name="partnerYear"  min="1900"       placeholder="YYYY" required />
			</div>
		</fieldset>
		<?php endif; ?>

		<div class="crwp-form__promo" data-crwp-promo></div>

		<div class="crwp-form__error" data-crwp-error hidden></div>

		<button type="submit" class="crwp-btn crwp-btn--gold crwp-btn--block" data-crwp-submit>
			<?php
			printf(
				/* translators: %s formatted price */
				esc_html__( 'Continue to Checkout — %s', 'cardology-reports' ),
				esc_html( '$' . number_format( $report['price_cents'] / 100, 2 ) )
			);
			?>
		</button>

		<p class="crwp-form__small">
			<?php echo esc_html__( 'Secure checkout by Stripe. Your report is generated automatically and emailed to you when ready (typically 10–15 min).', 'cardology-reports' ); ?>
		</p>
	</form>
</div>
