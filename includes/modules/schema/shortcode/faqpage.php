<?php
/**
 * Shortcode - FAQPage
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $schema['mainEntity'] ) ) {
	return;
}

?>
<div id="rank-math-faq" class="rank-math-block">
	<div class="rank-math-list">
		<?php foreach ( $schema['mainEntity'] as $key => $entity ) { ?>
			<div class="rank-math-list-item" id="<?php echo esc_attr( 'faq-' . ( $key + 1 ) ); ?>">
				<?php if ( ! empty( $entity['name'] ) ) { ?>
					<h3 class="rank-math-question"><?php echo esc_html( $entity['name'] ); ?></h3>
				<?php } ?>

				<?php if ( ! empty( $entity['acceptedAnswer']['text'] ) ) { ?>
					<div class="rank-math-answer">
						<?php if ( ! empty( $entity['image'] ) ) { ?>
							<img src="<?php echo esc_url( $entity['image'] ); ?>" class="alignright" width="150" height="150" />
						<?php } ?>
						<p><?php echo wp_kses_post( $entity['acceptedAnswer']['text'] ); ?></p>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
	</div>
</div>
