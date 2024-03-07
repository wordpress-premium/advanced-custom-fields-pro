<?php
/**
 * The Local_Seo Module
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\KB;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Admin\Admin_Helper;
use RankMath\Sitemap\Router;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 */
class Admin {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/settings/title', 'add_settings' );
		$this->filter( 'rank_math/settings/sitemap', 'add_sitemap_settings', 11 );
		$this->filter( 'rank_math/settings/snippet/types', 'add_local_business_schema_type', 10, 2 );
	}

	/**
	 * Add module settings into general optional panel.
	 *
	 * @param array $tabs Array of option panel tabs.
	 *
	 * @return array
	 */
	public function add_settings( $tabs ) {
		$tabs['local']['file'] = dirname( __FILE__ ) . '/views/titles-options.php';

		return $tabs;
	}

	/**
	 * Add module settings into general optional panel.
	 *
	 * @param array $tabs Array of option panel tabs.
	 *
	 * @return array
	 */
	public function add_sitemap_settings( $tabs ) {
		$sitemap_url      = Router::get_base_url( 'locations.kml' );
		$tabs['kml-file'] = [
			'icon'      => 'rm-icon rm-icon-local-seo',
			'title'     => esc_html__( 'Local Sitemap', 'rank-math-pro' ),
			'desc'      => wp_kses_post( sprintf( __( 'KML is a file format used to display geographic data in an Earth browser such as Google Earth. More information: <a href="%s" target="_blank">Locations KML</a>', 'rank-math-pro' ), KB::get( 'kml-sitemap', 'Options Panel Sitemap Local Tab' ) ) ),
			'file'      => dirname( __FILE__ ) . '/views/sitemap-settings.php',
			/* translators: KML File Url */
			'after_row' => '<div class="notice notice-alt notice-info info inline rank-math-notice"><p>' . sprintf( esc_html__( 'Your Locations KML file can be found here: %s', 'rank-math-pro' ), '<a href="' . $sitemap_url . '" target="_blank">' . $sitemap_url . '</a>' ) . '</p></div>',
		];

		return $tabs;
	}

	/**
	 * Add Pro schema types in Schema settings choices array.
	 *
	 * @param array  $types     Schema types.
	 * @param string $post_type Post type.
	 */
	public function add_local_business_schema_type( $types, $post_type ) {
		if ( 'rank_math_locations' === $post_type ) {
			$types = [
				'off'           => esc_html__( 'None', 'rank-math-pro' ),
				'LocalBusiness' => esc_html__( 'Local Business', 'rank-math-pro' ),
			];
		}

		return $types;
	}
}
