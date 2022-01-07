<?php
/**
 * Plugin activation and deactivation functionality.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    RankMathPro
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Helper;
use RankMath\Analytics\Workflow\Workflow;

defined( 'ABSPATH' ) || exit;

/**
 * Installer class.
 */
class Installer {

	/**
	 * Bind all events.
	 */
	public function __construct() {
		register_activation_hook( RANK_MATH_PRO_FILE, [ $this, 'activation' ] );
		register_deactivation_hook( RANK_MATH_PRO_FILE, [ $this, 'deactivation' ] );

		add_action( 'wpmu_new_blog', [ $this, 'activate_blog' ] );
		add_action( 'activate_blog', [ $this, 'activate_blog' ] );
		add_filter( 'wpmu_drop_tables', [ $this, 'on_delete_blog' ] );
	}

	/**
	 * Do things when activating Rank Math.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 */
	public function activation( $network_wide = false ) {
		if ( ! is_multisite() || ! $network_wide ) {
			$this->activate();
			return;
		}

		$this->network_activate_deactivate( true );
	}

	/**
	 * Do things when deactivating Rank Math.
	 *
	 * @param bool $network_wide Whether the plugin is being activated network-wide.
	 */
	public function deactivation( $network_wide = false ) {
		if ( ! is_multisite() || ! $network_wide ) {
			$this->deactivate();
			return;
		}

		$this->network_activate_deactivate( false );
	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @param int $blog_id ID of the new blog.
	 */
	public function activate_blog( $blog_id ) {
		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		$this->activate();
		restore_current_blog();
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 *
	 * @param  array $tables List of tables that will be deleted by WP.
	 * @return array
	 */
	public function on_delete_blog( $tables ) {
		global $wpdb;

		$tables[] = $wpdb->prefix . 'rank_math_analytics_ga';
		$tables[] = $wpdb->prefix . 'rank_math_analytics_adsense';
		$tables[] = $wpdb->prefix . 'rank_math_analytics_keyword_manager';

		return $tables;
	}

	/**
	 * Run network-wide activation/deactivation of the plugin.
	 *
	 * @param bool $activate True for plugin activation, false for de-activation.
	 *
	 * Forked from Yoast (https://github.com/Yoast/wordpress-seo/)
	 */
	private function network_activate_deactivate( $activate ) {
		global $wpdb;

		$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs WHERE archived = '0' AND spam = '0' AND deleted = '0'" );
		if ( empty( $blog_ids ) ) {
			return;
		}

		foreach ( $blog_ids as $blog_id ) {
			$func = true === $activate ? 'activate' : 'deactivate';

			switch_to_blog( $blog_id );
			$this->$func();
			restore_current_blog();
		}
	}

	/**
	 * Runs on activation of the plugin.
	 */
	private function activate() {
		\RankMathPro\Admin\Api::get()->get_settings();
		$this->create_options();

		// Add Analytics Installer.
		Workflow::do_workflow( 'analytics', 90, null, null );
		Workflow::do_workflow( 'adsense', 90, null, null );

		// Add action for scheduler.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			$task_name = 'rank_math/analytics/data_fetch';

			if ( false === as_next_scheduled_action( $task_name ) ) {
				$fetch_gap          = 3;
				$schedule_in_minute = wp_rand( 3, 1380 );
				$time_to_schedule   = ( strtotime( 'tomorrow' ) + ( $schedule_in_minute * MINUTE_IN_SECONDS ) );

				as_schedule_recurring_action(
					$time_to_schedule,
					DAY_IN_SECONDS * $fetch_gap,
					$task_name,
					[],
					'rank-math'
				);
			}
		}
	}

	/**
	 * Runs on deactivation of the plugin.
	 */
	private function deactivate() {}

	/**
	 * Add default values.
	 */
	private function create_options() {
		$all_opts = rank_math()->settings->all_raw();
		$general  = $all_opts['general'];
		$sitemap  = $all_opts['sitemap'];
		if ( empty( $sitemap['video_sitemap_post_type'] ) ) {
			$sitemap['video_sitemap_post_type'] = array_values( Helper::get_accessible_post_types() );
		}

		if ( empty( $general['google_updates'] ) ) {
			$general['google_updates'] = 'on';
		}

		if ( empty( $general['auto_add_focus_keywords'] ) ) {
			$general['auto_add_focus_keywords'] = [
				'enable_auto_import' => 1,
				'post_types'         => [ 'post', 'page' ],
			];
		}

		Helper::update_all_settings( $general, null, $sitemap );
	}
}
