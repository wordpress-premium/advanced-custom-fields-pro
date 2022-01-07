<?php
/**
 * The Schema Parser
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Schema;

use WP_Error;
use DOMXpath;
use DOMDocument;

defined( 'ABSPATH' ) || exit;

/**
 * Parser class.
 */
class Parser {

	/**
	 * Get json from url.
	 *
	 * @param string $url Url to fetch html from.
	 */
	public function from_url( $url ) {
		$response = wp_remote_get(
			$url,
			[
				'timeout'   => 30,
				'sslverify' => false,
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = wp_remote_retrieve_body( $response );
		if ( empty( $response ) ) {
			return new WP_Error( 'body_not_found', esc_html__( 'No html body found.', 'rank-math-pro' ) );
		}

		return $this->from_html( $response );
	}

	/**
	 * Get json from html.
	 *
	 * @param string $html HTML to parse.
	 */
	public function from_html( $html ) {
		libxml_use_internal_errors( 1 );

		// DOM.
		$dom = new DOMDocument();
		$dom->loadHTML( $html );

		// XPath.
		$xpath   = new DOMXpath( $dom );
		$scripts = $xpath->query( '//script[@type="application/ld+json"]' );

		$json = [];
		foreach ( $scripts as $script ) {
			$json[] = json_decode( trim( $script->nodeValue ) ); // phpcs:ignore
		}

		return $json;
	}
}
