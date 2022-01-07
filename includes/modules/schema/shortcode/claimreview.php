<?php
/**
 * Shortcode - course
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

defined( 'ABSPATH' ) || exit;

$shortcode->get_description( $schema['claimReviewed'] );
$shortcode->get_image();

?>
<div class="rank-math-review-data">

	<?php
	$shortcode->get_field(
		esc_html__( 'URL', 'rank-math-pro' ),
		'url'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Author Name', 'rank-math-pro' ),
		'itemReviewed.author.name'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Published Date', 'rank-math-pro' ),
		'itemReviewed.datePublished'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Appearance Headline', 'rank-math-pro' ),
		'itemReviewed.appearance.headline'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Appearance URL', 'rank-math-pro' ),
		'itemReviewed.appearance.url'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Appearance Author', 'rank-math-pro' ),
		'itemReviewed.appearance.author.name'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Appearance Published Date', 'rank-math-pro' ),
		'itemReviewed.appearance.datePublished'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Alternate Name', 'rank-math-pro' ),
		'reviewRating.alternateName'
	);
	?>

	<?php $shortcode->show_ratings( 'reviewRating.ratingValue' ); ?>
</div>
