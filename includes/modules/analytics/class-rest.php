<?php
/**
 * The Global functionality of the plugin.
 *
 * Defines the functionality loaded on admin.
 *
 * @since      1.0.15
 * @package    RankMathPro
 * @subpackage RankMathPro\Rest
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use WP_Error;
use WP_REST_Server;
use RankMath\Helper;
use RankMath\Helpers\DB as DB_Helper;
use WP_REST_Request;
use WP_REST_Controller;
use RankMath\Admin\Admin_Helper;
use RankMathPro\Google\PageSpeed;
use RankMath\SEO_Analysis\SEO_Analyzer;
use RankMathPro\Analytics\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Rest class.
 */
class Rest extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = \RankMath\Rest\Rest_Helper::BASE . '/an';
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/getKeywordPages',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ Keywords::get(), 'get_keyword_pages' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/postsOverview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_posts_overview' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/getTrackedKeywords',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tracked_keywords' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/getTrackedKeywordsRows',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tracked_keywords_rows' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/getTrackedKeywordSummary',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tracked_keyword_summary' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/trackedKeywordsOverview',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_tracked_keywords_overview' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/addTrackKeyword',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'add_track_keyword' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/autoAddFocusKeywords',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'auto_add_focus_keywords' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/removeTrackKeyword',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'remove_track_keyword' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
		register_rest_route(
			$this->namespace,
			'/deleteTrackedKeywords',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'delete_all_tracked_keywords' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/getPagespeed',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'get_pagespeed' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/postsRows',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ Posts::get(), 'get_posts_rows' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/inspectionStats',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_inspection_stats' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
	}

	/**
	 * Determines if the current user can manage analytics.
	 *
	 * @return true
	 */
	public function has_permission() {
		return current_user_can( 'rank_math_analytics' );
	}

	/**
	 * Get top 5 winning and losing posts rows.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_posts_overview( WP_REST_Request $request ) {
		return rest_ensure_response(
			[
				'winningPosts' => Posts::get()->get_winning_posts(),
				'losingPosts'  => Posts::get()->get_losing_posts(),
			]
		);
	}

	/**
	 * Get tracked keywords rows.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tracked_keywords( WP_REST_Request $request ) {
		return rest_ensure_response(
			[ 'rows' => Keywords::get()->get_tracked_keywords() ]
		);
	}

	/**
	 * Get tracked keywords rows.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	public function get_tracked_keywords_rows( WP_REST_Request $request ) {
		return Keywords::get()->get_tracked_keywords_rows( $request );
	}

	/**
	 * Get tracked keywords summary.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tracked_keyword_summary( WP_REST_Request $request ) {
		\RankMathPro\Admin\Api::get()->get_settings();

		return rest_ensure_response( Keywords::get()->get_tracked_keywords_summary() );
	}

	/**
	 * Get top 5 winning and losing tracked keywords overview.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_tracked_keywords_overview( WP_REST_Request $request ) {
		return rest_ensure_response(
			[
				'winningKeywords' => Keywords::get()->get_tracked_winning_keywords(),
				'losingKeywords'  => Keywords::get()->get_tracked_losing_keywords(),
			]
		);
	}
	/**
	 * Add track keyword to DB.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function auto_add_focus_keywords( WP_REST_Request $request ) {
		$data              = $request->get_param( 'data' );
		$secondary_keyword = ! empty( $data['secondary_keyword'] );
		$post_types        = ! empty( $data['post_types'] ) ? $data['post_types'] : [];

		$all_opts = rank_math()->settings->all_raw();
		$general  = $all_opts['general'];

		$general['auto_add_focus_keywords'] = $data;
		Helper::update_all_settings( $general, null, null );

		if ( empty( $post_types ) ) {
			return false;
		}

		global $wpdb;
		$focus_keywords = $wpdb->get_col(
			"SELECT {$wpdb->postmeta}.meta_value FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} 
			ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id 
			WHERE 1=1
			AND {$wpdb->posts}.post_type IN ('" . implode( "', '", esc_sql( $post_types ) ) . "')
			AND {$wpdb->posts}.post_status = 'publish'
			AND {$wpdb->postmeta}.meta_key = 'rank_math_focus_keyword'
			"
		);

		$keywords_data = [];
		foreach ( $focus_keywords as $focus_keyword ) {
			$keywords = explode( ',', mb_strtolower( $focus_keyword ) );
			if ( $secondary_keyword ) {
				$keywords_data = array_merge( $keywords, $keywords_data );
			} else {
				$keywords_data[] = current( $keywords );
			}
		}

		if ( empty( $keywords_data ) ) {
			return false;
		}

		return DB::bulk_insert_query_focus_keyword_data( array_unique( $keywords_data ) );
	}
	/**
	 * Add track keyword to DB.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function add_track_keyword( WP_REST_Request $request ) {
		$keywords = $request->get_param( 'keyword' );
		$keywords = mb_strtolower( filter_var( $keywords, FILTER_SANITIZE_FULL_SPECIAL_CHARS, FILTER_FLAG_NO_ENCODE_QUOTES ) );
		if ( empty( $keywords ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no keyword found.', 'rank-math-pro' )
			);
		}

		$keywords = html_entity_decode( $keywords );

		// Check remain keywords count can be added.
		$total_keywords = Keywords::get()->get_tracked_keywords_count();
		$new_keywords   = Keywords::get()->extract_addable_track_keyword( $keywords );
		$keywords_count = count( $new_keywords );
		if ( $keywords_count <= 0 ) {
			return false;
		}

		$summary = Keywords::get()->get_tracked_keywords_quota();
		$remain  = $summary['available'] - $total_keywords;
		if ( $remain <= 0 ) {
			return false;
		}

		// Add keywords.
		Keywords::get()->add_track_keyword( $new_keywords );

		$registered = Admin_Helper::get_registration_data();
		if ( ! $registered || empty( $registered['username'] ) || empty( $registered['api_key'] ) ) {
			return false;
		}

		// Send total keywords count to RankMath.
		$total_keywords = Keywords::get()->get_tracked_keywords_count();
		$response       = \RankMathPro\Admin\Api::get()->keywords_info( $registered['username'], $registered['api_key'], $total_keywords );
		if ( $response ) {
			update_option( 'rank_math_keyword_quota', $response );
		}

		return true;
	}

	/**
	 * Remove track keyword from DB.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function remove_track_keyword( WP_REST_Request $request ) {
		$keyword = $request->get_param( 'keyword' );
		if ( empty( $keyword ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no keyword found.', 'rank-math-pro' )
			);
		}

		// Remove keyword.
		Keywords::get()->remove_track_keyword( $keyword );

		$registered = Admin_Helper::get_registration_data();
		if ( ! $registered || empty( $registered['username'] ) || empty( $registered['api_key'] ) ) {
			return false;
		}

		// Send total keywords count to RankMath.
		$total_keywords = Keywords::get()->get_tracked_keywords_count();
		$response       = \RankMathPro\Admin\Api::get()->keywords_info( $registered['username'], $registered['api_key'], $total_keywords );
		if ( $response ) {
			update_option( 'rank_math_keyword_quota', $response );
		}

		return true;
	}

	/**
	 * Delete all the manually tracked keywords.
	 */
	public function delete_all_tracked_keywords() {

		// Delete all keywords.
		Keywords::get()->delete_all_tracked_keywords();

		$registered = Admin_Helper::get_registration_data();
		if ( empty( $registered['username'] ) || empty( $registered['api_key'] ) ) {
			return false;
		}

		// Send total keywords count as 0 to RankMath.
		$response = \RankMathPro\Admin\Api::get()->keywords_info( $registered['username'], $registered['api_key'], 0 );
		if ( $response ) {
			update_option( 'rank_math_keyword_quota', $response );
		}

		return true;
	}

	/**
	 * Check if keyword can be added.
	 *
	 * @param  string $keywords Comma separated keywords.
	 * @return bool True if remain keyword count is larger than zero.
	 */
	private function can_add_keyword( $keywords = '' ) {
		// Check remain keywords count can be added by supposing current keyword is added.
		$total_keywords = Keywords::get()->get_tracked_keywords_count();
		$new_keywords   = Keywords::get()->extract_addable_track_keyword( $keywords );
		$keywords_count = count( $new_keywords );
		$summary        = Keywords::get()->get_tracked_keywords_quota();
		$remain         = $summary['available'] - $total_keywords - $keywords_count;

		return $remain >= 0;
	}

	/**
	 * Get page speed data.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|bool Pagespeed info on success, false on failure.
	 */
	public function get_pagespeed( WP_REST_Request $request ) {
		$id = $request->get_param( 'id' );
		if ( empty( $id ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no record id found.', 'rank-math-pro' )
			);
		}

		$post_id = $request->get_param( 'objectID' );
		if ( empty( $id ) ) {
			return new WP_Error(
				'param_value_empty',
				esc_html__( 'Sorry, no post id found.', 'rank-math-pro' )
			);
		}

		if ( Helper::is_localhost() ) {
			return [
				'page_score'          => 0,
				'desktop_interactive' => 0,
				'desktop_pagescore'   => 0,
				'mobile_interactive'  => 0,
				'mobile_pagescore'    => 0,
				'pagespeed_refreshed' => current_time( 'mysql' ),
			];
		}

		$url = get_permalink( $post_id );
		$pre = apply_filters( 'rank_math/analytics/pre_pagespeed', false, $post_id, $request );
		if ( false !== $pre ) {
			return $pre;
		}

		$force        = \boolval( $request->get_param( 'force' ) );
		$is_admin_bar = \boolval( $request->get_param( 'isAdminBar' ) );
		if ( $force || ( ! $is_admin_bar && $this->should_update_pagespeed( $id ) ) ) {
			// Page Score.
			$analyzer = new SEO_Analyzer();
			$analyzer->set_url();

			$score  = $analyzer->get_page_score( $url );
			$update = [];
			if ( $score > 0 ) {
				$update['page_score'] = $score;
			}

			// PageSpeed desktop.
			$desktop = PageSpeed::get_pagespeed( $url, 'desktop' );
			if ( ! empty( $desktop ) ) {
				$update                        = \array_merge( $update, $desktop );
				$update['pagespeed_refreshed'] = current_time( 'mysql' );
			}

			// PageSpeed mobile.
			$mobile = PageSpeed::get_pagespeed( $url, 'mobile' );
			if ( ! empty( $mobile ) ) {
				$update                        = \array_merge( $update, $mobile );
				$update['pagespeed_refreshed'] = current_time( 'mysql' );
			}
		}

		if ( ! empty( $update ) ) {
			$update['id']        = $id;
			$update['object_id'] = $post_id;
			DB::update_object( $update );
		}

		return empty( $update ) ? false : $update;
	}

	/**
	 * Should update pagespeed record.
	 *
	 * @param  int $id      Database row id.
	 * @return bool
	 */
	private function should_update_pagespeed( $id ) {
		$record = DB::objects()->where( 'id', $id )->one();

		return \time() > ( \strtotime( $record->pagespeed_refreshed ) + ( DAY_IN_SECONDS * 7 ) );
	}

	/**
	 * Get inspection stats.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_inspection_stats() {
		// Early Bail!!
		if ( ! DB_Helper::check_table_exists( 'rank_math_analytics_inspections' ) ) {
			return [
				'presence' => [],
				'status'   => [],
			];
		}

		return rest_ensure_response(
			[
				'presence' => Url_Inspection::get_presence_stats(),
				'status'   => Url_Inspection::get_status_stats(),
			]
		);
	}
}
