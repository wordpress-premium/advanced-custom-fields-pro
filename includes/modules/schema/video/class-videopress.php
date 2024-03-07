<?php
/**
 * The VideoPress.
 *
 * @since      2.7.1
 * @package    RankMath
 * @subpackage RankMath\Schema\Video
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema\Video;

use RankMath\Helpers\Str;

defined( 'ABSPATH' ) || exit;

/**
 * VideoPress class.
 */
class VideoPress {

	/**
	 * Match url.
	 *
	 * @param  string $url Url to match.
	 * @return bool
	 */
	public static function match( $url ) {
		if ( ! Str::contains( 'video.wordpress.com', $url ) ) {
			return false;
		}

		$video_id = str_replace( 'https://video.wordpress.com/embed/', '', $url );
		if ( ! $video_id ) {
			return false;
		}

		return self::fetch_data( $video_id, $url );
	}

	/**
	 * Fetch data.
	 *
	 * @param  string $video_id Video ID.
	 * @param  string $url      Video Source.
	 * @return array
	 *
	 * @see https://developer.wordpress.com/docs/api/1/get/videos/%24guid/
	 */
	private static function fetch_data( $video_id, $url ) {
		$data = [
			'src'   => $url,
			'embed' => true,
		];

		$response = wp_remote_get( "https://public-api.wordpress.com/rest/v1.1/videos/{$video_id}" );
		if (
			is_wp_error( $response ) ||
			! in_array( wp_remote_retrieve_response_code( $response ), [ 200, 204 ], true )
		) {
			return $data;
		}

		$content = wp_remote_retrieve_body( $response );
		$content = json_decode( $content, true );
		$data    = [
			'name'             => ! empty( $content['title'] ) ? $content['title'] : '',
			'description'      => ! empty( $content['description'] ) ? $content['description'] : '',
			'src'              => $url,
			'embed'            => true,
			'width'            => isset( $content['width'] ) ? $content['width'] : '',
			'height'           => isset( $content['height'] ) ? $content['height'] : '',
			'isFamilyFriendly' => true,
			'duration'         => isset( $content['duration'] ) ? 'PT' . $content['duration'] . 'S' : '',
			'thumbnail'        => isset( $content['poster'] ) ? $content['poster'] : '',
			'uploadDate'       => isset( $content['upload_date'] ) ? $content['upload_date'] : '',
		];

		return $data;
	}
}
