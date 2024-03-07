<?php
/**
 * Elementor integration.
 *
 * @since      2.0.8
 * @package    RankMath
 * @subpackage RankMath\Core
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Elementor;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use Elementor\Controls_Manager;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor class.
 */
class Elementor {

	use Hooker;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		$this->action( 'elementor/editor/before_enqueue_scripts', 'editor_scripts' );
		$this->action( 'elementor/widgets/register', 'add_breadcrumb_widget' );
		$this->action( 'elementor/element/accordion/section_title/before_section_end', 'add_faq_setting', 99 );
		$this->filter( 'rank_math/json_ld', 'add_faq_schema', 99 );
	}

	/**
	 * Enqueue the editor scripts.
	 */
	public function editor_scripts() {

		wp_dequeue_script( 'rank-math-pro-metabox' );
		wp_enqueue_style(
			'rank-math-pro-editor',
			RANK_MATH_PRO_URL . 'assets/admin/css/elementor.css',
			[],
			RANK_MATH_PRO_VERSION
		);

		wp_enqueue_script(
			'rank-math-pro-editor',
			RANK_MATH_PRO_URL . 'assets/admin/js/elementor.js',
			[
				'rank-math-editor',
			],
			RANK_MATH_PRO_VERSION,
			true
		);
	}

	/**
	 * Add Breadcrumb Widget in Elementor Editor.
	 *
	 * @param Widgets_Manager $widget The widgets manager.
	 */
	public function add_breadcrumb_widget( $widget ) {
		$widget->register( new Widget_Breadcrumbs() );
	}

	/**
	 * Add toggle to enable/disable FAQ schema in Accordion Widget.
	 *
	 * @param Controls_Stack $widget The control.
	 */
	public function add_faq_setting( $widget ) {
		$widget->add_control(
			'rank_math_add_faq_schema',
			[
				'label'       => esc_html__( 'Add FAQ Schema Markup', 'rank-math-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'separator'   => 'before',
				'description' => esc_html__( 'Added by the Rank Math SEO Plugin.', 'rank-math-pro' ),
			]
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

		global $post;
		$elementor_document = \Elementor\Plugin::$instance->documents->get( $post->ID );
		if ( ! $elementor_document || ! $elementor_document->is_built_with_elementor() ) {
			return $data;
		}

		$elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
		if ( ! empty( $elementor_data ) && is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		$accordion_data = $this->get_accordion_data( $elementor_data );
		if ( empty( $accordion_data ) ) {
			return $data;
		}

		$data['faq-data'] = [
			'@type' => 'FAQPage',
		];

		foreach ( $accordion_data as $faqs ) {
			foreach ( $faqs as $faq ) {
				$data['faq-data']['mainEntity'][] = [
					'@type'          => 'Question',
					'name'           => $faq['tab_title'],
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $faq['tab_content'],
					],
				];
			}
		}

		return $data;
	}

	/**
	 * Get accordion data.
	 *
	 * @param array $elements Elements Data.
	 *
	 * @return array
	 */
	private function get_accordion_data( $elements ) {
		if ( ! is_array( $elements ) ) {
			return [];
		}

		$results = [];
		if (
			isset( $elements['rank_math_add_faq_schema'] ) &&
			'yes' === $elements['rank_math_add_faq_schema'] &&
			! empty( $elements['tabs'] )
		) {
			$results[] = $elements['tabs'];
		}

		foreach ( $elements as $element ) {
			$results = array_merge( $results, $this->get_accordion_data( $element ) );
		}

		return $results;
	}
}
