<?php
/**
 * The Content AI module.
 *
 * @since      3.0.25
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\ContentAI\Content_AI as Content_AI_Free;
use RankMath\Helper;
use RankMathPro\Admin\Admin_Helper;
use RankMath\Traits\Hooker;


defined( 'ABSPATH' ) || exit;

/**
 * Content_AI class.
 */
class Content_AI {
	use Hooker;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		if ( ! Admin_Helper::is_business_plan() || ! Content_AI_Free::can_add_tab() || ! Helper::get_current_editor() ) {
			return;
		}

		$this->action( 'rank_math/admin/editor_scripts', 'editor_scripts', 19 );
	}

	/**
	 * Enqueue assets for post editors.
	 *
	 * @return void
	 */
	public function editor_scripts() {
		wp_enqueue_script(
			'rank-math-pro-content-ai',
			RANK_MATH_PRO_URL . 'includes/modules/content-ai/assets/js/content-ai.js',
			[ 'rank-math-content-ai' ],
			rank_math_pro()->version,
			true
		);
	}
}
