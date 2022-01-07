<?php
/**
 * The Vimeo
 *
 * @since      2.0.0
 * @package    RankMath
 * @subpackage RankMath\Schema\Video
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema\Video;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper;
use MyThemeShop\Helpers\Str;

defined( 'ABSPATH' ) || exit;

/**
 * Vimeo class.
 */
class Vimeo {

	/**
	 * Match url.
	 *
	 * @param  string $url Url to match.
	 * @return bool
	 */
	public static function match( $url ) {
		if ( ! Str::contains( 'vimeo.com', $url ) ) {
			return false;
		}

		preg_match( '#(https?://)?(www.)?(player.)?vimeo.com/([a-z]*/)*([0-9]{6,11})[?]?.*#', $url, $match );
		if ( empty( $match[5] ) ) {
			return false;
		}

		return self::fetch_data( $match[5], $url );
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

		$response = wp_remote_get( "http://vimeo.com/api/v2/video/{$video_id}/json" );
		if (
			is_wp_error( $response ) ||
			! in_array( wp_remote_retrieve_response_code( $response ), [ 200, 204 ], true )
		) {
			return $data;
		}

		$content = wp_remote_retrieve_body( $response );
		$content = json_decode( $content, true );
		$content = $content[0];

		$data = [
			'name'             => ! empty( $content['title'] ) ? $content['title'] : '',
			'description'      => ! empty( $content['description'] ) ? $content['description'] : '',
			'src'              => $url,
			'embed'            => true,
			'width'            => $content['width'],
			'height'           => $content['height'],
			'isFamilyFriendly' => true,
			'duration'         => 'PT' . $content['duration'] . 'S',
			'thumbnail'        => $content['thumbnail_large'],
			'uploadDate'       => $content['upload_date'],
		];

		return $data;
	}
}
