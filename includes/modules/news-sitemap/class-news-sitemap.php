<?php
/**
 * The News Sitemap Module
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Sitemap;

use RankMath\Helper;
use RankMath\Helpers\Locale;
use RankMath\Sitemap\Cache_Watcher;
use RankMath\Traits\Hooker;
use RankMath\Sitemap\Router;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * News_Sitemap class.
 */
class News_Sitemap {

	use Hooker;

	/**
	 * NEWS Publication.
	 *
	 * @var string
	 */
	protected $news_publication = null;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			$this->filter( 'rank_math/settings/sitemap', 'add_settings', 11 );
		}

		new News_Metabox();
		$this->action( 'rank_math/head', 'robots', 10 );
		$this->filter( 'rank_math/sitemap/providers', 'add_provider' );
		$this->filter( 'rank_math/sitemap/news_urlset', 'xml_urlset' );
		$this->filter( 'rank_math/sitemap/xsl_news', 'sitemap_xsl' );
		$this->filter( 'rank_math/sitemap/news_stylesheet_url', 'stylesheet_url' );
		$this->filter( 'rank_math/sitemap/news_sitemap_url', 'sitemap_url', 10, 2 );

		$this->filter( 'rank_math/schema/default_type', 'change_default_schema_type', 10, 3 );
		$this->filter( 'rank_math/snippet/rich_snippet_article_entity', 'add_copyrights_data' );

		$this->action( 'admin_post_rank-math-options-sitemap', 'save_exclude_terms_data', 9 );
		$this->action( 'transition_post_status', 'status_transition', 10, 3 );
	}

	/**
	 * Function to pass empty array to exclude terms data when term is not selected for a Post type.
	 * This code is needed to save empty group value since CMB2 doesn't allow it.
	 *
	 * @since  2.8.1
	 * @return void
	 */
	public function save_exclude_terms_data() {
		$post_types = Helper::get_settings( 'sitemap.news_sitemap_post_type', [] );
		if ( empty( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			if ( ! isset( $_POST["news_sitemap_exclude_{$post_type}_terms"] ) ) { //phpcs:ignore
				$_POST["news_sitemap_exclude_{$post_type}_terms"] = []; //phpcs:ignore
			}
		}
	}

	/**
	 * Output the meta robots tag.
	 */
	public function robots() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();

		/**
		 * Filter: 'rank_math/sitemap/news/noindex' - Allow preventing of outputting noindex tag.
		 *
		 * @api string $meta_robots The noindex tag.
		 *
		 * @param object $post The post.
		 */
		if ( ! $this->do_filter( 'sitemap/news/noindex', true, $post ) || self::is_post_indexable( $post->ID ) ) {
			return;
		}

		echo '<meta name="Googlebot-News" content="noindex" />' . "\n";
	}

	/**
	 * Check if post is indexable.
	 *
	 * @param int $post_id Post ID to check.
	 *
	 * @return boolean
	 */
	public static function is_post_indexable( $post_id ) {
		$robots = get_post_meta( $post_id, 'rank_math_news_sitemap_robots', true );
		if ( ! empty( $robots ) && 'noindex' === $robots ) {
			return false;
		}

		return true;
	}

	/**
	 * Add module settings into general optional panel.
	 *
	 * @param array $tabs Array of option panel tabs.
	 *
	 * @return array
	 */
	public function add_settings( $tabs ) {
		$sitemap_url          = Router::get_base_url( 'news-sitemap.xml' );
		$tabs['news-sitemap'] = [
			'icon'      => 'fa fa-newspaper-o',
			'title'     => esc_html__( 'News Sitemap', 'rank-math-pro' ),
			'icon'      => 'rm-icon rm-icon-post',
			'desc'      => wp_kses_post( __( 'News Sitemaps allow you to control which content you submit to Google News. More information: <a href="https://rankmath.com/kb/news-sitemap/?utm_source=Plugin&utm_campaign=WP" target="_blank">News Sitemaps overview</a>', 'rank-math-pro' ) ),
			'file'      => dirname( __FILE__ ) . '/settings-news.php',
			/* translators: News Sitemap Url */
			'after_row' => '<div class="notice notice-alt notice-info info inline rank-math-notice"><p>' . sprintf( esc_html__( 'Your News Sitemap index can be found here: : %s', 'rank-math-pro' ), '<a href="' . $sitemap_url . '" target="_blank">' . $sitemap_url . '</a>' ) . '</p></div>',
		];

		return $tabs;
	}

	/**
	 * Add news sitemap provider.
	 *
	 * @param array $providers Sitemap provider registry.
	 */
	public function add_provider( $providers ) {
		$providers[] = new \RankMathPro\Sitemap\News_Provider();
		return $providers;
	}

	/**
	 * Produce XML output for google news urlset.
	 *
	 * @return string
	 */
	public function xml_urlset() {
		return '<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
			. 'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd '
			. 'http://www.google.com/schemas/sitemap-news/0.9 http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd" '
			. 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
			. 'xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
	}

	/**
	 * Stylesheet Url for google news.
	 *
	 * @param  string $url Current stylesheet url.
	 * @return string
	 */
	public function stylesheet_url( $url ) { // phpcs:ignore
		$stylesheet_url = preg_replace( '/(^http[s]?:)/', '', Router::get_base_url( 'news-sitemap.xsl' ) );
		return '<?xml-stylesheet type="text/xsl" href="' . $stylesheet_url . '"?>';
	}

	/**
	 * Stylesheet for google news.
	 *
	 * @param string $title Title for stylesheet.
	 */
	public function sitemap_xsl( $title ) { // phpcs:ignore
		require_once 'sitemap-xsl.php';
	}

	/**
	 * Build the `<url>` tag for a given URL.
	 *
	 * @param  array    $url      Array of parts that make up this entry.
	 * @param  Renderer $renderer Sitemap renderer class object.
	 * @return string
	 */
	public function sitemap_url( $url, $renderer ) {
		$date = null;
		if ( ! empty( $url['publication_date'] ) ) {
			// Create a DateTime object date in the correct timezone.
			$date = $renderer->timezone->format_date( $url['publication_date'] );
		}

		$output  = $renderer->newline( '<url>', 1 );
		$output .= $renderer->newline( '<loc>' . $renderer->encode_url_rfc3986( htmlspecialchars( $url['loc'] ) ) . '</loc>', 2 );

		$output .= $renderer->newline( '<news:news>', 2 );
		$output .= $this->get_news_publication( $renderer );

		$output .= empty( $date ) ? '' : $renderer->newline( '<news:publication_date>' . htmlspecialchars( $date ) . '</news:publication_date>', 3 );
		$output .= $renderer->add_cdata( $url['title'], 'news:title', 3 );

		$output .= $renderer->newline( '</news:news>', 2 );

		$output .= $renderer->newline( '</url>', 1 );

		/**
		 * Filters the output for the sitemap url tag.
		 *
		 * @param string $output The output for the sitemap url tag.
		 * @param array  $url    The sitemap url array on which the output is based.
		 */
		return $this->do_filter( 'sitemap_url', $output, $url );
	}

	/**
	 * Get News Pub Tags.
	 *
	 * @param  Renderer $renderer Sitemap renderer class object.
	 * @return string
	 */
	private function get_news_publication( $renderer ) {
		if ( is_null( $this->news_publication ) ) {

			$lang = Locale::get_site_language();
			$name = Helper::get_settings( 'sitemap.news_sitemap_publication_name' );
			$name = $name ? $name : get_bloginfo( 'name' );

			$this->news_publication  = '';
			$this->news_publication .= $renderer->newline( '<news:publication>', 3 );
			$this->news_publication .= $renderer->newline( '<news:name>' . esc_html( $name ) . '</news:name>', 4 );
			$this->news_publication .= $renderer->newline( '<news:language>' . $lang . '</news:language>', 4 );
			$this->news_publication .= $renderer->newline( '</news:publication>', 3 );
		}

		return $this->news_publication;
	}

	/**
	 * Change default schema type on News Posts.
	 *
	 * @param string $schema    Default schema type.
	 * @param string $post_type Current Post Type.
	 * @param int    $post_id   Current Post ID.
	 *
	 * @return string
	 */
	public function change_default_schema_type( $schema, $post_type, $post_id ) {
		$news_post_types = (array) Helper::get_settings( 'sitemap.news_sitemap_post_type' );
		if ( ! in_array( $post_type, $news_post_types, true ) ) {
			return $schema;
		}

		$exclude_terms = (array) Helper::get_settings( "sitemap.news_sitemap_exclude_{$post_type}_terms" );
		if ( empty( $exclude_terms[0] ) ) {
			return 'NewsArticle';	
		}

		$has_excluded_term = false;
		foreach ( $exclude_terms[0] as $taxonomy => $terms ) {
			if ( has_term( $terms, $taxonomy, $post_id ) ) {
				$has_excluded_term = true;
				break;
			}
		}

		return $has_excluded_term ? $schema : 'NewsArticle';
	}

	/**
	 * Filter to add Copyrights data in Article Schema on News Posts.
	 *
	 * @param array $entity Snippet Data.
	 * @return array
	 */
	public function add_copyrights_data( $entity ) {
		global $post;
		if ( is_null( $post ) ) {
			return $entity;
		}

		$news_post_types = (array) Helper::get_settings( 'sitemap.news_sitemap_post_type' );
		if ( ! in_array( $post->post_type, $news_post_types, true ) ) {
			return $entity;
		}

		$entity['copyrightYear'] = get_the_modified_date( 'Y', $post );
		if ( ! empty( $entity['publisher'] ) ) {
			$entity['copyrightHolder'] = $entity['publisher'];
		}

		return $entity;
	}

	/**
	 * Invalidate News Sitemap cache when a scheduled post is published.
	 *
	 * @param string $new_status New Status.
	 * @param string $old_status Old Status.
	 * @param object $post       Post Object.
	 */
	public function status_transition( $new_status, $old_status, $post ) {
		if ( $old_status === $new_status || 'publish' !== $new_status ) {
			return;
		}

		$news_post_types = (array) Helper::get_settings( 'sitemap.news_sitemap_post_type', [] );
		if ( ! in_array( $post->post_type, $news_post_types, true ) ) {
			return;
		}

		if ( false === Helper::is_post_indexable( $post->ID ) ) {
			return;
		}

		Cache_Watcher::invalidate( 'news' );
	}
}
