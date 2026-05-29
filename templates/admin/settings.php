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
	'stripe' => __( 'Stripe', 'cardology-reports' ),
	'api'    => __( 'Report Writer API', 'cardology-reports' ),
	'email'  => __( 'Email', 'cardology-reports' ),
	'pages'  => __( 'Pages', 'cardology-reports' ),
	'adv'    => __( 'Advanced', 'cardology-reports' ),
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
		<form method="post" action="options.php">
			<?php settings_fields( 'crwp_email' ); ?>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php echo esc_html__( 'From name', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="text" name="crwp_email_settings[from_name]" value="<?php echo esc_attr( $email['from_name'] ); ?>" /></td></tr>
				<tr><th scope="row"><?php echo esc_html__( 'From email', 'cardology-reports' ); ?></th>
					<td><input class="regular-text" type="email" name="crwp_email_settings[from_email]" value="<?php echo esc_attr( $email['from_email'] ); ?>" /></td></tr>
			</table>
			<p class="description">
				<?php echo esc_html__( 'Emails are sent via wp_mail(). Install an SMTP plugin (WP Mail SMTP, Postmark, etc.) for reliable delivery.', 'cardology-reports' ); ?>
			</p>
			<?php submit_button(); ?>
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
