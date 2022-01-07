<?php
/**
 *  Outputs specific schema code from Schema Template
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Schema;

use RankMath\Traits\Hooker;
use RankMath\Schema\Snippet_Shortcode;

defined( 'ABSPATH' ) || exit;

/**
 * Schema Frontend class.
 */
class Snippet_Pro_Shortcode extends Snippet_Shortcode {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/snippet/html', 'add_shortcode_view', 10, 4 );
		$this->filter( 'shortcode_atts_rank_math_rich_snippet', 'register_fields_attribute', 10, 4 );
		$this->filter( 'rank_math/schema/shortcode/filter_attributes', 'filter_attributes', 10, 2 );
	}

	/**
	 * Filter schema fields.
	 *
	 * @param array $schema Schema data.
	 * @param array $atts   The user defined shortcode attributes.
	 *
	 * @return array Filtered Schema fields.
	 */
	public function filter_attributes( $schema, $atts ) {
		if ( empty( $atts['fields'] ) ) {
			return $schema;
		}

		$fields   = explode( ',', $atts['fields'] );
		$fields[] = '@type';

		return array_intersect_key( $schema, array_flip( $fields ) );
	}

	/**
	 * Filters shortcode attributes.
	 *
	 * If the third parameter of the shortcode_atts() function is present then this filter is available.
	 *
	 * @param array  $out       The output array of shortcode attributes.
	 * @param array  $pairs     The supported attributes and their defaults.
	 * @param array  $atts      The user defined shortcode attributes.
	 * @param string $shortcode The shortcode name.
	 */
	public function register_fields_attribute( $out, $pairs, $atts, $shortcode ) { // phpcs:ignore
		return wp_parse_args( $atts, $out );
	}

	/**
	 * Filter to change the rank_math_rich_snippet shortcode content.
	 *
	 * @param string $html      Shortcode content.
	 * @param string $schema    Current Schema data.
	 * @param string $post      Current Post Object.
	 * @param string $shortcode Shortcode class instance.
	 *
	 * @return string Shortcode Content.
	 */
	public function add_shortcode_view( $html, $schema, $post, $shortcode ) { // phpcs:ignore
		$type = \strtolower( $schema['@type'] );
		if ( ! in_array( $type, [ 'dataset', 'movie', 'claimreview', 'faqpage', 'howto', 'jobposting', 'product', 'recipe' ], true ) ) {
			return $html;
		}

		wp_enqueue_style( 'rank-math-review-pro-snippet', RANK_MATH_PRO_URL . 'includes/modules/schema/assets/css/rank-math-snippet.css', null, rank_math_pro()->version );

		ob_start();

		echo '<div id="rank-math-rich-snippet-wrapper">';
		include "shortcode/$type.php";
		echo '</div>';

		return ob_get_clean();
	}
}
