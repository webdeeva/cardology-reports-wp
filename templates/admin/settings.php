<?php
/**
 * @var array $stripe
 * @var array $report
 * @var array $email
 * @var array $pages
 * @var array $advanced
 * @var string $webhook_url
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;

$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'stripe';
$tabs        = array(
	'stripe'     => __( 'Stripe', 'cardology-reports' ),
	'api'        => __( 'Report Writer API', 'cardology-reports' ),
	'email'      => __( 'Email', 'cardology-reports' ),
	'appearance' => __( 'Appearance', 'cardology-reports' ),
	'pages'      => __( 'Pages', 'cardology-reports' ),
	'adv'        => __( 'Advanced', 'cardology-reports' ),
);
?>
<div class="wrap crwp-admin">
	<h1><?php echo esc_html__( 'Cardology Reports — Settings', 'cardology-reports' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<?php foreach ( $tabs as $slug => $label ) : ?>
			<a class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'cardology-reports-settings', 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'stripe' === $current_tab ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'crwp_stripe' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php echo esc_html__( 'Mode', 'cardology-reports' ); ?></th>
					<td>
						<label><input type="radio" name="crwp_stripe_keys[mode]" value="test" <?php checked( $stripe['mode'], 'test' ); ?> /> <?php echo esc_html__( 'Test', 'cardology-reports' ); ?></label>
						&nbsp;&nbsp;
						<label><input type="radio" name="crwp_stripe_keys[mode]" value="live" <?php checked( $stripe['mode'], 'live' ); ?> /> <?php echo esc_html__( 'Live', 'cardology-reports' ); ?></label>
					</td>
				</tr>
				<tr><th scope="row"><?php echo esc_html__( 'Test publishable key', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="text" name="crwp_stripe_keys[test_publishable_key]" value="<?php echo esc_attr( $stripe['test_publishable_key'] ); ?>" placeholder="pk_test_..." /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Test secret key', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="password" name="crwp_stripe_keys[test_secret_key]" value="<?php echo esc_attr( $stripe['test_secret_key'] ); ?>" placeholder="sk_test_..." /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Test webhook signing secret', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="password" name="crwp_stripe_keys[test_webhook_secret]" value="<?php echo esc_attr( $stripe['test_webhook_secret'] ); ?>" placeholder="whsec_..." /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Live publishable key', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="text" name="crwp_stripe_keys[live_publishable_key]" value="<?php echo esc_attr( $stripe['live_publishable_key'] ); ?>" placeholder="pk_live_..." /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Live secret key', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="password" name="crwp_stripe_keys[live_secret_key]" value="<?php echo esc_attr( $stripe['live_secret_key'] ); ?>" placeholder="sk_live_..." /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Live webhook signing secret', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="password" name="crwp_stripe_keys[live_webhook_secret]" value="<?php echo esc_attr( $stripe['live_webhook_secret'] ); ?>" placeholder="whsec_..." /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Currency', 'cardology-reports' ); ?></th>
					<td><input type="text" name="crwp_stripe_keys[currency]" value="<?php echo esc_attr( $stripe['currency'] ); ?>" maxlength="3" style="width:6em;text-transform:lowercase;" />
						<p class="description"><?php echo esc_html__( 'Three-letter ISO code (lowercase). Default: usd.', 'cardology-reports' ); ?></p></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Webhook URL to add in Stripe', 'cardology-reports' ); ?></th>
					<td><input readonly class="large-text" value="<?php echo esc_attr( $webhook_url ); ?>" onclick="this.select()" />
						<p class="description"><?php echo esc_html__( 'In Stripe Dashboard → Developers → Webhooks → Add endpoint. Subscribe to checkout.session.completed.', 'cardology-reports' ); ?></p></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>

	<?php elseif ( 'api' === $current_tab ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'crwp_report_api' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php echo esc_html__( 'Report Writer API key', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="password" name="crwp_report_api[api_key]" value="<?php echo esc_attr( $report['api_key'] ); ?>" placeholder="rw_live_..." /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Report Writer API URL', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="url" name="crwp_report_api[api_url]" value="<?php echo esc_attr( $report['api_url'] ); ?>" /></td></tr>
			</table>
			<?php submit_button(); ?>
		</form>

	<?php elseif ( 'email' === $current_tab ) : ?>
		<?php
		$host          = \CRWP\Mailer::site_host();
		$admin_email   = (string) get_option( 'admin_email' );
		$noreply_addr  = 'noreply@' . $host;
		$default_addr  = 'wordpress@' . $host;
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'crwp_email' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php echo esc_html__( 'From name', 'cardology-reports' ); ?></th>
					<td>
						<input class="regular-text" type="text" name="crwp_email_settings[from_name]" value="<?php echo esc_attr( $email['from_name'] ); ?>" />
						<p class="description">
							<?php
							/* translators: %s site name */
							printf( esc_html__( 'Defaults to your WordPress site name (%s).', 'cardology-reports' ), '<code>' . esc_html( get_bloginfo( 'name' ) ) . '</code>' );
							?>
						</p>
					</td>
				</tr>
				<tr><th scope="row"><?php echo esc_html__( 'From address', 'cardology-reports' ); ?></th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:0.4rem;">
								<input type="radio" name="crwp_email_settings[from_email_mode]" value="<?php echo esc_attr( \CRWP\Mailer::FROM_MODE_NOREPLY ); ?>" <?php checked( $email['from_email_mode'], \CRWP\Mailer::FROM_MODE_NOREPLY ); ?> />
								<?php echo esc_html__( 'No-reply', 'cardology-reports' ); ?>
								<code><?php echo esc_html( $noreply_addr ); ?></code>
								<span class="description"><?php echo esc_html__( '— recommended for transactional emails', 'cardology-reports' ); ?></span>
							</label>
							<label style="display:block;margin-bottom:0.4rem;">
								<input type="radio" name="crwp_email_settings[from_email_mode]" value="<?php echo esc_attr( \CRWP\Mailer::FROM_MODE_SITE_DEFAULT ); ?>" <?php checked( $email['from_email_mode'], \CRWP\Mailer::FROM_MODE_SITE_DEFAULT ); ?> />
								<?php echo esc_html__( 'WordPress default', 'cardology-reports' ); ?>
								<code><?php echo esc_html( $default_addr ); ?></code>
							</label>
							<label style="display:block;margin-bottom:0.4rem;">
								<input type="radio" name="crwp_email_settings[from_email_mode]" value="<?php echo esc_attr( \CRWP\Mailer::FROM_MODE_ADMIN ); ?>" <?php checked( $email['from_email_mode'], \CRWP\Mailer::FROM_MODE_ADMIN ); ?> />
								<?php echo esc_html__( 'Site admin email', 'cardology-reports' ); ?>
								<code><?php echo esc_html( $admin_email ); ?></code>
							</label>
							<label style="display:block;margin-bottom:0.4rem;">
								<input type="radio" name="crwp_email_settings[from_email_mode]" value="<?php echo esc_attr( \CRWP\Mailer::FROM_MODE_CUSTOM ); ?>" <?php checked( $email['from_email_mode'], \CRWP\Mailer::FROM_MODE_CUSTOM ); ?> />
								<?php echo esc_html__( 'Custom address', 'cardology-reports' ); ?>
								<input class="regular-text" type="email" name="crwp_email_settings[from_email]" value="<?php echo esc_attr( $email['from_email'] ); ?>" placeholder="hello@yourdomain.com" style="margin-left:0.5rem;" />
							</label>
						</fieldset>
					</td>
				</tr>
				<tr><th scope="row"><?php echo esc_html__( 'Sale notifications', 'cardology-reports' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:0.6rem;">
							<input type="checkbox" name="crwp_email_settings[notify_owner]" value="1" <?php checked( ! empty( $email['notify_owner'] ) ); ?> />
							<?php echo esc_html__( 'Email me whenever a report is sold (customer name, email, and report).', 'cardology-reports' ); ?>
						</label>
						<input class="regular-text" type="email" name="crwp_email_settings[notify_email]" value="<?php echo esc_attr( $email['notify_email'] ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
						<p class="description">
							<?php
							/* translators: %s admin email address */
							printf( esc_html__( 'Where to send sale notifications. Leave blank to use the site admin email (%s).', 'cardology-reports' ), '<code>' . esc_html( get_option( 'admin_email' ) ) . '</code>' );
							?>
						</p>
					</td>
				</tr>
			</table>
			<p class="description">
				<?php echo esc_html__( 'Emails are sent via wp_mail(). The styled header uses your selected appearance theme automatically. Install an SMTP plugin (WP Mail SMTP, Postmark, Resend-WP, etc.) for reliable deliverability of no-reply addresses.', 'cardology-reports' ); ?>
			</p>
			<?php submit_button(); ?>
		</form>

	<?php elseif ( 'appearance' === $current_tab ) : ?>
		<form method="post" action="options.php" class="crwp-appearance-form">
			<?php settings_fields( 'crwp_appearance' ); ?>
			<p class="description">
				<?php echo esc_html__( 'Pick a preset to style the customer-facing catalog and order form. The default uses a white background with subtle grey borders and inherits text colors from your active WordPress theme.', 'cardology-reports' ); ?>
			</p>

			<?php foreach ( $presets as $group_slug => $group ) : ?>
				<h3 class="crwp-appearance__group-title"><?php echo esc_html( $group['label'] ); ?></h3>
				<div class="crwp-swatch-grid">
					<?php foreach ( $group['options'] as $preset_slug => $preset ) : ?>
						<label class="crwp-swatch <?php echo $appearance['theme'] === $preset_slug ? 'is-active' : ''; ?>">
							<input
								type="radio"
								name="<?php echo esc_attr( \CRWP\Appearance::OPTION_KEY ); ?>[theme]"
								value="<?php echo esc_attr( $preset_slug ); ?>"
								<?php checked( $appearance['theme'], $preset_slug ); ?>
							/>
							<span class="crwp-swatch__chips">
								<?php foreach ( $preset['swatches'] as $color ) : ?>
									<span class="crwp-swatch__chip" style="background:<?php echo esc_attr( $color ); ?>"></span>
								<?php endforeach; ?>
							</span>
							<span class="crwp-swatch__label"><?php echo esc_html( $preset['label'] ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>

			<h3 class="crwp-appearance__group-title"><?php echo esc_html__( 'Custom', 'cardology-reports' ); ?></h3>
			<label class="crwp-swatch crwp-swatch--custom <?php echo $appearance['theme'] === 'custom' ? 'is-active' : ''; ?>">
				<input
					type="radio"
					name="<?php echo esc_attr( \CRWP\Appearance::OPTION_KEY ); ?>[theme]"
					value="custom"
					<?php checked( $appearance['theme'], 'custom' ); ?>
				/>
				<span class="crwp-swatch__label"><?php echo esc_html__( 'Custom palette (use the fields below)', 'cardology-reports' ); ?></span>
			</label>

			<details class="crwp-custom-tokens" <?php echo $appearance['theme'] === 'custom' ? 'open' : ''; ?>>
				<summary><?php echo esc_html__( 'Edit custom palette', 'cardology-reports' ); ?></summary>
				<table class="form-table" role="presentation">
					<?php foreach ( $tokens as $token_key => $token ) : ?>
						<?php
						$value = $appearance['custom'][ $token_key ] ?? $token['default'];
						$is_color = false !== strpos( $token_key, 'crwp-bg' )
							|| false !== strpos( $token_key, 'crwp-text' )
							|| false !== strpos( $token_key, 'crwp-accent' )
							|| false !== strpos( $token_key, 'crwp-btn' )
							|| false !== strpos( $token_key, 'crwp-border' )
							|| false !== strpos( $token_key, 'crwp-error' )
							|| false !== strpos( $token_key, 'crwp-success' );
						?>
						<tr>
							<th scope="row">
								<label><?php echo esc_html( $token['label'] ); ?></label>
								<p class="description" style="font-weight:normal;"><code><?php echo esc_html( $token_key ); ?></code></p>
							</th>
							<td>
								<input
									type="text"
									name="<?php echo esc_attr( \CRWP\Appearance::OPTION_KEY ); ?>[custom][<?php echo esc_attr( $token_key ); ?>]"
									value="<?php echo esc_attr( $value ); ?>"
									placeholder="<?php echo esc_attr( $token['default'] ); ?>"
									class="regular-text"
								/>
								<?php if ( $is_color ) : ?>
									<input type="color" value="<?php echo esc_attr( preg_match( '/^#[0-9a-fA-F]{6}$/', $value ) ? $value : '#ffffff' ); ?>" onchange="this.previousElementSibling.value = this.value;" />
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</details>

			<?php submit_button( __( 'Save Appearance', 'cardology-reports' ) ); ?>
		</form>

	<?php elseif ( 'pages' === $current_tab ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'crwp_pages' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php echo esc_html__( 'Catalog page', 'cardology-reports' ); ?></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'             => 'crwp_catalog_page_id',
								'selected'         => $pages['catalog'],
								'show_option_none' => '— ' . __( 'Select page', 'cardology-reports' ) . ' —',
							)
						);
						?>
						<p class="description"><?php echo esc_html__( 'The page that holds the [cardology_reports] shortcode. Used for return links.', 'cardology-reports' ); ?></p>
					</td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'Status page', 'cardology-reports' ); ?></th>
					<td>
						<?php
						wp_dropdown_pages(
							array(
								'name'             => 'crwp_status_page_id',
								'selected'         => $pages['status'],
								'show_option_none' => '— ' . __( 'Select page', 'cardology-reports' ) . ' —',
							)
						);
						?>
						<p class="description"><?php echo esc_html__( 'The page that holds the [cardology_report_status] shortcode. Stripe success_url and emails will point here.', 'cardology-reports' ); ?></p>
					</td></tr>
			</table>
			<?php submit_button(); ?>
		</form>

	<?php elseif ( 'adv' === $current_tab ) : ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'crwp_advanced' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php echo esc_html__( 'Delete data on uninstall', 'cardology-reports' ); ?></th>
					<td>
						<label><input type="checkbox" name="crwp_delete_data_on_uninstall" value="1" <?php checked( $advanced['delete_on_uninstall'], 1 ); ?> />
							<?php echo esc_html__( 'When this plugin is deleted, drop the orders table and remove all settings.', 'cardology-reports' ); ?>
						</label>
					</td></tr>
			</table>
			<?php submit_button(); ?>
		</form>
	<?php endif; ?>
</div>
