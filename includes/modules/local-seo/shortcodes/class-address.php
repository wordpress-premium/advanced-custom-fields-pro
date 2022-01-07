<?php
/**
 * The Address shortcode Class.
 *
 * @since      1.0.1
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Address class.
 */
class Address {

	/**
	 * Get Address Data.
	 *
	 * @param Location_Shortcode $shortcode Location_Shortcode Instance.
	 * @param array              $schema    Array of Schema data.
	 *
	 * @return string
	 */
	public function get_data( $shortcode, $schema ) {
		$atts = $shortcode->atts;
		$data = $this->get_address( $schema, $atts );

		$schema = $schema['metadata'] + $schema;
		$labels = [
			'telephone'        => [
				'key'   => 'telephone',
				'label' => esc_html__( 'Phone', 'rank-math-pro' ),
			],
			'secondary_number' => [
				'key'   => 'secondary_number',
				'label' => esc_html__( 'Secondary phone', 'rank-math-pro' ),
			],
			'fax'              => [
				'key'   => 'faxNumber',
				'label' => esc_html__( 'Fax', 'rank-math-pro' ),
			],
			'email'            => [
				'key'   => 'email',
				'label' => esc_html__( 'Email', 'rank-math-pro' ),
			],
			'url'              => [
				'key'   => 'url',
				'label' => esc_html__( 'URL', 'rank-math-pro' ),
			],
			'vat_id'           => [
				'key'   => 'vatID',
				'label' => esc_html__( 'VAT ID', 'rank-math-pro' ),
			],
			'tax_id'           => [
				'key'   => 'taxID',
				'label' => esc_html__( 'Tax ID', 'rank-math-pro' ),
			],
			'coc_id'           => [
				'key'   => 'coc_id',
				'label' => esc_html__( 'Chamber of Commerce ID', 'rank-math-pro' ),
			],
			'priceRange'       => [
				'key'   => 'priceRange',
				'label' => esc_html__( 'Price indication', 'rank-math-pro' ),
			],
		];

		foreach ( $labels as $key => $label ) {
			if ( empty( $atts[ "show_$key" ] ) || empty( $schema[ $label['key'] ] ) ) {
				continue;
			}

			$value = esc_html( $schema[ $label['key'] ] );
			if ( 'email' === $key ) {
				$value = '<a href="mailto:' . $value . '">' . $value . '</a>';
			}
			if ( in_array( $key, [ 'telephone', 'secondary_number' ], true ) ) {
				$value = '<a href="tel:' . $value . '">' . $value . '</a>';
			}

			$data .= '<div><strong>' . $label['label'] . '</strong>: ' . $value . '</div>';
		}

		return $data;
	}

	/**
	 * Get Address Data.
	 *
	 * @param array $schema Array of Schema data.
	 * @param array $atts   Shortcode attributes.
	 *
	 * @return string
	 */
	public function get_address( $schema, $atts = [] ) {
		$address = array_filter( $schema['address'] );
		if ( false === $address || empty( $atts['show_company_address'] ) ) {
			return '';
		}

		$hash = array_filter(
			[
				'streetAddress'   => true,
				'addressLocality' => true,
				'addressRegion'   => ! empty( $atts['show_state'] ),
				'addressCountry'  => ! empty( $atts['show_country'] ),
				'postalCode'      => true,
			]
		);

		$glue = empty( $atts['show_on_one_line'] ) ? ',<br />' : ', ';
		$data = implode( $glue, array_intersect_key( $address, $hash ) );

		return '<h5>' . esc_html__( 'Address:', 'rank-math-pro' ) . '</h5><address>' . $data . '</address>';
	}
}
