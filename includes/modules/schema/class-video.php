<?php
/**
 * The Video Schema.
 *
 * @since      1.0
 * @package    RankMath
 * @subpackage RankMath\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * Video class.
 */
class Video {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		Video_Schema_Generator::get();

		if ( Conditional::is_rest() ) {
			$this->filter( 'rank_math/tools/generate_video_schema', 'generate_video_schema' );
		}

		$this->action( 'rank_math/pre_update_metadata', 'detect_video_in_content', 10, 3 );
		if ( is_admin() ) {
			$this->action( 'cmb2_admin_init', 'add_video_settings' );
			$this->action( 'rank_math/admin/settings/others', 'add_media_rss_field' );
			$this->filter( 'rank_math/database/tools', 'generate_video_schema_tool' );

			return;
		}

		$this->action( 'rank_math/opengraph/facebook', 'add_video_tags', 99 );
		new Media_RSS();
	}

	/**
	 * Add auto-detect Video fields in Titles & Meta settings.
	 */
	public function add_video_settings() {
		foreach ( Helper::get_accessible_post_types() as $post_type ) {
			$this->action( "rank_math/admin/settings/post-type-{$post_type}", 'add_video_schema_fields', 10, 2 );
		}
	}

	/**
	 * Add auto-generate video schema settings.
	 *
	 * @param object $cmb CMB2 instance.
	 * @param array  $tab Current settings tab.
	 */
	public function add_video_schema_fields( $cmb, $tab ) {
		if ( 'attachment' === $tab['post_type'] ) {
			return;
		}

		$field_ids      = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$field_position = array_search( "pt_{$tab['post_type']}_default_article_type", array_keys( $field_ids ), true ) + 1;

		$cmb->add_field(
			[
				'id'      => 'pt_' . $tab['post_type'] . '_autodetect_video',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Autodetect Video', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Populate automatic Video Schema by auto-detecting any video in the content.', 'rank-math-pro' ),
				'options' => [
					'off' => esc_html__( 'Default', 'rank-math-pro' ),
					'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
				],
				'default' => 'on',
			],
			++$field_position
		);

		$cmb->add_field(
			[
				'id'      => 'pt_' . $tab['post_type'] . '_autogenerate_image',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Autogenerate Image', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Auto-generate image for the auto detected video.', 'rank-math-pro' ),
				'options' => [
					'off' => esc_html__( 'Default', 'rank-math-pro' ),
					'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
				],
				'default' => 'off',
				'dep'     => [ [ 'pt_' . $tab['post_type'] . '_autodetect_video', 'on' ] ],
			],
			++$field_position
		);
	}

	/**
	 * Add new settings.
	 *
	 * @param object $cmb CMB2 instance.
	 */
	public function add_media_rss_field( $cmb ) {
		$field_ids      = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$field_position = array_search( 'rss_after_content', array_keys( $field_ids ), true ) + 1;

		$cmb->add_field(
			[
				'id'      => 'disable_media_rss',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Remove Media Data from RSS feed', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Remove Media Data from RSS feed', 'rank-math-pro' ),
				'options' => [
					'off' => esc_html__( 'Default', 'rank-math-pro' ),
					'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
				],
				'default' => 'off',
			],
			++$field_position
		);
	}

	/**
	 * Output the video tags.
	 *
	 * @link https://yandex.com/support/video/partners/open-graph.html#player
	 *
	 * @param OpenGraph $opengraph The current opengraph network object.
	 */
	public function add_video_tags( $opengraph ) {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		$video_data = get_post_meta( $post->ID, 'rank_math_schema_VideoObject', true );
		if ( empty( $video_data ) ) {
			return;
		}

		$tags = [
			'ya:ovs:adult'       => ! empty( $video_data['isFamilyFriendly'] ) ? false : true,
			'ya:ovs:upload_date' => ! empty( $video_data['uploadDate'] ) ? Helper::replace_vars( $video_data['uploadDate'], $post ) : '',
			'ya:ovs:allow_embed' => ! empty( $video_data['embedUrl'] ) ? 'true' : 'false',
		];

		foreach ( $tags as $tag => $value ) {
			$opengraph->tag( $tag, $value );
		}
	}

	/**
	 * Automatically add Video Schema when post is updated.
	 *
	 * @param int    $object_id   Object ID.
	 * @param int    $object_type Object type.
	 * @param string $content     Updated post content.
	 */
	public function detect_video_in_content( $object_id, $object_type, $content = '' ) {
		if ( 'post' !== $object_type ) {
			return;
		}

		$post = get_post( $object_id );
		if ( $content ) {
			$post->post_content = $content;
		}

		( new Video\Parser( $post ) )->save();
	}

	/**
	 * Add database tools.
	 *
	 * @param array $tools Array of tools.
	 *
	 * @return array
	 */
	public function generate_video_schema_tool( $tools ) {
		$posts = Video_Schema_Generator::get()->find_posts();
		if ( empty( $posts ) ) {
			return $tools;
		}

		$generate_video_schema = [
			'generate_video_schema' => [
				'title'        => esc_html__( 'Generate Video Schema for Old Posts/Pages', 'rank-math-pro' ),
				'description'  => esc_html__( 'Add Video schema to posts which have YouTube or Vimeo Video in the content. Applies to only those Posts/Pages/CPTs in which Autodetect Video Option is On.', 'rank-math-pro' ),
				'confirm_text' => esc_html__( 'Are you sure you want to add Video Schema to the posts/pages with the video in the content? This action is irreversible.', 'rank-math-pro' ),
				'button_text'  => esc_html__( 'Generate', 'rank-math-pro' ),
			],
		];

		$index = array_search( 'recreate_tables', array_keys( $tools ), true );
		$pos   = false === $index ? count( $tools ) : $index + 1;
		$tools = array_slice( $tools, 0, $pos, true ) + $generate_video_schema + array_slice( $tools, $pos, count( $tools ) - 1, true );

		return $tools;
	}

	/**
	 * Detect Video in the content and add schema.
	 *
	 * @return string
	 */
	public function generate_video_schema() {
		$posts = Video_Schema_Generator::get()->find_posts();
		if ( empty( $posts ) ) {
			return esc_html__( 'No posts found to convert.', 'rank-math-pro' );
		}

		Video_Schema_Generator::get()->start( $posts );

		return esc_html__( 'Conversion started. A success message will be shown here once the process completes. You can close this page.', 'rank-math-pro' );
	}
}
