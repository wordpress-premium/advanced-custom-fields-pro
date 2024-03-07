<?php
/**
 * The Updates routine for version 3.0.26.
 *
 * @since      3.0.26
 * @package    RankMathPro
 * @subpackage RankMathPro\Updates
 * @author     Rank Math <support@rankmath.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * This code is needed to update the UK country ISO code with GB value.
 */
function rank_math_pro_3_0_26_update_country_code() {
	$prev = get_option( 'rank_math_google_analytic_options', [] );

	if (
		empty( $prev ) ||
		empty( $prev['country'] ) ||
		$prev['country'] !== 'UK'
	) {
		return;
	}

	$prev['country'] = 'GB';

	update_option( 'rank_math_google_analytic_options', $prev );
}

rank_math_pro_3_0_26_update_country_code();
