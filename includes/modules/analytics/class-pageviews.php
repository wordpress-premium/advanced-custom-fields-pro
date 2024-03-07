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
use RankMath\Helpers\DB as DB_Helper;
use RankMath\Helper;

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
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT SQL_CALC_FOUND_ROWS t1.page as page, COALESCE( t1.pageviews, 0 ) as pageviews, COALESCE( t1.pageviews - t2.pageviews, 0 ) as difference
				FROM ( SELECT page, SUM(pageviews) as pageviews FROM {$wpdb->prefix}rank_math_analytics_ga WHERE 1=1{$pages}{$dates}{$sub_where} GROUP BY page ) as t1
				LEFT JOIN ( SELECT page, SUM(pageviews) as pageviews FROM {$wpdb->prefix}rank_math_analytics_ga WHERE 1=1{$pages}{$dates}{$sub_where} GROUP BY page ) as t2
				ON t1.page = t2.page
				{$where}
				{$order}
				{$limit}",
				Stats::get()->start_date,
				Stats::get()->end_date,
				Stats::get()->compare_start_date,
				Stats::get()->compare_end_date
			),
			ARRAY_A
		);
		$rowsFound = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		// phpcs:enable

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

		Helper::enable_big_selects_for_queries();
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
		$rows = $wpdb->get_results(
			$wpdb->prepare(
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
			),
			ARRAY_A
		);

		$rowsFound = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

		// phpcs:enable
		return \compact( 'rows', 'rowsFound' );
	}

	/**
	 * Get pageviews for single post by post Id.
	 *
	 * @param array $post_ids Post IDs.
	 *
	 * @return array
	 */
	public static function get_traffic_by_object_ids( $post_ids ) {
		if ( ! DB_Helper::check_table_exists( 'rank_math_analytics_ga' ) ) {
			return [];
		}

		global $wpdb;
		$placeholder = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );

		// phpcs:disable
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t2.object_id, SUM(t1.pageviews) AS traffic FROM {$wpdb->prefix}rank_math_analytics_ga AS t1 
				Left JOIN {$wpdb->prefix}rank_math_analytics_objects AS t2 ON t1.page=t2.page 
				WHERE t2.object_id IN ( {$placeholder} ) and t1.created BETWEEN Now() - interval 36 day and Now() - interval 3 day
				GROUP BY t2.object_id",
				$post_ids
			),
			ARRAY_A
		);
		// phpcs:enable

		return array_combine( array_column( $data, 'object_id' ), array_column( $data, 'traffic' ) );
	}

	/**
	 * Get pageviews for single post by post Id.
	 *
	 * @param array $post_ids Post IDs.
	 *
	 * @return array
	 */
	public static function get_impressions_by_object_ids( $post_ids ) {
		if ( ! DB_Helper::check_table_exists( 'rank_math_analytics_gsc' ) ) {
			return [];
		}

		global $wpdb;
		$placeholder = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );

		// phpcs:disable
		$data = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t2.object_id, SUM(impressions) AS traffic FROM {$wpdb->prefix}rank_math_analytics_gsc AS t1 
				Left JOIN {$wpdb->prefix}rank_math_analytics_objects AS t2 ON t1.page=t2.page 
				WHERE t2.object_id IN ( {$placeholder} ) and t1.created BETWEEN Now() - interval 36 day and Now() - interval 3 day
				GROUP BY t2.object_id",
				$post_ids
			),
			ARRAY_A
		);
		// phpcs:enable

		return array_combine( array_column( $data, 'object_id' ), array_column( $data, 'traffic' ) );
	}
}
