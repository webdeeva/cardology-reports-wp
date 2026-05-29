<?php
/**
 * @var string $rest_url
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap crwp-admin">
	<h1><?php echo esc_html__( 'Cardology Reports', 'cardology-reports' ); ?></h1>
	<p><?php echo esc_html__( 'Sell personalized Cardology reports on this site. Use the shortcodes below to render the catalog, an individual report form, or the customer status page.', 'cardology-reports' ); ?></p>

	<div class="crwp-admin__panel">
		<h2><?php echo esc_html__( 'Shortcodes', 'cardology-reports' ); ?></h2>
		<table class="widefat striped">
			<tbody>
				<tr><th><code>[cardology_reports]</code></th><td><?php echo esc_html__( 'Catalog grid of all reports.', 'cardology-reports' ); ?></td></tr>
				<tr><th><code>[cardology_report slug="life"]</code></th><td><?php echo esc_html__( 'Order form for a single report. Replace the slug to suit.', 'cardology-reports' ); ?></td></tr>
				<tr><th><code>[cardology_report_status]</code></th><td><?php echo esc_html__( 'Status / download page (uses ?session_id= from Stripe).', 'cardology-reports' ); ?></td></tr>
			</tbody>
		</table>
	</div>

	<div class="crwp-admin__panel">
		<h2><?php echo esc_html__( 'Stripe webhook endpoint', 'cardology-reports' ); ?></h2>
		<p><?php echo esc_html__( 'Add this URL as a webhook endpoint in your Stripe Dashboard, listening for the event:', 'cardology-reports' ); ?>
			<code>checkout.session.completed</code></p>
		<input readonly class="large-text" value="<?php echo esc_attr( $rest_url ); ?>" onclick="this.select()" />
	</div>

	<div class="crwp-admin__panel">
		<h2><?php echo esc_html__( 'Next steps', 'cardology-reports' ); ?></h2>
		<ol>
			<li><?php echo wp_kses_post( __( 'Open <strong>Settings</strong> and paste your Stripe keys, webhook secrets, and Report Writer API key.', 'cardology-reports' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'Open <strong>Reports Catalog</strong> to edit each report’s name, description, and price.', 'cardology-reports' ) ); ?></li>
			<li><?php echo wp_kses_post( __( 'Create the front-end pages and add the shortcodes, then point your Settings → Pages selectors at them.', 'cardology-reports' ) ); ?></li>
		</ol>
	</div>
</div>
