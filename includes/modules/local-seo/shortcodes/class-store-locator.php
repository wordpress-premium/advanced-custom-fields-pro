<?php
/**
 * The Store Locator shortcode Class.
 *
 * @since      1.0.1
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Helper;
use RankMath\Post;
use RankMath\Schema\DB;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Store_Locator class.
 */
class Store_Locator {
	/**
	 * Get Store_Locator Data.
	 *
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @return string
	 */
	public function get_data( $shortcode ) {
		$unit     = 'miles' === Helper::get_settings( 'titles.map_unit', 'kilometers' ) ? 'mi' : 'km';
		$radius   = Param::post( 'rank-math-search-radius', 20 );
		$address  = Param::post( 'rank-math-search-address' );
		$category = Param::post( 'rank-math-location-category' );
		$terms    = empty( $shortcode->atts['show_category_filter'] )
			? []
			: get_terms(
				[
					'taxonomy' => 'rank_math_location_category',
					'fields'   => 'id=>name',
				]
			);

		wp_enqueue_script( 'rank-math-local' );

		ob_start();
		?>
			<div class="rank-math-business-wrapper">
				<form id="rank-math-local-store-locator" method="post" action="#rank-math-local-store-locator">

					<?php if ( ! empty( $shortcode->atts['show_radius'] ) ) { ?>
						<div class="rank-math-form-field">
							<select name="rank-math-search-radius">
								<?php
								foreach ( [ 1, 5, 10, 20, 40, 50, 75, 100, 200, 300, 400, 500, 1000 ] as $value ) {
									echo "<option value='{$value}' " . selected( $radius, $value, true ) . ">{$value}{$unit}</option>";
								}
								?>
							</select>
						</div>
					<?php } ?>

					<?php if ( ! empty( $terms ) ) { ?>
						<div class="rank-math-form-field">
							<select name="rank-math-location-category">
								<option value=""><?php echo esc_html__( 'Select Category', 'rank-math-pro' ); ?></option>
								<?php foreach ( $terms as $term_id => $term_name ) { ?>
									<option value="<?php echo esc_attr( $term_id ); ?>" <?php selected( $category, $term_id ); ?>><?php echo esc_html( $term_name ); ?></option>
								<?php } ?>
							</select>
						</div>
					<?php } ?>

					<div class="rank-math-form-field">
						<input type="text" name="rank-math-search-address" id="rank-math-search-address" placeholder="<?php echo esc_html__( 'Address, Suburb, Region, Zip or Landmark', 'rank-math-pro' ); ?>" value="<?php echo esc_attr( $address ); ?>" />
						<input type="hidden" name="lat" id="rank-math-lat" />
						<input type="hidden" name="lng" id="rank-math-lng" />
					</div>
					<?php $this->detect_location(); ?>
					<div class="rank-math-form-field">
						<button type="submit" name="rank-math-submit" value="search"><?php echo esc_html__( 'Search', 'rank-math-pro' ); ?></button>
					</div>
				</form>
		<?php
					echo $this->get_results( $shortcode, $unit );
				echo '</div>';
		return ob_get_clean();
	}

	/**
	 * Add detect current location button.
	 *
	 * @return string
	 */
	private function detect_location() {
		if ( ! Helper::get_settings( 'titles.enable_location_detection' ) ) {
			return;
		}

		echo '<a href="#" id="rank-math-current-location">' . esc_html__( 'Detect Location', 'rank-math-pro' ) . '</a>';
	}

	/**
	 * Get Map Results.
	 *
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @param string             $unit      Map measurement unit.
	 * @return string
	 */
	private function get_results( $shortcode, $unit ) {
		if ( ! Param::post( 'rank-math-search-address' ) ) {
			return false;
		}

		global $wpdb;
		// Radius of the earth 3959 miles or 6371 kilometers.
		$earth_radius = 'mi' === $unit ? 3959 : 6371;
		$radius       = ! empty( $shortcode->atts['show_radius'] ) ? Param::post( 'rank-math-search-radius', 20 ) : $shortcode->atts['search_radius'];
		$latitude     = Param::post( 'lat' );
		$longitude    = Param::post( 'lng' );
		$category     = Param::post( 'rank-math-location-category', 0, FILTER_VALIDATE_INT );

		$inner_join = '';
		if ( $category ) {
			$inner_join .= $wpdb->prepare(
				"INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id
				INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				AND tt.taxonomy = 'rank_math_location_category'
				AND tt.term_id = %d",
				$category
			);
		}

		$nearby_locations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT
				p.*,
				map_lat.meta_value as locLat,
				map_lng.meta_value as locLong,
				( %d * acos(
					cos( radians( %s ) )
					* cos( radians( map_lat.meta_value ) )
					* cos( radians( map_lng.meta_value ) - radians( %s ) )
					+ sin( radians( %s ) )
					* sin( radians( map_lat.meta_value ) )
				) )
				AS distance
				FROM $wpdb->posts p
				INNER JOIN $wpdb->postmeta map_lat ON p.ID = map_lat.post_id
				INNER JOIN $wpdb->postmeta map_lng ON p.ID = map_lng.post_id
				$inner_join
				WHERE 1 = 1
				AND p.post_type = 'rank_math_locations'
				AND p.post_status = 'publish'
				AND map_lat.meta_key = 'rank_math_local_business_latitide'
				AND map_lng.meta_key = 'rank_math_local_business_longitude'
				HAVING distance < %s
				ORDER BY distance ASC",
				$earth_radius,
				$latitude,
				$longitude,
				$latitude,
				$radius
			)
		);
		//phpcs:enable

		if ( empty( $nearby_locations ) ) {
			return esc_html__( 'Sorry, no locations were found.', 'rank-math-pro' );
		}

		$data = ! empty( $shortcode->atts['show_map'] ) ? $shortcode->map->get_data( $shortcode, $nearby_locations ) : '';
		foreach ( $nearby_locations as $location ) {
			$schema = DB::get_schemas( $location->ID );
			if ( empty( $schema ) ) {
				continue;
			}

			$schema = current( $shortcode->replace_variables( $schema, $location ) );

			$data .= $shortcode->get_title( $schema );
			$data .= $shortcode->address->get_data( $shortcode, $schema );
			$data .= ! empty( $shortcode->atts['show_opening_hours'] ) ? $shortcode->opening_hours->get_data( $shortcode, $schema ) : '';
			$data .= $this->get_directions( $location, $shortcode );
		}

		return $data;
	}

	/**
	 * Get Map Results.
	 *
	 * @param Object             $location  Current Location Post.
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @return string
	 */
	public function get_directions( $location, $shortcode ) {
		if ( empty( $shortcode->atts['show_route_planner'] ) || empty( $shortcode->atts['show_map'] ) ) {
			return '';
		}

		$lat = Post::get_meta( 'local_business_latitide', $location->ID );
		$lng = Post::get_meta( 'local_business_longitude', $location->ID );

		ob_start();
		?>
			<div class="rank-math-directions-wrapper">
				<a href="#" class="rank-math-show-route" data-toggle-text="<?php echo esc_html__( 'Hide route', 'rank-math-pro' ); ?>">
					<?php echo esc_html( $shortcode->atts['route_label'] ); ?>
				</a>

				<div class="rank-math-directions-result">
					<h3><?php echo esc_html__( 'Route', 'rank-math-pro' ); ?></h3>
					<div class="rank-math-directions-form">
							<form method="post">
								<div class="rank-math-form-field">
									<label for="origin"><?php echo esc_html__( 'Your location:', 'rank-math-pro' ); ?></label>
									<input type="text" name="origin" id="rank-math-origin" value="<?php echo Param::post( 'rank-math-search-address' ); ?>" />
									<input type="submit" name="get-direction" value="<?php echo esc_html__( 'Show route', 'rank-math-pro' ); ?>" />
									<input type="hidden" name="rank-math-lat" id="rank-math-lat" value="<?php echo esc_attr( $lat ); ?>" />
									<input type="hidden" name="rank-math-lng" id="rank-math-lng" value="<?php echo esc_attr( $lng ); ?>" />
								</div>
							</form>
					</div>

					<div class="rank-math-directions"></div>
				</div>
			</div>
		<?php
		return ob_get_clean();
	}
}
