<?php
/**
 * Add Podcasts RSS feed.
 *
 * @since      3.0.17
 * @package    RankMath
 * @subpackage RankMathPro\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Podcast;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Media_RSS class.
 */
class Podcast_RSS {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$prefix = 'rss2_podcast';
		if ( apply_filters( 'rank_math/podcast/enhance_all_feeds', true ) ) {
			$prefix = 'rss2';
		}

		remove_action( 'rss2_head', 'rss2_site_icon' );
		$this->action( "{$prefix}_ns", 'add_namespace' );
		$this->action( "{$prefix}_head", 'add_channel_data' );
		$this->action( "{$prefix}_item", 'add_podcast_data', 10, 1 );
	}

	/**
	 * Add namespace to RSS feed.
	 */
	public function add_namespace() {
		if ( apply_filters( 'rank_math/rss/add_podcasts_namespace', true ) ) {
			echo 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" ';
		}

		$this->filter( 'get_wp_title_rss', 'feed_title' );
		$this->filter( 'bloginfo_rss', 'feed_description', 10, 2 );
	}

	/**
	 * Change the feed title.
	 *
	 * @param string $wp_title_rss The current blog title.
	 */
	public function feed_title( $wp_title_rss ) {
		$podcast_title = Helper::get_settings( 'general.podcast_title' );
		if ( $podcast_title ) {
			return Helper::replace_vars( $podcast_title );
		}

		return $wp_title_rss;
	}

	/**
	 * Change the feed description.
	 *
	 * @param string $value RSS container for the blog information.
	 * @param string $show  The type of blog information to retrieve.
	 */
	public function feed_description( $value, $show ) {
		if ( 'description' !== $show ) {
			return $value;
		}

		$podcast_description = Helper::get_settings( 'general.podcast_description' );
		if ( $podcast_description ) {
			return Helper::replace_vars( $podcast_description );
		}

		return $value;
	}

	/**
	 * Add Podcast channel data
	 */
	public function add_channel_data() {
		$category = Helper::get_settings( 'general.podcast_category' );
		if ( $category ) {
			$this->newline( '<itunes:category text="' . esc_attr( $category ) . '" />', 1 );
		}

		$author_name  = Helper::get_settings( 'general.podcast_owner' );
		$author_email = Helper::get_settings( 'general.podcast_owner_email' );
		if ( $author_email ) {
			$this->newline( '<itunes:author>' . esc_html( $author_name ) . '</itunes:author>', 1 );
			$this->newline( '<itunes:owner>', 1 );
			if ( $author_email ) {
				$this->newline( '<itunes:name>' . esc_html( $author_name ) . '</itunes:name>', 2 );
			}
			$this->newline( '<itunes:email>' . esc_html( $author_email ) . '</itunes:email>', 2 );
			$this->newline( '</itunes:owner>', 1 );
		}

		$image = Helper::get_settings( 'general.podcast_image' );
		if ( $image ) {
			$this->newline( '<itunes:image href="' . esc_url( $image ) . '" />', 1 );
			$this->newline( '<image>', 1 );
			$this->newline( '<title>' . get_wp_title_rss() . '</title>', 2 );
			$this->newline( '<url>' . esc_url( $image ) . '</url>', 2 );
			$this->newline( '<link>' . get_bloginfo_rss( 'url' ) . '</link>', 2 );
			$this->newline( '</image>', 1 );
		}

		$title = Helper::get_settings( 'general.podcast_title' );
		if ( $title ) {
			$this->newline( '<itunes:subtitle>' . esc_html( Helper::replace_vars( $title ) ) . '</itunes:subtitle>', 1 );
		}

		$summary = Helper::get_settings( 'general.podcast_description' );
		if ( $summary ) {
			$this->newline( '<itunes:summary>' . esc_html( Helper::replace_vars( $summary ) ) . '</itunes:summary>', 1 );
		}

		$is_explicit = Helper::get_settings( 'general.podcast_explicit' ) ? 'yes' : 'clean';
		$this->newline( '<itunes:explicit>' . $is_explicit . '</itunes:explicit>', 1 );

		$copyright = Helper::get_settings( 'general.podcast_copyright_text' );
		if ( $copyright ) {
			$copyright = str_replace( '©', '&#xA9;', $copyright );
			$this->newline( '<copyright>' . esc_html( $copyright ) . '</copyright>', 1 );
		}
	}

	/**
	 * Add Podcast Data in RSS feed.
	 *
	 * @see https://support.google.com/podcast-publishers/answer/9889544
	 * @see https://podcasters.apple.com/support/823-podcast-requirements
	 */
	public function add_podcast_data() {
		global $post;
		$podcast = get_post_meta( $post->ID, 'rank_math_schema_PodcastEpisode', true );
		if ( empty( $podcast ) ) {
			return;
		}

		$title          = ! empty( $podcast['name'] ) ? Helper::replace_vars( $podcast['name'], $post ) : '';
		$description    = ! empty( $podcast['description'] ) ? Helper::replace_vars( $podcast['description'], $post ) : '';
		$audio_file     = Helper::replace_vars( $podcast['associatedMedia']['contentUrl'], $post );
		$duration       = ! empty( $podcast['timeRequired'] ) ? Helper::duration_to_seconds( $podcast['timeRequired'] ) : '';
		$image          = ! empty( $podcast['thumbnailUrl'] ) ? Helper::replace_vars( $podcast['thumbnailUrl'], $post ) : '';
		$author         = ! empty( $podcast['author'] ) ? Helper::replace_vars( $podcast['author']['name'], $post ) : '';
		$is_explicit    = empty( $podcast['isFamilyFriendly'] ) ? 'yes' : 'clean';
		$episode_number = ! empty( $podcast['episodeNumber'] ) ? $podcast['episodeNumber'] : '';
		$season_number  = ! empty( $podcast['partOfSeason'] ) && ! empty( $podcast['partOfSeason']['seasonNumber'] ) ? $podcast['partOfSeason']['seasonNumber'] : '';

		if ( $title ) {
			$this->newline( '<itunes:title>' . wp_kses_post( $title ) . '</itunes:title>' );
		}

		if ( $description ) {
			$this->newline( '<itunes:summary>' . wp_kses_post( $description ) . '</itunes:summary>', 2 );
		}

		if ( $image ) {
			$this->newline( '<itunes:image href="' . esc_url( $image ) . '" />', 2 );
		}

		if ( $duration ) {
			$this->newline( '<itunes:duration>' . $duration . '</itunes:duration>', 2 );
		}

		if ( $author ) {
			$this->newline( '<itunes:author>' . esc_html( $author ) . '</itunes:author>', 2 );
		}

		if ( $season_number ) {
			$this->newline( '<itunes:season>' . esc_html( $season_number ) . '</itunes:season>', 2 );
		}

		if ( $episode_number ) {
			$this->newline( '<itunes:episode>' . esc_html( $episode_number ) . '</itunes:episode>', 2 );
		}

		$this->newline( '<itunes:explicit>' . $is_explicit . '</itunes:explicit>', 2 );

		$tracking_prefix = Helper::get_settings( 'general.podcast_tracking_prefix' );
		$this->newline( '<enclosure url="' . esc_attr( $tracking_prefix ) . esc_url( $audio_file ) . '" length="' . $duration . '" type="audio/mpeg" />', 2 );
	}

	/**
	 * Write a newline with indent count.
	 *
	 * @param string  $content Content to write.
	 * @param integer $indent  Count of indent.
	 */
	private function newline( $content, $indent = 0 ) {
		echo str_repeat( "\t", $indent ) . $content . "\n";
	}
}
