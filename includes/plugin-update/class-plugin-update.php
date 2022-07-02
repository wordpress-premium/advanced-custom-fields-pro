<?php
/**
 * Plugin update class
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Plugin_Update;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin_Update class
 */
class Plugin_Update {

	use Hooker;

	/**
	 * Placeholder for opening tag inserted with JS.
	 *
	 * @var string
	 */
	const NOTICE_START_MARKER = '[[';

	/**
	 * Placeholder for closing tag inserted with JS.
	 *
	 * @var string
	 */
	const NOTICE_END_MARKER = ']]';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	private $slug = 'seo-by-rank-math-pro';

	/**
	 * Rank Math API URL.
	 *
	 * @var string
	 */
	private $api_url = 'https://rankmath.com/wp-json/rankmath/v1';

	/**
	 * Keep a log of external requests made in this thread so we can avoid
	 * running them multiple times.
	 *
	 * @var string
	 */
	private $requested = [];

	/**
	 * The Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->action( 'admin_enqueue_scripts', 'enqueue' );
		$this->action( 'admin_notices', 'admin_license_notice', 20 );
		$this->action( 'in_plugin_update_message-seo-by-rank-math-pro/rank-math-pro.php', 'beta_update_message', 20, 2 );
		$this->action( 'in_plugin_update_message-' . plugin_basename( RANK_MATH_PRO_FILE ), 'in_plugin_update_message', 30, 2 );
		$this->action( 'add_option_rank_math_connect_data', 'check_and_inject' );
		$this->action( 'update_option_rank_math_connect_data', 'check_and_inject' );
		$this->action( 'delete_option_rank_math_connect_data', 'check_and_inject' );
		$this->action( 'rank_math/settings/toggle_auto_update', 'toggle_auto_update', 10, 1 );
		$this->action( 'update_site_option_auto_update_plugins', 'connect_auto_update_toggles', 20, 4 );

		$this->filter( 'plugin_action_links_' . plugin_basename( RANK_MATH_PRO_FILE ), 'plugin_action_links', 50 );
		$this->filter( 'pre_set_site_transient_update_plugins', 'maybe_inject_update', 20, 1 );
		$this->filter( 'site_transient_update_plugins', 'maybe_disable_update', 90, 1 );
		$this->filter( 'pre_set_site_transient_update_plugins', 'maybe_add_upgrade_notice', 120, 1 );
		$this->filter( 'plugins_api', 'filter_info', 10, 3 );
		$this->filter( 'admin_print_footer_scripts-plugin-install.php', 'iframe_footer_scripts', 10, 3 );

		$this->filter( 'auto_update_plugin', 'auto_update_plugin', 20, 2 );
		$this->filter( 'plugin_auto_update_setting_html', 'plugin_auto_update_setting_html', 10, 3 );

		$this->filter( 'rank_math/admin/should_send_update_notification', 'should_send_update_notification', 10, 2 );
		$this->filter( 'rank_math/admin/update_notification_products', 'update_notification_products', 10, 2 );
	}

	/**
	 * Enqueue styles and scripts.
	 *
	 * @param  string $hook Page hook prefix.
	 *
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'update-core.php' !== $hook ) {
			return;
		}

		Helper::add_json( 'canUpdatePro', $this->can_update() );
		Helper::add_json( 'betaOptinEnabled', Helper::get_settings( 'general.beta_optin' ) );
		wp_enqueue_script( 'rank-math-pro-updates', RANK_MATH_PRO_URL . 'assets/admin/js/updates.js', [], RANK_MATH_PRO_VERSION, true );
	}

	/**
	 * Returns false if Rank Math Pro update object exists but its 'package'
	 * item is empty, which means that the user cannot update. Returns true in
	 * all other cases – even if update is not available ATM.
	 *
	 * @param mixed $transient Update object to check or null to load transient.
	 * @return boolean
	 */
	public function can_update( $transient = null ) {
		if ( is_null( $transient ) ) {
			$transient = get_site_transient( 'update_plugins' );
		}

		if ( ! is_object( $transient ) ) {
			return true;
		}

		if ( ! isset( $transient->response ) || ! isset( $transient->response['seo-by-rank-math-pro/rank-math-pro.php'] ) ) {
			return true;
		}

		return ( ! empty( $transient->response['seo-by-rank-math-pro/rank-math-pro.php']->package ) );
	}

	/**
	 * Add connect/activation notice.
	 *
	 * @return void
	 */
	public function admin_license_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $this->is_block_editor_page() ) {
			return;
		}

		if ( Helper::is_site_connected() || rank_math()->registration->invalid ) {
			return;
		}
		?>
			<div class="notice notice-success rank-math-notice">
				<p>
					<?php
					// translators: 1: opening HTML anchor tag, 2: closing HTML anchor tags.
					echo wp_kses_post( sprintf( __( 'Rank Math Pro is installed but not activated yet. %1$sActivate now%2$s. It only takes 20 seconds!', 'rank-math-pro' ), '<a href="' . esc_url( Admin_Helper::get_activate_url() ) . '">', '</a>' ) );
					?>
				</p>
			</div>
		<?php
	}

	/**
	 * Check if we are on Block Editor page.
	 *
	 * @return bool
	 */
	private function is_block_editor_page() {
		$current_screen = get_current_screen();

		if ( $current_screen instanceof \WP_Screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return true;
		}

		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			return true;
		}

		return false;
	}

	/**
	 * Add Connect/Activate action link
	 *
	 * @param  array $links Action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		if ( ! Helper::is_site_connected() ) {
			$links['activate_license'] = sprintf( '<a href="%s" class="rank-math-pro-activate-link" style="color:green">%s</a>', esc_url( Admin_Helper::get_activate_url( network_admin_url( 'plugins.php' ) ) ), __( 'Enable updates', 'rank-math-pro' ) );
		}

		return $links;
	}

	/**
	 * If user requested check with force-check parameter.
	 *
	 * @return bool
	 */
	public function is_check_requested() {
		return (bool) Param::get( 'force-check' );
	}

	/**
	 * Check for updates & inject to update_plugins transient.
	 *
	 * @return void
	 */
	public function check_and_inject() {
		$this->inject_update( $this->fetch_latest_version( true ) );
	}

	/**
	 * Inject update fetched from the rankmath.com API or pushed to this site via the REST API.
	 *
	 * @param object $transient Origial transient.
	 * @return mixed
	 */
	public function maybe_inject_update( $transient ) {
		$force_check = $this->is_check_requested();
		return $this->inject_update( $this->fetch_latest_version( $force_check ), $transient );
	}

	/**
	 * Remove package download URL if needed.
	 *
	 * @param object $transient Original transient.
	 * @return mixed
	 */
	public function maybe_disable_update( $transient ) {
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return $transient;
		}

		// If we're in the process of updating RM Free then don't disable RM Pro update.
		if ( Param::get( 'action' ) === 'update-selected' && strpos( Param::get( 'plugins', '' ), 'seo-by-rank-math/rank-math.php' ) !== false ) {
			return $transient;
		}

		if ( isset( $transient->response['seo-by-rank-math/rank-math.php'] )
			&& isset( $transient->response['seo-by-rank-math-pro/rank-math-pro.php'] )
			&& ! empty( $transient->response['seo-by-rank-math-pro/rank-math-pro.php']->package )
		) {
			unset( $transient->response['seo-by-rank-math-pro/rank-math-pro.php']->package );
			$transient->response['seo-by-rank-math-pro/rank-math-pro.php']->unavailability_reason = 'update_free';
		}

		return $transient;
	}

	/**
	 * Add upgrade notice if needed, which is displayed on the Updates page (wp-admin/update-core.php)
	 *
	 * @param object $transient Original transient.
	 * @return mixed
	 */
	public function maybe_add_upgrade_notice( $transient ) {
		if ( ! $this->can_update( $transient ) ) {
			$before  = self::NOTICE_START_MARKER;
			$before .= __( 'Automatic updates are not available.', 'rank-math-pro' );
			$before .= ' ';
			$after   = self::NOTICE_END_MARKER;

			$message = $this->get_update_message( $transient->response['seo-by-rank-math-pro/rank-math-pro.php']->unavailability_reason );
			$message = $before . $message . $after;

			$transient->response['seo-by-rank-math-pro/rank-math-pro.php']->upgrade_notice = $message;
		}

		return $transient;
	}

	/**
	 * Inject our update in the update_plugins transient.
	 *
	 * @param  mixed $update    New update object or array, or false to clear current update.
	 * @param  mixed $transient Original updates transient.
	 * @return mixed
	 */
	public function inject_update( $update, $transient = null ) {
		$save = false;
		if ( is_null( $transient ) ) {
			$transient = get_site_transient( 'update_plugins' );
			$save      = true;
		}

		$plugin = plugin_basename( RANK_MATH_PRO_FILE );
		if ( false !== $update ) {
			$obj = $this->get_default_update_data();
			$obj = (object) array_merge( (array) $obj, (array) $update );

			$obj = $this->maybe_apply_beta( $obj );

			// If a newer version is already present in the update_plugins transient then don't inject.
			if ( $this->has_newer_version( $transient, $obj->new_version ) ) {
				return $transient;
			}

			// Default transient data.
			if ( empty( $transient ) ) {
				$transient = new \stdClass();

				$transient->last_checked = time();
				$transient->checked      = [ $plugin => RANK_MATH_PRO_VERSION ];
				$transient->response     = [];
			}

			// Inject if new data has URL and is a newer version than the one currently in use.
			if ( version_compare( $obj->new_version, RANK_MATH_PRO_VERSION, '>' ) ) {
				$transient->response[ $plugin ] = $obj;
				if ( $save ) {
					set_site_transient( 'update_plugins', $transient );
				}
			} else {
				// No update is available.
				$item = (object) [
					'id'            => 'seo-by-rank-math-pro/rank-math-pro.php',
					'slug'          => 'seo-by-rank-math-pro',
					'plugin'        => 'seo-by-rank-math-pro/rank-math-pro.php',
					'new_version'   => RANK_MATH_PRO_VERSION,
					'url'           => '',
					'package'       => '',
					'icons'         => [],
					'banners'       => [],
					'banners_rtl'   => [],
					'tested'        => '',
					'requires_php'  => '',
					'compatibility' => new \stdClass(),
				];

				if ( empty( $transient->no_update ) ) {
					$transient->no_update = [];
				}

				$transient->no_update[ $plugin ] = $item;
			}
		} elseif ( isset( $transient->response[ $plugin ] ) ) {
			unset( $transient->response[ $plugin ] );
		}

		return $transient;
	}

	/**
	 * Replace `new_version` & `package` properties with beta if needed.
	 *
	 * @param object $update_object Plugin update data object.
	 * @return object
	 */
	public function maybe_apply_beta( $update_object ) {
		if ( ! Helper::get_settings( 'general.beta_optin' ) ) {
			return $update_object;
		}

		if (
			empty( $update_object->new_version )
			|| empty( $update_object->beta_version )
			|| empty( $update_object->package )
			|| empty( $update_object->beta_package )
		) {
			return $update_object;
		}

		if ( version_compare( $update_object->new_version, $update_object->beta_version, '>' ) ) {
			return $update_object;
		}

		$update_object->is_beta = true;

		// Store stable version data in case it's needed later.
		$update_object->stable_version = $update_object->new_version;
		$update_object->stable_package = $update_object->package;

		// Override stable version data with beta.
		$update_object->new_version = $update_object->beta_version;
		$update_object->package     = $update_object->beta_package;

		// Add notice that's shown on the Updates page.
		$update_object->upgrade_notice = self::NOTICE_START_MARKER . ' ' . __( 'This update will install a beta version of Rank Math SEO PRO.', 'rank-math-pro' ) . ' ' . self::NOTICE_END_MARKER;

		return $update_object;
	}

	/**
	 * Show beta update message on the Plugins screen.
	 *
	 * @param  array  $plugin_data An array of plugin metadata.
	 * @param  object $response    An array of metadata about the available plugin update.
	 */
	public function beta_update_message( $plugin_data, $response ) {
		if ( empty( $plugin_data['is_beta'] ) ) {
			return;
		}

		printf(
			'</p><p class="rank-math-beta-update-notice">%s',
			esc_html__( 'This update will install a beta version of Rank Math SEO PRO.', 'rank-math-pro' )
		);

		$this->action( 'admin_footer', 'print_beta_notice_css' );
	}

	/**
	 * Print CSS related to the beta notice on the Plugins screen.
	 *
	 * @return void
	 */
	public function print_beta_notice_css() {
		?>
		<style>
			.update-message .rank-math-beta-update-notice {
				font-weight: bold;
				margin-top: 20px;
			}
			.update-message p.rank-math-beta-update-notice:before {
				content: "\f534";
			}
		</style>
		<?php
	}

	/**
	 * Check if the transient already contains newer version of the plugin.
	 *
	 * @param object $transient   Site transient: 'update_plugins'.
	 * @param string $new_version New version we check against, e.g. '1.2'.
	 * @return boolean
	 */
	public function has_newer_version( $transient, $new_version ) {
		$plugin = plugin_basename( RANK_MATH_PRO_FILE );

		return isset( $transient->response[ $plugin ] )
			&& is_object( $transient->response[ $plugin ] )
			&& isset( $transient->response[ $plugin ]->new_version )
			&& version_compare( $transient->response[ $plugin ]->new_version, $new_version, '>' );
	}

	/**
	 * Filter plugin information.
	 *
	 * @param false|object|array $result The result object or array. Default false.
	 * @param string             $action The type of information being requested from the Plugin Installation API.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object      false or Response object.
	 */
	public function filter_info( $result, $action, $args ) {
		if ( ! isset( $args->slug ) || ! ( $this->slug === $args->slug && 'plugin_information' === $action ) ) {
			return $result;
		}

		$information = $this->get_plugin_info();
		if ( $this->has_beta_update( $information ) ) {
			$information->sections['changelog'] = $information->sections['beta_changelog'];
		}
		unset( $information->sections['beta_changelog'] );

		return $information;
	}

	/**
	 * Add JS in plugin-info iframe, to disable the Update button if necessary.
	 *
	 * @return void
	 */
	public function iframe_footer_scripts() {
		if ( Param::get( 'plugin' ) !== 'seo-by-rank-math-pro' ) {
			return;
		}

		$transient = get_site_transient( 'update_plugins' );
		$response = isset( $transient->response['seo-by-rank-math-pro/rank-math-pro.php'] ) ? $transient->response['seo-by-rank-math-pro/rank-math-pro.php'] : new \stdClass();
		if ( empty( $response->package ) && isset( $response->unavailability_reason ) ) {
				$message = $this->get_update_message( $response->unavailability_reason );
			?>
			<script type="text/javascript">jQuery( function() {
				var $button = jQuery( '#plugin_update_from_iframe' );
				if ( $button.data( 'plugin' ) !== 'seo-by-rank-math-pro/rank-math-pro.php' ) {
					return;
				}

				$button.css( 'pointer-events', 'none' ).addClass( 'disabled' ).text( '<?php echo esc_js( __( 'Cannot Update', 'rank-math-pro' ) ); ?>' );
				jQuery( '#section-holder' ).prepend( '<div class="notice notice-error notice-alt"><p><strong><?php echo esc_js( $message ); ?></strong></p></div>' );
			} );</script>
			<?php
		}
	}

	/**
	 * Check if plugin update data & info object contain a valid beta update.
	 *
	 * @param object $plugin_info
	 * @return boolean
	 */
	private function has_beta_update( $plugin_info ) {
		$beta_optin_enabled = Helper::get_settings( 'general.beta_optin' );
		$plugin    = plugin_basename( RANK_MATH_PRO_FILE );
		$transient = get_site_transient( 'update_plugins' );

		return (
			$beta_optin_enabled
			&& ! empty( $transient->response[ $plugin ]->beta_version )
			&& ! empty( $plugin_info->sections['beta_changelog'] )
			&& version_compare( $transient->response[ $plugin ]->beta_version, ! empty( $transient->response[ $plugin ]->stable_version ) ? $transient->response[ $plugin ]->stable_version : $transient->response[ $plugin ]->new_version, '>=' )
		);
	}

	/**
	 * Merge default plugin data with fetched data.
	 *
	 * @param bool   $force_check Disregard cached data & always re-fetch.
	 * @param string $locale      Requested locale. Optional. Default: site locale.
	 *
	 * @return object|bool If there is an update, returns the license information.
	 *                     Otherwise returns false.
	 */
	private function get_plugin_info( $force_check = false, $locale = '' ) {
		$information = $this->get_default_plugin_info();
		$fetched     = $this->fetch_plugin_info( $force_check, $locale );
		if ( is_object( $fetched ) ) {
			$information = (object) array_merge( (array) $information, (array) $fetched );
		}

		return $information;
	}

	/**
	 * Get default plugin info.
	 *
	 * @return object
	 */
	private function get_default_plugin_info() {
		$description  = '<p><strong>' . __( 'Rank Math SEO PRO For WordPress', 'rank-math-pro' ) . '</strong><br />';
		$description .= '★★★★★</p>';
		$description .= '<p><strong>' . __( 'SEO is the most consistent source of traffic for any website', 'rank-math-pro' ) . '.</strong> ';
		// Translators: placeholders are the anchor tag opening and closing.
		$description .= sprintf( __( 'We created %1$sRank Math, a WordPress SEO plugin%2$s, to help every website owner get access to the SEO tools they need to improve their SEO and attract more traffic to their website.', 'rank-math-pro' ), '<a href="https://rankmath.com/wordpress/plugin/seo-suite/?utm_source=LP&amp;utm_campaign=WP" rel="nofollow ugc"><strong>', '</strong></a>' ) . '</p>';

		$plugin_info = [
			'external' => true,
			'name'     => 'Rank Math SEO PRO',
			'slug'     => $this->slug,
			'author'   => '<a href="https://rankmath.com/">Rank Math</a>',
			'homepage' => 'https://rankmath.com/',
			'banners'  => [
				'low'  => 'https://ps.w.org/seo-by-rank-math/assets/banner-772x250.png',
				'high' => 'https://ps.w.org/seo-by-rank-math/assets/banner-1544x500.png',
			],
			'sections' => [
				'description' => $description,
			],
			'version'  => RANK_MATH_PRO_VERSION,
		];

		return (object) $plugin_info;
	}

	/**
	 * Get default plugin info if we can't get anything from the API.
	 *
	 * @return object
	 */
	private function get_default_update_data() {
		$plugin = plugin_basename( RANK_MATH_PRO_FILE );

		$update = [
			'slug'        => $this->slug,
			'plugin'      => $plugin,
			'url'         => 'https://rankmath.com/',
			'icons'       => [
				'svg' => 'https://ps.w.org/seo-by-rank-math/assets/icon.svg?rev=2348086',
				'1x'  => 'https://ps.w.org/seo-by-rank-math/assets/icon-128x128.png',
				'2x'  => 'https://ps.w.org/seo-by-rank-math/assets/icon-256x256.png',
			],
			'new_version' => '',
			'package'     => '',
		];

		return (object) $update;
	}

	/**
	 * Checks the license manager to see if there is an update available for this product.
	 *
	 * @param bool $force_check Disregard cached data & always re-fetch.
	 * @return object|bool If there is an update, returns the license information.
	 *                     Otherwise returns false.
	 */
	public function fetch_latest_version( $force_check = false ) {
		$stored = get_site_transient( 'rank_math_pro_updates' );
		if ( ! $force_check && ! empty( $stored ) ) {
			return $stored;
		}

		if ( ! empty( $this->requested['version'] ) ) {
			return ( false === $stored ? [] : $stored );
		}

		$params = [
			'site_url'     => is_multisite() ? network_site_url() : home_url(),
			'product_slug' => $this->slug,
		];

		$this->maybe_add_auth_params( $params );

		// Send the request.
		$response = wp_remote_post(
			$this->api_url . '/updateCheck/',
			[
				'timeout' => defined( 'DOING_CRON' ) && DOING_CRON ? 30 : 10,
				'body'    => $params,
			]
		);

		$this->requested['version'] = true;

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );
		$result        = json_decode( $response_body, true );
		if ( ! is_array( $result ) || ! isset( $result['new_version'] ) ) {
			return false;
		}

		set_site_transient( 'rank_math_pro_updates', $result, 3600 );
		update_site_option( 'rank_math_pro_google_updates', $result['g_updates'] );
		return $result;
	}


	/**
	 * Checks the license manager to see if there is an update available for this product.
	 *
	 * @param bool   $force_check Disregard cached data & always re-fetch.
	 * @param string $locale      Requested locale. Optional. Default: site locale.
	 * @return object|bool If there is an update, returns the license information.
	 *                     Otherwise returns false.
	 */
	public function fetch_plugin_info( $force_check = false, $locale = '' ) {
		if ( ! $locale ) {
			$locale = get_locale();
		}

		$stored = get_site_transient( 'rank_math_pro_info_' . $locale );
		if ( ! $force_check && ! empty( $stored ) ) {
			return $stored;
		}

		if ( ! empty( $this->requested['info'] ) ) {
			return ( false === $stored ? (object) [] : $stored );
		}

		$params = [
			'product_slug' => $this->slug,
			'locale'       => $locale,
			'site_url'     => is_multisite() ? network_site_url() : home_url(),
		];

		$this->maybe_add_auth_params( $params );

		// Send the request.
		$response = wp_remote_post(
			$this->api_url . '/pluginInfo/',
			[
				'timeout' => defined( 'DOING_CRON' ) && DOING_CRON ? 30 : 10,
				'body'    => $params,
			]
		);

		$this->requested['info'] = true;

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$response_body = wp_remote_retrieve_body( $response );

		// We do assoc=true and then cast to object to keep sub-items as array.
		$result = (object) json_decode( $response_body, true );

		if ( ! is_object( $result ) ) {
			return false;
		}

		set_site_transient( 'rank_math_pro_info_' . $locale, $result, 3600 );
		return $result;
	}

	/**
	 * Add username & api key if the site is connected.
	 *
	 * @param array $params Params passed by reference.
	 * @return void
	 */
	private function maybe_add_auth_params( &$params ) {
		$registered = Admin_Helper::get_registration_data();
		if ( $registered && isset( $registered['username'] ) && isset( $registered['api_key'] ) ) {
			$params['username'] = $registered['username'];
			$params['api_key']  = $registered['api_key'];
		}
	}

	/**
	 * Add additional text to notice if download is not available and account is connected.
	 *
	 * @param  array  $plugin_data An array of plugin metadata.
	 * @param  object $response    An array of metadata about the available plugin update.
	 * @return void
	 */
	public function in_plugin_update_message( $plugin_data, $response ) {
		if ( current_user_can( 'update_plugins' ) ) {
			if ( empty( $response->package ) && isset( $response->unavailability_reason ) ) {
				$message = $this->get_update_message( $response->unavailability_reason );
				echo ' <strong>' . wp_kses_post( $message ) . '</strong>';
			}
		}
	}

	/**
	 * Get unavailability reason message.
	 *
	 * @param string $reason  Unavailability reason ID, like 'not_connected'.
	 * @param mixed  $default Default text to return when specified ID has no message attached to it.
	 * @return string
	 */
	public function get_update_message( $reason = '', $default = null ) {
		if ( is_null( $default ) ) {
			$default = '';
		}

		$unavailability_reasons = [
			'update_free'    => __( 'Please update the free version before updating Rank Math SEO PRO.', 'rank-math-pro' ),
			'not_subscribed' => sprintf(
				/* translators: 1: Plugin name, 2: Pricing Link's opening HTML anchor tag, 3: Pricing Link's closing HTML anchor tag. */
				__( 'It seems that you don\'t have an active subscription for %1$s. Please see %2$sdetails and pricing%3$s.', 'rank-math-pro' ),
				'Rank Math SEO PRO',
				'<a href="https://rankmath.com/pricing/">',
				'</a>'
			),
			'not_connected'  => sprintf(
				/* translators: 1: Link's opening HTML anchor tag, 2: Link's closing HTML anchor tag. */
				__( 'Please %1$s connect Rank Math SEO PRO %2$s for automatic updates.', 'rank-math-pro' ),
				'<a href="' . Helper::get_connect_url() . '">',
				'</a>'
			),
		];

		$unavailability_reasons = $this->do_filter( 'updates/unavailability_reasons', $unavailability_reasons );

		if ( isset( $unavailability_reasons[ $reason ] ) ) {
			return $unavailability_reasons[ $reason ];
		}

		return $default;
	}

	/**
	 * Toggle auto updates option.
	 * Hooked to run when the option is changed in the free version.
	 *
	 * @param string $toggle New status.
	 * @return void
	 */
	public function toggle_auto_update( $toggle ) {
		$auto_updates = (array) get_site_option( 'auto_update_plugins', [] );
		if ( ! empty( $toggle ) && 'off' !== $toggle ) {
			$auto_updates[] = 'seo-by-rank-math-pro/rank-math-pro.php';
			update_site_option( 'auto_update_plugins', array_unique( $auto_updates ) );
			return;
		}

		update_site_option( 'auto_update_plugins', array_diff( $auto_updates, [ 'seo-by-rank-math-pro/rank-math-pro.php' ] ) );
	}

	/**
	 * Don't auto-update if it's a beta version.
	 *
	 * @param bool  $update Whether to update the plugin or not.
	 * @param array $item  The update plugin object.
	 *
	 * @return bool
	 */
	public function auto_update_plugin( $update, $item ) {
		// Show auto-updates control on Plugins page.
		if ( did_action( 'load-plugins.php' ) ) {
			return $update;
		}

		if ( $this->is_rm_pro_update( $item ) && $this->is_beta_update( $item ) ) {
			return false;
		}

		return $update;
	}

	/**
	 * Check if updatable object is RM.
	 *
	 * @param object $item Updatable object.
	 * @return boolean
	 */
	public function is_rm_pro_update( $item ) {
		return isset( $item->slug ) &&
			'seo-by-rank-math-pro' === $item->slug &&
			isset( $item->new_version );
	}

	/**
	 * Check if given version is beta.
	 *
	 * @param object $item Update data.
	 * @return boolean
	 */
	public function is_beta_update( $item ) {
		return ( is_object( $item ) && isset( $item->new_version ) && false !== stripos( $item->new_version, 'beta' ) );
	}

	/**
	 * Hide "update scheduled in X hours" message if update is a beta version because we don't auto-update those.
	 *
	 * @param string $html        HTML string.
	 * @param string $plugin_file Plugin file relative to the plugin directory.
	 * @param array  $plugin_data Plugin update data.
	 * @return string
	 */
	public function plugin_auto_update_setting_html( $html, $plugin_file, $plugin_data ) {
		if ( 'seo-by-rank-math-pro/rank-math-pro.php' !== $plugin_file ) {
			return $html;
		}

		if ( ! empty( $plugin_data['is_beta'] ) ) {
			$html = str_replace( 'class="auto-update-time"', 'class="auto-update-time hidden"', $html );
		}

		return $html;
	}

	/**
	 * Set should_send to true if Pro update is available, to send the notification email.
	 * Notification feature is only available if auto-updates are disabled.
	 *
	 * @param boolean $should_send Original should_send value.
	 * @param mixed   $transient   update_plugins site ransient value.
	 * @return boolean
	 */
	public function should_send_update_notification( $should_send, $transient ) {
		if ( $this->get_auto_update_setting() ) {
			return $should_send;
		}

		if ( ! $this->is_update_available( $transient ) ) {
			return $should_send;
		}

		$new_version = $transient->response['seo-by-rank-math-pro/rank-math-pro.php']->new_version;

		// Now let's check if we've already sent this email.
		$sent = get_option( 'rank_math_update_notifications_sent', [ 'pro' => [ 'new_version' => '1.0' ] ] );
		if ( ! isset( $sent['pro'] ) ) {
			$sent['pro'] = [ 'new_version' => '1.0' ];
		}

		if ( version_compare( $sent['pro']['new_version'], $new_version, '>=' ) ) {
			return $should_send;
		}

		return true;
	}

	/**
	 * Add Pro to notification product list if an update is available.
	 *
	 * @param array $products  Products data array.
	 * @param mixed $transient Transient value.
	 * @return array
	 */
	public function update_notification_products( $products, $transient ) {
		if ( $this->get_auto_update_setting() ) {
			return $products;
		}

		if ( ! $this->is_update_available( $transient ) ) {
			return $products;
		}

		$products['pro'] = [
			'name'        => __( 'Rank Math PRO', 'rank-math-pro' ),
			'old_version' => RANK_MATH_PRO_VERSION,
			'new_version' => $transient->response['seo-by-rank-math-pro/rank-math-pro.php']->new_version,
			'changelog'   => __( 'https://rankmath.com/changelog/', 'rank-math-pro' ),
		];

		return $products;
	}

	/**
	 * Check if update is available for the PRO version.
	 *
	 * @param  mixed $transient The update_plugins transient value.
	 * @return boolean
	 */
	public function is_update_available( $transient ) {
		if ( ! is_object( $transient )
			|| empty( $transient->response )
			|| empty( $transient->response['seo-by-rank-math-pro/rank-math-pro.php'] )
			|| empty( $transient->response['seo-by-rank-math-pro/rank-math-pro.php']->new_version )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Get Auto update setting status.
	 *
	 * @return bool
	 */
	public function get_auto_update_setting() {
		return in_array( 'seo-by-rank-math-pro/rank-math-pro.php', (array) get_site_option( 'auto_update_plugins', [] ), true );
	}

	/**
	 * Connect auto-update toggles: if we enable it for the Pro, then Free should be enabled too,
	 * and if we disable it for Free then it should be disabled for the Pro too.
	 *
	 * @param string $option     Option name.
	 * @param mixed  $value      Option value.
	 * @param mixed  $old_value  Previous option value before the change.
	 * @param int    $network_id Network ID.
	 * @return void
	 */
	public function connect_auto_update_toggles( $option, $value, $old_value, $network_id ) {
		$this->remove_action( 'update_site_option_auto_update_plugins', 'connect_auto_update_toggles', 20 );
		if ( ! is_array( $value ) ) {
			return;
		}

		$free_file = 'seo-by-rank-math/rank-math.php';
		$pro_file  = 'seo-by-rank-math-pro/rank-math-pro.php';

		// If we just enabled it for Rank Math SEO Pro.
		if ( in_array( $pro_file, $value, true ) && ! in_array( $pro_file, $old_value, true ) && ! in_array( $free_file, $value, true ) ) {
			$value[] = $free_file;
			update_site_option( $option, $value );
			return;
		}

		// If we just disabled it for Rank Math SEO Free.
		if ( ! in_array( $free_file, $value, true ) && in_array( $free_file, $old_value, true ) && in_array( $pro_file, $value, true ) ) {
			$value = array_diff( $value, [ $pro_file ] );
			update_site_option( $option, $value );
			return;
		}
	}
}
