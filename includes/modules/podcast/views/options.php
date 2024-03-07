<?php
/**
 * Podcast general settings.
 *
 * @package    RankMath
 * @subpackage RankMathPro\Schema
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

$cmb->add_field(
	[
		'id'      => 'podcast_title',
		'type'    => 'text',
		'name'    => esc_html__( 'Podcast Name', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Name of the podcast.', 'rank-math-pro' ),
		'classes' => 'rank-math-supports-variables',
		'default' => '%sitename%',
	]
);

$cmb->add_field(
	[
		'id'      => 'podcast_description',
		'type'    => 'textarea_small',
		'name'    => esc_html__( 'Podcast Description', 'rank-math-pro' ),
		'desc'    => esc_html__( 'A plaintext description of the podcast.', 'rank-math-pro' ),
		'classes' => 'rank-math-supports-variables',
		'default' => '%sitedesc%',
	]
);

$cmb->add_field(
	[
		'id'   => 'podcast_owner',
		'type' => 'text',
		'name' => esc_html__( 'Owner Name', 'rank-math-pro' ),
		'desc' => esc_html__( 'The podcast owner contact name.', 'rank-math-pro' ),
	]
);

$cmb->add_field(
	[
		'id'   => 'podcast_owner_email',
		'type' => 'text',
		'name' => esc_html__( 'Owner Email ', 'rank-math-pro' ),
		'desc' => esc_html__( 'The email address of the podcast owner. Please make sure the email address is active and monitored.', 'rank-math-pro' ),
	]
);

$cmb->add_field(
	[
		'id'      => 'podcast_category',
		'type'    => 'select',
		'name'    => esc_html__( 'Podcast Category', 'rank-math-pro' ),
		'options' => [
			''                               => esc_html__( 'None', 'rank-math-pro' ),
			'Arts'                           => esc_html__( 'Arts', 'rank-math-pro' ),
			'Business'                       => esc_html__( 'Business', 'rank-math-pro' ),
			'Comedy'                         => esc_html__( 'Comedy', 'rank-math-pro' ),
			'Education'                      => esc_html__( 'Education', 'rank-math-pro' ),
			'Games &amp; Hobbies'            => esc_html__( 'Games &amp; Hobbies', 'rank-math-pro' ),
			'Government &amp; Organizations' => esc_html__( 'Government &amp; Organizations', 'rank-math-pro' ),
			'Health'                         => esc_html__( 'Health', 'rank-math-pro' ),
			'Kids &amp; Family'              => esc_html__( 'Kids &amp; Family', 'rank-math-pro' ),
			'Music'                          => esc_html__( 'Music', 'rank-math-pro' ),
			'News &amp; Politics'            => esc_html__( 'News &amp; Politics', 'rank-math-pro' ),
			'Religion &amp; Spirituality'    => esc_html__( 'Religion &amp; Spirituality', 'rank-math-pro' ),
			'Science &amp; Medicine'         => esc_html__( 'Science &amp; Medicine', 'rank-math-pro' ),
			'Society &amp; Culture'          => esc_html__( 'Society &amp; Culture', 'rank-math-pro' ),
			'Sports &amp; Recreation'        => esc_html__( 'Sports &amp; Recreation', 'rank-math-pro' ),
			'TV &amp; Film'                  => esc_html__( 'TV &amp; Film', 'rank-math-pro' ),
			'Technology'                     => esc_html__( 'Technology', 'rank-math-pro' ),
		],
		'default' => '',
		'desc'    => esc_html__( 'Select the category that best reflects the content of your show.', 'rank-math-pro' ),
	]
);

$cmb->add_field(
	[
		'id'      => 'podcast_image',
		'type'    => 'file',
		'name'    => esc_html__( 'Podcast Image', 'rank-math-pro' ),
		'desc'    => __( '<strong>Min Size: 1400x1400px, Max Size: 3000x3000px</strong>.<br /> The filesize should not exceed 0.5MB.', 'rank-math-pro' ),
		'options' => [ 'url' => false ],
	]
);

$cmb->add_field(
	[
		'id'   => 'podcast_tracking_prefix',
		'type' => 'text',
		'name' => esc_html__( 'Tracking Prefix', 'rank-math-pro' ),
		'desc' => esc_html__( 'Add the tracking prefix provided by your tracking service like Chartable, Podsights, Podtrac, etc.', 'rank-math-pro' ),
	]
);


$cmb->add_field(
	[
		'id'      => 'podcast_explicit',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Is Explicit', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Indicates whether the podcast is explicit language or adult content.', 'rank-math-pro' ),
		'default' => 'off',
	]
);

$cmb->add_field(
	[
		'id'   => 'podcast_copyright_text',
		'type' => 'text',
		'name' => esc_html__( 'Copyright Text', 'rank-math-pro' ),
		'desc' => esc_html__( 'Add copyright details if your show is copyrighted.', 'rank-math-pro' ),
	]
);
