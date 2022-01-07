<?php
/**
 * Add Media RSS feed.
 *
 * @since      1.0
 * @package    RankMath
 * @subpackage RankMath\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Media_RSS class.
 */
class Media_RSS {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( Helper::get_settings( 'general.disable_media_rss' ) ) {
			return;
		}

		$this->action( 'rss2_ns', 'add_namespace' );
		$this->action( 'rss2_item', 'add_video_data', 10, 1 );
	}

	/**
	 * Add namespace to RSS feed.
	 *
	 * @copyright Copyright (C) 2008-2019, Yoast BV
	 * The following code is a derivative work of the code from the Yoast(https://github.com/Yoast/wordpress-seo/), which is licensed under GPL v3.
	 */
	public function add_namespace() {
		if ( apply_filters( 'rank_math/rss/add_media_namespace', true ) ) {
			echo ' xmlns:media="http://search.yahoo.com/mrss/" ';
		}
	}

	/**
	 * Add Video Data in RSS feed.
	 *
	 * @see https://www.rssboard.org/media-rss
	 * @see https://support.google.com/news/publisher-center/answer/9545420?hl=en
	 */
	public function add_video_data() {
		global $post;
		$video_schema = get_post_meta( $post->ID, 'rank_math_schema_VideoObject', true );
		if ( empty( $video_schema ) ) {
			return;
		}

		$url = ! empty( $video_schema['contentUrl'] ) ? $video_schema['contentUrl'] : ( ! empty( $video_schema['embedUrl'] ) ? $video_schema['embedUrl'] : '' );
		if ( ! $url ) {
			return;
		}

		$attrs       = ! empty( $video_schema['width'] ) ? ' width="' . esc_attr( $video_schema['width'] ) . '"' : '';
		$attrs      .= ! empty( $video_schema['height'] ) ? ' height="' . esc_attr( $video_schema['height'] ) . '"' : '';
		$duration    = ! empty( $video_schema['duration'] ) ? Helper::duration_to_seconds( $video_schema['duration'] ) : '';
		$name        = ! empty( $video_schema['name'] ) ? Helper::replace_vars( $video_schema['name'], $post ) : '';
		$description = ! empty( $video_schema['description'] ) ? Helper::replace_vars( $video_schema['description'], $post ) : '';
		$thumbnail   = ! empty( $video_schema['thumbnailUrl'] ) ? Helper::replace_vars( $video_schema['thumbnailUrl'], $post ) : '';
		$tags        = ! empty( $video_schema['metadata']['tags'] ) ? Helper::replace_vars( $video_schema['metadata']['tags'] ) : '';
		$categories  = ! empty( $video_schema['metadata']['category'] ) ? Helper::replace_vars( $video_schema['metadata']['category'] ) : '';
		$rating      = ! empty( $video_schema['isFamilyFriendly'] ) ? 'nonadult' : 'adult';

		$this->newline( '<media:content url="' . esc_url( $url ) . '" medium="video"' . $attrs . '>' );
		$this->newline( '<media:player>' . esc_url( $url ) . '</media:player>', 3 );

		if ( $name ) {
			$this->newline( '<media:title type="plain">' . esc_html( $name ) . '</media:title>', 3 );
		}

		if ( $description ) {
			$this->newline( '<media:description type="html"><![CDATA[' . wp_kses_post( $description ) . ']]></media:description>', 3 );
		}

		if ( $thumbnail ) {
			$this->newline( '<media:thumbnail url="' . esc_url( $thumbnail ) . '" />', 3 );
		}

		if ( $tags ) {
			$this->newline( '<media:keywords>' . esc_html( $tags ) . '</media:keywords>', 3 );
		}

		if ( $categories ) {
			$this->newline( '<media:category>' . esc_html( $categories ) . '</media:category>', 3 );
		}

		if ( $rating ) {
			$this->newline( '<media:rating scheme="urn:simple">' . esc_html( $rating ) . '</media:rating>', 3 );
		}
		$this->newline( '</media:content>', 2 );
	}

	/**
	 * Write a newline with indent count.
	 *
	 * @param string  $content Content to write.
	 * @param integer $indent  Count of indent.
	 */
	private function newline( $content, $indent = 0 ) {
		echo str_repeat( "\t", $indent ) . $content . "\n";
	}
}
