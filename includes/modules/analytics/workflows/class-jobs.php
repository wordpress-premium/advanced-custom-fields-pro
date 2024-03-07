<?php
/**
 * Jobs.
 *
 * @since      1.0.54
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics\Workflow;

use DateTime;
use Exception;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMathPro\Analytics\DB;
use RankMath\Analytics\Workflow\Base;
use RankMath\Analytics\DB as AnalyticsDB;
use RankMathPro\Google\Adsense;
use RankMath\Google\Analytics;
use RankMath\Analytics\Workflow\Jobs as AnalyticsJobs;

defined( 'ABSPATH' ) || exit;

/**
 * Jobs class.
 */
class Jobs {

	use Hooker;

	/**
	 * Is an Analytics account connected?
	 *
	 * @var boolean
	 */
	private $analytics_connected = false;

	/**
	 * Is an AdSense account connected?
	 *
	 * @var boolean
	 */
	private $adsense_connected = false;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Jobs
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Jobs ) ) {
			$instance = new Jobs();
			$instance->hooks();
		}

		return $instance;
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		$this->analytics_connected = Analytics::is_analytics_connected();
		$this->adsense_connected   = \RankMathPro\Google\Adsense::is_adsense_connected();

		// Check missing data for analytics and adsense.
		$this->action( 'rank_math/analytics/data_fetch', 'data_fetch' );

		// Data Fetcher.
		if ( $this->adsense_connected ) {
			$this->filter( 'rank_math/analytics/get_adsense_days', 'get_adsense_days' );
			$this->action( 'rank_math/analytics/get_adsense_data', 'get_adsense_data', 10, 2 );
		}

		if ( $this->analytics_connected ) {
			$this->action( 'rank_math/analytics/get_analytics_days', 'get_analytics_days' );
			$this->action( 'rank_math/analytics/get_analytics_data', 'get_analytics_data' );
			$this->action( 'rank_math/analytics/handle_analytics_response', 'handle_analytics_response' );
			$this->action( 'rank_math/analytics/clear_cache', 'clear_cache' );
		}

		// Cache.
		$this->action( 'rank_math/analytics/purge_cache', 'purge_cache' );
		$this->action( 'rank_math/analytics/delete_by_days', 'delete_by_days' );
		$this->action( 'rank_math/analytics/delete_data_log', 'delete_data_log' );
	}

	/**
	 * Check missing data for analytics and adsense. Perform this task periodically.
	 */
	public function data_fetch() {
		if ( $this->analytics_connected ) {
			AnalyticsJobs::get()->check_for_missing_dates( 'analytics' );
		}

		if ( $this->adsense_connected ) {
			AnalyticsJobs::get()->check_for_missing_dates( 'adsense' );
		}
	}

	/**
	 * Set the analytics start and end dates.
	 */
	public function get_analytics_days( $args = [] ) {
		$rows = Analytics::get_analytics(
			[
				'start_date' => $args['start_date'],
				'end_date'   => $args['end_date'],
			],
			true
		);
		if ( is_wp_error( $rows ) || empty( $rows ) ) {
			return [];
		}

		$empty_dates = get_option( 'rank_math_analytics_empty_dates', [] );
		$dates       = [];

		foreach ( $rows as $row ) {
			$date = '';

			// GA4
			if ( isset( $row['dimensionValues'] ) ) {
				$date = $row['dimensionValues'][0]['value'];
			} elseif ( isset( $row['dimensions'] ) ) {
				$date = $row['dimensions'][0];
			}

			if ( ! empty( $date ) ) {
				$date = substr( $date, 0, 4 ) . '-' . substr( $date, 4, 2 ) . '-' . substr( $date, 6, 2 );

				if ( ! AnalyticsDB::date_exists( $date, 'analytics' ) && ! in_array( $date, $empty_dates, true ) ) {
					$dates[] = [
						'start_date' => $date,
						'end_date'   => $date,
					];
				}
			}
		}

		return $dates;
	}

	/**
	 * Get analytics data and save it into database.
	 *
	 * @param string $date Date to fetch data for.
	 */
	public function get_analytics_data( $date ) {
		$rows = Analytics::get_analytics(
			[
				'start_date' => $date,
				'end_date'   => $date,
			]
		);

		if ( is_wp_error( $rows ) || empty( $rows ) ) {
			return [];
		}

		try {
			DB::add_analytics_bulk( $date, $rows );
			return $rows;
		} catch ( Exception $e ) {} // phpcs:ignore
	}

	/**
	 * Set the AdSense start and end dates.
	 */
	public function get_adsense_days( $args = [] ) {
		$dates = [];

		$begin = new DateTime( $args['start_date'] );
		$end   = new DateTime( $args['end_date'] );

		$missing_dates = [];
		for ( $i = $end; $i >= $begin; $i->modify( '-1 day' ) ) {
			$date = $i->format( 'Y-m-d' );
			if ( ! AnalyticsDB::date_exists( $date, 'adsense' ) ) {
				$missing_dates[] = $date;
			}
		}

		if ( empty( $missing_dates ) ) {
			$dates[] = [
				'start_date' => $args['start_date'],
				'end_date'   => $args['end_date'],
			];

			return $dates;
		}

		// Request for one date range because its not large data to send individual request for each date.
		$dates[] = [
			'start_date' => $missing_dates[ count( $missing_dates ) - 1 ],
			'end_date'   => $missing_dates[0],
		];

		return $dates;
	}

	/**
	 * Get adsense data and save it into database.
	 *
	 * @param string $start_date The start date to fetch.
	 * @param string $end_date   The end date to fetch.
	 */
	public function get_adsense_data( $start_date = '', $end_date = '' ) {
		$rows = Adsense::get_adsense(
			[
				'start_date' => $start_date,
				'end_date'   => $end_date,
			]
		);
		if ( is_wp_error( $rows ) || empty( $rows ) ) {
			return [];
		}

		try {
			DB::add_adsense( $rows );
			return $rows;
		} catch ( Exception $e ) {} // phpcs:ignore
	}

	/**
	 * Handlle analytics response.
	 *
	 * @param array $data API request and response data.
	 */
	public function handle_analytics_response( $data = [] ) {
		if ( 200 !== $data['code'] ) {
			return;
		}

		if ( isset( $data['formatted_response']['rows'] ) && ! empty( $data['formatted_response']['rows'] ) ) {
			return;
		}

		$dates = get_option( 'rank_math_analytics_empty_dates', [] );
		if ( ! $dates ) {
			$dates = [];
		}

		$dates[] = $data['args']['dateRanges'][0]['startDate'];
		$dates[] = $data['args']['dateRanges'][0]['endDate'];

		$dates = array_unique( $dates );

		update_option( 'rank_math_analytics_empty_dates', $dates );
	}

	/**
	 * Clear cache.
	 */
	public function clear_cache() {
		global $wpdb;

		// Delete all useless data from analytics data table.
		$wpdb->get_results( "DELETE FROM {$wpdb->prefix}rank_math_analytics_ga WHERE page NOT IN ( SELECT page from {$wpdb->prefix}rank_math_analytics_objects )" );
	}

	/**
	 * Purge cache.
	 *
	 * @param object $table Table insance.
	 */
	public function purge_cache( $table ) {
		$table->whereLike( 'option_name', 'losing_posts' )->delete();
		$table->whereLike( 'option_name', 'winning_posts' )->delete();
		$table->whereLike( 'option_name', 'losing_keywords' )->delete();
		$table->whereLike( 'option_name', 'winning_keywords' )->delete();
		$table->whereLike( 'option_name', 'tracked_keywords_summary' )->delete();
	}

	/**
	 * Delete analytics and adsense data by days.
	 *
	 * @param  int $days Decide whether to delete all or delete 90 days data.
	 */
	public function delete_by_days( $days ) {
		if ( -1 === $days ) {
			if ( $this->analytics_connected ) {
				DB::traffic()->truncate();
			}
			if ( $this->adsense_connected ) {
				DB::adsense()->truncate();
			}

			return;
		}

		$start = date_i18n( 'Y-m-d H:i:s', strtotime( '-1 days' ) );
		$end   = date_i18n( 'Y-m-d H:i:s', strtotime( '-' . $days . ' days' ) );

		if ( $this->analytics_connected ) {
			DB::traffic()->whereBetween( 'created', [ $end, $start ] )->delete();
		}

		if ( $this->adsense_connected ) {
			DB::adsense()->whereBetween( 'created', [ $end, $start ] )->delete();
		}
	}

	/**
	 * Delete record for comparison.
	 *
	 * @param string $start Start date.
	 */
	public function delete_data_log( $start ) {
		if ( $this->analytics_connected ) {
			DB::traffic()->where( 'created', '<', $start )->delete();
		}

		if ( $this->adsense_connected ) {
			DB::adsense()->where( 'created', '<', $start )->delete();
		}
	}

}
