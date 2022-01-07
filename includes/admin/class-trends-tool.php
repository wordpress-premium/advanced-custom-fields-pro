<?php
/**
 * Google Trends tool for the post editor.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Trends tool class.
 *
 * @codeCoverageIgnore
 */
class Trends_Tool {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'rank_math/admin/editor_scripts', 'editor_scripts', 20 );
	}

	/**
	 * Enqueue assets for post/term/user editors.
	 *
	 * @return void
	 */
	public function editor_scripts() {
		global $pagenow;
		if ( ! Admin_Helper::is_post_edit() && 'term.php' !== $pagenow && ! Admin_Helper::is_user_edit() ) {
			return;
		}

		if ( ! wp_script_is( 'rank-math-editor' ) ) {
			return;
		}

		wp_enqueue_script(
			'rank-math-pro-editor',
			RANK_MATH_PRO_URL . 'assets/admin/js/gutenberg.js',
			[
				'jquery-ui-autocomplete',
				'rank-math-editor',
			],
			RANK_MATH_PRO_VERSION,
			true
		);
		wp_enqueue_style( 'rank-math-pro-editor', RANK_MATH_PRO_URL . 'assets/admin/css/gutenberg.css', [], RANK_MATH_PRO_VERSION );
	}
}
