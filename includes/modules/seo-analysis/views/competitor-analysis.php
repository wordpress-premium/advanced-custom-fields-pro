<?php
/**
 * SEO Analyzer - Competitor Analysis
 *
 * @package   RANK_MATH
 * @author    Rank Math <support@rankmath.com>
 * @license   GPL-2.0+
 * @link      https://rankmath.com/wordpress/plugin/seo-suite/
 * @copyright 2019 Rank Math
 */

use RankMath\Helper;

$module   = Helper::get_module( 'seo-analysis' );
$analyzer = $module->admin->analyzer;

// Check rank_math_seo_analysis_competitor_url option.
$url = '';
if ( ! empty( $analyzer->results ) ) {
	$url = get_option( 'rank_math_seo_analysis_competitor_url' );
}

defined( 'ABSPATH' ) || exit;
?>
<div class="rank-math-box">
	<h2>
		<?php esc_html_e( 'Competitor Analysis', 'rank-math-pro' ); ?>
	</h2>
	<p><?php esc_html_e( 'Enter a site URL to see how it ranks for the same SEO criteria as your site.', 'rank-math-pro' ); ?></p>

	<div class="url-form">
		<input type="text" name="competitor_url" id="competitor_url" class="rank-math-analyze-url" placeholder="https://" value="<?php echo esc_attr( $url ); ?>" />
		<input type="hidden" name="competitor_analyzer" id="competitor_analyzer" value="1" />
		<button type="button" class="button button-primary rank-math-recheck no-autostart" id="competitor_url_submit"><?php esc_html_e( 'Start Audit', 'rank-math-pro' ); ?></button>
	</div>
</div>

<div class="rank-math-box rank-math-analyzer-result <?php echo '' === $url ? '' : esc_attr( 'has-results' ); ?>">

	<span class="wp-header-end"></span>

	<div class="progress-bar">
		<div class="progress"></div>
		<div class="progress-text"><span>0%</span> <?php esc_html_e( 'Complete', 'rank-math-pro' ); ?></div>
	</div>

	<div class="rank-math-results-wrapper">
		<?php $analyzer->display(); ?>
	</div>

	<p style="text-align: right;"><em><strong><?php esc_html_e( 'Note:', 'rank-math-pro' ); ?></strong> <?php esc_html_e( "The total test count is different for the competitor as we don't have access to their database.", 'rank-math-pro' ); ?></em></p>

</div>