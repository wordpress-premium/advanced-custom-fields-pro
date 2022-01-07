<?php
/**
 * Status module.
 *
 * @since      1.0
 * @package    RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Status;

use RankMath\Traits\Hooker;
use RankMath\Admin\Admin_Helper;
use RankMath\Google\Authentication;
use RankMath\Status\Error_Log;
use RankMath\Status\System_Status as System_Status_Free;

defined( 'ABSPATH' ) || exit;

/**
 * System_Status class.
 */
class System_Status {
	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->filter( 'rank_math/status/rank_math_info', 'filter_status_info' );
	}

	/**
	 * Filter Status Info
	 *
	 * @param array $rankmath Array of rankmath.
	 */
	public function filter_status_info( $rankmath ) {
		$rankmath['fields']['version']['label'] = esc_html__( 'Free version', 'rank-math-pro' );
		array_splice(
			$rankmath['fields'],
			1,
			0,
			[
				[
					'label' => esc_html__( 'PRO version', 'rank-math-pro' ),
					'value' => get_option( 'rank_math_pro_version' ),
				],
			]
		);
		// Change pro_version key with keeping array order the same.
		$keys               = array_keys( $rankmath['fields'] );
		$keys[1]            = 'pro_version';
		$rankmath['fields'] = array_combine( $keys, array_values( $rankmath['fields'] ) );

		return $rankmath;
	}
}
