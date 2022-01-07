<?php
/**
 * Miscellaneous functions.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Url;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * @codeCoverageIgnore
 */
class Common {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'rank_math/admin_bar/items', 'add_admin_bar_items' );
		$this->filter( 'rank_math/metabox/values', 'add_json_data' );
	}

	/**
	 * Add Pinterest Rich Pins Validator to the top admin bar.
	 *
	 * @param object $object The Admin_Bar_Menu object.
	 */
	public function add_admin_bar_items( $object ) {
		$url = rawurlencode( Url::get_current_url() );
		$object->add_sub_menu(
			'rich-pins',
			[
				'title' => esc_html__( 'Rich Pins Validator', 'rank-math-pro' ),
				'href'  => 'https://developers.pinterest.com/tools/url-debugger/?link=' . $url,
				'meta'  => [
					'title'  => esc_html__( 'Pinterest Debugger', 'rank-math-pro' ),
					'target' => '_blank',
				],
			],
			'third-party'
		);
	}

	/**
	 * Add settings in the Advanced tab of the metabox.
	 *
	 * @param array $values Localized data.
	 */
	public function add_json_data( $values ) {
		$values['maxTags'] = 100;

		if ( ! Helper::is_site_connected() ) {
			$values['trendsIcon']         = $this->get_icon_svg();
			$values['trendsUpgradeLink']  = esc_url_raw( admin_url( 'admin.php?page=rank-math&view=help' ) );
			$values['trendsUpgradeLabel'] = esc_html__( 'Activate now', 'rank-math-pro' );
		}

		return $values;
	}

	/**
	 * Get Trends icon <svg> element.
	 *
	 * @return string
	 */
	private function get_icon_svg() {
		return '<svg width="100%" height="100%" viewBox="0 0 36 36" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fit="" preserveAspectRatio="xMidYMid meet" focusable="false">
		<g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
			<g id="Trends-Arrow">
				<g id="TrendsArrow">
					<path d="M1.11227159,26.3181534 L10.2875029,17.1429221 L13.7152617,20.5706809 L4.5400304,29.7459122 C4.20518633,30.0807563 3.66229681,30.0807562 3.32745277,29.7459122 L1.11136262,27.529822 C0.776518575,27.194978 0.776518548,26.6520885 1.11136262,26.3172444 L1.11227159,26.3181534 Z" id="Shape" fill="#4285F4" fill-rule="nonzero"></path>
					<path d="M14.3201543,14.3211528 L22.283717,22.2847155 L19.4658829,25.1025495 C19.1310388,25.4373936 18.5881494,25.4373937 18.2533053,25.1025495 L10.2906516,17.1398959 L13.1084857,14.3220618 C13.4429747,13.987572 13.9851638,13.9871653 14.3201543,14.3211528 Z" id="Shape" fill="#EA4335" fill-rule="nonzero"></path>
					<polygon id="Rectangle-path" fill="#FABB05" fill-rule="nonzero" points="18.8573051 18.8577571 28.2843236 9.43073862 31.7120824 12.8584974 22.2850639 22.2855159"></polygon>
					<path d="M35.0711567,15.5054713 L35.0711567,7 L35.0711567,7 C35.0711567,6.44771525 34.6234415,6 34.0711567,6 L25.5656854,6 L25.5656854,6 C25.0134007,6 24.5656854,6.44771525 24.5656854,7 C24.5656854,7.26521649 24.6710423,7.5195704 24.8585786,7.70710678 L33.3640499,16.2125781 L33.3640499,16.2125781 C33.7545742,16.6031024 34.3877392,16.6031024 34.7782635,16.2125781 C34.9657999,16.0250417 35.0711567,15.7706878 35.0711567,15.5054713 Z" id="Shape" fill="#34A853" fill-rule="nonzero"></path>
					<rect id="Rectangle-path" x="0" y="0" width="36" height="36"></rect>
				</g>
			</g>
		</g>
	</svg>';
	}

}
