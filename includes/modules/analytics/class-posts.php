<?php
/**
 * The Analytics Module
 *
 * @since      2.0.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use stdClass;
use WP_Error;
use WP_REST_Request;
use RankMath\Traits\Hooker;
use RankMath\Analytics\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Posts class.
 */
class Posts {

	use Hooker;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Posts
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Posts ) ) {
			$instance = new Posts();
			$instance->setup();
		}

		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function setup() {
		$this->filter( 'rank_math/analytics/single/report', 'add_badges', 10, 1 );
		$this->filter( 'rank_math/analytics/single/report', 'add_backlinks', 10, 1 );
		$this->filter( 'rank_math/analytics/single/report', 'add_ranking_keywords', 10, 1 );
		$this->filter( 'rank_math/analytics/single/report', 'get_graph_data_for_post', 10, 1 );
		$this->filter( 'rank_math/analytics/post_data', 'sort_new_data', 10, 2 );
		$this->filter( 'rank_math/analytics/get_objects_by_score_args', 'get_objects_by_score_args', 10, 2 );
		$this->filter( 'rank_math/analytics/get_posts_rows_by_objects', 'get_posts_rows_by_objects', 10, 2 );
	}
	/**
	 * Get posts by objects.
	 *
	 * @param  boolean         $result Check.
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return $args for order and orderby.
	 */
	public function get_objects_by_score_args( $result, WP_REST_Request $request ) {

		$orderby              = $request->get_param( 'orderby' );
		$is_valid_order_param = in_array( $orderby, [ 'title', 'seo_score' ], true );
		$orderby              = $is_valid_order_param ? $orderby : 'created';
		$order                = strtoupper( $request->get_param( 'order' ) );

		$args['orderBy'] = $orderby;
		$args['order']   = $order;

		return $args;
	}
	/**
	 * Change user perference.
	 *
	 * @param  array           $data array.
	 * @param  WP_REST_Request $request post object.
	 * @return array $data sorted array.
	 */
	public function sort_new_data( $data, WP_REST_Request $request ) {
		$id      = $request->get_param( 'id' );
		$orderby = $request->get_param( 'orderby' );
		$order   = strtoupper( $request->get_param( 'order' ) );

		if ( 'query' !== $orderby ) {

			$data['rankingKeywords'] = $this->ranking_keyword_array_sort( $data['rankingKeywords'], $order, $orderby );

		}

		if ( 'query' === $orderby ) {

			if ( 'DESC' === $order ) {
				uasort(
					$data['rankingKeywords'],
					function( $a, $b ) use ( $orderby ) {
						return strtolower( $a[ $orderby ] ) < strtolower( $b[ $orderby ] );
					}
				);
			}

			if ( 'ASC' === $order ) {
				uasort(
					$data['rankingKeywords'],
					function( $a, $b ) use ( $orderby ) {
						return strtolower( $a[ $orderby ] ) > strtolower( $b[ $orderby ] );
					}
				);
			}
		}

		return $data;
	}

	/**
	 * Sort array for ranking keyword by order and orderby
	 *
	 * @param  array    $arr array.
	 *
	 * @param  Variable $arr_order is order direction.
	 *
	 * @param  Variable $arr_orderby is key for sort.
	 */
	public function ranking_keyword_array_sort( $arr, $arr_order, $arr_orderby ) {

		if ( 'DESC' === $arr_order ) {
			uasort(
				$arr,
				function( $a, $b ) use ( $arr_orderby ) {
					return $a[ $arr_orderby ]['total'] < $b[ $arr_orderby ]['total'];
				}
			);
		}

		if ( 'ASC' === $arr_order ) {
			uasort(
				$arr,
				function( $a, $b ) use ( $arr_orderby ) {
					return $a[ $arr_orderby ]['total'] > $b[ $arr_orderby ]['total'];
				}
			);
		}

		return $arr;
	}
	/**
	 * Get posts by objects.
	 *
	 * @param  boolean         $result Check.
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return array Posts rows.
	 */
	public function get_posts_rows_by_objects( $result, WP_REST_Request $request ) {
		$per_page  = 25;
		$offset    = ( $request->get_param( 'page' ) - 1 ) * $per_page;
		$orderby   = $request->get_param( 'orderby' );
		$order     = strtoupper( $request->get_param( 'order' ) );
		$objects   = Stats::get()->get_objects_by_score( $request );
		$objects   = Links::get_links_by_objects( $objects );
		$pages     = isset( $objects['rows'] ) ? \array_keys( $objects['rows'] ) : [];
		$pageviews = Pageviews::get_pageviews( [ 'pages' => $pages ] );
		$pageviews = Stats::get()->set_page_as_key( $pageviews['rows'] );
		$console   = Stats::get()->get_analytics_data(
			[
				'orderBy'   => 'diffImpressions',
				'pageview'  => true,
				'offset'    => 0, // Here offset should always zero.
				'perpage'   => ! empty( $objects['rowsFound'] ) ? $objects['rowsFound'] : 0,
				'sub_where' => " AND page IN ('" . join( "', '", $pages ) . "')",
			]
		);

		$new_rows = [];
		if ( ! empty( $objects['rows'] ) ) {
			foreach ( $objects['rows'] as $object ) {
				$page = $object['page'];

				if ( isset( $pageviews[ $page ] ) ) {
					$object['pageviews'] = [
						'total'      => (int) $pageviews[ $page ]['pageviews'],
						'difference' => (int) $pageviews[ $page ]['difference'],
					];
				}

				if ( isset( $console[ $page ] ) ) {
					$object = \array_merge( $console[ $page ], $object );
				}

				if ( ! isset( $object['links'] ) ) {
					$object['links'] = new stdClass();
				}

				$new_rows[ $page ] = $object;
			}
		}

		$history  = $this->get_graph_data_for_pages( $pages );
		$new_rows = Stats::get()->set_page_position_graph( $new_rows, $history );

		if ( in_array( $orderby, [ 'position', 'clicks', 'pageviews', 'impressions' ], true ) ) {
			$new_rows = $this->analytics_array_sort( $new_rows, $order, $orderby );
		}
		$count = count( $new_rows );

		if ( $offset + 25 <= $count ) {
			$new_rows = array_slice( $new_rows, $offset, 25 );

		} else {
			$rest     = $count - $offset;
			$new_rows = array_slice( $new_rows, $offset, $rest );
		}
		if ( empty( $new_rows ) ) {
			$new_rows['response'] = 'No Data';
		}
		return [
			'rows'      => $new_rows,
			'rowsFound' => ! empty( $objects['rowsFound'] ) ? $objects['rowsFound'] : 0,
		];
	}

	/**
	 * Sort array by order and orderby
	 *
	 * @param  array    $arr array.
	 *
	 * @param  Variable $arr_order is order direction.
	 *
	 * @param  Variable $arr_orderby is key for sort.
	 *
	 * @return $arr sorted array
	 */
	public function analytics_array_sort( $arr, $arr_order, $arr_orderby ) {

		if ( 'DESC' === $arr_order ) {
			uasort(
				$arr,
				function( $a, $b ) use ( $arr_orderby ) {

					if ( false === array_key_exists( $arr_orderby, $a ) ) {
						$a[ $arr_orderby ] = [ 'total' => '0' ];
					}
					if ( false === array_key_exists( $arr_orderby, $b ) ) {
						$b[ $arr_orderby ] = [ 'total' => '0' ];
					}

					return $a[ $arr_orderby ]['total'] < $b[ $arr_orderby ]['total'];
				}
			);
		}

		if ( 'ASC' === $arr_order ) {
			uasort(
				$arr,
				function( $a, $b ) use ( $arr_orderby ) {

					if ( false === array_key_exists( $arr_orderby, $a ) ) {
						$a[ $arr_orderby ] = [ 'total' => '0' ];
					}
					if ( false === array_key_exists( $arr_orderby, $b ) ) {
						$b[ $arr_orderby ] = [ 'total' => '0' ];
					}

					return $a[ $arr_orderby ]['total'] > $b[ $arr_orderby ]['total'];
				}
			);
		}

		return $arr;
	}
	/**
	 * Get ranking keywords data and append it to existing post data.
	 *
	 * @param  object $post Post object.
	 * @return object
	 */
	public function add_ranking_keywords( $post ) {
		$page    = $post->page;
		$data    = Stats::get()->get_analytics_data(
			[
				'dimension' => 'query',
				'offset'    => 0,
				'perpage'   => 20,
				'orderBy'   => 'impressions',
				'sub_where' => "AND page = '{$page}'",
			]
		);
		$history = Keywords::get()->get_graph_data_for_keywords( \array_keys( $data ) );

		$post->rankingKeywords = Stats::get()->set_query_position( $data, $history ); // phpcs:ignore

		return $post;
	}

	/**
	 * Append backlinks data into existing post data.
	 *
	 * @param  object $post  Post object.
	 * @return object
	 */
	public function add_backlinks( $post ) {
		$post->backlinks = [
			'total'      => 0,
			'previous'   => 0,
			'difference' => 0,
		];

		return $post;
	}

	/**
	 * Append badges data into existing post data.
	 *
	 * @param  object $post  Post object.
	 * @return object
	 */
	public function add_badges( $post ) {
		$post->badges = [
			'clicks'      => $this->get_position_for_badges( 'clicks', $post->page ),
			'traffic'     => $this->get_position_for_badges( 'traffic', $post->page ),
			'keywords'    => $this->get_position_for_badges( 'query', $post->page ),
			'impressions' => $this->get_position_for_badges( 'impressions', $post->page ),
		];

		return $post;
	}

	/**
	 * Get position for badges.
	 *
	 * @param  string $column Column name.
	 * @param  string $page   Page url.
	 * @return integer
	 */
	public function get_position_for_badges( $column, $page ) {
		$start = strtotime( '-30 days ', Stats::get()->end );
		if ( 'traffic' === $column ) {
			$rows = DB::traffic()
				->select( 'page' )
				->selectSum( 'pageviews', 'pageviews' )
				->whereBetween( 'created', [ $start, Stats::get()->end_date ] )
				->groupBy( 'page' )
				->orderBy( 'pageviews', 'DESC' )
				->limit( 5 );
		} else {
			$rows = DB::analytics()
				->select( 'page' )
				->whereBetween( 'created', [ $start, Stats::get()->end_date ] )
				->groupBy( 'page' )
				->orderBy( $column, 'DESC' )
				->limit( 5 );
		}

		if ( 'impressions' === $column || 'click' === $column ) {
			$rows->selectSum( $column, $column );
		}

		if ( 'query' === $column ) {
			$rows->selectCount( 'DISTINCT(query)', 'keywords' );
		}

		$rows = $rows->get( ARRAY_A );
		foreach ( $rows as $index => $row ) {
			if ( $page === $row['page'] ) {
				return $index + 1;
			}
		}

		return 99;
	}

	/**
	 * Append analytics graph data into existing post data.
	 *
	 * @param  object $post Post object.
	 * @return object
	 */
	public function get_graph_data_for_post( $post ) {
		global $wpdb;

		// Step1. Get splitted date intervals for graph within selected date range.
		$data          = new stdClass();
		$page          = $post->page;
		$intervals     = Stats::get()->get_intervals();
		$sql_daterange = Stats::get()->get_sql_date_intervals( $intervals );

		// Step2. Get analytics data summary for each splitted date intervals.
		$query   = $wpdb->prepare(
			"SELECT DATE_FORMAT( created, '%%Y-%%m-%%d') as date, SUM( clicks ) as clicks, SUM(impressions) as impressions, ROUND( AVG(ctr), 2 ) as ctr, {$sql_daterange}
			FROM {$wpdb->prefix}rank_math_analytics_gsc
			WHERE created BETWEEN %s AND %s AND page LIKE '%{$page}'
			GROUP BY range_group",
			Stats::get()->start_date,
			Stats::get()->end_date
		);
		$metrics = $wpdb->get_results( $query );

		// Step3. Get position data summary for each splitted date intervals.
		$query     = $wpdb->prepare(
			"SELECT page, MAX(CONCAT(t.uid, ':', t.range_group)) as range_group FROM
				(SELECT page, MAX(CONCAT(page, ':', DATE(created), ':', LPAD((100 - position), 3, '0'))) as uid, {$sql_daterange}
				FROM {$wpdb->prefix}rank_math_analytics_gsc
				WHERE created BETWEEN %s AND %s AND page LIKE '%{$page}'
				GROUP BY range_group, DATE(created)
				ORDER BY DATE(created) DESC) AS t
			GROUP BY t.range_group",
			Stats::get()->start_date,
			Stats::get()->end_date
		);
		$positions = $wpdb->get_results( $query );
		$positions = Stats::get()->extract_data_from_mixed( $positions, 'range_group', ':', [ 'range_group', 'position', 'date' ] );

		// Step4. Get keywords count for each splitted date intervals.
		$query    = $wpdb->prepare(
			"SELECT DATE_FORMAT( created, '%%Y-%%m-%%d') as date, COUNT(DISTINCT(query)) as keywords, {$sql_daterange}
			FROM {$wpdb->prefix}rank_math_analytics_gsc
			WHERE created BETWEEN %s AND %s AND page LIKE '%{$page}'
			GROUP BY range_group",
			Stats::get()->start_date,
			Stats::get()->end_date
		);
		$keywords = $wpdb->get_results( $query );
		// phpcs:enable

		// Step5. Filter graph data.
		$metrics   = Stats::get()->filter_graph_rows( $metrics );
		$positions = Stats::get()->filter_graph_rows( $positions );
		$keywords  = Stats::get()->filter_graph_rows( $keywords );

		// Step6. Convert types.
		$metrics   = array_map( [ Stats::get(), 'normalize_graph_rows' ], $metrics );
		$positions = array_map( [ Stats::get(), 'normalize_graph_rows' ], $positions );
		$keywords  = array_map( [ Stats::get(), 'normalize_graph_rows' ], $keywords );

		// Step7. Merge all analytics data.
		$data = Stats::get()->get_date_array(
			$intervals['dates'],
			[
				'clicks'      => [],
				'impressions' => [],
				'position'    => [],
				'ctr'         => [],
				'keywords'    => [],
				'pageviews'   => [],
			]
		);

		$data = Stats::get()->get_merge_data_graph( $metrics, $data, $intervals['map'] );
		$data = Stats::get()->get_merge_data_graph( $positions, $data, $intervals['map'] );
		$data = Stats::get()->get_merge_data_graph( $keywords, $data, $intervals['map'] );

		// Step8. Get traffic data in case analytics is connected for each splitted data intervals.
		if ( \RankMath\Google\Analytics::is_analytics_connected() ) {
			$query   = $wpdb->prepare(
				"SELECT DATE_FORMAT( created, '%%Y-%%m-%%d') as date, SUM( pageviews ) as pageviews, {$sql_daterange}
				FROM {$wpdb->prefix}rank_math_analytics_ga
				WHERE created BETWEEN %s AND %s AND page LIKE '%{$page}'
				GROUP BY range_group",
				Stats::get()->start_date,
				Stats::get()->end_date
			);
			$traffic = $wpdb->get_results( $query );

			// Filter graph data.
			$traffic = Stats::get()->filter_graph_rows( $traffic );

			// Convert types.
			$traffic = array_map( [ Stats::get(), 'normalize_graph_rows' ], $traffic );

			$data = Stats::get()->get_merge_data_graph( $traffic, $data, $intervals['map'] );
		}

		$data = Stats::get()->get_graph_data_flat( $data );

		// Step9. Append graph data into existing post data.
		$post->graph = array_values( $data );

		return $post;
	}

	/**
	 * Get posts rows.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array Posts rows.
	 */
	public function get_posts_rows( WP_REST_Request $request ) {
		// Pagination.
		$per_page  = 25;
		$offset    = ( $request->get_param( 'page' ) - 1 ) * $per_page;
		$orderby   = $request->get_param( 'orderby' );
		$post_type = sanitize_key( $request->get_param( 'postType' ) );
		$order     = $request->get_param( 'order' );
		$order     = in_array( $order, [ 'asc', 'desc' ], true ) ? $order : 'desc';
		$order     = strtoupper( $order );

		$post_type_clause = $post_type ? " AND o.object_subtype = '{$post_type}'" : '';
		if ( 'pageviews' === $orderby ) {
			// Get posts order by pageviews.
			$data      = Pageviews::get_pageviews_with_object(
				[
					'order'     => $order,
					'limit'     => "LIMIT {$offset}, {$per_page}",
					'sub_where' => $post_type_clause,
				]
			);
			$pageviews = Stats::get()->set_page_as_key( $data['rows'] );
			$pages     = \array_keys( $pageviews );
			$pages     = array_map( 'esc_sql', $pages );
			$console   = Stats::get()->get_analytics_data(
				[
					'offset'    => 0, // Should set as 0.
					'perpage'   => $per_page,
					'objects'   => false,
					'sub_where' => " AND page IN ('" . join( "', '", $pages ) . "')",
				]
			);

			foreach ( $pageviews as $page => &$pageview ) {
				$pageview['pageviews'] = [
					'total'      => (int) $pageview['pageviews'],
					'difference' => (int) $pageview['difference'],
				];

				if ( isset( $console[ $page ] ) ) {
					unset( $console[ $page ]['pageviews'] );
					$pageview = \array_merge( $pageview, $console[ $page ] );
				}
			}

			$history   = $this->get_graph_data_for_pages( $pages );
			$pageviews = Stats::get()->set_page_position_graph( $pageviews, $history );

			$data['rows'] = $pageviews;

		} else {
			// Get posts order by impressions.
			$data = DB::objects()
				->select( [ 'page', 'title', 'object_id' ] )
				->where( 'is_indexable', 1 );
			if ( 'title' === $orderby ) {
				$data->orderBy( $orderby, $order )
					->limit( $per_page, $offset );
			}
			$data = $data->get( ARRAY_A );

			$pages  = Stats::get()->set_page_as_key( $data );
			$params = \array_keys( $pages );
			$params = array_map( 'esc_sql', $params );

			$args = [
				'dimension' => 'page',
				'offset'    => 0,
				'perpage'   => 20000,
				'sub_where' => " AND page IN ('" . join( "', '", $params ) . "')",
			];

			if ( 'title' !== $orderby ) {
				$args['orderBy'] = $orderby;
				$args['order']   = $order;
			}

			$rows = Stats::get()->get_analytics_data( $args );

			if ( 'title' !== $orderby ) {
				foreach ( $pages as $page => $row ) {
					if ( ! isset( $rows[ $page ] ) ) {
						$rows[ $page ] = $row;
					} else {
						$rows[ $page ] = \array_merge( $rows[ $page ], $row );
					}
				}

				$history           = $this->get_graph_data_for_pages( $params );
				$data['rows']      = Stats::get()->set_page_position_graph( $rows, $history );
				$data['rowsFound'] = count( $pages );

				// Filter array by $offset, $perpage value.
				$data['rows'] = array_slice( $data['rows'], $offset, $per_page, true );

			} else {
				foreach ( $pages as $page => &$row ) {
					if ( isset( $rows[ $page ] ) ) {
						$row = \array_merge( $row, $rows[ $page ] );
					}
				}

				$rows_found = DB::objects()
					->selectCount( 'page' )
					->where( 'is_indexable', 1 )
					->getVar();

				$history           = $this->get_graph_data_for_pages( $params );
				$data['rows']      = Stats::get()->set_page_position_graph( $pages, $history );
				$data['rowsFound'] = $rows_found;
			}

			// Get fetched page info again.
			$pages  = Stats::get()->set_page_as_key( $data['rows'] );
			$params = \array_keys( $pages );
			$params = array_map( 'esc_sql', $params );

			// Get pageviews info.
			$pageviews = Pageviews::get_pageviews_with_object(
				[
					'limit'     => "LIMIT 0, {$per_page}",
					'sub_where' => " AND o.page IN ('" . join( "', '", $params ) . "')" . $post_type_clause,
				]
			);
			$pageviews = Stats::get()->set_page_as_key( $pageviews['rows'] );

			// Merge pageview info into main data.
			foreach ( $data['rows'] as $page => &$row ) {
				if ( isset( $pageviews[ $page ] ) ) {
					$pageview = [
						'pageviews' => [
							'total'      => (int) $pageviews[ $page ]['pageviews'],
							'difference' => (int) $pageviews[ $page ]['difference'],
						],
					];
					$row      = \array_merge( $row, $pageview );
				}
			}
		}
		if ( empty( $data ) ) {
			$data['response'] = 'No Data';
		}
		return $data;
	}

	/**
	 * Get top 5 winning posts.
	 *
	 * @return array
	 */
	public function get_winning_posts() {
		global $wpdb;

		$cache_key = Stats::get()->get_cache_key( 'winning_posts', Stats::get()->days . 'days' );
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return $cache;
		}

		$rows = Stats::get()->get_analytics_data(
			[
				'order'    => 'ASC',
				'objects'  => true,
				'pageview' => true,
				'offset'   => 0,
				'perpage'  => 5,
				'type'     => 'win',
			]
		);

		$history = $this->get_graph_data_for_pages( \array_keys( $rows ) );
		$rows    = Stats::get()->set_page_position_graph( $rows, $history );

		if ( empty( $rows ) ) {
			$rows['response'] = 'No Data';
		}
		set_transient( $cache_key, $rows, DAY_IN_SECONDS );

		return $rows;
	}

	/**
	 * Get top 5 losing posts.
	 *
	 * @return object
	 */
	public function get_losing_posts() {
		global $wpdb;

		$cache_key = Stats::get()->get_cache_key( 'losing_posts', Stats::get()->days . 'days' );
		$cache     = get_transient( $cache_key );

		if ( false !== $cache ) {
			return $cache;
		}

		$rows = Stats::get()->get_analytics_data(
			[
				'objects'  => true,
				'pageview' => true,
				'offset'   => 0,
				'perpage'  => 5,
				'type'     => 'lose',
			]
		);

		$history = $this->get_graph_data_for_pages( \array_keys( $rows ) );
		$rows    = Stats::get()->set_page_position_graph( $rows, $history );
		if ( empty( $rows ) ) {
			$rows['response'] = 'No Data';
		}
		set_transient( $cache_key, $rows, DAY_IN_SECONDS );

		return $rows;
	}

	/**
	 * Get graph data for pages.
	 *
	 * @param array $pages Pages to get data for.
	 *
	 * @return array
	 */
	public function get_graph_data_for_pages( $pages ) {
		global $wpdb;

		$intervals     = Stats::get()->get_intervals();
		$sql_daterange = Stats::get()->get_sql_date_intervals( $intervals );
		$pages         = \array_map( 'esc_sql', $pages );
		$pages         = '(\'' . join( '\', \'', $pages ) . '\')';

		$query = $wpdb->prepare(
			"SELECT page, date, MAX(CONCAT(t.uid, ':', t.range_group)) as range_group FROM
				( SELECT page, DATE_FORMAT( created,'%%Y-%%m-%%d') as date, MAX( CONCAT( page, ':', DATE( created ), ':', LPAD( ( 100 - position ), 3, '0' ) ) ) as uid, {$sql_daterange}
				FROM {$wpdb->prefix}rank_math_analytics_gsc
				WHERE page IN {$pages} AND created BETWEEN %s AND %s
				GROUP BY page, range_group, DATE(created)
				ORDER BY page ASC, DATE(created) DESC) AS t
			GROUP BY t.page, t.range_group
			ORDER BY date ASC",
			Stats::get()->start_date,
			Stats::get()->end_date
		);
		$data  = $wpdb->get_results( $query );

		$data = Stats::get()->extract_data_from_mixed( $data, 'range_group', ':', [ 'range_group', 'position' ] );
		$data = Stats::get()->filter_graph_rows( $data );

		return array_map( [ Stats::get(), 'normalize_graph_rows' ], $data );
	}
}
