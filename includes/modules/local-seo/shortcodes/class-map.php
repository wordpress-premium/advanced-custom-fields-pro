<?php
/**
 * The Map shortcode Class.
 *
 * @since      1.0.1
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Schema\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Map class.
 */
class Map {
	/**
	 * Get Address Data.
	 *
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @param array              $locations Locations data.
	 * @return string
	 */
	public function get_data( $shortcode, $locations ) {
		$this->shortcode = $shortcode;
		$atts            = $shortcode->atts;

		$options = [
			'map_style'       => $atts['map_style'],
			'allow_zoom'      => $atts['allow_zoom'],
			'zoom_level'      => $atts['zoom_level'],
			'allow_dragging'  => $atts['allow_dragging'],
			'show_clustering' => $atts['show_marker_clustering'],
			'show_infowindow' => $atts['show_infowindow'],
		];

		$terms_data = [];
		foreach ( $locations as $location ) {
			$schema = DB::get_schemas( $location->ID );
			if ( empty( $schema ) ) {
				continue;
			}

			$schema = current( $shortcode->replace_variables( $schema ) );

			if ( empty( $schema['geo']['latitude'] ) || empty( $schema['geo']['longitude'] ) ) {
				continue;
			}

			$options['locations'][ $location->ID ] = [
				'content' => $this->get_infobox_content( $location->ID, $schema ),
				'lat'     => $schema['geo']['latitude'],
				'lng'     => $schema['geo']['longitude'],
			];

			if ( ! empty( $atts['show_category_filter'] ) && 'map' === $atts['type'] ) {
				$terms = get_the_terms( $location->ID, 'rank_math_location_category' );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$terms_data = array_merge( $terms_data, $terms );

					$options['locations'][ $location->ID ]['terms'] = wp_list_pluck( $terms, 'term_id' );
				}
			}
		}

		if ( empty( $options['locations'] ) ) {
			return;
		}

		wp_enqueue_script( 'rank-math-local' );

		$width  = ! empty( $atts['map_width'] ) ? $atts['map_width'] : '100%';
		$height = ! empty( $atts['map_height'] ) ? $atts['map_height'] : '500px';
		$style  = sprintf( 'style="width: %s; height: %s"', $width, $height );
		ob_start();
		?>
		<div class="rank-math-local-map-wrapper">
			<div class="rank-math-local-map" data-map-options="<?php echo esc_attr( wp_json_encode( $options ) ); ?>" <?php printf( 'style="width: %s; height: %s"', esc_attr( $width ), esc_attr( $height ) ); ?>></div>
			<?php
			if ( ! empty( $terms_data ) ) {
				echo '<select id="rank-math-select-category">';
					echo '<option value="">' . esc_html__( 'Select Category', 'rank-math-pro' ) . '</option>';
				foreach ( $terms_data as $term ) {
					echo '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
				}
				echo '</select>';
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get Infobox Content.
	 *
	 * @param int   $location_id The Location ID.
	 * @param array $schema      Schema Data.
	 * @return string
	 */
	public function get_infobox_content( $location_id, $schema ) {
		return '<div class="rank-math-infobox-wrapper">
			<h5><a href="' . esc_url( get_the_permalink( $location_id ) ) . '">' . esc_html( get_the_title( $location_id ) ) . '</a></h5>
			<p>' . $this->shortcode->address->get_data( $this->shortcode, $schema ) . '</p>
		</div>';
	}
}
