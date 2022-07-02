<?php
/**
 * The Video Sitemap Provider
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Sitemap;

use RankMath\Helper;
use RankMath\Sitemap\Router;
use RankMath\Sitemap\Sitemap;
use RankMath\Sitemap\Providers\Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Video_Provider class.
 */
class Video_Provider extends Post_Type {

	/**
	 * Check if provider supports given item type.
	 *
	 * @param  string $type Type string to check for.
	 * @return boolean
	 */
	public function handles_type( $type ) {
		return 'video' === $type;
	}

	/**
	 * Get set of sitemaps index link data.
	 *
	 * @param  int $max_entries Entries per sitemap.
	 * @return array
	 */
	public function get_index_links( $max_entries ) {
		$post_types = (array) Helper::get_settings( 'sitemap.video_sitemap_post_type', [] );
		if ( empty( $post_types ) ) {
			return [];
		}

		global $wpdb;

		$sql = "SELECT p.ID, p.post_modified_gmt FROM {$wpdb->postmeta} as pm
						INNER JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
						WHERE pm.meta_key = 'rank_math_schema_VideoObject'
						AND post_type IN ( '" . join( "', '", esc_sql( $post_types ) ) . "' )
						AND post_status IN ( 'publish', 'inherit' )
						GROUP BY p.ID
						ORDER BY p.post_modified_gmt DESC";

		$posts       = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore
		$total_count = count( $posts );
		if ( 0 === $total_count ) {
			return [];
		}

		$max_pages = 1;
		if ( $total_count > $max_entries ) {
			$max_pages = (int) ceil( $total_count / $max_entries );
		}

		$all_dates = array_chunk( $posts, $max_entries );
		$index     = [];
		for ( $page_counter = 0; $page_counter < $max_pages; $page_counter++ ) {
			$current_page = ( $max_pages > 1 ) ? ( $page_counter + 1 ) : '';
			$index[]      = [
				'loc'     => Router::get_base_url( 'video-sitemap' . $current_page . '.xml' ),
				'lastmod' => $all_dates[ $page_counter ][0]['post_modified_gmt'],
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
	public function get_sitemap_links( $type, $max_entries, $current_page ) {
		rank_math()->variables->setup();

		$post_types = (array) Helper::get_settings( 'sitemap.video_sitemap_post_type', [] );
		$links      = [];
		$steps      = min( 100, $max_entries );
		$offset     = ( $current_page > 1 ) ? ( ( $current_page - 1 ) * $max_entries ) : 0;
		$total      = ( $offset + $max_entries );
		$typecount  = 0;

		$stacked_urls = [];
		while ( $total > $offset ) {
			$posts   = $this->get_posts( $post_types, $steps, $offset );
			$offset += $steps;

			if ( empty( $posts ) ) {
				continue;
			}

			foreach ( $posts as $post ) {

				if ( ! Helper::is_post_indexable( $post->ID ) ) {
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

				$stacked_urls[] = $url['loc'];
				$links[]        = $url;
			}

			unset( $post, $url );
		}

		return $links;
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
		unset( $canonical );
		$schemas = get_post_meta( $post->ID, 'rank_math_schema_VideoObject' );
		if ( empty( $schemas ) ) {
			return false;
		}

		if ( 'post' !== $post->post_type ) {
			$url['loc'] = trailingslashit( $url['loc'] );
		}

		$url['author'] = $post->post_author;
		$url['videos'] = [];
		foreach ( $schemas as $schema ) {
			$url['videos'][] = [
				'title'            => ! empty( $schema['name'] ) ? Helper::replace_vars( $schema['name'], $post ) : '',
				'thumbnail_loc'    => ! empty( $schema['thumbnailUrl'] ) ? Helper::replace_vars( $schema['thumbnailUrl'], $post ) : '',
				'description'      => ! empty( $schema['description'] ) ? Helper::replace_vars( $schema['description'], $post ) : '',
				'publication_date' => ! empty( $schema['uploadDate'] ) ? Helper::replace_vars( $schema['uploadDate'], $post ) : '',
				'content_loc'      => ! empty( $schema['contentUrl'] ) ? Helper::replace_vars( $schema['contentUrl'], $post ) : '',
				'player_loc'       => ! empty( $schema['embedUrl'] ) ? Helper::replace_vars( $schema['embedUrl'], $post ) : '',
				'duration'         => ! empty( $schema['duration'] ) ? Helper::duration_to_seconds( $schema['duration'] ) : '',
				'tags'             => ! empty( $schema['metadata']['tags'] ) ? Helper::replace_vars( $schema['metadata']['tags'], $post ) : '',
				'family_friendly'  => ! empty( $schema['isFamilyFriendly'] ) ? 'yes' : 'no',
				'rating'           => ! empty( $schema['metadata']['rating'] ) ? $schema['metadata']['rating'] : '',
			];
		}

		return $url;
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
		$sql = "SELECT p.* FROM {$wpdb->postmeta} as pm
						INNER JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
						WHERE pm.meta_key = 'rank_math_schema_VideoObject'
						AND post_type IN ( '" . join( "', '", esc_sql( $post_types ) ) . "' )
						AND post_status IN ( 'publish', 'inherit' )
						AND post_password = ''
						GROUP BY p.ID
						ORDER BY p.post_modified DESC
						LIMIT %d OFFSET %d";

		return $wpdb->get_results( $wpdb->prepare( $sql, $count, $offset ) ); // phpcs:ignore

	}
}
