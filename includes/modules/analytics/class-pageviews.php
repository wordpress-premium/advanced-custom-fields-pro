<?php
/**
 * The Analytics Module
 *
 * @since      2.0.0
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use RankMath\Analytics\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Pageviews class.
 */
class Pageviews {

	/**
	 * Get page views for pages.
	 *
	 * @param array $args Array of urls.
	 *
	 * @return array
	 */
	public static function get_pageviews( $args = [] ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'order'     => 'DESC',
				'orderBy'   => 't1.pageviews',
				'where'     => '',
				'sub_where' => '',
				'dates'     => ' AND created BETWEEN %s AND %s',
				'limit'     => '',
				'pages'     => '',
			]
		);

		if ( empty( $args['pages'] ) ) {
			return [
				'rows'      => [],
				'rowsFound' => 0,
			];
		}

		$args['pages'] = ' AND page IN (\'' . join( '\', \'', $args['pages'] ) . '\')';

		$pages     = $args['pages'];
		$where     = $args['where'];
		$limit     = $args['limit'];
		$dates     = $args['dates'];
		$sub_where = $args['sub_where'];
		$order     = sprintf( 'ORDER BY %s %s', $args['orderBy'], $args['order'] );

		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS t1.page as page, COALESCE( t1.pageviews, 0 ) as pageviews, COALESCE( t1.pageviews - t2.pageviews, 0 ) as difference
			FROM
				( SELECT page, SUM(pageviews) as pageviews FROM {$wpdb->prefix}rank_math_analytics_ga WHERE 1=1{$pages}{$dates}{$sub_where} GROUP BY page ) as t1
			LEFT JOIN
				( SELECT page, SUM(pageviews) as pageviews FROM {$wpdb->prefix}rank_math_analytics_ga WHERE 1=1{$pages}{$dates}{$sub_where} GROUP BY page ) as t2
			ON t1.page = t2.page
			{$where}
			{$order}
			{$limit}",
			Stats::get()->start_date,
			Stats::get()->end_date,
			Stats::get()->compare_start_date,
			Stats::get()->compare_end_date
		);
		$rows      = $wpdb->get_results( $query, ARRAY_A );
		$rowsFound = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		return \compact( 'rows', 'rowsFound' );
	}

	/**
	 * Get page views for pages.
	 *
	 * @param array $args Array of urls.
	 *
	 * @return array
	 */
	public static function get_pageviews_with_object( $args = [] ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			[
				'order'     => 'DESC',
				'dates'     => ' AND created BETWEEN %s AND %s',
				'limit'     => '',
				'sub_where' => '',
			]
		);

		$order    = $args['order'];
		$limit    = $args['limit'];
		$dates    = $args['dates'];
		$subwhere = $args['sub_where'];

		// phpcs:disable
		$query = $wpdb->prepare(
			"SELECT SQL_CALC_FOUND_ROWS o.*, COALESCE( traffic.pageviews, 0 ) as pageviews, COALESCE( traffic.difference, 0 ) as difference
			FROM {$wpdb->prefix}rank_math_analytics_objects as o
			LEFT JOIN (SELECT t1.page as page, COALESCE( t1.pageviews, 0 ) as pageviews, COALESCE( t1.pageviews - t2.pageviews, 0 ) as difference
				FROM
			    	( SELECT page, SUM(pageviews) as pageviews FROM {$wpdb->prefix}rank_math_analytics_ga WHERE 1=1{$dates} GROUP BY page ) as t1
				LEFT JOIN
			    	( SELECT page, SUM(pageviews) as pageviews FROM {$wpdb->prefix}rank_math_analytics_ga WHERE 1=1{$dates} GROUP BY page ) as t2
				ON t1.page = t2.page ) traffic ON o.page = traffic.page
			WHERE o.is_indexable = '1'{$subwhere}
			ORDER BY pageviews {$order}
			{$limit}",
			Stats::get()->start_date,
			Stats::get()->end_date,
			Stats::get()->compare_start_date,
			Stats::get()->compare_end_date
		);
		$rows      = $wpdb->get_results( $query, ARRAY_A );
		$rowsFound = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		return \compact( 'rows', 'rowsFound' );
	}
}
