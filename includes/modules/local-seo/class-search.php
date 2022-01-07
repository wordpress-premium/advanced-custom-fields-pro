<?php
/**
 * The Local_Seo Module
 *
 * @since      1.0.0
 * @package    RankMathPro
 * @subpackage RankMath
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Search class.
 */
class Search {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'wp', 'integrations' );
	}

	/**
	 * Initialize integrations.
	 */
	public function integrations() {
		if ( ! is_search() || ! Helper::get_settings( 'titles.locations_enhanced_search' ) ) {
			return;
		}

		$this->filter( 'the_excerpt', 'add_locations_data' );
	}

	/**
	 * Add Locations data to search results.
	 *
	 * @param  string $excerpt Post excerpt.
	 * @return string $excerpt Processed excerpt.
	 */
	public function add_locations_data( $excerpt ) {
		global $post;

		if ( get_post_type( $post->ID ) !== 'rank_math_locations' ) {
			return $excerpt;
		}

		$excerpt .= do_shortcode( '[rank_math_local type="address" locations="' . $post->ID . '" show_company_name=""]' );

		return $excerpt;
	}
}
