<?php
/**
 * @var array<string,mixed> $order
 * @var string              $report_url
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;line-height:1.6;color:#333;max-width:600px;margin:0 auto;padding:20px;">
	<div style="background:linear-gradient(135deg,#380611,#000);color:#f5c542;padding:24px;border-radius:10px 10px 0 0;text-align:center;">
		<h1 style="margin:0;">
			<?php printf( esc_html__( 'Your %s is ready', 'cardology-reports' ), esc_html( $order['report_title'] ) ); ?>
		</h1>
	</div>
	<div style="background:#f8f9fa;padding:24px;border-radius:0 0 10px 10px;">
		<p><?php printf( esc_html__( 'Hi %s,', 'cardology-reports' ), esc_html( $order['customer_name'] ) ); ?></p>
		<p><?php echo esc_html__( 'Your personalized cardology reading is complete. Tap the button below to read it whenever you’re ready.', 'cardology-reports' ); ?></p>
		<p style="text-align:center;margin:28px 0;">
			<a href="<?php echo esc_url( $report_url ); ?>" style="background:#f5c542;color:#000;padding:14px 28px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;">
				<?php echo esc_html__( 'Open your report', 'cardology-reports' ); ?>
			</a>
		</p>
		<p style="color:#666;font-size:13px;"><?php echo esc_html__( 'Save this email — the same link will work later if you want to revisit your report.', 'cardology-reports' ); ?></p>
		<p style="color:#666;font-size:13px;">— <?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
	</div>
</body>
</html>
