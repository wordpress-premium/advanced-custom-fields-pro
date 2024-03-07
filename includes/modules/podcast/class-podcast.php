<?php
/**
 * The Podcast Schema.
 *
 * @since      3.0.17
 * @package    RankMath
 * @subpackage RankMathPro\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Podcast;

use RankMath\KB;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Podcast class.
 */
class Podcast {

	use Hooker;

	/**
	 * Store podcast feed slug.
	 */
	private $podcast;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/settings/general', 'add_settings' );
		$this->action( 'init', 'init' );
		$this->action( 'rank_math/vars/register_extra_replacements', 'register_replacements' );
	}

	/**
	 * Intialize.
	 */
	public function init() {
		/**
		 * Filter to modify the permalink of Podcast RSS feed. Pass false to remove the feed.
		 *
		 * @pram string $podcast Podcast RSS feed slug.
		 */
		$this->podcast = $this->do_filter( 'podcast/feed', 'podcast' );
		if ( ! $this->podcast ) {
			return;
		}

		add_feed( $this->podcast, [ $this, 'podcast_feed' ] );
		new Podcast_RSS();
		new Publish_Podcast();
	}

	/**
	 * Registers variable replacements for Rank Math Pro.
	 */
	public function register_replacements() {
		rank_math_register_var_replacement(
			'podcast_image',
			[
				'name'        => esc_html__( 'Podcast Image', 'rank-math-pro' ),
				'description' => esc_html__( 'Podcast channel image configured in the Rank Math Settings.', 'rank-math-pro' ),
				'variable'    => 'podcast_image',
				'example'     => '',
			],
			[ $this, 'get_podcast_image' ]
		);
	}

	/**
	 * Get Podcast image from the Settings.
	 *
	 * @return string Podcast image.
	 */
	public function get_podcast_image() {
		return Helper::get_settings( 'general.podcast_image' );
	}

	/**
	 * Add module settings in the General Settings panel.
	 *
	 * @param  array $tabs Array of option panel tabs.
	 * @return array
	 */
	public function add_settings( $tabs ) {
		Arr::insert(
			$tabs,
			[
				'podcast' => [
					'icon'      => 'rm-icon rm-icon-podcast',
					'title'     => esc_html__( 'Podcast', 'rank-math-pro' ),
					/* translators: Link to kb article */
					'desc'      => sprintf( esc_html__( 'Make your podcasts discoverable via Google Podcasts, Apple Podcasts, and similar services. %s.', 'rank-math' ), '<a href="' . KB::get( 'podcast-settings', 'Options Panel Podcast Tab' ) . '" target="_blank">' . esc_html__( 'Learn more', 'rank-math-pro' ) . '</a>' ),
					'file'      => dirname( __FILE__ ) . '/views/options.php',
					/* translators: Link to Podcast RSS feed */
					'after_row' => '<div class="notice notice-alt notice-info info inline rank-math-notice"><p>' . sprintf( esc_html__( 'Your Podcast RSS feed can be found here: %s', 'rank-math-pro' ), '<a href="' . get_feed_link( $this->podcast ) . '" target="_blank">' . get_feed_link( $this->podcast ) . '</a>' ) . '</p></div>',
				],
			],
			12
		);

		return $tabs;
	}

	/**
	 * Add all podcasts feed to /feed/podcast.
	 */
	public function podcast_feed() {
		require dirname( __FILE__ ) . '/views/feed-rss2.php';
	}

	/**
	 * Get podcasts
	 */
	public function get_podcasts() {
		$post_types = array_filter(
			Helper::get_accessible_post_types(),
			function( $post_type ) {
				return 'attachment' !== $post_type;
			}
		);

		$args = $this->do_filter(
			'podcast_args',
			[
				'post_type'      => array_keys( $post_types ),
				'posts_per_page' => get_option( 'posts_per_rss' ),
				'meta_query'     => [
					[
						'key'     => 'rank_math_schema_PodcastEpisode',
						'compare' => 'EXISTS',
					],
				],
			]
		);

		return new \WP_Query( $args );
	}

}
