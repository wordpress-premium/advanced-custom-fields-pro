<?php
/**
 * The Updates routine for version 3.0.17.
 *
 * @since      3.0.17
 * @package    RankMathPro
 * @subpackage RankMathPro\Updates
 * @author     Rank Math <support@rankmath.com>
 */

defined( 'ABSPATH' ) || exit;

use RankMath\Helper;

/**
 * This code is needed to update the podcast settings value.
 */
function rank_math_pro_3_0_17_update_podcast_settings() {
	$all_opts = rank_math()->settings->all_raw();
	$general  = $all_opts['general'];

	$general['podcast_title']                 = '%sitename%';
    $general['podcast_description']           = '%sitedesc%';
    $general['podcast_tipodcast_explicittle'] = 'off';

	Helper::update_all_settings( $general, null, null );
	rank_math()->settings->reset();
}
rank_math_pro_3_0_17_update_podcast_settings();
