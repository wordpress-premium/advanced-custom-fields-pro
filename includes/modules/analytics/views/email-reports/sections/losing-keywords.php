<?php
/**
 * Analytics Report Losing Keywords.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin
 */

use RankMathPro\Analytics\Email_Reports;
use MyThemeShop\Helpers\Str;

defined( 'ABSPATH' ) || exit;

$keywords = (array) $this->get_variable( 'losing_keywords' );

?>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="report-heading">
	<tr>
		<td>
			<h2><?php esc_html_e( 'Top Losing Keywords', 'rank-math-pro' ); ?></h2>
		</td>
	</tr>
</table>

<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="keywords-table-wrapper">
	<tr>
		<td>
			<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="keywords-table stats-table losing-keywords">
				<tr class="table-heading">
					<td class="col-1">
						<?php esc_html_e( 'Keywords', 'rank-math-pro' ); ?>
					</td>
					<td class="col-2">
						<?php esc_html_e( 'Impressions', 'rank-math-pro' ); ?>
					</td>
					<td class="col-3">
						<?php esc_html_e( 'Position', 'rank-math-pro' ); ?>
					</td>
				</tr>
				<?php foreach ( $keywords as $keyword => $data ) : ?>
					<?php if ( ! is_array( $data ) ) { continue; } ?>
					<tr>
						<td style="width:280px;box-sizing:border-box;">
							<span title="<?php echo esc_html( $keyword ); ?>"><?php echo esc_html( Str::truncate( $keyword, 36, '...' ) ); ?></span>
						</td>
						<td>
							<?php $this->template_part( 'stat', Email_Reports::get_stats_val( $data, 'impressions' ) ); ?>
						</td>
						<td>
							<?php $this->template_part( 'stat', array_merge( Email_Reports::get_stats_val( $data, 'position' ), [ 'invert' => true ] ) ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $keywords ) ) : ?>
					<tr>
						<td colspan="3">
							<?php esc_html_e( 'No data to show.', 'rank-math-pro' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</table>
		</td>
	</tr>
</table>
