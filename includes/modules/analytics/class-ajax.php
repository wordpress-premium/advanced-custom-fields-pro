<?php
/**
 * The Analytics AJAX
 *
 * @since      1.4.0
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Ajax class.
 */
class Ajax {

	use \RankMath\Traits\Ajax;

	/**
	 * The Constructor
	 */
	public function __construct() {
		$this->ajax( 'save_adsense_account', 'save_adsense_account' );
	}

	/**
	 * Save adsense profile.
	 */
	public function save_adsense_account() {
		$this->verify_nonce( 'rank-math-ajax-nonce' );
		$this->has_cap_ajax( 'analytics' );

		$prev                = get_option( 'rank_math_google_analytic_options', [] );
		$value               = get_option( 'rank_math_google_analytic_options', [] );
		$value['adsense_id'] = Param::post( 'accountID' );
		update_option( 'rank_math_google_analytic_options', $value );

		$days = Param::get( 'days', 90, FILTER_VALIDATE_INT );
		\RankMath\Analytics\Workflow\Workflow::do_workflow(
			'adsense',
			$days,
			$prev,
			$value
		);

		$this->success();
	}
}
