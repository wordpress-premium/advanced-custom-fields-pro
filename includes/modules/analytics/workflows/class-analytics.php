<?php
/**
 * Google Analytics.
 *
 * @since      1.0.49
 * @package    RankMathPro
 * @subpackage RankMathPro\Analytics
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics\Workflow;

use Exception;
use MyThemeShop\Helpers\DB;
use RankMath\Analytics\Workflow\Base;
use function as_unschedule_all_actions;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics class.
 */
class Analytics extends Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// If analytics is not connected, no need to proceed.
		if ( ! \RankMath\Google\Analytics::is_analytics_connected() ) {
			return;
		}

		$this->action( 'rank_math/analytics/workflow/analytics', 'kill_jobs', 5, 0 );
		$this->action( 'rank_math/analytics/workflow/create_tables', 'create_tables' );
		$this->action( 'rank_math/analytics/workflow/analytics', 'create_tables', 6, 0 );
		$this->action( 'rank_math/analytics/workflow/analytics', 'create_data_jobs', 10, 3 );
	}

	/**
	 * Kill jobs.
	 *
	 * Stop processing queue items, clear cronjob and delete all batches.
	 */
	public function kill_jobs() {
		as_unschedule_all_actions( 'rank_math/analytics/get_analytics_data' );
	}

	/**
	 * Create tables.
	 */
	public function create_tables() {
		global $wpdb;

		$collate = $wpdb->get_charset_collate();
		$table   = 'rank_math_analytics_ga';

		// Early Bail!!
		if ( DB::check_table_exists( $table ) ) {
			return;
		}

		$schema = "CREATE TABLE {$wpdb->prefix}{$table} (
				id bigint(20) unsigned NOT NULL auto_increment,
				page varchar(500) NOT NULL,
				created timestamp NOT NULL,
				pageviews mediumint(6) NOT NULL,
				visitors mediumint(6) NOT NULL,
				PRIMARY KEY  (id),
				KEY analytics_object_analytics (page(190))
			) $collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		try {
			dbDelta( $schema );
		} catch ( Exception $e ) { // phpcs:ignore
			// Will log.
		}
	}

	/**
	 * Create jobs to fetch data.
	 *
	 * @param integer $days Number of days to fetch from past.
	 * @param string  $prev Previous saved value.
	 * @param string  $new  New posted value.
	 */
	public function create_data_jobs( $days, $prev, $new ) {
		// If saved and new profile are same.
		if ( ! $this->is_profile_updated( 'view_id', $prev, $new ) ) {
			return;
		}

		// Fetch now.
		$this->create_jobs( $days, 'analytics' );
	}
}
