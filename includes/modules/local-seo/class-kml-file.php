<?php
/**
 * The KML File
 *
 * @since      2.1.2
 * @package    RankMath
 * @subpackage RankMathPro\Local_Seo
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Sitemap\Cache_Watcher;
use RankMath\Schema\DB;

defined( 'ABSPATH' ) || exit;

/**
 * KML_File class.
 */
class KML_File {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/sitemap/locations/data', 'add_location_data' );
		$this->action( 'save_post_rank_math_locations', 'save_post' );
	}

	/**
	 * Check for relevant post type before invalidation.
	 *
	 * @param int $post_id Post ID to possibly invalidate for.
	 */
	public function save_post( $post_id ) {
		if (
			wp_is_post_revision( $post_id ) ||
			false === Helper::is_post_indexable( $post_id )
		) {
			return false;
		}

		Cache_Watcher::invalidate( 'locations' );
	}

	/**
	 * Generate the KML file contents.
	 *
	 * @return string $kml KML file content.
	 */
	public function add_location_data() {
		$rm_locations = get_posts(
			[
				'post_type'   => 'rank_math_locations',
				'numberposts' => -1,
			]
		);
		if ( empty( $rm_locations ) ) {
			return [];
		}

		$locations = [];
		foreach ( $rm_locations as $rm_location ) {
			$locations_data = current( DB::get_schemas( $rm_location->ID ) );
			if ( empty( $locations_data ) ) {
				continue;
			}

			rank_math()->variables->setup();
			$name        = ! empty( $locations_data['name'] ) ? $locations_data['name'] : '%seo_title%';
			$description = ! empty( $locations_data['description'] ) ? $locations_data['description'] : '%seo_description%';
			$address     = '';
			if ( ! empty( $locations_data['address'] ) ) {
				if ( isset( $locations_data['address']['@type'] ) ) {
					unset( $locations_data['address']['@type'] );
					$address = $locations_data['address'];
				}
			}
			$locations[] = [
				'name'        => Helper::replace_vars( $name, $rm_location ),
				'description' => Helper::replace_vars( $description, $rm_location ),
				'email'       => ! empty( $locations_data['email'] ) ? $locations_data['email'] : '',
				'phone'       => ! empty( $locations_data['telephone'] ) ? $locations_data['telephone'] : '',
				'url'         => get_the_permalink( $rm_location ),
				'address'     => ! empty( $locations_data['address'] ) ? $locations_data['address'] : '',
				'coords'      => ! empty( $locations_data['geo'] ) ? $locations_data['geo'] : '',
			];
		}

		return $locations;
	}
}
