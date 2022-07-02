<?php
/**
 * Analytics Report email template.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin
 */

defined( 'ABSPATH' ) || exit;

$sections = $this->get_setting( 'sections', [ 'summary', 'positions', 'winning_posts', 'winning_keywords', 'losing_keywords' ] );

$analytics              = get_option( 'rank_math_google_analytic_options' );
$is_analytics_connected = ! empty( $analytics ) && ! empty( $analytics['view_id'] );

// Header & optional sections.
$this->template_part( 'header' );
$this->template_part( 'header-after' );

foreach ( $sections as $section ) {
	$template = str_replace( '_', '-', $section );
	$this->template_part( "sections/{$template}", [ 'analytics_connected' => $is_analytics_connected ] );
}

// phpcs:enable
?>
<?php if ( $this->get_setting( 'link_full_report', true ) ) : ?>
	<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="details-button">
		<tr class="button">
			<td align="left">
				<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn btn-primary">
					<tbody>
						<tr>
							<td align="left" style="padding-bottom: 0;">
								<a href="###REPORT_URL###" target="_blank"><?php esc_html_e( 'VIEW DETAILED ANALYTICS', 'rank-math-pro' ); ?></a>
							</td>
						</tr>
					</tbody>
				</table>
			</td>
		</tr>
	</table>
<?php endif; ?>

<?php $this->template_part( 'footer' ); ?>
