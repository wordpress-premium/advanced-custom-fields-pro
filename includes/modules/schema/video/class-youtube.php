<?php
/**
 * The Youtube
 *
 * @since      2.0.0
 * @package    RankMath
 * @subpackage RankMath\Schema\Video
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema\Video;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Youtube class.
 */
class Youtube {

	/**
	 * Match url.
	 *
	 * @param  string $url Url to match.
	 * @return bool
	 */
	public static function match( $url ) {
		if ( ! preg_match( '#^https?://(?:www\.)?(?:youtube\.com/|youtu\.be/)#', $url ) ) {
			return false;
		}

		preg_match( '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match );
		if ( empty( $match[1] ) ) {
			return false;
		}

		return self::fetch_data( $match[1], $url );
	}

	/**
	 * Fetch data.
	 *
	 * @param  string $video_id Video ID.
	 * @param  string $url      Video Source.
	 * @return array
	 */
	private static function fetch_data( $video_id, $url ) {
		$data = [
			'src'   => $url,
			'embed' => true,
		];

		$response = wp_remote_get( "https://www.youtube.com/watch?v={$video_id}" );
		if (
			is_wp_error( $response ) ||
			! in_array( wp_remote_retrieve_response_code( $response ), [ 200, 204 ], true )
		) {
			return $data;
		}

		$content = wp_remote_retrieve_body( $response );
		preg_match_all( '/<meta itemprop="(width|height|isFamilyFriendly|duration|uploadDate)" content="(.*?)">/i', $content, $item_props, PREG_SET_ORDER );
		foreach ( $item_props as $item_prop ) {
			$data[ $item_prop[1] ] = $item_prop[2];
		}

		preg_match_all( '/<meta name="(title|description)" content="(.*?)">/i', $content, $item_props, PREG_SET_ORDER );
		foreach ( $item_props as $item_prop ) {
			$key          = 'title' === $item_prop[1] ? 'name' : $item_prop[1];
			$data[ $key ] = $item_prop[2];
		}

		preg_match( '/<meta property="og:image" content="(.*?)">/i', $content, $image );
		$data['thumbnail'] = ! empty( $image ) && isset( $image[1] ) ? $image[1] : '';

		return $data;
	}
}
