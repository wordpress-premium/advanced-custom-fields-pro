<?php
/**
 * Divi integration.
 *
 * @since      2.0.8
 * @package    RankMath
 * @subpackage RankMath\Core
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Divi;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Traits\Meta;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor class.
 */
class Divi {

	use Meta;
	use Hooker;

	/**
	 * Holds data of FAQ schema activated accordions.
	 *
	 * @var array
	 */
	private $faq_accordion_data = [];

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->filter( 'et_builder_get_parent_modules', 'filter_et_builder_parent_modules' );
		$this->filter( 'rank_math/json_ld', 'add_faq_schema', 10 );
		$this->action( 'wp_footer', 'add_divi_scripts' );
	}

	/**
	 * Get accordion data.
	 *
	 * This function is a bit of a reconstruction of WP's `do_shortcode` function
	 * in order to retreive the setting from Divi's accordion module.
	 */
	public function get_accordion_data() {
		$post_content = get_the_content();
		if ( ! has_shortcode( $post_content, 'et_pb_accordion' ) ) {
			return [];
		}

		$accordions = $this->get_shortcode_data( $post_content, 'et_pb_accordion' );

		foreach ( $accordions as &$accordion ) {
			if ( ! empty( $accordion['content'] ) ) {
				$accordion['content'] = $this->get_shortcode_data(
					$accordion['content'],
					'et_pb_accordion_item',
					false
				);
			}
		}

		return array_filter( $accordions );
	}

	/**
	 * Get shortcode data.
	 *
	 * @param string       $string The string to search for shortcodes.
	 * @param string|array $tagname The shortcode name as a string or an array of names.
	 * @param bool         $check_for_schema Whether to only allow truthy schema attr shortcodes.
	 *
	 * @return array Array of all found shortcodes.
	 */
	public function get_shortcode_data( $string, $tagname, $check_for_schema = true ) {
		$pattern = get_shortcode_regex( is_array( $tagname ) ? $tagname : [ $tagname ] );
		if ( ! preg_match_all( "/$pattern/s", $string, $matches, PREG_SET_ORDER ) ) {
			return [];
		}

		return array_map(
			function( $m ) use ( $check_for_schema ) {
				global $shortcode_tags;

				// Allow [[foo]] syntax for escaping a tag.
				if ( '[' === $m[1] && ']' === $m[6] ) {
					return [];
				}

				$attr = shortcode_parse_atts( $m[3] );

				if (
					$check_for_schema &&
					(
						! isset( $attr['rank_math_faq_schema'] ) ||
						! filter_var( $attr['rank_math_faq_schema'], FILTER_VALIDATE_BOOLEAN )
					)
				) {
					return [];
				}

				$tag = $m[2];

				/**
				 * Filters whether to call a shortcode callback.
				 *
				 * NOTE: This is a WP core filter through which a shortcode can be prevented
				 * from being rendered.
				 *
				 * @param false|string $return      Short-circuit return value. Either false or the value to replace the shortcode with.
				 * @param string       $tag         Shortcode name.
				 * @param array|string $attr        Shortcode attributes array or empty string.
				 * @param array        $m           Regular expression match array.
				 */
				// phpcs:ignore
				if ( apply_filters( 'pre_do_shortcode_tag', false, $tag, $attr, $m ) ) {
					return [];
				}

				$content = isset( $m[5] ) ? $m[5] : '';

				if ( has_filter( 'do_shortcode_tag' ) ) {

					$output = $m[1] . call_user_func( $shortcode_tags[ $tag ], $attr, $content, $tag ) . $m[6];

					/**
					 * Filters the output created by a shortcode callback.
					 *
					 * NOTE: This is a WP core filter through which a shortcode can be prevented
					 * from being rendered.
					 *
					 * @param string       $output Shortcode output.
					 * @param string       $tag    Shortcode name.
					 * @param array|string $attr   Shortcode attributes array or empty string.
					 * @param array        $m      Regular expression match array.
					 */
					$output = apply_filters( 'do_shortcode_tag', $output, $tag, $attr, $m );

					if ( empty( $output ) ) {
						return [];
					}
				}

				return [
					'tag'     => $tag,
					'atts'    => $attr,
					'content' => $content,
				];
			},
			$matches
		);
	}

	/**
	 * Add FAQ schema using the accordion content.
	 *
	 * @param array $data Array of json-ld data.
	 *
	 * @return array
	 */
	public function add_faq_schema( $data ) {
		if ( ! is_singular() ) {
			return $data;
		}

		$accordions = $this->get_accordion_data();

		if ( empty( $accordions ) ) {
			return $data;
		}

		$data['faq-data'] = [
			'@type' => 'FAQPage',
		];
		foreach ( $accordions as $accordion ) {
			if ( empty( $accordion['content'] ) || ! is_array( $accordion['content'] ) ) {
				continue;
			}
			foreach ( $accordion['content'] as $item ) {
				if ( empty( $item['atts']['title'] ) ) {
					continue;
				}
				$data['faq-data']['mainEntity'][] = [
					'@type'          => 'Question',
					'name'           => $item['atts']['title'],
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $item['content'],
					],
				];
			}
		}
		return $data;
	}

	/**
	 * Enqueue assets for Divi frontend editor.
	 *
	 * @return void
	 */
	public function add_divi_scripts() {
		if ( ! $this->can_add_tab() ) {
			return;
		}

		$this->add_global_json_data();
		wp_dequeue_script( 'rank-math-pro-metabox' );
		wp_enqueue_style(
			'rank-math-pro-editor',
			RANK_MATH_PRO_URL . 'assets/admin/css/divi.css',
			[],
			RANK_MATH_PRO_VERSION
		);
		wp_enqueue_script(
			'rank-math-pro-editor',
			RANK_MATH_PRO_URL . 'assets/admin/js/divi.js',
			[
				'rm-react',
				'rm-react-dom',
				'jquery-ui-autocomplete',
				'moment',
				'wp-components',
				'wp-compose',
				'wp-data',
				'wp-element',
				'wp-hooks',
				'wp-i18n',
				'wp-plugins',
			],
			RANK_MATH_PRO_VERSION,
			true
		);
	}

	/**
	 * Add JSON data to rankMath global variable.
	 */
	private function add_global_json_data() {
		$id     = get_the_ID();
		$robots = $this->get_meta( 'post', $id, 'rank_math_news_sitemap_robots' );

		Helper::add_json(
			'newsSitemap',
			[
				'robots' => $robots ? $robots : 'index',
			]
		);
	}

	/**
	 * Show field check callback.
	 *
	 * @return boolean
	 */
	private function can_add_tab() {
		if (
			! Helper::is_divi_frontend_editor() ||
			! defined( 'ET_BUILDER_PRODUCT_VERSION' ) ||
			! version_compare( '4.9.2', ET_BUILDER_PRODUCT_VERSION, 'le' )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Add custom toggle (options group) and custom field option on all modules.
	 *
	 * @param array $modules ET builder modules.
	 *
	 * @return array Returns ET builder modules.
	 */
	public function filter_et_builder_parent_modules( $modules ) {
		if ( empty( $modules ) ) {
			return $modules;
		}

		if ( isset( $modules['et_pb_accordion'] ) ) {
			$modules['et_pb_accordion'] = $this->filter_module_et_pb_accordion(
				$modules['et_pb_accordion']
			);
		}

		return $modules;
	}

	/**
	 * Filter ET Accordion module.
	 *
	 * @param object $module The Accordion module.
	 * @return object $module Returns the module.
	 */
	private function filter_module_et_pb_accordion( $module ) {
		static $is_accordion_filtered = false;
		if (
			$is_accordion_filtered ||
			! isset( $module->settings_modal_toggles ) ||
			! isset( $module->fields_unprocessed )
		) {
			return $module;
		}

		/**
		 * Toggles list on the module.
		 *
		 * @var array
		 *
		 * Official tabs list:
		 * 'general':    Content tab.
		 * 'advanced':   Design tab.
		 * 'custom_css': Advanced tab.
		 *
		 * The structures:
		 * array(
		 *     'general'    => array(),
		 *     'advanced'   => array(),
		 *     'custom_css' => array(
		 *         'toggles' => array(
		 *              'toggle_slug' => $toggle_definition,
		 *              ... Other toggles.
		 *         ),
		 *     ),
		 *     ... Other tabs if they exist.
		 * )
		 */
		$toggles_list = $module->settings_modal_toggles;

		// Add Rank Math toggle on general tab.
		if (
			isset( $toggles_list['general'] ) &&
			! empty( $toggles_list['general']['toggles'] )
		) {
			$toggles_list['general']['toggles']['rank_math_faq_schema_toggle'] = [
				'title'    => wp_strip_all_tags( __( 'Rank Math FAQ Schema', 'rank-math-pro' ) ),
				'priority' => 220,
			];

			$module->settings_modal_toggles = $toggles_list;
		}

		/**
		 * Fields list on the module.
		 *
		 * @var array
		 *
		 * The structures:
		 * array(
		 *     'field_slug' => array(
		 *         'label'       => '',
		 *         'description' => '',
		 *         'type'        => '',
		 *         'toggle_slug' => '',
		 *         'tab_slug'    => '',
		 *     ),
		 *     ... Other fields.
		 * )
		 */
		$fields_list = $module->fields_unprocessed;

		// Add 'Member Field' option on 'Member Toggle' options group.
		if ( ! empty( $fields_list ) ) {
			$fields_list['rank_math_faq_schema'] = [
				'label'       => wp_strip_all_tags( __( 'Add FAQ Schema Markup', 'rank-math-pro' ) ),
				'description' => wp_strip_all_tags( __( 'Added by the Rank Math SEO Plugin.', 'rank-math-pro' ) ),
				'toggle_slug' => 'rank_math_faq_schema_toggle',
				'tab_slug'    => 'general',
				'type'        => 'yes_no_button',
				'default'     => 'off',
				'options'     => [
					'on'  => wp_strip_all_tags( __( 'Yes', 'rank-math-pro' ) ),
					'off' => wp_strip_all_tags( __( 'No', 'rank-math-pro' ) ),
				],
			];

			$module->fields_unprocessed = $fields_list;
		}

		$is_accordion_filtered = true;

		return $module;
	}
}
