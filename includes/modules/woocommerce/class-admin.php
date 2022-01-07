<?php
/**
 * WooCommerce module.
 *
 * @since      1.0
 * @package    RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;
use MyThemeShop\Helpers\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * @codeCoverageIgnore
 */
class Admin {

	use Hooker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->action( 'rank_math/admin/settings/woocommerce', 'add_woocommerce_fields' );
		$this->action( 'woocommerce_product_options_sku', 'add_gtin_field' );
		$this->action( 'woocommerce_product_after_variable_attributes', 'add_variation_gtin_field', 10, 3 );
		$this->action( 'woocommerce_admin_process_product_object', 'save_gtin_data' );
		$this->action( 'woocommerce_save_product_variation', 'save_variation_gtin_data', 10, 2 );
	}

	/**
	 * Add options to WooCommerce module.
	 *
	 * @param object $cmb CMB object.
	 */
	public function add_woocommerce_fields( $cmb ) {
		$options           = Helper::get_object_taxonomies( 'product', 'choices', false );
		$options['custom'] = esc_html__( 'Custom', 'rank-math-pro' );

		$cmb->add_field(
			[
				'id'      => 'product_brand',
				'type'    => 'select',
				'name'    => esc_html__( 'Select Brand', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Select Product Brand Taxonomy to use in Schema.org & OpenGraph markup.', 'rank-math-pro' ),
				'options' => $options,
			]
		);

		$cmb->add_field(
			[
				'id'   => 'custom_product_brand',
				'type' => 'text',
				'name' => esc_html__( 'Brand', 'rank-math-pro' ),
				'desc' => esc_html__( 'Brand value to use in Schema.org & OpenGraph markup.', 'rank-math-pro' ),
				'dep'  => [ [ 'product_brand', 'custom' ] ],
			]
		);

		$cmb->add_field(
			[
				'id'      => 'gtin',
				'type'    => 'select',
				'name'    => esc_html__( 'Global Identifier', 'rank-math-pro' ),
				'desc'    => wp_kses_post( __( 'Global Identifier key to use in the Product Schema.', 'rank-math-pro' ) ),
				'options' => [
					'gtin8'  => esc_html__( 'GTIN-8', 'rank-math-pro' ),
					'gtin12' => esc_html__( 'GTIN-12', 'rank-math-pro' ),
					'gtin13' => esc_html__( 'GTIN-13', 'rank-math-pro' ),
					'gtin14' => esc_html__( 'GTIN-14', 'rank-math-pro' ),
					'isbn'   => esc_html__( 'ISBN', 'rank-math-pro' ),
					'mpn'    => esc_html__( 'MPN', 'rank-math-pro' ),
				],
				'default' => 'gtin8',
			]
		);

		$cmb->add_field(
			[
				'id'      => 'show_gtin',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Show Global Identifier', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Display the Global Identified on Product Page along with other product details.', 'rank-math-pro' ),
				'default' => 'off',
			]
		);

		$cmb->add_field(
			[
				'id'      => 'gtin_label',
				'type'    => 'text',
				'name'    => esc_html__( 'Global Identifier label', 'rank-math-pro' ),
				'desc'    => esc_html__( 'Global Identifier label to show on Product Page.', 'rank-math-pro' ),
				'default' => 'GTIN:',
				'dep'     => [ [ 'show_gtin', 'on' ] ],
			]
		);

		$cmb->add_field(
			[
				'id'      => 'noindex_hidden_products',
				'type'    => 'toggle',
				'name'    => esc_html__( 'Noindex Hidden Products', 'rank-math-pro' ),
				'desc'    => wp_kses_post( __( 'Set Product Pages to noindex when WooCommerce Catalog visibility is set to hidden.', 'rank-math-pro' ) ),
				'default' => 'on',
			]
		);
	}

	/**
	 * Add GTIN field in Product Metabox.
	 */
	public function add_gtin_field() {
		$gtin  = Helper::get_settings( 'general.gtin', 'gtin8' );
		$label = 'isbn' === $gtin ? esc_html__( 'ISBN', 'rank-math-pro' ) : ( 'mpn' === $gtin ? esc_html__( 'MPN', 'rank-math-pro' ) : esc_html__( 'GTIN', 'rank-math-pro' ) );
		?>
		<div class="options_group">
			<?php
			woocommerce_wp_text_input(
				[
					'id'          => '_rank_math_gtin_code',
					'label'       => $label,
					'desc_tip'    => true,
					// Translators: Global Identifier name.
					'description' => sprintf( esc_html__( '%s value to use in the Product schema.', 'rank-math-pro' ), $label ),
				]
			);
			?>
		</div>
		<?php
	}

	/**
	 * Add GTIN field in Variable tab.
	 *
	 * @param int    $loop           Current variation index.
	 * @param array  $variation_data Current variation data.
	 * @param object $variation      Current variation object.
	 */
	public function add_variation_gtin_field( $loop, $variation_data, $variation ) {
		$gtin             = Helper::get_settings( 'general.gtin', 'gtin8' );
		$label            = 'isbn' === $gtin ? esc_html__( 'ISBN', 'rank-math-pro' ) : ( 'mpn' === $gtin ? esc_html__( 'MPN', 'rank-math-pro' ) : esc_html__( 'GTIN', 'rank-math-pro' ) );
		$variation_object = wc_get_product( $variation->ID );
		$value            = $variation_object->get_meta( '_rank_math_gtin_code' );

		woocommerce_wp_text_input(
			[
				'id'            => "_rank_math_gtin_code_variable{$loop}",
				'name'          => "_rank_math_gtin_code_variable[{$loop}]",
				'value'         => $value,
				'label'         => $label,
				'desc_tip'      => true,
				// Translators: Global Identifier name.
				'description'   => sprintf( esc_html__( '%s value to use in Product schema.', 'rank-math-pro' ), $label ),
				'wrapper_class' => 'form-row widefat',
			]
		);
	}

	/**
	 * Save GTIN code.
	 *
	 * @param WC_PRODUCT $product Product Object.
	 */
	public function save_gtin_data( $product ) {
		if ( ! isset( $_POST['_rank_math_gtin_code'] ) ) {
			return;
		}

		$gtin_code = Param::post( '_rank_math_gtin_code' );
		$product->update_meta_data( '_rank_math_gtin_code', wc_clean( wp_unslash( $gtin_code ) ) );
	}

	/**
	 * Save GTIN code for curent variation.
	 *
	 * @param int $variation_id Current variation ID.
	 * @param int $id           Index of current variation.
	 */
	public function save_variation_gtin_data( $variation_id, $id ) {
		if ( ! isset( $_POST['_rank_math_gtin_code_variable'] ) ) {
			return;
		}

		$gtin_code = $_POST['_rank_math_gtin_code_variable'][ $id ];
		$variation = wc_get_product( $variation_id );
		$variation->update_meta_data( '_rank_math_gtin_code', wc_clean( wp_unslash( $gtin_code ) ) );
		$variation->save_meta_data();
	}
}
