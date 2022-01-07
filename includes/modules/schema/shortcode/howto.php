<?php
/**
 * Shortcode - HowTo
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

 defined( 'ABSPATH' ) || exit;

if ( empty( $schema['step'] ) ) {
	return;
}
?>
<div id="rank-math-howto" class="rank-math-block">
	<div class="rank-math-howto-description">
		<?php
		$shortcode->get_image();
		$shortcode->get_description();
		?>

		<div class="rank-math-howto-duration">
			<?php
			$shortcode->get_field(
				esc_html__( 'Total Time', 'rank-math-pro' ),
				'totalTime'
			);
			?>
		</div>
		<div class="rank-math-steps">
			<?php foreach ( $schema['step'] as $key => $step ) { ?>
				<div class="rank-math-step" id="<?php echo esc_attr( 'step-' . ( $key + 1 ) ); ?>">
					<h3 class="rank-math-step-title"><?php echo esc_html( $step['name'] ); ?></h3>
					<div class="rank-math-step-content">
						<?php echo esc_html( $step['itemListElement']['text'] ); ?>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
</div>
