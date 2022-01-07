<?php
/**
 * Analytics Report header template.
 *
 * @package    RankMath
 * @subpackage RankMath\Admin
 */

use RankMath\Helper;

 defined( 'ABSPATH' ) || exit;

?>
<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="report-info">
	<tr>
		<td>
			<h1><?php esc_html_e( 'SEO Report of Your Website', 'rank-math-pro' ); ?></h1>
			###TOP_HTML###
			<h2 class="report-date">###START_DATE### - ###END_DATE###</h2>
			<a href="###SITE_URL###" target="_blank" class="site-url">###SITE_URL_SIMPLE###</a>
		</td>
		<?php if ( $this->get_setting( 'link_full_report', true ) ) : ?>
			<td class="full-report-link">
				<a href="###REPORT_URL###" target="_blank" class="full-report-link">
					<?php esc_html_e( 'FULL REPORT', 'rank-math-pro' ); ?>
					<?php $this->image( 'report-icon-external.png', 12, 12, __( 'External Link Icon', 'rank-math-pro' ) ); ?>
				</a>
			</td>
		<?php endif; ?>
	</tr>
</table>

<?php if ( $this->get_variable( 'stats_invalid_data' ) ) { ?>
	<table role="presentation" border="0" cellpadding="0" cellspacing="0" class="report-error">
		<tr>
			<td>
				<h2><?php esc_html_e( 'Uh-oh', 'rank-math-pro' ); ?></h2>
				<p><em><?php esc_html_e( 'It seems that there are no stats to show right now.', 'rank-math-pro' ); ?></em></p>
				<?php // Translators: placeholders are anchor opening and closing tags. ?>
				<p><?php printf( esc_html__( 'If you can see the site data in your Search Console and Analytics accounts, but not here, then %1$s try reconnecting your account %2$s and make sure that the correct properties are selected in the %1$s Analytics Settings%2$s.', 'rank-math-pro' ), '<a href="' . Helper::get_admin_url( 'options-general#setting-panel-analytics' ) . '">', '</a>' ); ?></p>
			</td>
		</tr>
	</table>
<?php } ?>