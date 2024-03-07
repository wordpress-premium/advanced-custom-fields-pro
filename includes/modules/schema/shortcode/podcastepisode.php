<?php
/**
 * Shortcode - PodcastEpisode
 *
 * @since      3.0.17
 * @package    RankMathPro
 * @subpackage RankMathPro\Schema
 */

use RankMath\Helper;
use RankMath\Helpers\Url;

defined( 'ABSPATH' ) || exit;

if ( empty( $schema['associatedMedia'] ) || empty( $schema['associatedMedia']['contentUrl'] ) ) {
	return;
}

$post_title    = get_the_title( $post->ID );
$episode_title = $schema['name'];
if ( $schema['name'] === $post_title && $post->ID === get_the_ID() ) {
	$episode_title = '';
}

/**
 * Filter: 'rank_math/schema/podcast_episode_title' - Allow changing the title of the podcast episode. Pass false to disable.
 *
 * @var string $post_title The title of the podcast episode.
 *
 * @param WP_Post $post   The post object.
 * @param array   $schema The schema array.
 */
$episode_title = apply_filters( 'rank_math/schema/podcast_episode_title', $episode_title, $post, $schema );

$season        = ! empty( $schema['partOfSeason'] ) ? $schema['partOfSeason'] : [];
$time_required = [];
if ( isset( $schema['timeRequired'] ) && Helper::get_formatted_duration( $schema['timeRequired'] ) ) {
	$duration        = new \DateInterval( $schema['timeRequired'] );
	$time_required[] = ! empty( $duration->h ) ? sprintf( esc_html__( '%d Hour', 'rank-math-pro' ), $duration->h ) : '';
	$time_required[] = ! empty( $duration->i ) ? sprintf( esc_html__( '%d Min', 'rank-math-pro' ), $duration->i ) : '';
	$time_required[] = ! empty( $duration->s ) ?sprintf( esc_html__( '%d Sec', 'rank-math-pro' ), $duration->s ) : '';
	$time_required   = array_filter( $time_required );
}

ob_start();
?>
<!-- wp:columns -->
<div class="wp-block-columns" style="gap: 2em;">
	<!-- wp:column -->
	<?php if ( ! empty( $schema['thumbnailUrl'] ) && Url::is_url( $schema['thumbnailUrl'] ) ) {
		$image_id = attachment_url_to_postid( $schema['thumbnailUrl'] );
		$img      = '<img src="' . esc_url( $schema['thumbnailUrl'] ) . '" />';

		if ( $image_id ) {
			$img = wp_get_attachment_image( $image_id, 'medium', false, [ 'class' => 'wp-image-' . $image_id ] );
		}
		?>
		<div class="wp-block-column" style="flex: 0 0 25%;">
			<!-- wp:image -->
				<figure class="wp-block-image size-medium is-resized">
					<?php echo wp_kses_post( $img ); ?>
				</figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->
	<?php } ?>

	<!-- wp:column -->
	<div class="wp-block-column">
		<!-- wp:paragraph -->
		<p>
			<?php if ( ! empty( $schema['datePublished'] ) ) { ?>
				<span class="rank-math-podcast-date">
					<?php echo esc_html( date( "j F", strtotime( $schema['datePublished'] ) ) ); ?>
				</span> &#183;
			<?php } ?>
			<span>
				<?php if ( ! empty( $season['seasonNumber'] ) ) { ?>
					<?php echo esc_html__( 'Season', 'rank-math-pro' ); ?> <?php echo esc_html( $season['seasonNumber'] ); ?>
					<?php if ( ! empty( $season['name'] ) ) { ?>
						: <?php if ( ! empty( $season['url'] ) ) { ?>
							<a href="<?php echo esc_url( $season['url'] ); ?>"><?php echo esc_html( $season['name'] ); ?></a>
						<?php } else { ?>
							<?php echo esc_html( $season['name'] ); ?>
						<?php } ?>
					<?php } ?> &#183;
				<?php } ?>

				<?php if ( ! empty( $schema['episodeNumber'] ) ) { ?>
					<?php echo esc_html__( 'Episode', 'rank-math-pro' ); ?> <?php echo esc_html( $schema['episodeNumber'] ); ?>
				<?php } ?>
			</span>
		</p>
		<!-- /wp:paragraph -->

		<?php if ( $episode_title ) { ?>
			<!-- wp:heading -->
				<h2>
					<?php echo esc_html( $episode_title ); ?>
				</h2>
			<!-- /wp:heading -->
		<?php } ?>

		<!-- wp:paragraph -->
			<p>
				<?php if ( ! empty( $time_required ) ) { ?>
					<span>
						<?php echo implode( ', ', $time_required ); ?>
					</span>
					&#183;
				<?php } ?>
				<?php if ( ! empty( $schema['author'] ) ) { ?>
					<?php echo esc_html__( 'By', 'rank-math-pro' ); ?> <?php echo esc_html( $schema['author']['name'] ); ?>
				<?php } ?>
			</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:column -->
</div>
<!-- /wp:columns -->
<!-- wp:audio -->
<figure class="wp-block-audio">
	<audio controls src="<?php echo esc_url( $schema['associatedMedia']['contentUrl'] ); ?>"></audio>
</figure>
<!-- /wp:audio -->

<?php if ( ! empty( $schema['description'] ) ) { ?>
	<!-- wp:paragraph -->
		<p><?php echo esc_html( $schema['description'] ); ?></p>
	<!-- /wp:paragraph -->
<?php } ?>
<?php

echo do_blocks( ob_get_clean() );
