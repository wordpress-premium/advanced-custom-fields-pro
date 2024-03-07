<?php
/**
 *  Outputs specific schema code from Schema Template
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     RankMath <support@rankmath.com>
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
		$this->filter( 'rank_math/snippet/after_schema_content', 'show_review_notes' );
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
		wp_enqueue_style( 'rank-math-review-pro-snippet', RANK_MATH_PRO_URL . 'includes/modules/schema/assets/css/rank-math-snippet.css', null, rank_math_pro()->version );

		$type = \strtolower( $schema['@type'] );
		if ( ! in_array( $type, [ 'dataset', 'movie', 'claimreview', 'faqpage', 'howto', 'jobposting', 'product', 'recipe', 'podcastepisode' ], true ) ) {
			return $html;
		}

		ob_start();

		echo '<div id="rank-math-rich-snippet-wrapper">';
		include "shortcode/$type.php";
		$this->show_review_notes( $shortcode );
		echo '</div>';

		return ob_get_clean();
	}

	/**
	 * Display Pros & Cons.
	 *
	 * @since 3.0.18
	 */
	public function show_review_notes( $shortcode ) {
		$labels = [
			'pros' => __( 'Pros', 'rank-math-pro' ),
			'cons' => __( 'Cons', 'rank-math-pro' ),
		];

		/**
		 * Filter: Allow changing the Pros & Cons labels.
		 *
		 * @param array $labels {
		 *  @type string $pros Pros label.
		 *  @type string $cons Cons label.
		 * }
		 */
		$labels = $this->do_filter( 'schema/review_notes_labels', $labels );

		$positive_notes = ! empty( $shortcode->get_field_value( 'positiveNotes' ) ) ? $shortcode->get_field_value( 'positiveNotes' ) : $shortcode->get_field_value( 'review.positiveNotes' );
		if ( ! empty( $positive_notes['itemListElement'] ) ) {
			?>
			<div class="rank-math-review-notes rank-math-review-pros">
				<h4><?php echo esc_html( $labels['pros'] ); ?></h4>
				<ul>
					<?php foreach ( $positive_notes['itemListElement'] as $positive_note ) { ?>
						<li><?php echo esc_html( $positive_note['name'] ); ?></li>
					<?php } ?>
				</ul>
			</div>
			<?php
		}

		$negative_notes = ! empty( $shortcode->get_field_value( 'negativeNotes' ) ) ? $shortcode->get_field_value( 'negativeNotes' ) : $shortcode->get_field_value( 'review.negativeNotes' );
		if ( ! empty( $negative_notes['itemListElement'] ) ) {
			?>
			<div class="rank-math-review-notes rank-math-review-cons">
				<h4><?php echo esc_html( $labels['cons'] ); ?></h4>
				<ul>
					<?php foreach ( $negative_notes['itemListElement'] as $negative_note ) { ?>
						<li><?php echo esc_html( $negative_note['name'] ); ?></li>
					<?php } ?>
				</ul>
			</div>
			<?php
		}
	}
}
