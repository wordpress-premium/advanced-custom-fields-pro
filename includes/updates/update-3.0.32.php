<?php
/**
 * The Updates routine for version 3.0.32.
 *
 * @since      3.0.32
 * @package    RankMathPro
 * @subpackage RankMathPro\Updates
 * @author     Rank Math <support@rankmath.com>
 */

use RankMath\Admin\Database\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Update the deprecated schema type Attorney and use it as LegalService.
 */
function rank_math_pro_3_0_32_update_local_business_type() {
	$old_meta_key = 'rank_math_schema_Attorney';
	$schemas      = Database::table( 'postmeta' )
				->select( 'post_id' )
				->whereLike( 'meta_key', $old_meta_key )
				->get();

	if ( ! $schemas ) {
		return;
	}

	foreach ( $schemas as $schema ) {
		$meta = get_post_meta( $schema->post_id, $old_meta_key, true );
		if ( ! $meta ) {
			continue;
		}

		$meta['@type'] = 'LegalService';

		// Add new meta.
		update_post_meta( $schema->post_id, 'rank_math_schema_LegalService', $meta );

		// Delete old meta.
		delete_post_meta( $schema->post_id, $old_meta_key );
	}
}

rank_math_pro_3_0_32_update_local_business_type();
