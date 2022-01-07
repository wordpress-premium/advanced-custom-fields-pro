<?php
/**
 * The Video Sitemap Metabox.
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Sitemap;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\WordPress;
use RankMath\Sitemap\Cache_Watcher;

defined( 'ABSPATH' ) || exit;

/**
 * Video_Metabox class.
 */
class Video_Metabox {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'save_post', 'save_post' );
	}

	/**
	 * Check for relevant post type before invalidation.
	 *
	 * @param int $post_id Post ID to possibly invalidate for.
	 */
	public function save_post( $post_id ) {
		if (
			! $this->can_add_tab( get_post_type( $post_id ) ) ||
			false === Helper::is_post_indexable( $post_id ) ||
			wp_is_post_revision( $post_id )
		) {
			return false;
		}

		Cache_Watcher::invalidate( 'video' );
	}

	/**
	 * Show field check callback.
	 *
	 * @param string $post_type Post type.
	 *
	 * @return boolean
	 */
	private function can_add_tab( $post_type ) {
		return in_array(
			$post_type,
			(array) Helper::get_settings( 'sitemap.video_sitemap_post_type' ),
			true
		);
	}
}
