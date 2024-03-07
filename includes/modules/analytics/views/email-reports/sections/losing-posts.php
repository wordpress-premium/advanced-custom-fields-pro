<?php
/**
 * Analytics Report Losing Posts.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin
 */

use RankMathPro\Analytics\Email_Reports;
use RankMath\Helpers\Str;

defined( 'ABSPATH' ) || exit;

$posts = (array) $this->get_variable( 'losing_posts' );

?>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="report-heading">
	<tr>
		<td>
			<h2><?php esc_html_e( 'Top Losing Posts', 'rank-math-pro' ); ?></h2>
		</td>
	</tr>
</table>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="traffic-table stats-table">
	<tr class="table-heading">
		<td class="col-1">
			<?php esc_html_e( 'Post', 'rank-math-pro' ); ?>
		</td>
		<?php if ( ! empty( $analytics_connected ) ) : ?>
			<td class="col-2">
				<?php esc_html_e( 'Search Traffic', 'rank-math-pro' ); ?>
			</td>
		<?php else : ?>
			<td class="col-2">
				<?php esc_html_e( 'Impressions', 'rank-math-pro' ); ?>
			</td>
		<?php endif; ?>
		<td class="col-3">
			<?php esc_html_e( 'Position', 'rank-math-pro' ); ?>
		</td>
	</tr>

	<?php foreach ( $posts as $post_url => $data ) : // phpcs:disable ?>
		<?php if ( ! is_array( $data ) ) { continue; } ?>
		<tr>
			<td>
				<a href="###SITE_URL###<?php echo esc_attr( $post_url ); ?>" target="_blank">
					<span class="post-title"><?php echo esc_html( Str::truncate( ( ! empty( $data['title'] ) ? $data['title'] : $data['page'] ), 55, '...' ) ); ?></span>
					<span class="post-url"><?php echo esc_html( Email_Reports::shorten_url( $post_url, 30, '...' ) ); ?></span>
				</a>
			</td>
			<td>
				<?php if ( ! empty( $analytics_connected ) ) : ?>
					<?php $this->template_part( 'stat', Email_Reports::get_stats_val( $data, 'pageviews' ) ); ?>
				<?php else : ?>
					<?php $this->template_part( 'stat', Email_Reports::get_stats_val( $data, 'impressions' ) ); ?>
				<?php endif; ?>
			</td>
			<td>
				<?php $this->template_part( 'stat', array_merge( Email_Reports::get_stats_val( $data, 'position' ), [ 'invert' => true ] ) ); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	<?php if ( empty( $posts ) ) : ?>
		<tr>
			<td colspan="3">
				<?php esc_html_e( 'No data to show.', 'rank-math-pro' ); ?>
			</td>
		</tr>
	<?php endif; ?>
</table>
