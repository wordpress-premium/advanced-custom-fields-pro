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
use MyThemeShop\Helpers\Conditional;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce class.
 *
 * @codeCoverageIgnore
 */
class WooCommerce {

	use Hooker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			new Admin();
			return;
		}
		$this->filter( 'rank_math/sitemap/entry', 'remove_hidden_products', 10, 3 );
		$this->action( 'wp', 'init' );
	}

	/**
	 * Filter/Hooks to add GTIN value on Product page.
	 */
	public function init() {
		if ( ! is_product() ) {
			return;
		}

		$this->filter( 'rank_math/frontend/robots', 'robots' );
		$this->filter( 'rank_math/snippet/rich_snippet_product_entity', 'add_gtin_in_schema' );
		$this->filter( 'rank_math/woocommerce/product_brand', 'add_custom_product_brand' );
		$this->filter( 'rank_math/snippet/rich_snippet_product_entity', 'add_variations_data' );
		$this->action( 'rank_math/opengraph/facebook', 'og_retailer_id', 60 );
		$this->filter( 'rank_math/snippet/rich_snippet_product_entity', 'additional_schema_properties' );

		if ( Helper::get_settings( 'general.show_gtin' ) ) {
			$this->action( 'woocommerce_product_meta_start', 'add_gtin_meta' );
			$this->filter( 'woocommerce_available_variation', 'add_gtin_to_variation_param', 10, 3 );
			$this->action( 'wp_footer', 'add_variation_script' );
		}
	}

	/**
	 * Remove hidden products from the sitemap.
	 *
	 * @param array  $url    Array of URL parts.
	 * @param string $type   URL type. Can be user, post or term.
	 * @param object $object Data object for the URL.
	 */
	public function remove_hidden_products( $url, $type, $object ) {
		if (
			'post' !== $type ||
			! isset( $object->post_type ) ||
			'product' !== $object->post_type ||
			! Helper::get_settings( 'general.noindex_hidden_products' ) ||
			'hidden' !== \wc_get_product( $object->ID )->get_catalog_visibility()
		) {
			return $url;
		}

		return false;
	}

	/**
	 * Change robots for WooCommerce pages according to settings
	 *
	 * @param array $robots Array of robots to sanitize.
	 *
	 * @return array Modified robots.
	 */
	public function robots( $robots ) {
		$is_hidden = \wc_get_product()->get_catalog_visibility() === 'hidden';

		if ( Helper::get_settings( 'general.noindex_hidden_products' ) && $is_hidden ) {
			return [
				'noindex'  => 'noindex',
				'nofollow' => 'nofollow',
			];
		}

		return $robots;
	}

	/**
	 * Filter to change Product brand value based on the Settings.
	 *
	 * @param string $brand Brand.
	 *
	 * @return string Modified brand.
	 */
	public function add_custom_product_brand( $brand ) {
		return 'custom' === Helper::get_settings( 'general.product_brand' ) ? Helper::get_settings( 'general.custom_product_brand' ) : $brand;
	}

	/**
	 * Filter to add url, manufacturer & brand url in Product schema.
	 *
	 * @param  array $entity Snippet Data.
	 * @return array
	 *
	 * @since 2.7.0
	 */
	public function additional_schema_properties( $entity ) {
		if ( ! $this->do_filter( 'schema/woocommerce/additional_properties', false ) ) {
			return $entity;
		}

		$type                   = 'company' === Helper::get_settings( 'titles.knowledgegraph_type' ) ? 'organization' : 'person';
		$entity['manufacturer'] = [ '@id' => home_url( "/#{$type}" ) ];
		$entity['url']          = get_the_permalink();

		$taxonomy = Helper::get_settings( 'general.product_brand' );
		if ( ! empty( $entity['brand'] ) && $taxonomy && taxonomy_exists( $taxonomy ) ) {
			$brands                 = get_the_terms( $product_id, $taxonomy );
			$entity['brand']['url'] = is_wp_error( $brands ) || empty( $brands[0] ) ? '' : get_term_link( $brands[0], $taxonomy );
		}

		return $entity;
	}

	/**
	 * Filter to add GTIN in Product schema.
	 *
	 * @param array $entity Snippet Data.
	 * @return array
	 */
	public function add_gtin_in_schema( $entity ) {
		$gtin_key = Helper::get_settings( 'general.gtin', 'gtin8' );
		if ( ! empty( $entity[ $gtin_key ] ) ) {
			return $entity;
		}

		global $product;
		if ( ! is_object( $product ) ) {
			$product = wc_get_product( get_the_ID() );
		}

		$gtin = $product->get_meta( '_rank_math_gtin_code' );
		if ( $gtin ) {
			$entity[ $gtin_key ] = $gtin;
		}

		if ( ! empty( $entity['isbn'] ) ) {
			$entity['@type'] = [
				'Product',
				'Book',
			];
		}

		return $entity;
	}

	/**
	 * Add GTIN data in Product metadata.
	 */
	public function add_gtin_meta() {
		global $product;
		$gtin_code = $product->get_meta( '_rank_math_gtin_code' );
		if ( ! $gtin_code ) {
			return;
		}

		echo '<span class="rank-math-gtin-wrapper">';
		echo $this->get_formatted_value( $gtin_code );
		echo '</span>';
	}

	/**
	 * Add GTIN value to available variations.
	 *
	 * @param array  $args      Array of variation arguments.
	 * @param Object $product   Current Product Object.
	 * @param Object $variation Product variation.
	 *
	 * @return array Modified robots.
	 */
	public function add_gtin_to_variation_param( $args, $product, $variation ) {
		$args['rank_math_gtin'] = $this->get_formatted_value( $variation->get_meta( '_rank_math_gtin_code' ) );

		return $args;
	}

	/**
	 * Variation script to change GTIN when variation is changed from the dropdown.
	 */
	public function add_variation_script() {
		global $product;
		if ( ! $product->is_type( 'variable' ) ) {
			return;
		}
		?>
		<script>
			const $form = jQuery( '.variations_form' );
			const wrapper = jQuery( '.rank-math-gtin-wrapper' );
			const gtin_code = wrapper.text();
			if ( $form.length ) {
				$form.on( 'found_variation', function( event, variation ) {
					if ( variation.rank_math_gtin ) {
						wrapper.text( variation.rank_math_gtin );
					}
				} );

				$form.on( 'reset_data', function() {
					wrapper.text( gtin_code );
				} );
			}
		</script>
		<?php
	}

	/**
	 * Filter to add Offers array in Product schema.
	 *
	 * @param array $entity Snippet Data.
	 * @return array
	 */
	public function add_variations_data( $entity ) {
		$product = wc_get_product( get_the_ID() );
		if ( ! $product->is_type( 'variable' ) ) {
			return $entity;
		}

		if ( empty( $entity['offers']['@type'] ) || 'AggregateOffer' !== $entity['offers']['@type'] ) {
			return $entity;
		}

		$variations = $product->get_available_variations( 'object' );
		if ( empty( $variations ) ) {
			return $entity;
		}

		$this->add_variable_gtin( get_the_ID(), $entity['offers'] );

		$offers = [];
		foreach ( $variations as $variation ) {
			$price_valid_until = get_post_meta( $variation->get_id(), '_sale_price_dates_to', true );
			if ( ! $price_valid_until ) {
				$price_valid_until = strtotime( ( date( 'Y' ) + 1 ) . '-12-31' );
			}

			$offer_entity      = [
				'@type'           => 'Offer',
				'description'     => wp_strip_all_tags( $variation->get_description() ),
				'price'           => wc_get_price_to_display( $variation ),
				'priceCurrency'   => get_woocommerce_currency(),
				'availability'    => 'outofstock' === $variation->get_stock_status() ? 'https://schema.org/OutOfStock' : 'https://schema.org/InStock',
				'itemCondition'   => 'NewCondition',
				'priceValidUntil' => date_i18n( 'Y-m-d', $price_valid_until ),
				'url'             => $product->get_permalink(),
			];

			$this->add_variable_gtin( $variation->get_id(), $offer_entity );

			$offers[] = $offer_entity;
		}

		$entity['offers']['offers'] = $offers;

		return $entity;
	}

	/**
	 * Add product retailer ID to the OpenGraph output.
	 *
	 * @param OpenGraph $opengraph The current opengraph network object.
	 */
	public function og_retailer_id( $opengraph ) {
		$product = wc_get_product( get_the_ID() );
		if ( empty( $product ) || ! $product->get_sku() ) {
			return;
		}

		$opengraph->tag( 'product:retailer_item_id', $product->get_sku() );
	}

	/**
	 * Add gtin value in variable offer datta.
	 *
	 * @param int   $variation_id Variation ID.
	 * @param array $entity       Offer entity.
	 */
	private function add_variable_gtin( $variation_id, &$entity ) {
		if ( ! Helper::get_settings( 'general.show_gtin' ) ) {
			return;
		}

		$gtin_key = Helper::get_settings( 'general.gtin', 'gtin8' );
		$gtin     = get_post_meta( $variation_id, '_rank_math_gtin_code', true );
		if ( ! $gtin || 'isbn' === $gtin_key ) {
			return;
		}

		$entity[ $gtin_key ] = $gtin;
	}

	/**
	 * Get formatted GTIN value with label.
	 *
	 * @param string $gtin GTIN code.
	 *
	 * @return string Formatted GTIN value with label.
	 */
	private function get_formatted_value( $gtin ) {
		$label = Helper::get_settings( 'general.gtin_label' );
		$label = $label ? $label . ' ' : '';

		return esc_html( $this->do_filter( 'woocommerce/gtin_label', $label ) . $gtin );
	}
}
