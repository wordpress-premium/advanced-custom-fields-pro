<?php
/**
 * The Rank Math Tutorial class.
 *
 * @since      2.1.2
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 *
 * @copyright Copyright (C) 2008-2020, WooCommerce
 * The following code is a derivative work of the code from the WooCommerce(https://github.com/woocommerce/woocommerce), which is licensed under GPL v3.
 */

namespace RankMathPro\Local_Seo;

use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * RM_Pointers Class.
 */
class RM_Pointers {

	use Hooker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->action( 'admin_enqueue_scripts', 'enqueue_pointers' );
	}

	/**
	 * Enqueue pointers and add script to page.
	 */
	public function enqueue_pointers() {
		$screen = get_current_screen();
		if ( ! $screen || 'rank_math_locations' !== $screen->id ) {
			return;
		}

		if ( get_option( 'rank_math_remove_locations_tutorial' ) ) {
			return;
		}

		update_option( 'rank_math_remove_locations_tutorial', true );

		wp_enqueue_style( 'wp-pointer' );
		wp_enqueue_script(
			'rank-math-pro-pointers',
			RANK_MATH_PRO_URL . 'includes/modules/local-seo/assets/js/rank-math-pointers.js',
			[
				'jquery',
				'wp-pointer',
				'wp-i18n',
				'lodash',
			],
			rank_math_pro()->version,
			true
		);
	}
}
