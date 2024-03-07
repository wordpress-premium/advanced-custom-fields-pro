<?php
/**
 * The DailyMotion Video.
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
 * DailyMotion class.
 */
class DailyMotion {

	/**
	 * Match url.
	 *
	 * @param  string $url Url to match.
	 * @return bool
	 */
	public static function match( $url ) {
		if ( ! Str::contains( 'dailymotion.com', $url ) ) {
			return false;
		}

		$id = strtok( basename( $url ), '_' );
		if ( empty( $id ) ) {
			return false;
		}
		return self::fetch_data( $id, $url );
	}

	/**
	 * Fetch data.
	 *
	 * @param  string $video_id Video ID.
	 * @param  string $url      Video Source.
	 * @return array
	 *
	 * @link https://developer.dailymotion.com/api/#video-fields
	 */
	private static function fetch_data( $video_id, $url ) {
		$data = [
			'src'   => $url,
			'embed' => true,
		];

		$response = wp_remote_get( "https://api.dailymotion.com/video/{$video_id}?fields=title,description,duration,thumbnail_url,width,height,created_time" );
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
			'width'            => $content['width'],
			'height'           => $content['height'],
			'isFamilyFriendly' => true,
			'duration'         => 'PT' . $content['duration'] . 'S',
			'thumbnail'        => $content['thumbnail_url'],
			'uploadDate'       => gmdate( 'Y-m-d\TH:i:s', $content['created_time'] ),
		];

		return $data;
	}
}
