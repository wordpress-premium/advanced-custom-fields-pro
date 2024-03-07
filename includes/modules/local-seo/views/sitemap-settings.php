<?php
/**
 * Locations KML File settings.
 *
 * @since      2.2.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     RankMath <support@rankmath.com>
 */

defined( 'ABSPATH' ) || exit;

$cmb->add_field(
	[
		'id'      => 'local_sitemap',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Include KML File in the Sitemap', 'rank-math-pro' ),
		'desc'    => esc_html__( 'locations.kml Sitemap is generated automatically when the Local SEO module is enabled, and the geo-coordinates are added.', 'rank-math-pro' ),
		'default' => 'on',
	]
);
