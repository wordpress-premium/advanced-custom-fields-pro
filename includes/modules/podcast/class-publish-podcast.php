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
use RankMath\Schema\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Publish_Podcast class.
 */
class Publish_Podcast {

	use Hooker;

	/**
	 * Has Podcast schema.
	 *
	 * @var string
	 */
	protected $has_podcast_schema = false;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'rank_math/schema/update', 'publish_podcast' );
		$this->action( 'rank_math/pre_update_schema', 'has_podcast_schema' );

		$this->action( 'rss2_podcast_head', 'add_hub_urls' );
	}

	/**
	 * Check if current post already have a Podcast schema.
	 *
	 * @param int $post_id Current Post ID.
	 */
	public function has_podcast_schema( $post_id ) {
		$schema_types = DB::get_schema_types( $post_id );
		$this->has_podcast_schema =  ! empty( $schema_types ) && in_array( 'PodcastEpisode', explode( ', ', $schema_types ), true );
	}

	/**
	 * Publish podcast when a new post is published.
	 *
	 * @param int $post_id Current Post ID.
	 */
	public function publish_podcast( $post_id ) {
		if ( $this->has_podcast_schema ) {
			return;
		}

		$podcast = get_post_meta( $post_id, 'rank_math_schema_PodcastEpisode', true );
		if ( empty( $podcast ) ) {
			return;
		}

		$hub_urls = $this->get_hub_urls();
		if ( empty( $hub_urls ) ) {
			return;
		}

		$user_agent   = $this->do_filter( 'podcast/useragent', 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ) );
		$podcast_feed = esc_url( home_url( 'feed/podcast' ) );
		$args         = [
			'timeout'    => 100,
			'user-agent' => "$user_agent; PubSubHubbub/WebSub",
			'body'       => "hub.mode=publish&hub.url={$podcast_feed}",
		];
		
		foreach ( $hub_urls as $hub_url ) {
			wp_remote_post( $hub_url, $args );
		}
	}

	/**
	 * Add Hub urls to podcast feed.
	 */
	public function add_hub_urls() {
		$hub_urls = $this->get_hub_urls();
		if ( empty( $hub_urls ) ) {
			return;
		}

		foreach ( $hub_urls as $hub_url ) {
			echo '<atom:link rel="hub" href="' . esc_url( $hub_url ) . '" />';
		}
	}

	/**
	 * Get podcast Hub URLs.
	 */
	private function get_hub_urls() {
		return $this->do_filter(
			'podcast/hub_urls',
			[
				'https://pubsubhubbub.appspot.com',
				'https://pubsubhubbub.superfeedr.com',
				'https://websubhub.com/hub',
			]
		);
	}
}
