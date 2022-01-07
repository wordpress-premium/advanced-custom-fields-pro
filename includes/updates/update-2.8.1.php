<?php
/**
 * The Updates routine for version 2.8.1.
 *
 * @since      2.8.1
 * @package    RankMathPro
 * @subpackage RankMathPro\Updates
 * @author     Rank Math <support@rankmath.com>
 */

defined( 'ABSPATH' ) || exit;

use RankMath\Helper;

/**
 * This code is needed to opening hours data.
 */
function rank_math_pro_2_8_1_update_news_sitemap_settings() {
	$all_opts = rank_math()->settings->all_raw();
	$sitemap  = $all_opts['sitemap'];
	if ( empty( $sitemap['news_sitemap_post_type'] ) ) {
		return;
	}

	foreach ( $sitemap['news_sitemap_post_type'] as $post_type ) {
		$exclude_terms = isset( $sitemap[ "news_sitemap_exclude_{$post_type}_terms" ] ) ? $sitemap[ "news_sitemap_exclude_{$post_type}_terms" ] : [];
		if ( empty( $exclude_terms ) ) {
			continue;
		}

		$term = get_term( current( $exclude_terms ) );
		if ( empty( $term ) || is_wp_error( $term ) ) {
			continue;
		}

		$sitemap[ "news_sitemap_exclude_{$post_type}_terms" ] = [
			[ $term->taxonomy => $exclude_terms ],
		];
	}

	Helper::update_all_settings( null, null, $sitemap );
}

rank_math_pro_2_8_1_update_news_sitemap_settings();
