<?php
/**
 * The Video Schema generator.
 *
 * @since      2.0.9
 * @package    RankMathPro
 * @subpackage RankMathPro\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Video_Schema_Generator class.
 */
class Video_Schema_Generator extends \WP_Background_Process {
	/**
	 * Prefix.
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'rank_math';

	/**
	 * Action.
	 *
	 * @var string
	 */
	protected $action = 'add_video_schema';

	/**
	 * Main instance
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Video_Schema_Generator
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) && ! ( $instance instanceof Video_Schema_Generator ) ) {
			$instance = new Video_Schema_Generator();
		}

		return $instance;
	}

	/**
	 * Start creating batches.
	 *
	 * @param [type] $posts [description].
	 */
	public function start( $posts ) {
		$chunks = array_chunk( $posts, 10 );
		foreach ( $chunks as $chunk ) {
			$this->push_to_queue( $chunk );
		}

		$this->save()->dispatch();
	}

	/**
	 * Complete.
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		delete_option( 'rank_math_video_posts' );
		Helper::add_notification(
			esc_html__( 'Rank Math: Added Video Schema to posts successfully.', 'rank-math-pro' ),
			[
				'type'    => 'success',
				'id'      => 'rank_math_video_posts',
				'classes' => 'rank-math-notice',
			]
		);

		parent::complete();
	}

	/**
	 * Task to perform
	 *
	 * @param array $posts Posts to process.
	 *
	 * @return bool
	 */
	protected function task( $posts ) {
		try {
			foreach ( $posts as $post ) {
				$this->convert( $post );
			}
			return false;
		} catch ( Exception $error ) {
			return true;
		}
	}

	/**
	 * Convert post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function convert( $post_id ) {
		update_post_meta( $post_id, '_rank_math_video', 'true' );
		( new Video\Parser( get_post( $post_id ) ) )->save();
	}

	/**
	 * Find posts.
	 *
	 * @return array
	 */
	public function find_posts() {
		global $wpdb;
		$posts = get_option( 'rank_math_video_posts' );
		if ( false !== $posts ) {
			return $posts;
		}

		// Schema Posts.
		$post_types = array_filter(
			Helper::get_accessible_post_types(),
			function( $post_type ) {
				return 'attachment' !== $post_type && Helper::get_settings( "titles.pt_{$post_type}_autodetect_video", 'on' );
			}
		);

		$posts = get_posts(
			[
				'post_type'   => array_keys( $post_types ),
				'numberposts' => -1,
				'fields'      => 'ids',
				'meta_query'  => [
					[
						'key'     => 'rank_math_schema_VideoObject',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);

		update_option( 'rank_math_video_posts', $posts );

		return $posts;
	}
}
