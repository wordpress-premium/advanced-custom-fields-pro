<?php
/**
 * The Schema Module
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Str;

defined( 'ABSPATH' ) || exit;

/**
 * Schema class.
 */
class Schema {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include required files.
	 */
	public function includes() {
		new Admin();
		new Ajax();
		new Post_Type();
		new \RankMath\Schema\Schema();
		new Frontend();
		new Video();
	}

	/**
	 * Register hooks.
	 */
	public function hooks() {
		$this->action( 'enqueue_block_editor_assets', 'editor_assets', 9 );
		$this->filter( 'rank_math/schema/block/howto-block', 'add_graph', 11, 2 );
		$this->filter( 'rank_math/schema/block/howto/content', 'block_content', 11, 3 );
	}

	/**
	 * Enqueue Styles and Scripts required for blocks at backend.
	 */
	public function editor_assets() {
		wp_enqueue_script(
			'rank-math-howto-block',
			RANK_MATH_PRO_URL . 'assets/admin/js/blocks.js',
			[],
			rank_math_pro()->version,
			true
		);

		if ( Helper::is_module_active( 'local-seo' ) ) {
			Helper::add_json( 'previewImage', RANK_MATH_PRO_URL . 'includes/modules/local-seo/assets/img/map-placeholder.jpg' );
			Helper::add_json( 'mapStyle', Helper::get_settings( 'titles.map_style', 'roadmap' ) );
			Helper::add_json( 'limitLocations', Helper::get_settings( 'titles.limit_results', 10 ) );
		}
	}

	/**
	 * Display additional content in the HowTo Block.
	 *
	 * @param string $output     Schema data.
	 * @param array  $data       Output data.
	 * @param array  $attributes Schema attributes.
	 */
	public function block_content( $output, $data, $attributes ) {
		$data[] = $this->build_estimated_cost( $attributes );
		$data[] = $this->build_supplies( $attributes );
		$data[] = $this->build_tools( $attributes );
		$data[] = $this->build_materials( $attributes );
		return join( "\n", $data );
	}

	/**
	 * FAQ rich snippet.
	 *
	 * @param array $data  Array of JSON-LD data.
	 * @param array $block JsonLD Instance.
	 *
	 * @return array
	 */
	public function add_graph( $data, $block ) {
		$attrs = $block['attrs'];

		$this->add_estimated_cost( $data['howto'], $attrs );
		$this->add_supplies( $data['howto'], $attrs );
		$this->add_tools( $data['howto'], $attrs );
		$this->add_materials( $data['howto'], $attrs );

		return $data;
	}

	/**
	 * [build_estimated_cost description]
	 *
	 * @param [type] $attrs [description].
	 *
	 * @return [type]        [description]
	 */
	private function build_estimated_cost( $attrs ) {
		if ( empty( $attrs['estimatedCost'] ) ) {
			return;
		}

		$currency = ! empty( $attrs['estimatedCostCurrency'] ) ? $attrs['estimatedCostCurrency'] : 'USD';

		return sprintf(
			'<p class="rank-math-howto-estimatedCost"><strong>%2$s</strong> <span>%1$s</span></p>',
			$attrs['estimatedCost'] . ' ' . $currency,
			__( 'Estimated Cost:', 'rank-math-pro' )
		);
	}

	/**
	 * [build_estimated_cost description]
	 *
	 * @param [type] $attrs [description].
	 *
	 * @return [type]        [description]
	 */
	private function build_supplies( $attrs ) {
		if ( empty( $attrs['supply'] ) ) {
			return;
		}

		$supplies = Str::to_arr_no_empty( $attrs['supply'] );
		if ( empty( $supplies ) ) {
			return;
		}

		return sprintf(
			'<p class="rank-math-howto-supply"><strong>%2$s</strong> <ul><li>%1$s</li></ul></p>',
			implode( '</li><li>', $supplies ),
			__( 'Supply:', 'rank-math-pro' )
		);

	}

	/**
	 * [build_estimated_cost description]
	 *
	 * @param [type] $attrs [description].
	 *
	 * @return [type]        [description]
	 */
	private function build_tools( $attrs ) {
		if ( empty( $attrs['tools'] ) ) {
			return;
		}

		$tools = Str::to_arr_no_empty( $attrs['tools'] );
		if ( empty( $tools ) ) {
			return;
		}

		return sprintf(
			'<p class="rank-math-howto-tools"><strong>%2$s</strong> <ul><li>%1$s</li></ul></p>',
			implode( '</li><li>', $tools ),
			__( 'Tools:', 'rank-math-pro' )
		);
	}

	/**
	 * [build_estimated_cost description]
	 *
	 * @param [type] $attrs [description].
	 *
	 * @return [type]        [description]
	 */
	private function build_materials( $attrs ) {
		if ( empty( $attrs['material'] ) ) {
			return;
		}

		$tools = Str::to_arr_no_empty( $attrs['tools'] );
		if ( empty( $tools ) ) {
			return;
		}

		return sprintf(
			'<p class="rank-math-howto-tools"><strong>%2$s</strong> <span>%1$s</span></p>',
			$attrs['material'],
			__( 'Materials:', 'rank-math-pro' )
		);
	}

	/**
	 * Add Duration.
	 *
	 * @param [type] $data  [description].
	 * @param [type] $attrs [description].
	 */
	private function add_estimated_cost( &$data, $attrs ) {
		if ( empty( $attrs['estimatedCost'] ) ) {
			return;
		}

		$data['estimatedCost'] = [
			'@type'    => 'MonetaryAmount',
			'currency' => ! empty( $attrs['estimatedCostCurrency'] ) ? $attrs['estimatedCostCurrency'] : 'USD',
			'value'    => $attrs['estimatedCost'],
		];
	}

	/**
	 * Add Duration.
	 *
	 * @param [type] $data  [description].
	 * @param [type] $attrs [description].
	 */
	private function add_supplies( &$data, $attrs ) {
		if ( empty( $attrs['supply'] ) ) {
			return;
		}

		$supplies = Str::to_arr_no_empty( $attrs['supply'] );
		if ( empty( $supplies ) ) {
			return;
		}
		$supply = [];

		foreach ( $supplies as $value ) {
			$supply[] = [
				'@type' => 'HowToSupply',
				'name'  => $value,
			];
		}

		$data['supply'] = $supply;
	}

	/**
	 * Add Duration.
	 *
	 * @param [type] $data  [description].
	 * @param [type] $attrs [description].
	 */
	private function add_tools( &$data, $attrs ) {
		if ( empty( $attrs['tools'] ) ) {
			return;
		}

		$tools = Str::to_arr_no_empty( $attrs['tools'] );

		if ( empty( $tools ) ) {
			return;
		}
		$tool = [];

		foreach ( $tools as $value ) {
			$tool[] = [
				'@type' => 'HowToTool',
				'name'  => $value,
			];
		}

		$data['tool'] = $tool;
	}

	/**
	 * Add Duration.
	 *
	 * @param [type] $data  [description].
	 * @param [type] $attrs [description].
	 */
	private function add_materials( &$data, $attrs ) {
		if ( empty( $attrs['material'] ) ) {
			return;
		}

		$data['material'] = $attrs['material'];
	}
}
