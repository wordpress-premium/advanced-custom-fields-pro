<?php
/**
 * The Analytics Module
 *
 * @since      2.0.0
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Links class.
 */
class Links {

	/**
	 * Get links by post.
	 *
	 * @param  array $objects Array of ids.
	 * @return array
	 */
	public static function get_links_by_objects( $objects ) {
		if ( empty( $objects ) || empty( $objects['rows'] ) ) {
			return [];
		}

		if ( ! Helper::is_module_active( 'link-counter' ) ) {
			return $objects;
		}

		$ids   = wp_list_pluck( $objects['rows'], 'object_id' );
		$links = DB::links()
			->whereIn( 'object_id', \array_unique( $ids ) )
			->get( ARRAY_A );
		$ids   = array_flip( $ids );

		foreach ( $links as $link ) {
			$post_id   = $link['object_id'];
			$object_id = $ids[ $post_id ];

			$objects['rows'][ $object_id ]['links'] = [
				'internal' => $link['internal_link_count'],
				'external' => $link['external_link_count'],
				'incoming' => $link['incoming_link_count'],
			];
		}

		return $objects;
	}
}
