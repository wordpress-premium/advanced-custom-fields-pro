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

use Exception;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMathPro\Analytics\DB;
use RankMath\Analytics\DB as AnalyticsDB;
use RankMathPro\Google\Adsense;
use RankMathPro\Google\Analytics;

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
		$this->analytics_connected = \RankMath\Google\Analytics::is_analytics_connected();
		$this->adsense_connected   = \RankMathPro\Google\Adsense::is_adsense_connected();

		// Check missing data for analytics and adsense.
		$this->action( 'rank_math/analytics/data_fetch', 'data_fetch' );

		// Data Fetcher.
		if ( $this->adsense_connected ) {
			$this->action( 'rank_math/analytics/get_adsense_data', 'get_adsense_data' );
		}

		if ( $this->analytics_connected ) {
			$this->action( 'rank_math/analytics/get_analytics_data', 'get_analytics_data' );
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
			$this->check_for_missing_dates( 'analytics' );
		}

		if ( $this->adsense_connected ) {
			$this->check_for_missing_dates( 'adsense' );
		}
	}

	/**
	 * Get analytics data and save it into database.
	 *
	 * @param string $date Date to fetch data for.
	 */
	public function get_analytics_data( $date ) {
		$rows = Analytics::get_analytics( $date, $date );
		if ( empty( $rows ) ) {
			return;
		}

		try {
			DB::add_analytics_bulk( $date, $rows );
		} catch ( Exception $e ) {} // phpcs:ignore
	}

	/**
	 * Get adsense data and save it into database.
	 *
	 * @param string $date Date to fetch data for.
	 */
	public function get_adsense_data( $date ) {
		$rows = Adsense::get_adsense( $date, $date );
		if ( empty( $rows ) ) {
			return;
		}

		try {
			DB::add_adsense( $date, $rows );
		} catch ( Exception $e ) {} // phpcs:ignore
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

	/**
	 * Check for missing dates.
	 *
	 * @param string $action Action to perform.
	 */
	private function check_for_missing_dates( $action ) {
		$count = 1;
		$hook  = "get_{$action}_data";
		$start = Helper::get_midnight( time() + DAY_IN_SECONDS );
		$days  = Helper::get_settings( 'general.console_caching_control', 90 );

		for ( $current = 1; $current <= $days; $current++ ) {
			$date = Helper::get_date( 'Y-m-d', $start - ( DAY_IN_SECONDS * $current ), false, true );
			if ( AnalyticsDB::date_exists( $date, $action ) ) {
				continue;
			}

			$count++;
			as_schedule_single_action(
				time() + ( 60 * ( $count / 2 ) ),
				'rank_math/analytics/' . $hook,
				[ $date ],
				'rank-math'
			);
		}

		// Clear cache.
		if ( $count > 1 ) {
			as_schedule_single_action(
				time() + ( 60 * ( ( $count + 1 ) / 2 ) ),
				'rank_math/analytics/clear_cache',
				[],
				'rank-math'
			);
		}
	}
}
