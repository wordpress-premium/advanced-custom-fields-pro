<?php
/**
 * The News Sitemap Metabox.
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Sitemap;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Admin\Admin_Helper;
use RankMath\Sitemap\Cache_Watcher;
use MyThemeShop\Helpers\Param;
use MyThemeShop\Helpers\WordPress;

defined( 'ABSPATH' ) || exit;

/**
 * News_Metabox class.
 */
class News_Metabox {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( ! Helper::has_cap( 'sitemap' ) ) {
			return;
		}

		$this->action( 'save_post', 'save_post' );
		$this->action( 'rank_math/admin/editor_scripts', 'enqueue_news_sitemap', 11 );
		$this->filter( 'rank_math/metabox/post/values', 'add_metadata', 10, 2 );
	}

	/**
	 * Enqueue scripts for the metabox.
	 */
	public function enqueue_news_sitemap() {
		if ( ! $this->can_add_tab() ) {
			return;
		}

		wp_enqueue_script(
			'rank-math-pro-news',
			RANK_MATH_PRO_URL . 'includes/modules/news-sitemap/assets/js/news-sitemap.js',
			[ 'rank-math-pro-editor' ],
			rank_math_pro()->version,
			true
		);
	}

	/**
	 * Add meta data to use in gutenberg.
	 *
	 * @param array  $values Aray of tabs.
	 * @param Screen $screen Sceen object.
	 *
	 * @return array
	 */
	public function add_metadata( $values, $screen ) {
		$object_id   = $screen->get_object_id();
		$object_type = $screen->get_object_type();
		$robots      = $screen->get_meta( $object_type, $object_id, 'rank_math_news_sitemap_robots' );

		$values['newsSitemap'] = [
			'robots' => $robots ? $robots : 'index',
		];

		return $values;
	}

	/**
	 * Check for relevant post type before invalidation.
	 *
	 * @copyright Copyright (C) 2008-2019, Yoast BV
	 * The following code is a derivative work of the code from the Yoast(https://github.com/Yoast/wordpress-seo/), which is licensed under GPL v3.
	 *
	 * @param int $post_id Post ID to possibly invalidate for.
	 */
	public function save_post( $post_id ) {
		if (
			wp_is_post_revision( $post_id ) ||
			! $this->can_add_tab( get_post_type( $post_id ) ) ||
			false === Helper::is_post_indexable( $post_id )
		) {
			return false;
		}

		Cache_Watcher::invalidate( 'news' );
	}

	/**
	 * Show field check callback.
	 *
	 * @param string $post_type Current Post Type.
	 *
	 * @return boolean
	 */
	private function can_add_tab( $post_type = false ) {
		if ( Admin_Helper::is_term_profile_page() || Admin_Helper::is_posts_page() ) {
			return false;
		}

		$post_type = $post_type ? $post_type : WordPress::get_post_type();
		return in_array(
			$post_type,
			(array) Helper::get_settings( 'sitemap.news_sitemap_post_type' ),
			true
		);
	}
}
