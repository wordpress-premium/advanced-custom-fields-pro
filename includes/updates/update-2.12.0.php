<?php
/**
 * The Updates routine for version 2.12.0.
 *
 * @since      2.12.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Updates
 * @author     Rank Math <support@rankmath.com>
 */

defined( 'ABSPATH' ) || exit;

use RankMath\Helper;

/**
 * This code is needed to update the g_update settings value.
 */
function rank_math_pro_2_12_0_update_analytics_settings() {
	$all_opts = rank_math()->settings->all_raw();
	$general  = $all_opts['general'];

	// Turn this option on by default after updating.
	$general['google_updates'] = 'on';

	Helper::update_all_settings( $general, null, null );
	rank_math()->settings->reset();
}
rank_math_pro_2_12_0_update_analytics_settings();

/**
 * This code is needed to add the g_update data.
 */
function rank_math_pro_2_12_0_add_g_update_data() {
	$registered = RankMath\Admin\Admin_Helper::get_registration_data();
	if ( empty( $registered ) || empty( $registered['username'] ) || empty( $registered['api_key'] ) ) {
		return;
	}

	// Clear cache and trigger re-check.
	delete_site_transient( 'rank_math_pro_updates' );
	$transient = get_site_transient( 'update_plugins' );
	set_site_transient( 'update_plugins', $transient );
}
rank_math_pro_2_12_0_add_g_update_data();
