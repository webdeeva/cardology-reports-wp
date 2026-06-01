<?php
/**
 * Owner notification: a report sale occurred.
 *
 * @var array<string,mixed>  $order     Keys: customer_name, customer_email, report_title, amount_cents.
 * @var array<string,string> $palette
 * @var string               $site_name
 * @var string               $site_url
 * @var string               $admin_url
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;

$amount_cents = isset( $order['amount_cents'] ) ? (int) $order['amount_cents'] : 0;
$amount_label = $amount_cents > 0
	? '$' . number_format( $amount_cents / 100, 2 )
	: __( 'Free (coupon)', 'cardology-reports' );
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo esc_html__( 'New report sale', 'cardology-reports' ); ?></title>
</head>
<body style="margin:0;padding:24px 12px;background-color:#f1f1f1;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.6;color:<?php echo esc_attr( $palette['body_text'] ); ?>;">
	<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="600" style="max-width:600px;margin:0 auto;background:<?php echo esc_attr( $palette['body_bg'] ); ?>;border-radius:10px;overflow:hidden;border:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;">
		<tr>
			<td style="background-color:<?php echo esc_attr( $palette['header_bg'] ); ?>;color:<?php echo esc_attr( $palette['header_text'] ); ?>;padding:28px 24px;text-align:center;">
				<span style="font-size:14px;letter-spacing:0.15em;text-transform:uppercase;opacity:0.85;">
					<?php echo esc_html( $site_name ); ?>
				</span>
				<h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;font-weight:700;color:<?php echo esc_attr( $palette['header_text'] ); ?>;">
					<?php echo esc_html__( '🎉 New report sale', 'cardology-reports' ); ?>
				</h1>
			</td>
		</tr>
		<tr>
			<td style="padding:28px 28px 24px;">
				<p style="margin:0 0 18px;font-size:15px;">
					<?php echo esc_html__( 'You just made a sale. Details:', 'cardology-reports' ); ?>
				</p>
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="font-size:15px;border-collapse:collapse;">
					<tr>
						<td style="padding:10px 0;border-bottom:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;color:<?php echo esc_attr( $palette['muted'] ); ?>;width:38%;"><?php echo esc_html__( 'Report', 'cardology-reports' ); ?></td>
						<td style="padding:10px 0;border-bottom:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;font-weight:600;"><?php echo esc_html( $order['report_title'] ); ?></td>
					</tr>
					<tr>
						<td style="padding:10px 0;border-bottom:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;color:<?php echo esc_attr( $palette['muted'] ); ?>;"><?php echo esc_html__( 'Amount', 'cardology-reports' ); ?></td>
						<td style="padding:10px 0;border-bottom:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;font-weight:600;"><?php echo esc_html( $amount_label ); ?></td>
					</tr>
					<tr>
						<td style="padding:10px 0;border-bottom:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;color:<?php echo esc_attr( $palette['muted'] ); ?>;"><?php echo esc_html__( 'Customer', 'cardology-reports' ); ?></td>
						<td style="padding:10px 0;border-bottom:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;font-weight:600;"><?php echo esc_html( $order['customer_name'] ); ?></td>
					</tr>
					<tr>
						<td style="padding:10px 0;color:<?php echo esc_attr( $palette['muted'] ); ?>;"><?php echo esc_html__( 'Email', 'cardology-reports' ); ?></td>
						<td style="padding:10px 0;font-weight:600;">
							<a href="mailto:<?php echo esc_attr( $order['customer_email'] ); ?>" style="color:<?php echo esc_attr( $palette['btn_bg'] ); ?>;"><?php echo esc_html( $order['customer_email'] ); ?></a>
						</td>
					</tr>
				</table>
				<p style="text-align:center;margin:28px 0 4px;">
					<a href="<?php echo esc_url( $admin_url ); ?>" style="display:inline-block;background-color:<?php echo esc_attr( $palette['btn_bg'] ); ?>;color:<?php echo esc_attr( $palette['btn_text'] ); ?>;padding:13px 26px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;">
						<?php echo esc_html__( 'View customers', 'cardology-reports' ); ?>
					</a>
				</p>
			</td>
		</tr>
		<tr>
			<td style="background-color:<?php echo esc_attr( $palette['body_bg'] ); ?>;border-top:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;padding:18px 28px;text-align:center;font-size:12px;color:<?php echo esc_attr( $palette['muted'] ); ?>;">
				<?php printf( esc_html__( 'Sale notification from %s', 'cardology-reports' ), '<a href="' . esc_url( $site_url ) . '" style="color:' . esc_attr( $palette['muted'] ) . ';">' . esc_html( $site_name ) . '</a>' ); ?>
				&middot; <?php echo esc_html__( 'Reply to this email to reach the customer.', 'cardology-reports' ); ?>
			</td>
		</tr>
	</table>
</body>
</html>
