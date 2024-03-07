<?php
/**
 * Shortcode - Product
 *
 * @since      2.7.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Schema
 */

defined( 'ABSPATH' ) || exit;

$shortcode->get_title();
$shortcode->get_image();

$offers         = $shortcode->get_field_value( 'offers' );
$positive_notes = $shortcode->get_field_value( 'review.positiveNotes' );
$negative_notes = $shortcode->get_field_value( 'review.negativeNotes' );
?>
<div class="rank-math-review-data">

	<?php $shortcode->get_description(); ?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Product SKU', 'rank-math-pro' ),
		'sku'
	);
	?>

	<?php
	$brand = $shortcode->get_field_value( 'brand' );
	if ( ! empty( $brand['url'] ) && ! empty( $brand['name'] ) ) {
		?>
			<p>
				<strong><?php echo esc_html__( 'Product Brand', 'rank-math-pro' ); ?>: </strong>
				<a href="<?php echo esc_url( $brand['url'] ); ?>"><?php echo esc_html( $brand['name'] ); ?></a>
			</p>
		<?php
	} else {
			$shortcode->get_field(
				esc_html__( 'Product Brand', 'rank-math-pro' ),
				'brand.name'
			);
	}
	?>

	<?php
	if (
		! empty( $offers['price'] ) ||
		(
			empty( $positive_notes ) &&
			empty( $negative_notes )
		)
	) {
	?>

		<?php
		$shortcode->get_field(
			esc_html__( 'Product Currency', 'rank-math-pro' ),
			'offers.priceCurrency'
		);
		?>

		<?php
		$shortcode->get_field(
			esc_html__( 'Product Price', 'rank-math-pro' ),
			'offers.price'
		);
		?>

		<?php
		$shortcode->get_field(
			esc_html__( 'Price Valid Until', 'rank-math-pro' ),
			'offers.priceValidUntil'
		);
		?>

		<?php
		$shortcode->get_field(
			esc_html__( 'Product In-Stock', 'rank-math-pro' ),
			'offers.availability'
		);
		?>

	<?php } ?>

	<?php $shortcode->show_ratings(); ?>

</div>
