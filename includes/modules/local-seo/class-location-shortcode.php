<?php
/**
 * The Schema Shortcode
 *
 * @since      1.0.24
 * @package    RankMath
 * @subpackage RankMath\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Helper;
use RankMath\Schema\DB;
use RankMath\Traits\Hooker;
use RankMath\Traits\Shortcode;
use MyThemeShop\Helpers\Param;
use MyThemeShop\Helpers\Str;

defined( 'ABSPATH' ) || exit;

/**
 * Snippet_Shortcode class.
 */
class Location_Shortcode {

	use Hooker, Shortcode;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->address       = new Address();
		$this->opening_hours = new Opening_Hours();
		$this->map           = new Map();
		$this->store_locator = new Store_Locator();
		$this->api_key       = Helper::get_settings( 'titles.maps_api_key' );

		// Add Yoast compatibility shortcodes.
		$this->add_shortcode( 'wpseo_all_locations', 'yoast_locations' );
		$this->add_shortcode( 'wpseo_storelocator', 'yoast_store_locator' );
		$this->add_shortcode( 'wpseo_opening_hours', 'yoast_opening_hours' );
		$this->add_shortcode( 'wpseo_map', 'yoast_map' );

		$this->add_shortcode( 'rank_math_local', 'local_shortcode' );
		$this->action( 'wp_enqueue_scripts', 'enqueue' );
		$this->action( 'wp_enqueue_scripts', 'enqueue' );

		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			'rank-math/local-business',
			[
				'render_callback' => [ $this, 'local_shortcode' ],
				'attributes'      => $this->get_attributes(),
			]
		);
	}

	/**
	 * Enqueue Map scripts.
	 */
	public function enqueue() {
		if ( ! $this->api_key ) {
			return;
		}

		wp_register_script( 'rank-math-google-maps', '//maps.googleapis.com/maps/api/js?&key=' . rawurlencode( $this->api_key ), [], rank_math_pro()->version, true );
		wp_register_script( 'rank-math-google-maps-cluster', 'https://developers-dot-devsite-v2-prod.appspot.com/maps/documentation/javascript/examples/markerclusterer/markerclustererplus@4.0.1.min.js', [], rank_math_pro()->version, true );
		wp_register_script( 'rank-math-local', RANK_MATH_PRO_URL . 'includes/modules/local-seo/assets/js/rank-math-local.js', [ 'jquery', 'lodash', 'rank-math-google-maps', 'rank-math-google-maps-cluster' ], rank_math_pro()->version, true );
	}

	/**
	 * Location shortcode.
	 *
	 * @param  array $atts Optional. Shortcode arguments.
	 *
	 * @return string Shortcode output.
	 */
	public function local_shortcode( $atts ) {
		$defaults = [];
		foreach ( $this->get_attributes() as $key => $attribute ) {
			$defaults[ $key ] = $attribute['default'];
		}
		$this->atts = shortcode_atts(
			$defaults,
			$atts,
			'rank_math_local'
		);

		if ( ! $this->api_key && is_user_logged_in() && in_array( $this->atts['type'], [ 'store-locator', 'map' ], true ) ) {
			return sprintf(
				/* Translators: %s expands to General Settings Link. */
				esc_html__( 'This page can\'t load Google Maps correctly. Please add %s.', 'rank-math-pro' ),
				'<a href="' . Helper::get_admin_url( 'options-titles#setting-panel-local' ) . '" target="_blank">' . esc_html__( 'API Key', 'rank-math-pro' ) . '</a>'
			);
		}

		wp_enqueue_style( 'rank-math-local-business', RANK_MATH_PRO_URL . 'includes/modules/local-seo/assets/css/local-business.css', null, rank_math_pro()->version );

		if ( 'store-locator' === $this->atts['type'] ) {
			return $this->store_locator->get_data( $this );
		}

		return $this->get_shortcode_data();
	}

	/**
	 * Yoast Map compatibility functionality.
	 *
	 * @param  array $atts Array of arguments.
	 * @return string
	 */
	public function yoast_map( $atts ) {
		$atts['type'] = 'map';
		return $this->yoast_locations( $atts );
	}

	/**
	 * Yoast Opening Hours compatibility functionality.
	 *
	 * @param  array $atts Array of arguments.
	 * @return string
	 */
	public function yoast_opening_hours( $atts ) {
		$atts['type'] = 'opening-hours';
		return $this->yoast_locations( $atts );
	}

	/**
	 * Yoast Store Locator compatibility functionality.
	 *
	 * @param  array $atts Array of arguments.
	 * @return string
	 */
	public function yoast_store_locator( $atts ) {
		$atts['type'] = 'store-locator';
		return $this->yoast_locations( $atts );
	}

	/**
	 * Yoast locations compatibility functionality.
	 *
	 * @param  array $args Array of arguments.
	 * @return string
	 */
	public function yoast_locations( $args ) {
		$defaults = [
			'id'                      => '',
			'number'                  => -1,
			'type'                    => 'address',
			'term_id'                 => '',
			'orderby'                 => 'menu_order title',
			'order'                   => 'ASC',
			'show_state'              => true,
			'show_country'            => true,
			'show_phone'              => true,
			'show_phone_2'            => true,
			'show_fax'                => true,
			'show_email'              => true,
			'show_url'                => false,
			'show_logo'               => false,
			'show_opening_hours'      => false,
			'hide_closed'             => false,
			'oneline'                 => false,
			'echo'                    => false,
			'comment'                 => '',
			'radius'                  => 10,
			'max_number'              => '',
			'show_radius'             => false,
			'show_nearest_suggestion' => true,
			'show_map'                => true,
			'show_filter'             => false,
			'map_width'               => '100%',
			'scrollable'              => true,
			'draggable'               => true,
			'marker_clustering'       => false,
			'map_style'               => 'ROADMAP',
			'show_route'              => true,
			'show_route_label'        => '',
			'show_category_filter'    => false,
			'height'                  => 300,
			'zoom'                    => -1,
			'show_open_label'         => false,
			'show_days'               => '',
			'center'                  => '',
			'default_show_infowindow' => false,

		];

		$new_atts = [];
		$atts     = shortcode_atts( $defaults, $args, 'wpseo_local_show_all_locations' );
		$data     = [
			'type'                    => 'type',
			'id'                      => 'locations',
			'number'                  => 'limit',
			'max_number'              => 'limit',
			'term_id'                 => 'terms',
			'show_state'              => 'show_state',
			'show_country'            => 'show_country',
			'show_phone'              => 'show_telephone',
			'show_phone_2'            => 'show_secondary_number',
			'show_fax'                => 'show_fax',
			'show_email'              => 'show_email',
			'show_url'                => 'show_url',
			'show_logo'               => 'show_logo',
			'show_opening_hours'      => 'show_opening_hours',
			'show_days'               => 'show_days',
			'hide_closed'             => 'hide_closed_days',
			'oneline'                 => 'show_on_one_line',
			'comment'                 => 'opening_hours_note',
			'radius'                  => 'search_radius',
			'show_radius'             => 'show_radius',
			'show_nearest_suggestion' => 'show_nearest_location',
			'show_map'                => 'show_map',
			'show_filter'             => 'show_category_filter',
			'map_width'               => 'map_width',
			'map_height'              => 'map_height',
			'center'                  => 'map_center',
			'zoom'                    => 'zoom_level',
			'scrollable'              => 'allow_scrolling',
			'draggable'               => 'allow_dragging',
			'marker_clustering'       => 'show_marker_clustering',
			'map_style'               => 'map_style',
			'show_route'              => 'show_route_planner',
			'show_route_label'        => 'route_label',
			'show_category_filter'    => 'show_category_filter',
			'default_show_infowindow' => 'show_infowindow',
		];
		foreach ( $atts as $key => $value ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}
			$new_atts[ $data[ $key ] ] = $value;
		}

		return $this->local_shortcode( $new_atts );
	}

	/**
	 * Get Location Shortcode data.
	 *
	 * @return string Shortcode data.
	 */
	private function get_shortcode_data() {
		$args = [
			'post_type'   => 'rank_math_locations',
			'numberposts' => empty( $this->atts['limit'] ) ? -1 : (int) $this->atts['limit'],
			'include'     => (int) $this->atts['locations'],
		];

		if ( ! empty( $this->atts['terms'] ) ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'rank_math_location_category',
					'field'    => 'term_id',
					'terms'    => $this->atts['terms'],
				],
			];
		}

		/**
		 * Filter to change Locations query args.
		 *
		 * @param  array $args Arguments to retrieve locations.
		 * @return array $args.
		 */
		$args      = $this->do_filter( 'location_args', $args );
		$locations = get_posts( $args );
		if ( empty( $locations ) ) {
			return esc_html__( 'Sorry, no locations were found.', 'rank-math-pro' );
		}

		if ( 'map' === $this->atts['type'] ) {
			return $this->map->get_data( $this, $locations );
		}

		$data = '';
		foreach ( $locations as $location ) {
			$schema = DB::get_schemas( $location->ID );
			if ( empty( $schema ) ) {
				continue;
			}

			$schema = current( $this->replace_variables( $schema, $location ) );

			$data .= '<div class="rank-math-business-wrapper">';
			$data .= $this->get_title( $schema );
			$data .= $this->get_image( $schema );

			if ( 'address' === $this->atts['type'] ) {
				$data .= $this->address->get_data( $this, $schema );
			}

			if ( 'opening-hours' === $this->atts['type'] || $this->atts['show_opening_hours'] ) {
				$data .= $this->opening_hours->get_data( $this, $schema );
			}

			$data .= '</div>';
		}

		return $data;
	}

	/**
	 * Get Location Title.
	 *
	 * @param Object $schema Location schema data.
	 *
	 * @return string Shortcode data.
	 */
	public function get_title( $schema ) {
		if ( empty( $this->atts['show_company_name'] ) || empty( $schema['name'] ) ) {
			return;
		}

		return '<h3 class="rank-math-business-name">' . esc_html( $schema['name'] ) . '</h3>';
	}

	/**
	 * Get Location Image.
	 *
	 * @param Object $schema Schema Data.
	 *
	 * @return string Shortcode data.
	 */
	public function get_image( $schema ) {
		if ( empty( $this->atts['show_logo'] ) || empty( $schema['image'] ) ) {
			return;
		}

		return '<div class="rank-math-business-image"><img src="' . esc_url( $schema['image']['url'] ) . '" /><div>';
	}

	/**
	 * Replace variable.
	 *
	 * @param  array  $schemas  Schema to replace.
	 * @param  object $location Location Post Object.
	 * @return array
	 */
	public function replace_variables( $schemas, $location = [] ) {
		$new_schemas = [];

		foreach ( $schemas as $key => $schema ) {
			if ( is_array( $schema ) ) {
				$new_schemas[ $key ] = $this->replace_variables( $schema, $location );
				continue;
			}

			$new_schemas[ $key ] = Str::contains( '%', $schema ) ? Helper::replace_seo_fields( $schema, $location ) : $schema;
		}

		return $new_schemas;
	}

	/**
	 * Shortcode & Block default attributes.
	 */
	private function get_attributes() {
		return [
			'type'                   => [
				'default' => 'address',
				'type'    => 'string',
			],
			'locations'              => [
				'default' => '',
				'type'    => 'string',
			],
			'terms'                  => [
				'default' => [],
				'type'    => 'array',
			],
			'limit'                  => [
				'default' => Helper::get_settings( 'titles.limit_results', 10 ),
				'type'    => 'integer',
			],
			'show_company_name'      => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_company_address'   => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_on_one_line'       => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_state'             => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_country'           => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_telephone'         => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_secondary_number'  => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_fax'               => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_email'             => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_url'               => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_logo'              => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_vat_id'            => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_tax_id'            => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_coc_id'            => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_priceRange'        => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_opening_hours'     => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_days'              => [
				'type'    => 'string',
				'default' => 'Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday',
			],
			'hide_closed_days'       => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_opening_now_label' => [
				'type'    => 'boolean',
				'default' => false,
			],
			'opening_hours_note'     => [
				'type'    => 'string',
				'default' => 'Open Now',
			],
			'show_map'               => [
				'type'    => 'boolean',
				'default' => false,
			],
			'map_style'              => [
				'type'    => 'string',
				'default' => Helper::get_settings( 'titles.map_style', 'roadmap' ),
			],
			'map_width'              => [
				'type'    => 'string',
				'default' => '100%',
			],
			'map_height'             => [
				'type'    => 'string',
				'default' => '300px',
			],
			'zoom_level'             => [
				'type'    => 'integer',
				'default' => -1,
			],
			'allow_zoom'             => [
				'type'    => 'boolean',
				'default' => true,
			],
			'allow_scrolling'        => [
				'type'    => 'boolean',
				'default' => true,
			],
			'allow_dragging'         => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_route_planner'     => [
				'type'    => 'boolean',
				'default' => true,
			],
			'route_label'            => [
				'type'    => 'string',
				'default' => Helper::get_settings( 'titles.route_label' ),
			],
			'show_category_filter'   => [
				'type'    => 'boolean',
				'default' => false,
			],
			'show_marker_clustering' => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_infowindow'        => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_radius'            => [
				'type'    => 'boolean',
				'default' => true,
			],
			'show_nearest_location'  => [
				'type'    => 'boolean',
				'default' => true,
			],
			'search_radius'          => [
				'type'    => 'string',
				'default' => '10',
			],
		];
	}
}
