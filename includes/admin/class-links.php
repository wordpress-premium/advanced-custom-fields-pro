<?php
/**
 * Links related functionality.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Str;
use MyThemeShop\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Links class.
 *
 * @codeCoverageIgnore
 */
class Links {

	use Hooker;

	/**
	 * Base host.
	 *
	 * @var string
	 */
	protected $base_host = '';

	/**
	 * Base path.
	 *
	 * @var string
	 */
	protected $base_path = '';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->base_host = Helper::get_url_part( home_url(), 'host' );

		$base_path = Helper::get_url_part( home_url(), 'path' );
		if ( $base_path ) {
			$this->base_path = trailingslashit( $base_path );
		}

		$this->action( 'cmb2_admin_init', 'cmb_init', 99 );
		$this->action( 'rank_math/links/is_external', 'link_is_external', 20, 2 );
		$this->action( 'rank_math/admin/enqueue_scripts', 'add_json', 20 );
	}

	/**
	 * Hook CMB2 init process.
	 */
	public function cmb_init() {
		$this->action( 'cmb2_init_hookup_rank-math-options-general_options', 'add_options', 110 );
	}

	/**
	 * Add options to Image SEO module.
	 *
	 * @param object $cmb CMB object.
	 */
	public function add_options( $cmb ) {
		$field_ids       = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$fields_position = array_search( 'new_window_external_links', array_keys( $field_ids ), true ) + 1;

		$cmb->add_field(
			[
				'id'         => 'affiliate_link_prefixes',
				'type'       => 'textarea_small',
				'name'       => esc_html__( 'Affiliate Link Prefix', 'rank-math-pro' ),
				'desc'       => wp_kses_post( __( 'Add the URI prefixes you use for affiliate (cloaked) links, which redirect to external sites. These will not count as internal links in the content analysis. Add one per line.', 'rank-math-pro' ) ),
				'classes'    => 'rank-math-advanced-option',
				'dep'        => [
					[ 'nofollow_external_links', 'on' ],
					[ 'nofollow_image_links', 'on' ],
				],
				'attributes' => [
					'placeholder' => esc_html__( 'Example: /get/', 'rank-math-pro' ),
				],
			],
			++$fields_position
		);
	}

	/**
	 * Make cloaked affiliate links external.
	 *
	 * @param bool  $is_external Is external link.
	 * @param array $url_parts   URL parts from wp_parse_url().
	 * @return mixed
	 */
	public function link_is_external( $is_external, $url_parts ) {
		if ( empty( $url_parts['path'] ) ) {
			return $is_external;
		}

		// Check if internal link.
		if (
			! isset( $url_parts['host'] )
			|| '' === $url_parts['host']
			|| $url_parts['host'] === $this->base_host
		) {
			// Check if path starts with one of the affiliate_link_prefixes.
			$prefixes = Arr::from_string( Helper::get_settings( 'general.affiliate_link_prefixes' ), "\n" );
			foreach ( $prefixes as $prefix ) {
				if ( Str::starts_with( $prefix, $url_parts['path'] ) ) {
					return true;
				}
			}
		}

		return $is_external;
	}

	/**
	 * Add JSON.
	 *
	 * @return void
	 */
	public function add_json() {
		$prefixes = Arr::from_string( Helper::get_settings( 'general.affiliate_link_prefixes' ), "\n" );
		wp_enqueue_script(
			'rank-math-pro-links',
			RANK_MATH_PRO_URL . 'assets/admin/js/links.js',
			[ 'wp-hooks' ],
			rank_math_pro()->version
		);
		Helper::add_json( 'affiliate_link_prefixes', $prefixes );
	}

}
