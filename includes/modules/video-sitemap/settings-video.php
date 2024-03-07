<?php
/**
 * Sitemap - Video
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     RankMath <support@rankmath.com>
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

$cmb->add_field(
	[
		'id'   => 'hide_video_sitemap',
		'type' => 'toggle',
		'name' => esc_html__( 'Hide Sitemap', 'rank-math-pro' ),
		'desc' => esc_html__( 'Hide the sitemap from normal visitors?', 'rank-math-pro' ),
	]
);

$post_types = Helper::choices_post_types();
if ( isset( $post_types['attachment'] ) && Helper::get_settings( 'general.attachment_redirect_urls', true ) ) {
	unset( $post_types['attachment'] );
}

$cmb->add_field(
	[
		'id'      => 'video_sitemap_post_type',
		'type'    => 'multicheck_inline',
		'name'    => esc_html__( 'Video Post Type', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Select the post type where you use videos and want them to be shown in the Video search.', 'rank-math-pro' ),
		'options' => $post_types,
		'default' => array_keys( $post_types ),
	]
);

$cmb->add_field(
	[
		'id'      => 'video_sitemap_custom_fields',
		'type'    => 'textarea_small',
		'name'    => esc_html__( 'Custom Fields', 'rank-math-pro' ),
		'desc'    => esc_html__( 'List of custom fields name to check for video content. Add one per line.', 'rank-math-pro' ),
		'default' => '',
		'classes' => 'rank-math-advanced-option',
	]
);
