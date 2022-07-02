<?php
/**
 *  Install Keyword manager.
 *
 * @since      1.0.49
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics\Workflow;

use Exception;
use MyThemeShop\Helpers\DB;
use RankMath\Analytics\Workflow\Base;

defined( 'ABSPATH' ) || exit;

/**
 * Keywords class.
 */
class Keywords extends Base {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$done = \boolval( get_option( 'rank_math_analytics_pro_installed' ) );
		if ( $done ) {
			return;
		}

		$this->create_keywords_tables();
		update_option( 'rank_math_analytics_pro_installed', true );
	}

	/**
	 * Create keywords tables.
	 */
	public function create_keywords_tables() {
		global $wpdb;

		$collate = $wpdb->get_charset_collate();
		$table   = 'rank_math_analytics_keyword_manager';

		// Early Bail!!
		if ( DB::check_table_exists( $table ) ) {
			return;
		}

		$schema = "CREATE TABLE {$wpdb->prefix}{$table} (
				id bigint(20) unsigned NOT NULL auto_increment,
				keyword varchar(1000) NOT NULL,
				collection varchar(200) NULL,
				is_active tinyint(1) NOT NULL default 1,
				PRIMARY KEY  (id)
			) $collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		try {
			dbDelta( $schema );
		} catch ( Exception $e ) { // phpcs:ignore
			// Will log.
		}
	}
}
