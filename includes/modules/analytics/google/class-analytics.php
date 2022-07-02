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
use MyThemeShop\Helpers\Str;

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
		$view_id = self::get_view_id();
		if ( ! $view_id ) {
			return false;
		}

		$options = get_option( 'rank_math_google_analytic_options', [] );
		$is_ga4  = ! empty( $options['property_id'] ) && ! Str::starts_with( 'UA-', $options['property_id'] );
		if ( ! $is_ga4 ) {
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

		$args = [
			'dateRanges' => [
				[
					'startDate' => $start_date,
					'endDate'   => $end_date,
				],
			],
			'dimensions' => [
				[ 'name' => 'pagePathPlusQueryString' ],
				[ 'name' => 'countryId' ],
			],
			'metrics'    => [
				[ 'name' => 'screenPageViews' ],
				[ 'name' => 'totalUsers' ],
			],
			'dimensionFilter' => [
				'filter' => [
					'fieldName'    => 'streamId',
					'stringFilter' => [
						'matchType' => 'EXACT',
						'value'     => $view_id,
					],
				],
			],
		];

		if ( 'all' !== $options['country'] ) {
			$args['dimensionFilter']['filter'][] = [
				'fieldName'    => 'countryId',
				'stringFilter' => [
					'matchType' => 'EXACT',
					'value'     => $options['country'],
				],
			];
		}

		$response = Api::get()->http_post(
			'https://analyticsdata.googleapis.com/v1beta/properties/' . $options['property_id'] . ':runReport',
			$args
		);
		Api::get()->log_failed_request( $response, 'analytics', $start_date, func_get_args() );

		if ( ! Api::get()->is_success() || ! isset( $response['rows'] ) ) {
			return false;
		}

		return $response['rows'];
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
