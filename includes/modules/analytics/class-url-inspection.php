<?php
/**
 * URL Inspection features.
 *
 * @since      3.0.8
 * @package    RankMathPro
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMathPro\Analytics\DB;

/**
 * Url_Inspection class.
 */
class Url_Inspection {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/analytics/url_inspection_map_properties', 'map_inspection_properties', 10, 2 );
		$this->action( 'rank_math/analytics/get_inspections_query', 'add_filter_params', 10, 2 );
		$this->action( 'rank_math/analytics/get_inspections_count_query', 'add_filter_params', 10, 2 );
		$this->filter( 'rank_math/analytics/post_data', 'add_index_verdict_data', 10, 2 );

		// Enqueue.
		$this->action( 'rank_math/admin/enqueue_scripts', 'enqueue_scripts' );
	}

	/**
	 * Filter to alter the where clause used in the get_inspections function.
	 *
	 * @param string $where  WHERE clause.
	 * @param array  $params Parameters.
	 *
	 * @return string
	 */
	public function add_filter_params( $query, $params ) {
		if ( empty( $params['indexingFilter'] ) ) {
			return;
		}

		$table = DB::inspections()->table;
		$query->where( "$table.coverage_state", $params['indexingFilter'] );
	}

	/**
	 * Map properties in the API result to columns in the database.
	 *
	 * @param array $normalized Normalized data.
	 * @param array $incoming   Incoming data from the API.
	 *
	 * @return array
	 */
	public function map_inspection_properties( $normalized, $incoming ) {
		$handler = \RankMath\Google\Url_Inspection::get();

		$handler->assign_inspection_value( $incoming, 'richResultsResult.detectedItems', 'rich_results_items', $normalized );
		$handler->assign_inspection_value( $incoming, 'indexStatusResult.lastCrawlTime', 'last_crawl_time', $normalized );

		// Store the raw response, too.
		$normalized['raw_api_response'] = wp_json_encode( $incoming );

		return $normalized;
	}

	/**
	 * Get stats for "Presence on Google" widget.
	 */
	public static function get_presence_stats() {
		return DB::get_presence_stats();
	}

	/**
	 * Get stats for "Top Statuses" widget.
	 */
	public static function get_status_stats() {
		return DB::get_status_stats();
	}

	/**
	 * Change user perference.
	 *
	 * @param  array           $data array.
	 * @param  WP_REST_Request $request post object.
	 * @return array $data sorted array.
	 */
	public function add_index_verdict_data( $data, \WP_REST_Request $request ) {
		if ( ! Helper::can_add_index_status() ) {
			return $data;
		}

		$data['indexStatus'] = DB::get_index_verdict( $data['page'] );
		return $data;
	}

	/**
	 * Enqueue scripts.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( 'rank-math_page_rank-math-analytics' !== $screen->id ) {
			return;
		}

		$submit_url = add_query_arg(
			[
				'page'        => 'instant-indexing',
				'tab'         => 'console',
				'apiaction'   => 'update',
				'_wpnonce'    => wp_create_nonce( 'giapi-action' ),
				'apipostid[]' => '',

			],
			admin_url( 'admin.php' )
		);
		$settings = get_option( 'rank-math-options-instant-indexing', [] );

		Helper::add_json(
			'instantIndexingSupport',
			[
				'isPluginActive'     => is_plugin_active( 'fast-indexing-api/instant-indexing.php' ),
				'isGoogleConfigured' => ! empty( $settings['json_key'] ),
				'submitUrl'          => $submit_url,
			]
		);
	}
}
