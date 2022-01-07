<?php
/**
 * The Global functionality of the plugin.
 *
 * Defines the functionality loaded on admin.
 *
 * @since      1.0.15
 * @package    RankMathPro
 * @subpackage RankMathPro\Rest
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Rest;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Controller;
use RankMath\Admin\Admin_Helper;
use RankMathPro\Admin\Admin_Helper as ProAdminHelper;

defined( 'ABSPATH' ) || exit;

/**
 * Rest class.
 */
class Rest extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = \RankMath\Rest\Rest_Helper::BASE;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/pingSettings',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'ping_settings' ],
				'permission_callback' => [ $this, 'has_ping_permission' ],
			]
		);
	}

	/**
	 * Check API key in request.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool                     Whether the API key matches or not.
	 */
	public function has_ping_permission( WP_REST_Request $request ) {
		$data = Admin_Helper::get_registration_data();

		return $request->get_param( 'apiKey' ) === $data['api_key'] &&
			$request->get_param( 'username' ) === $data['username'];
	}

	/**
	 * Disconnect website.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function ping_settings( WP_REST_Request $request ) {
		$data         = Admin_Helper::get_registration_data();
		$data['plan'] = $request->get_param( 'plan' );

		Admin_Helper::get_registration_data( $data );
		update_option( 'rank_math_keyword_quota', json_decode( $request->get_param( 'keywords' ) ) );

		$settings = json_decode( $request->get_param( 'settings' ), true );
		if ( ! ProAdminHelper::is_business_plan() && ! empty( $settings['analytics'] ) ) {
			cmb2_update_option( 'rank-math-options-general', 'sync_global_setting', $settings['analytics'] );
		}

		return true;
	}
}
