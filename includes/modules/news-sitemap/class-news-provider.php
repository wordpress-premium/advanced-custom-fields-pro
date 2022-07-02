<?php
/**
 * The Sitemap Module
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Sitemap;

use RankMath\Helper;
use RankMath\Sitemap\Router;
use RankMath\Sitemap\Providers\Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * News_Provider class.
 */
class News_Provider extends Post_Type {

	/**
	 * Indicate that this provider should show an empty sitemap instead of a 404.
	 *
	 * @var boolean
	 */
	public $should_show_empty = true;

	/**
	 * Check if provider supports given item type.
	 *
	 * @param  string $type Type string to check for.
	 * @return boolean
	 */
	public function handles_type( $type ) {
		return 'news' === $type;
	}

	/**
	 * Get set of sitemaps index link data.
	 *
	 * @param  int $max_entries Entries per sitemap.
	 * @return array
	 */
	public function get_index_links( $max_entries ) { // phpcs:ignore
		global $wpdb;

		$index      = [];
		$post_types = Helper::get_settings( 'sitemap.news_sitemap_post_type' );

		$posts = $this->get_posts( $post_types, 1, 0 );
		if ( ! empty( $posts ) ) {
			$index[] = [
				'loc'     => Router::get_base_url( 'news-sitemap.xml' ),
				'lastmod' => get_lastpostdate( 'gmt' ),
			];
		}

		return $index;
	}

	/**
	 * Get set of sitemap link data.
	 *
	 * @param  string $type         Sitemap type.
	 * @param  int    $max_entries  Entries per sitemap.
	 * @param  int    $current_page Current page of the sitemap.
	 * @return array
	 */
	public function get_sitemap_links( $type, $max_entries, $current_page ) { // phpcs:ignore
		$links      = [];
		$post_types = Helper::get_settings( 'sitemap.news_sitemap_post_type' );
		$posts      = $this->get_posts( $post_types, 1000, 0 );

		if ( empty( $posts ) ) {
			return $links;
		}

		foreach ( $posts as $post ) {
			if ( ! Helper::is_post_indexable( $post->ID ) ) {
				continue;
			}

			if ( ! News_Sitemap::is_post_indexable( $post->ID ) ) {
				continue;
			}

			$url = $this->get_url( $post );
			if ( ! isset( $url['loc'] ) ) {
				continue;
			}

			/**
			 * Filter URL entry before it gets added to the sitemap.
			 *
			 * @param array  $url  Array of URL parts.
			 * @param string $type URL type.
			 * @param object $user Data object for the URL.
			 */
			$url = $this->do_filter( 'sitemap/entry', $url, 'post', $post );
			if ( empty( $url ) ) {
				continue;
			}

			if ( (int) $post->ID === $this->get_page_for_posts_id() || (int) $post->ID === $this->get_page_on_front_id() ) {
				array_unshift( $links, $url );
				continue;
			}
			$links[] = $url;
		}

		return $links;
	}

	/**
	 * Retrieve set of posts with optimized query routine.
	 *
	 * @param array $post_types Post type to retrieve.
	 * @param int   $count      Count of posts to retrieve.
	 * @param int   $offset     Starting offset.
	 *
	 * @return object[]
	 */
	protected function get_posts( $post_types, $count, $offset ) { // phpcs:ignore
		global $wpdb;

		if ( empty( $post_types ) ) {
			return [];
		}

		if ( ! is_array( $post_types ) ) {
			$post_types = [ $post_types ];
		}

		/**
		 * Get posts for the last two days only
		 *
		 * @copyright Copyright (C) 2008-2019, Yoast BV & Alex Moss.
		 * The following code is a derivative work of the code from the Yoast(https://github.com/Yoast/wordpress-seo/), which is licensed under GPL v3.
		 */
		$sql = "
			SELECT *
			FROM {$wpdb->posts}
			WHERE post_status='publish'
				AND ( TIMESTAMPDIFF( MINUTE, post_date_gmt, UTC_TIMESTAMP() ) <= ( 48 * 60 ) )
				AND post_type IN ( '" . join( "', '", esc_sql( $post_types ) ) . "' )
				AND post_password = ''
			ORDER BY post_date_gmt DESC
			LIMIT 0, %d
		";

		$count = max( 1, min( 1000, $count ) );
		$posts = $wpdb->get_results( $wpdb->prepare( $sql, $count ) ); // phpcs:ignore

		return $this->filter_posts_by_terms( $posts );
	}

	/**
	 * Produce array of URL parts for given post object.
	 *
	 * @param  object $post Post object to get URL parts for.
	 * @return array|boolean
	 */
	protected function get_url( $post ) {
		$url = [];

		/**
		 * Filter the URL Rank Math SEO uses in the XML sitemap.
		 *
		 * Note that only absolute local URLs are allowed as the check after this removes external URLs.
		 *
		 * @param string $url  URL to use in the XML sitemap
		 * @param object $post Post object for the URL.
		 */
		$url['loc'] = $this->do_filter( 'sitemap/xml_post_url', get_permalink( $post ), $post );

		/**
		 * Do not include external URLs.
		 *
		 * @see https://wordpress.org/plugins/page-links-to/ can rewrite permalinks to external URLs.
		 */
		if ( 'external' === $this->get_classifier()->classify( $url['loc'] ) ) {
			return false;
		}

		$canonical = Helper::get_post_meta( 'canonical', $post->ID );
		if ( '' !== $canonical && $canonical !== $url['loc'] ) {
			/*
			 * Let's assume that if a canonical is set for this page and it's different from
			 * the URL of this post, that page is either already in the XML sitemap OR is on
			 * an external site, either way, we shouldn't include it here.
			 */
			return false;
		}

		rank_math()->variables->setup();

		$url['title']            = $this->get_title( $post );
		$url['publication_date'] = $post->post_date_gmt;

		return $url;
	}

	/**
	 * Get Post Title.
	 *
	 * @param WP_Post $post Post Object.
	 *
	 * @return string
	 */
	private function get_title( $post ) {
		$title = Helper::get_post_meta( 'title', $post->ID );
		return $title ? $title : $post->post_title;
	}

	/**
	 * Filter Posts by excluded terms.
	 *
	 * @param object $posts News Sitemap Posts.
	 *
	 * @return object[]
	 */
	private function filter_posts_by_terms( $posts ) {
		if ( empty( $posts ) ) {
			return $posts;
		}

		foreach ( $posts as $key => $post ) {
			$exclude_terms = current( Helper::get_settings( "sitemap.news_sitemap_exclude_{$post->post_type}_terms", [] ) );
			if ( empty( $exclude_terms ) ) {
				continue;
			}

			$exclude_terms = array_merge( ...array_values( $exclude_terms ) );
			$taxonomies    = get_object_taxonomies( $post->post_type, 'names' );
			if ( empty( $taxonomies ) ) {
				continue;
			}

			$terms = wp_get_object_terms(
				$post->ID,
				$taxonomies,
				[
					'fields' => 'ids',
				]
			);

			if ( ! empty( array_intersect( $terms, $exclude_terms ) ) ) {
				unset( $posts[ $key ] );
			}
		}

		return $posts;
	}
}
