<?php
/**
 * The TedVideos
 *
 * @since      2.7.1
 * @package    RankMath
 * @subpackage RankMath\Schema\Video
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema\Video;

use RankMath\Helper;
use MyThemeShop\Helpers\Str;

defined( 'ABSPATH' ) || exit;

/**
 * TedVideos class.
 */
class TedVideos {

	/**
	 * Match url.
	 *
	 * @param  string $url Url to match.
	 * @return bool
	 */
	public static function match( $url ) {
		if ( ! Str::contains( 'ted.com', $url ) ) {
			return false;
		}

		return self::fetch_data( $url );
	}

	/**
	 * Fetch data.
	 *
	 * @param  string $url Video Source.
	 * @return array
	 */
	private static function fetch_data( $url ) {
		$data = [
			'src'   => $url,
			'embed' => true,
		];

		$response = wp_remote_get( str_replace( 'embed.', '', $url ) );
		if (
			is_wp_error( $response ) ||
			! in_array( wp_remote_retrieve_response_code( $response ), [ 200, 204 ], true )
		) {
			return $data;
		}

		$content = wp_remote_retrieve_body( $response );
		preg_match_all( "/<meta content='(.*?)' itemprop='(width|height|duration|uploadDate)'>/i", $content, $item_props, PREG_SET_ORDER );

		foreach ( $item_props as $item_prop ) {
			$data[ $item_prop[2] ] = $item_prop[1];
		}

		preg_match_all( '/<meta name="(title|description)" content="(.*?)" \/>/i', $content, $item_props, PREG_SET_ORDER );
		foreach ( $item_props as $item_prop ) {
			$key          = 'title' === $item_prop[1] ? 'name' : $item_prop[1];
			$data[ $key ] = $item_prop[2];
		}

		preg_match( '/<meta property="og:image" content="(.*?)" \/>/i', $content, $image );
		$data['thumbnail'] = ! empty( $image ) && isset( $image[1] ) ? $image[1] : '';

		return $data;
	}
}
