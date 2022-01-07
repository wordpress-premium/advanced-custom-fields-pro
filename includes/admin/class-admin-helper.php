<?php
/**
 * Admin helper Functions.
 *
 * This file contains functions needed on the admin screens.
 *
 * @since      2.0.0
 * @package    RankMath
 * @subpackage RankMath\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper as Free_Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Helper class.
 */
class Admin_Helper {

	/**
	 * Get primary term ID.
	 *
	 * @param  int $post_id Post ID.
	 *
	 * @return int
	 */
	public static function get_primary_term_id( $post_id = null ) {
		$taxonomy = self::get_primary_taxonomy( $post_id );
		if ( ! $taxonomy ) {
			return 0;
		}

		$id = get_post_meta( $post_id ? $post_id : get_the_ID(), 'rank_math_primary_' . $taxonomy['name'], true );

		return $id ? absint( $id ) : 0;
	}

	/**
	 * Get current post type.
	 *
	 * @param  int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_current_post_type( $post_id = null ) {
		if ( ! $post_id && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			return $screen->post_type;
		}

		return get_post_type( $post_id );
	}

	/**
	 * Get primary taxonomy.
	 *
	 * @param  int $post_id Post ID.
	 *
	 * @return bool|array
	 */
	public static function get_primary_taxonomy( $post_id = null ) {
		$taxonomy  = false;
		$post_type = self::get_current_post_type( $post_id );

		/**
		 * Allow disabling the primary term feature.
		 *
		 * @param bool $return True to disable.
		 */
		if ( false === apply_filters( 'rank_math/admin/disable_primary_term', false ) ) {
			$taxonomy = Helper::get_settings( 'titles.pt_' . $post_type . '_primary_taxonomy', false );
		}

		if ( ! $taxonomy ) {
			return false;
		}

		$taxonomy = get_taxonomy( $taxonomy );

		$primary_taxonomy = [
			'title'         => $taxonomy->labels->singular_name,
			'name'          => $taxonomy->name,
			'singularLabel' => $taxonomy->labels->singular_name,
			'restBase'      => ( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name,
		];

		return $primary_taxonomy;
	}

	/**
	 * Check if current plan is business.
	 *
	 * @return boolean
	 */
	public static function is_business_plan() {
		$registered = Free_Admin_Helper::get_registration_data();
		return ( isset( $registered['plan'] ) && in_array( $registered['plan'], [ 'business', 'agency' ], true ) );
	}
}
