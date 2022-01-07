<?php
/**
 * Workflow.
 *
 * @since      1.0.54
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics\Workflow;

use RankMath\Traits\Hooker;
use function as_enqueue_async_action;
use function as_unschedule_all_actions;

defined( 'ABSPATH' ) || exit;

/**
 * Workflow class.
 */
class Workflow {

	use Hooker;

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Workflow
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Workflow ) ) {
			$instance = new Workflow();
			$instance->hooks();
		}

		return $instance;
	}

	/**
	 * Hooks.
	 */
	public function hooks() {
		// Common.
		$this->action( 'rank_math/analytics/workflow', 'maybe_first_install', 5, 0 );
		$this->action( 'rank_math/analytics/workflow/create_tables', 'create_tables_only', 5 );

		// Services.
		$this->action( 'rank_math/analytics/workflow/analytics', 'init_analytics_workflow', 5, 0 );
		$this->action( 'rank_math/analytics/workflow/adsense', 'init_adsense_workflow', 5, 0 );
	}

	/**
	 * Maybe first install.
	 */
	public function maybe_first_install() {
		new \RankMathPro\Analytics\Workflow\Keywords();
	}

	/**
	 * Init Analytics workflow
	 */
	public function init_analytics_workflow() {
		new \RankMathPro\Analytics\Workflow\Analytics();
	}

	/**
	 * Init Adsense workflow
	 */
	public function init_adsense_workflow() {
		new \RankMathPro\Analytics\Workflow\Adsense();
	}

	/**
	 * Create tables only.
	 */
	public function create_tables_only() {
		new \RankMathPro\Analytics\Workflow\Analytics();
		new \RankMathPro\Analytics\Workflow\Adsense();
		( new \RankMathPro\Analytics\Workflow\Keywords() )->create_keywords_tables();
	}
}
