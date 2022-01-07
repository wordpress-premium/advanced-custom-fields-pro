<?php
/**
 * The Updates routine for version 2.0.6.
 *
 * @since      2.0.6
 * @package    RankMathPro
 * @subpackage RankMathPro\Updates
 * @author     Rank Math <support@rankmath.com>
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * This code is needed to flush the new rewrite rules we added to fix the Code Validation issue.
 */
function rank_math_pro_2_0_6_flush_rewrite_rules() {
	flush_rewrite_rules();
}

rank_math_pro_2_0_6_flush_rewrite_rules();
