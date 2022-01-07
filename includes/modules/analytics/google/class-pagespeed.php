<?php
/**
 *  Google PageSpeed.
 *
 * @since      1.0.34
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Google;

use RankMath\Google\Api;
use RankMath\Helpers\Security;

defined( 'ABSPATH' ) || exit;

/**
 * PageSpeed class.
 */
class PageSpeed {

	/**
	 * Get pagespeed score info.
	 *
	 * @param string $url      Url to get pagespeed for.
	 * @param string $strategy Data for desktop or mobile.
	 *
	 * @return array
	 */
	public static function get_pagespeed( $url, $strategy = 'desktop' ) {
		$response = Api::get()->http_get( 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?category=PERFORMANCE&url=' . \rawurlencode( $url ) . '&strategy=' . \strtoupper( $strategy ), [], 30 );
		if ( ! Api::get()->is_success() ) {
			return false;
		}

		return [
			$strategy . '_interactive' => round( \floatval( $response['lighthouseResult']['audits']['interactive']['displayValue'] ), 0 ),
			$strategy . '_pagescore'   => round( $response['lighthouseResult']['categories']['performance']['score'] * 100, 0 ),
		];
	}
}
