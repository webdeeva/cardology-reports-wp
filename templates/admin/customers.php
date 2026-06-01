<?php
/**
 * Admin "Customers" screen — record of sales and customer info.
 *
 * @var array<int,array<string,mixed>> $orders   Current page of orders.
 * @var int                            $total    Total matching orders.
 * @var array{orders:int,revenue_cents:int} $totals
 * @var array<string,array<string,mixed>>  $catalog Report slug => report.
 * @var int                            $paged    Current page (1-based).
 * @var int                            $pages    Total pages.
 * @var int                            $per_page
 * @var string                         $search
 *
 * @package Cardology_Reports
 */

defined( 'ABSPATH' ) || exit;

$base_url = admin_url( 'admin.php?page=cardology-reports-customers' );

/** Map a status to a WP-ish badge colour. */
$status_color = static function ( string $status ): array {
	switch ( $status ) {
		case 'completed':
		case 'delivered':
			return array( '#067a46', '#e6f6ee' );
		case 'processing':
		case 'paid':
			return array( '#8a6d00', '#fbf3da' );
		case 'failed':
			return array( '#b32d2e', '#fce8e8' );
		default:
			return array( '#50575e', '#f0f0f1' );
	}
};
?>
<div class="wrap crwp-customers">
	<h1 class="wp-heading-inline"><?php echo esc_html__( 'Customers', 'cardology-reports' ); ?></h1>

	<div class="crwp-stat-cards">
		<div class="crwp-stat-card">
			<span class="crwp-stat-card__num"><?php echo esc_html( number_format_i18n( $totals['orders'] ) ); ?></span>
			<span class="crwp-stat-card__label"><?php echo esc_html__( 'Total orders', 'cardology-reports' ); ?></span>
		</div>
		<div class="crwp-stat-card">
			<span class="crwp-stat-card__num">$<?php echo esc_html( number_format( $totals['revenue_cents'] / 100, 2 ) ); ?></span>
			<span class="crwp-stat-card__label"><?php echo esc_html__( 'Revenue (excl. failed)', 'cardology-reports' ); ?></span>
		</div>
	</div>

	<form method="get" class="crwp-customers__search">
		<input type="hidden" name="page" value="cardology-reports-customers" />
		<p class="search-box">
			<label class="screen-reader-text" for="crwp-customer-search"><?php echo esc_html__( 'Search customers', 'cardology-reports' ); ?></label>
			<input type="search" id="crwp-customer-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php echo esc_attr__( 'Name, email, or report', 'cardology-reports' ); ?>" />
			<input type="submit" class="button" value="<?php echo esc_attr__( 'Search', 'cardology-reports' ); ?>" />
			<?php if ( '' !== $search ) : ?>
				<a class="button" href="<?php echo esc_url( $base_url ); ?>"><?php echo esc_html__( 'Clear', 'cardology-reports' ); ?></a>
			<?php endif; ?>
		</p>
	</form>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th scope="col"><?php echo esc_html__( 'Date', 'cardology-reports' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Customer', 'cardology-reports' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Report', 'cardology-reports' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Amount', 'cardology-reports' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Status', 'cardology-reports' ); ?></th>
				<th scope="col"><?php echo esc_html__( 'Report file', 'cardology-reports' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( empty( $orders ) ) : ?>
				<tr><td colspan="6"><?php echo esc_html__( 'No orders yet.', 'cardology-reports' ); ?></td></tr>
			<?php else : ?>
				<?php
				foreach ( $orders as $order ) :
					$slug         = (string) ( $order['report_slug'] ?? '' );
					$report_title = $catalog[ $slug ]['title'] ?? $slug;
					$status       = (string) ( $order['status'] ?? '' );
					list( $fg, $bg ) = $status_color( $status );
					$created      = ! empty( $order['created_at'] )
						? get_date_from_gmt( (string) $order['created_at'], 'M j, Y g:i a' )
						: '—';
					?>
					<tr>
						<td><?php echo esc_html( $created ); ?></td>
						<td>
							<strong><?php echo esc_html( $order['customer_name'] ?: '—' ); ?></strong>
							<?php if ( ! empty( $order['customer_email'] ) ) : ?>
								<br /><a href="mailto:<?php echo esc_attr( $order['customer_email'] ); ?>"><?php echo esc_html( $order['customer_email'] ); ?></a>
							<?php endif; ?>
							<?php if ( ! empty( $order['customer_birthdate'] ) ) : ?>
								<br /><span class="crwp-muted"><?php echo esc_html__( 'Born:', 'cardology-reports' ); ?> <?php echo esc_html( $order['customer_birthdate'] ); ?><?php echo ! empty( $order['customer_birth_place'] ) ? ' · ' . esc_html( $order['customer_birth_place'] ) : ''; ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $order['partner_name'] ) ) : ?>
								<br /><span class="crwp-muted"><?php echo esc_html__( 'Partner:', 'cardology-reports' ); ?> <?php echo esc_html( $order['partner_name'] ); ?><?php echo ! empty( $order['partner_birthdate'] ) ? ' (' . esc_html( $order['partner_birthdate'] ) . ')' : ''; ?></span>
							<?php endif; ?>
						</td>
						<td><?php echo esc_html( $report_title ); ?></td>
						<td>
							<?php
							$amount = (int) ( $order['amount_cents'] ?? 0 );
							echo $amount > 0 ? '$' . esc_html( number_format( $amount / 100, 2 ) ) : esc_html__( 'Free', 'cardology-reports' );
							?>
						</td>
						<td>
							<span class="crwp-badge" style="color:<?php echo esc_attr( $fg ); ?>;background:<?php echo esc_attr( $bg ); ?>;">
								<?php echo esc_html( ucfirst( $status ?: 'pending' ) ); ?>
							</span>
						</td>
						<td>
							<?php if ( ! empty( $order['report_url'] ) ) : ?>
								<a href="<?php echo esc_url( $order['report_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Open', 'cardology-reports' ); ?></a>
							<?php else : ?>
								<span class="crwp-muted">—</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
	</table>

	<?php if ( $pages > 1 ) : ?>
		<div class="tablenav"><div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				/* translators: %s number of items */
				printf( esc_html( _n( '%s item', '%s items', $total, 'cardology-reports' ) ), esc_html( number_format_i18n( $total ) ) );
				?>
			</span>
			<span class="pagination-links">
				<?php
				$args = array( 'page' => 'cardology-reports-customers' );
				if ( '' !== $search ) {
					$args['s'] = $search;
				}
				$prev = $paged > 1 ? add_query_arg( array_merge( $args, array( 'paged' => $paged - 1 ) ), admin_url( 'admin.php' ) ) : '';
				$next = $paged < $pages ? add_query_arg( array_merge( $args, array( 'paged' => $paged + 1 ) ), admin_url( 'admin.php' ) ) : '';
				?>
				<?php if ( $prev ) : ?>
					<a class="button" href="<?php echo esc_url( $prev ); ?>">&lsaquo; <?php echo esc_html__( 'Prev', 'cardology-reports' ); ?></a>
				<?php endif; ?>
				<span class="paging-input" style="margin:0 6px;">
					<?php
					/* translators: 1: current page, 2: total pages */
					printf( esc_html__( 'Page %1$s of %2$s', 'cardology-reports' ), esc_html( $paged ), esc_html( $pages ) );
					?>
				</span>
				<?php if ( $next ) : ?>
					<a class="button" href="<?php echo esc_url( $next ); ?>"><?php echo esc_html__( 'Next', 'cardology-reports' ); ?> &rsaquo;</a>
				<?php endif; ?>
			</span>
		</div></div>
	<?php endif; ?>
</div>
