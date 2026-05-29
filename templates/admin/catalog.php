<?php
/**
 * @var array<string,array<string,mixed>> $reports
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap crwp-admin">
	<h1><?php echo esc_html__( 'Reports Catalog', 'cardology-reports' ); ?></h1>
	<p><?php echo esc_html__( 'Edit the customer-facing name, description, and price for each report. Slugs and required fields are fixed by the upstream Report Writer API and cannot be changed here.', 'cardology-reports' ); ?></p>

	<form method="post" action="">
		<?php wp_nonce_field( 'crwp_catalog_save', 'crwp_catalog_nonce' ); ?>
		<input type="hidden" name="crwp_catalog_save" value="1" />

		<?php foreach ( $reports as $slug => $report ) : ?>
			<div class="crwp-admin__panel <?php echo empty( $report['enabled'] ) ? 'crwp-admin__panel--disabled' : ''; ?>">
				<h2>
					<?php echo esc_html( $report['title'] ); ?>
					<small style="opacity:0.6;font-weight:normal;">(<?php echo esc_html( $slug ); ?>)</small>
					<?php if ( empty( $report['enabled'] ) ) : ?>
						<span class="crwp-pill crwp-pill--off"><?php echo esc_html__( 'Disabled', 'cardology-reports' ); ?></span>
					<?php endif; ?>
				</h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label><?php echo esc_html__( 'Status', 'cardology-reports' ); ?></label></th>
						<td>
							<label class="crwp-switch">
								<input type="checkbox" name="crwp_catalog[<?php echo esc_attr( $slug ); ?>][enabled]" value="1" <?php checked( ! empty( $report['enabled'] ) ); ?> />
								<?php echo esc_html__( 'Enabled — customers can see and purchase this report.', 'cardology-reports' ); ?>
							</label>
							<p class="description">
								<?php echo esc_html__( 'Uncheck to hide this report from the catalog and reject in-flight purchase attempts at this slug.', 'cardology-reports' ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php echo esc_html__( 'Title', 'cardology-reports' ); ?></label></th>
						<td><input class="regular-text" type="text" name="crwp_catalog[<?php echo esc_attr( $slug ); ?>][title]" value="<?php echo esc_attr( $report['title'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label><?php echo esc_html__( 'Short Description', 'cardology-reports' ); ?></label></th>
						<td><input class="large-text" type="text" name="crwp_catalog[<?php echo esc_attr( $slug ); ?>][short_description]" value="<?php echo esc_attr( $report['short_description'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label><?php echo esc_html__( 'Long Description', 'cardology-reports' ); ?></label></th>
						<td><textarea class="large-text" rows="4" name="crwp_catalog[<?php echo esc_attr( $slug ); ?>][description]"><?php echo esc_textarea( $report['description'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label><?php echo esc_html__( 'Price (USD cents)', 'cardology-reports' ); ?></label></th>
						<td>
							<input type="number" min="50" step="1" name="crwp_catalog[<?php echo esc_attr( $slug ); ?>][price_cents]" value="<?php echo esc_attr( (int) $report['price_cents'] ); ?>" />
							<p class="description">
								<?php
								printf(
									/* translators: %s formatted price */
									esc_html__( 'Currently %s. Stored as cents (e.g. 2500 = $25.00). Stripe’s minimum is 50 cents.', 'cardology-reports' ),
									'<strong>$' . esc_html( number_format( $report['price_cents'] / 100, 2 ) ) . '</strong>'
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Required Fields', 'cardology-reports' ); ?></th>
						<td>
							<?php if ( $report['requires_partner'] ) : ?>
								<span class="crwp-pill"><?php echo esc_html__( 'Partner info', 'cardology-reports' ); ?></span>
							<?php endif; ?>
							<?php if ( $report['requires_birth_time_and_place'] ) : ?>
								<span class="crwp-pill"><?php echo esc_html__( 'Birth time + place', 'cardology-reports' ); ?></span>
							<?php endif; ?>
							<?php if ( $report['supports_age'] ) : ?>
								<span class="crwp-pill"><?php echo esc_html__( 'Age-targeted', 'cardology-reports' ); ?></span>
							<?php endif; ?>
							<?php if ( ! $report['requires_partner'] && ! $report['requires_birth_time_and_place'] && ! $report['supports_age'] ) : ?>
								<em><?php echo esc_html__( 'None — base birth info only.', 'cardology-reports' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
				</table>
			</div>
		<?php endforeach; ?>

		<?php submit_button( __( 'Save Catalog', 'cardology-reports' ) ); ?>
	</form>
</div>
