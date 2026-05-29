<?php
/**
 * @var array<string,mixed>            $order
 * @var string                         $status_url
 * @var array<string,string>           $palette
 * @var string                         $site_name
 * @var string                         $site_url
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php printf( esc_html__( 'Your %s is being prepared', 'cardology-reports' ), esc_html( $order['report_title'] ) ); ?></title>
</head>
<body style="margin:0;padding:24px 12px;background-color:#f1f1f1;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.6;color:<?php echo esc_attr( $palette['body_text'] ); ?>;">
	<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="600" style="max-width:600px;margin:0 auto;background:<?php echo esc_attr( $palette['body_bg'] ); ?>;border-radius:10px;overflow:hidden;border:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;">
		<tr>
			<td style="background-color:<?php echo esc_attr( $palette['header_bg'] ); ?>;color:<?php echo esc_attr( $palette['header_text'] ); ?>;padding:28px 24px;text-align:center;">
				<a href="<?php echo esc_url( $site_url ); ?>" style="color:<?php echo esc_attr( $palette['header_text'] ); ?>;text-decoration:none;font-size:14px;letter-spacing:0.15em;text-transform:uppercase;opacity:0.85;">
					<?php echo esc_html( $site_name ); ?>
				</a>
				<h1 style="margin:8px 0 0;font-size:24px;line-height:1.25;font-weight:700;color:<?php echo esc_attr( $palette['header_text'] ); ?>;">
					<?php echo esc_html__( 'Your report is being prepared', 'cardology-reports' ); ?>
				</h1>
			</td>
		</tr>
		<tr>
			<td style="padding:28px 28px 32px;">
				<p style="margin:0 0 14px;font-size:16px;">
					<?php printf( esc_html__( 'Hi %s,', 'cardology-reports' ), esc_html( $order['customer_name'] ) ); ?>
				</p>
				<p style="margin:0 0 18px;font-size:15px;">
					<?php
					printf(
						/* translators: %s report title */
						esc_html__( 'Thanks for your order! Your %s is being written specifically for you and typically takes 10–15 minutes.', 'cardology-reports' ),
						'<strong>' . esc_html( $order['report_title'] ) . '</strong>'
					);
					?>
				</p>
				<p style="text-align:center;margin:28px 0;">
					<a href="<?php echo esc_url( $status_url ); ?>" style="display:inline-block;background-color:<?php echo esc_attr( $palette['btn_bg'] ); ?>;color:<?php echo esc_attr( $palette['btn_text'] ); ?>;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;font-size:15px;">
						<?php echo esc_html__( 'Check report status', 'cardology-reports' ); ?>
					</a>
				</p>
				<p style="margin:24px 0 0;font-size:13px;color:<?php echo esc_attr( $palette['muted'] ); ?>;">
					<?php echo esc_html__( 'We will email you a download link the moment it is ready. You can also leave the status page open and it will update automatically.', 'cardology-reports' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<td style="background-color:<?php echo esc_attr( $palette['body_bg'] ); ?>;border-top:1px solid <?php echo esc_attr( $palette['divider'] ); ?>;padding:18px 28px;text-align:center;font-size:12px;color:<?php echo esc_attr( $palette['muted'] ); ?>;">
				<?php printf( esc_html__( 'Sent by %s', 'cardology-reports' ), '<a href="' . esc_url( $site_url ) . '" style="color:' . esc_attr( $palette['muted'] ) . ';">' . esc_html( $site_name ) . '</a>' ); ?>
			</td>
		</tr>
	</table>
</body>
</html>
