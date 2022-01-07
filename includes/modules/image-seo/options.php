<?php
/**
 * The images settings.
 *
 * @package    RankMath
 * @subpackage RankMath\Settings
 */

defined( 'ABSPATH' ) || exit;

$cmb->add_field(
	[
		'id'      => 'add_img_caption',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Add missing image caption', 'rank-math-pro' ),
		'desc'    => wp_kses_post( __( 'Add a caption for all images without a caption automatically. The caption is dynamically applied when the content is displayed, the stored content is not changed.', 'rank-math-pro' ) ),
		'default' => 'off',
	],
	++$fields_position
);

$cmb->add_field(
	[
		'id'              => 'img_caption_format',
		'type'            => 'text',
		'name'            => esc_html__( 'Caption format', 'rank-math-pro' ),
		'desc'            => wp_kses_post( __( 'Format used for the new captions.', 'rank-math-pro' ) ),
		'classes'         => 'large-text rank-math-supports-variables dropdown-up',
		'default'         => '%title% %count(title)%',
		'dep'             => [ [ 'add_img_caption', 'on' ] ],
		'sanitization_cb' => false,
		'attributes'      => [ 'data-exclude-variables' => 'seo_title,seo_description' ],
	],
	++$fields_position
);

$cmb->add_field(
	[
		'id'      => 'add_img_description',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Add missing image description', 'rank-math-pro' ),
		'desc'    => wp_kses_post( __( 'Add a description for all images without a description automatically. The description is dynamically applied when the content is displayed, the stored content is not changed.', 'rank-math-pro' ) ),
		'default' => 'off',
	],
	++$fields_position
);

$cmb->add_field(
	[
		'id'              => 'img_description_format',
		'type'            => 'text',
		'name'            => esc_html__( 'Description format', 'rank-math-pro' ),
		'desc'            => wp_kses_post( __( 'Format used for the new descriptions.', 'rank-math-pro' ) ),
		'classes'         => 'large-text rank-math-supports-variables dropdown-up',
		'default'         => '%title% %count(title)%',
		'dep'             => [ [ 'add_img_description', 'on' ] ],
		'sanitization_cb' => false,
		'attributes'      => [ 'data-exclude-variables' => 'seo_title,seo_description' ],
	],
	++$fields_position
);

$cmb->add_field(
	[
		'id'      => 'img_title_change_case',
		'type'    => 'select',
		'name'    => esc_html__( 'Change title casing', 'rank-math-pro' ),
		'desc'    => wp_kses_post( __( 'Capitalization settings for the <code>title</code> attribute values. This will be applied for <strong>all</strong> <code>title</code> attributes.', 'rank-math-pro' ) ),
		'default' => 'off',
		'options' => [
			'off'          => esc_html__( 'No change', 'rank-math-pro' ),
			'titlecase'    => esc_html__( 'Title Casing', 'rank-math-pro' ),
			'sentencecase' => esc_html__( 'Sentence casing', 'rank-math-pro' ),
			'lowercase'    => esc_html__( 'all lowercase', 'rank-math-pro' ),
			'uppercase'    => esc_html__( 'ALL UPPERCASE', 'rank-math-pro' ),
		],
	],
	++$fields_position
);

$cmb->add_field(
	[
		'id'      => 'img_alt_change_case',
		'type'    => 'select',
		'name'    => esc_html__( 'Change alt attribute casing', 'rank-math-pro' ),
		'desc'    => wp_kses_post( __( 'Capitalization settings for the <code>alt</code> attribute values. This will be applied for <strong>all</strong> <code>alt</code> attributes.', 'rank-math-pro' ) ),
		'default' => 'off',
		'options' => [
			'off'          => esc_html__( 'No change', 'rank-math-pro' ),
			'titlecase'    => esc_html__( 'Title Casing', 'rank-math-pro' ),
			'sentencecase' => esc_html__( 'Sentence casing', 'rank-math-pro' ),
			'lowercase'    => esc_html__( 'all lowercase', 'rank-math-pro' ),
			'uppercase'    => esc_html__( 'ALL UPPERCASE', 'rank-math-pro' ),
		],
	],
	++$fields_position
);

$cmb->add_field(
	[
		'id'      => 'img_description_change_case',
		'type'    => 'select',
		'name'    => esc_html__( 'Change description casing', 'rank-math-pro' ),
		'desc'    => wp_kses_post( __( 'Capitalization settings for the image descriptions. This will be applied for <strong>all</strong> image descriptions.', 'rank-math-pro' ) ),
		'default' => 'off',
		'options' => [
			'off'          => esc_html__( 'No change', 'rank-math-pro' ),
			'titlecase'    => esc_html__( 'Title Casing', 'rank-math-pro' ),
			'sentencecase' => esc_html__( 'Sentence casing', 'rank-math-pro' ),
			'lowercase'    => esc_html__( 'all lowercase', 'rank-math-pro' ),
			'uppercase'    => esc_html__( 'ALL UPPERCASE', 'rank-math-pro' ),
		],
	],
	++$fields_position
);


$cmb->add_field(
	[
		'id'      => 'img_caption_change_case',
		'type'    => 'select',
		'name'    => esc_html__( 'Change caption casing', 'rank-math-pro' ),
		'desc'    => wp_kses_post( __( 'Capitalization settings for the image captions.  This will be applied for <strong>all</strong> image captions.', 'rank-math-pro' ) ),
		'default' => 'off',
		'options' => [
			'off'          => esc_html__( 'No change', 'rank-math-pro' ),
			'titlecase'    => esc_html__( 'Title Casing', 'rank-math-pro' ),
			'sentencecase' => esc_html__( 'Sentence casing', 'rank-math-pro' ),
			'lowercase'    => esc_html__( 'all lowercase', 'rank-math-pro' ),
			'uppercase'    => esc_html__( 'ALL UPPERCASE', 'rank-math-pro' ),
		],
	],
	++$fields_position
);

$cmb->add_field(
	[
		'id'      => 'add_avatar_alt',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Add ALT attributes for avatars', 'rank-math-pro' ),
		'desc'    => wp_kses_post( __( 'Add <code>alt</code> attributes for commenter profile pictures (avatars) automatically. The alt attribute value will be the username.', 'rank-math-pro' ) ),
		'default' => 'off',
	],
	++$fields_position
);

$replacement_fields = $cmb->add_field( //phpcs:ignore
	[
		'id'      => 'image_replacements',
		'type'    => 'group',
		'name'    => esc_html__( 'Replacements', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Replace characters or words in the alt tags, title tags, or in the captions.', 'rank-math-pro' ),
		'options' => [
			'add_button'    => esc_html__( 'Add another', 'rank-math-pro' ),
			'remove_button' => esc_html__( 'Remove', 'rank-math-pro' ),
		],
		'classes' => 'cmb-group-text-only',
	],
	++$fields_position
);

$cmb->add_group_field(
	$replacement_fields,
	[
		'id'         => 'find',
		'type'       => 'text',
		'attributes' => [ 'placeholder' => esc_attr__( 'Find', 'rank-math-pro' ) ],
	]
);

$cmb->add_group_field(
	$replacement_fields,
	[
		'id'         => 'replace',
		'type'       => 'text',
		'attributes' => [ 'placeholder' => esc_attr__( 'Replace', 'rank-math-pro' ) ],
	]
);

$cmb->add_group_field(
	$replacement_fields,
	[
		'id'      => 'replace_in',
		'type'    => 'multicheck',
		'options' => [
			'alt'     => __( 'Alt', 'rank-math-pro' ),
			'title'   => __( 'Title', 'rank-math-pro' ),
			'caption' => __( 'Caption', 'rank-math-pro' ),
		],
	]
);
