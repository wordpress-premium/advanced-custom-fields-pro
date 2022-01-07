<?php
/**
 * Shortcode - Movie
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

defined( 'ABSPATH' ) || exit;

$shortcode->get_title();
$shortcode->get_image();

?>
<div class="rank-math-review-data">

	<?php
	$shortcode->get_field(
		esc_html__( 'Director', 'rank-math-pro' ),
		'director'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Date Created', 'rank-math-pro' ),
		'dateCreated',
		true
	);
	?>

	<?php $shortcode->show_ratings(); ?>
</div>
