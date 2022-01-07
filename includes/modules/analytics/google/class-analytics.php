<?php
/**
 *  Google Analytics.
 *
 * @since      1.0.34
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Google;

use RankMath\Google\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics class.
 */
class Analytics {

	/**
	 * Query analytics data from google client api.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 *
	 * @return array
	 */
	public static function get_analytics( $start_date, $end_date ) {
		if ( ! self::get_view_id() ) {
			return false;
		}

		$args = [
			'viewId'                 => self::get_view_id(),
			'pageSize'               => Api::get()->get_row_limit(),
			'dateRanges'             => [
				[
					'startDate' => $start_date,
					'endDate'   => $end_date,
				],
			],
			'metrics'                => [
				[ 'expression' => 'ga:pageviews' ],
				[ 'expression' => 'ga:users' ],
			],
			'dimensions'             => [
				[ 'name' => 'ga:date' ],
				[ 'name' => 'ga:pagePath' ],
			],
			'dimensionFilterClauses' => [
				[
					'filters' => [
						[
							'dimensionName' => 'ga:medium',
							'operator'      => 'EXACT',
							'expressions'   => 'organic',
						],
					],
				],
			],
			'orderBys'               => [
				[
					'fieldName' => 'ga:pageviews',
					'sortOrder' => 'DESCENDING',
				],
			],
		];

		$options = get_option( 'rank_math_google_analytic_options', [] );
		if ( ! empty( $options ) && 'all' !== $options['country'] ) {
			$args['dimensionFilterClauses'][0]['filters'][] = [
				'dimensionName' => 'ga:countryIsoCode',
				'operator'      => 'EXACT',
				'expressions'   => $options['country'],
			];
		}

		$response = Api::get()->http_post(
			'https://analyticsreporting.googleapis.com/v4/reports:batchGet',
			[
				'reportRequests' => [ $args ],
			]
		);

		Api::get()->log_failed_request( $response, 'analytics', $start_date, func_get_args() );

		if ( ! Api::get()->is_success() || ! isset( $response['reports'], $response['reports'][0]['data']['rows'] ) ) {
			return false;
		}

		return $response['reports'][0]['data']['rows'];
	}


	/**
	 * Get view id.
	 *
	 * @return string
	 */
	public static function get_view_id() {
		static $rank_math_view_id;

		if ( is_null( $rank_math_view_id ) ) {
			$options           = get_option( 'rank_math_google_analytic_options' );
			$rank_math_view_id = ! empty( $options['view_id'] ) ? $options['view_id'] : false;
		}

		return $rank_math_view_id;
	}
}
