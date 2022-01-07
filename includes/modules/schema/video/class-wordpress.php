<?php
/**
 * The WordPress
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
 * WordPress class.
 */
class WordPress {

	/**
	 * Match url.
	 *
	 * @param  string $url Url to match.
	 * @return bool
	 */
	public static function match( $url ) {
		$type = wp_check_filetype( $url, wp_get_mime_types() );

		if ( ! in_array( strtolower( $type['ext'] ), wp_get_video_extensions(), true ) ) {
			return [];
		}

		return self::fetch_data( $url );
	}

	/**
	 * Fetch data.
	 *
	 * @param  string $url      Video Source.
	 * @return array
	 */
	private static function fetch_data( $url ) {
		$data          = [];
		$attachment_id = attachment_url_to_postid( $url );
		if ( $attachment_id ) {
			$video_details = wp_get_attachment_metadata( $attachment_id );
			$data          = [
				'width'  => ! empty( $video_details['width'] ) ? $video_details['width'] : '',
				'height' => ! empty( $video_details['height'] ) ? $video_details['height'] : '',
			];
		}

		return array_merge(
			[
				'src'   => $url,
				'embed' => false,
			],
			$data
		);
	}
}
