<?php
/**
 * Elementor Integration.
 *
 * @since      2.0.8
 * @package    RankMathPro
 * @subpackage RankMath\Core
 * @author     Rank Math <support@rankmath.com>
 * @copyright Copyright (C) 2008-2019, Elementor Ltd
 * The following code is a derivative work of the code from the Elementor(https://github.com/elementor/elementor/), which is licensed under GPL v3.
 */

namespace RankMathPro\Elementor;

use RankMath\Helper;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Core\Schemes\Typography;

defined( 'ABSPATH' ) || exit;

/**
 * Elementor Breadcrumb Widget class.
 */
class Widget_Breadcrumbs extends Widget_Base {

	/**
	 * Get element name.
	 *
	 * @return string The name.
	 */
	public function get_name() {
		return 'breadcrumbs';
	}

	/**
	 * Get element title.
	 *
	 * @return string Element title.
	 */
	public function get_title() {
		return __( 'Breadcrumbs', 'rank-math-pro' );
	}

	/**
	 * Get element icon.
	 *
	 * @return string Element icon.
	 */
	public function get_icon() {
		return 'eicon-rank-math';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'rankmath', 'seo', 'breadcrumbs', 'rank math', 'schema' ];
	}

	/**
	 * Register model controls. Used to add new controls to the page settings model.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_breadcrumbs_content',
			[
				'label' => __( 'Breadcrumbs', 'rank-math-pro' ),
			]
		);

		if ( ! Helper::is_breadcrumbs_enabled() ) {
			$this->add_control(
				'html_disabled_alert',
				[
					'raw'             => __( 'Breadcrumbs are disabled in the Rank Math SEO', 'rank-math-pro' ) . ' ' . sprintf( '<a href="%s" target="_blank">%s</a>', admin_url( 'admin.php?page=rank-math-options-general#setting-panel-breadcrumbs' ), __( 'Breadcrumbs Panel', 'rank-math-pro' ) ),
					'type'            => Controls_Manager::RAW_HTML,
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-danger',
				]
			);
		}

		$this->add_responsive_control(
			'align',
			[
				'label'        => __( 'Alignment', 'rank-math-pro' ),
				'type'         => Controls_Manager::CHOOSE,
				'options'      => [
					'left'   => [
						'title' => __( 'Left', 'rank-math-pro' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'rank-math-pro' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => __( 'Right', 'rank-math-pro' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'prefix_class' => 'elementor%s-align-',
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => __( 'HTML Tag', 'rank-math-pro' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''     => __( 'Default', 'rank-math-pro' ),
					'p'    => 'p',
					'div'  => 'div',
					'nav'  => 'nav',
					'span' => 'span',
				],
				'default' => '',
			]
		);

		$this->add_control(
			'html_description',
			[
				'raw'             => __( 'Additional settings are available in the Rank Math SEO', 'rank-math-pro' ) . ' ' . sprintf( '<a href="%s" target="_blank">%s</a>', admin_url( 'admin.php?page=rank-math-options-general#setting-panel-breadcrumbs' ), __( 'Breadcrumbs Panel', 'rank-math-pro' ) ),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => __( 'Breadcrumbs', 'rank-math-pro' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'typography',
				'selector' => '{{WRAPPER}}',
				'scheme'   => Typography::TYPOGRAPHY_2,
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => __( 'Text Color', 'rank-math-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}}' => 'color: {{VALUE}};',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_breadcrumbs_style' );

		$this->start_controls_tab(
			'tab_color_normal',
			[
				'label' => __( 'Normal', 'rank-math-pro' ),
			]
		);

		$this->add_control(
			'link_color',
			[
				'label'     => __( 'Link Color', 'rank-math-pro' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => [
					'{{WRAPPER}} a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_color_hover',
			[
				'label' => __( 'Hover', 'rank-math-pro' ),
			]
		);

		$this->add_control(
			'link_hover_color',
			[
				'label'     => __( 'Color', 'rank-math-pro' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Get HTML tag. Retrieve the section element HTML tag.
	 *
	 * @param array $args Html tags args.
	 *
	 * @return array Section HTML tag.
	 */
	public function get_html_tag( $args ) {
		$html_tag = $this->get_settings( 'html_tag' );
		if ( $html_tag ) {
			$args['wrap_before'] = "<{$html_tag}>";
			$args['wrap_after']  = "</{$html_tag}>";
		}
		return $args;
	}

	/**
	 * Render element. Generates the final HTML on the frontend.
	 */
	protected function render() {
		add_filter( 'rank_math/frontend/breadcrumb/args', [ $this, 'get_html_tag' ] );
		rank_math_the_breadcrumbs();
	}
}
