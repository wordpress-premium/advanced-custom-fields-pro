<?php
/**
 * The Updates routine for version 2.1.0
 *
 * @since      2.1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Updates
 * @author     Rank Math <support@rankmath.com>
 */

defined( 'ABSPATH' ) || exit;

/**
 * This code is needed to opening hours data.
 */
function rank_math_pro_2_1_0_update_opening_hours() {
	$locations = get_posts(
		[
			'post_type'   => 'rank_math_locations',
			'numberposts' => -1,
			'fields'      => 'ids',
		]
	);

	if ( empty( $locations ) || is_wp_error( $locations ) ) {
		return;
	}

	foreach ( $locations as $location ) {
		$schemas = RankMath\Schema\DB::get_schemas( $location );
		foreach ( $schemas as $meta_id => $schema ) {
			if ( empty( $schema['openingHoursSpecification'] ) ) {
				continue;
			}

			foreach ( $schema['openingHoursSpecification'] as $key => $opening_hour ) {
				$opening_hour['dayOfWeek']                   = array_map( 'ucfirst', $opening_hour['dayOfWeek'] );
				$schema['openingHoursSpecification'][ $key ] = $opening_hour;
			}

			$meta_key = 'rank_math_schema_' . $schema['@type'];
			$db_id    = absint( str_replace( 'schema-', '', $meta_id ) );
			update_metadata_by_mid( 'post', $db_id, $schema, $meta_key );
		}
	}
}

rank_math_pro_2_1_0_update_opening_hours();
