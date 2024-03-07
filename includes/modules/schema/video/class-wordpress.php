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
	 * The current post content.
	 *
	 * @var string
	 */
	private static $post_content = '';


	/**
	 * Match url.
	 *
	 * @param  array $data contains the Url to match and the current post.
	 * @return array
	 */
	public static function match( $data ) {
		$type = wp_check_filetype( $data['url'], wp_get_mime_types() );

		if ( ! in_array( strtolower( $type['ext'] ), wp_get_video_extensions(), true ) ) {
			return [];
		}
		self::$post_content = $data['post']->post_content;
		return self::fetch_data( $data['url'] );
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
				'width'     => ! empty( $video_details['width'] ) ? $video_details['width'] : '',
				'height'    => ! empty( $video_details['height'] ) ? $video_details['height'] : '',
				'thumbnail' => self::get_video_thumbnail( $url, $attachment_id ),
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

	/**
	 * Gets the video thumbnail URL.
	 *
	 * @param string $url           The Video URL.
	 * @param int    $attachment_id The attachment post ID.
	 *
	 * @return false|string
	 */
	private static function get_video_thumbnail( $url, $attachment_id ) {
		$blocks      = parse_blocks( self::$post_content );
		$url_pattern = str_replace( '/', '\/', $url );
		foreach ( $blocks as $block ) {
			if ( 'core/video' !== $block['blockName'] ) {
				continue;
			}

			$pattern = '/<video controls( poster="(.*)?")? src="(' . $url_pattern . ')"><\/video>/sU';
			preg_match( $pattern, $block['innerHTML'], $matches );

			if ( empty( $matches ) || ( ! empty( $matches[3] ) && $url !== $matches[3] ) ) {
				continue;
			}
			return ! empty( $matches[2] ) ? $matches[2] : get_the_post_thumbnail_url( $attachment_id );

		}
		return '';
	}
}
