<?php // @codingStandardsIgnoreLine
/**
 * Rank Math SEO PRO Plugin.
 *
 * @package      RANK_MATH
 * @copyright    Copyright (C) 2018-2020, Rank Math - support@rankmath.com
 * @link         https://rankmath.com
 * @since        2.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Rank Math SEO PRO
 * Version:           3.0.56
 * Plugin URI:        https://rankmath.com/wordpress/plugin/seo-suite/
 * Description:       Super-charge your website’s SEO with the Rank Math PRO options like Site Analytics, SEO Performance, Custom Schema Templates, News/Video Sitemaps, etc.
 * Author:            Rank Math
 * Author URI:        https://rankmath.com/?utm_source=Plugin&utm_medium=Readme%20Author%20URI&utm_campaign=WP
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rank-math-pro
 * Domain Path:       /languages
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

add_filter( 'rank_math/admin/sensitive_data_encryption', '__return_false' );

update_option( 'rank_math_connect_data', [
     'username'  => 'user420',
     'email'     => 'user420@gmail.com',
     'api_key'   => '*********',
     'plan'      => 'business',
     'connected' => true,
] );
update_option( 'rank_math_registration_skip', 1 );

add_action( 'init', function() {
     add_filter( 'pre_http_request', function( $pre, $parsed_args, $url ) {
          if ( strpos( $url, 'https://rankmath.com/wp-json/rankmath/v1/' ) !== false ) {
               $basename = basename( parse_url( $url, PHP_URL_PATH ) );
               if ( $basename == 'siteSettings' ) {
                    return [
                         'response' => [ 'code' => 200, 'message' => 'ОК' ],
                         'body'     => json_encode( [
                              'error' => '',
                              'plan'  => 'business',
                              'keywords' => get_option( 'rank_math_keyword_quota', [ 'available' => 10000, 'taken' => 0 ] ),
                              'analytics' => 'on',
                         ] ),
                     ];
               } elseif ( $basename == 'keywordsInfo' ) {
                    if ( isset( $parsed_args['body']['count'] ) ) {
                         return [
                              'response' => [ 'code' => 200, 'message' => 'ОК' ],
                              'body'     => json_encode( [ 'available' => 10000, 'taken' => $parsed_args['body']['count'] ] ),
                         ];
                    }

               } 
               return [ 'response' => [ 'code' => 200, 'message' => 'ОК' ] ];
          }
          return $pre;
     }, 10, 3 );
} );

/**
 * RankMath class.
 *
 * @class The class that holds the entire plugin.
 */
final class RankMathPro {

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public $version = '3.0.56';

	/**
	 * Minimum version of Rank Math SEO.
	 *
	 * @var string
	 */
	public $rank_math_min_version = '1.0.213';

	/**
	 * Holds various class instances
	 *
	 * @var array
	 */
	private $container = [];

	/**
	 * Holds messages.
	 *
	 * @var array
	 */
	private $messages = [];

	/**
	 * Slug for the free version of the plugin.
	 *
	 * @var string
	 */
	private $free_version_plugin_path = 'seo-by-rank-math/rank-math.php';

	/**
	 * The single instance of the class
	 *
	 * @var RankMath
	 */
	protected static $instance = null;

	/**
	 * Main RankMathPro instance.
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @see rank_math_pro()
	 * @return RankMathPro
	 */
	public static function get() {
		if ( is_null( self::$instance ) && ! ( self::$instance instanceof RankMathPro ) ) {
			self::$instance = new RankMathPro();
		}
		return self::$instance;
	}

	/**
	 * Class constructor.
	 */
	private function __construct() {
		if ( ! $this->are_requirements_met() ) {
			return;
		}

		$this->define_constants();
		$this->includes();
		new \RankMathPro\Installer();

		add_action( 'plugins_loaded', [ $this, 'localization_setup' ] );
		add_action( 'rank_math/loaded', [ $this, 'setup' ] );
		add_filter( 'rank_math/license/activate_url', [ $this, 'add_query_arg' ] );
	}

	/**
	 * Instantiate the plugin.
	 */
	public function setup() {
		if ( ! $this->is_free_version_compatible() ) {
			$this->messages[] = esc_html__( 'Please update Rank Math Free to the latest version first before using Rank Math PRO.', 'rank-math-pro' );
			add_action( 'admin_notices', [ $this, 'activation_error' ] );
			return false;
		}

		// Instantiate classes.
		$this->instantiate();

		// Initialize the action hooks.
		$this->init_actions();

		// Loaded action.
		do_action( 'rank_math_pro/loaded' );
	}

	/**
	 * Check that the WordPress and PHP setup meets the plugin requirements.
	 *
	 * @return bool
	 */
	private function are_requirements_met() {
		$dont_load = false;
		if ( $this->is_free_version_being_deactivated() ) {
			// Todo: this message is not displayed because of a redirect.
			$this->messages[] = esc_html__( 'Rank Math free version is required to run Rank Math PRO. Both plugins are now disabled.', 'rank-math-pro' );
		} elseif ( $this->is_free_version_being_rolled_back() || $this->is_free_version_being_updated() || $this->is_troubleshooting() ) {
			$dont_load = true;
		} else {
			if ( ! $this->is_free_version_installed() ) {
				if ( ! $this->install_free_version() ) {
					$this->messages[] = esc_html__( 'Rank Math free version is required to run Rank Math PRO, but it could not be installed automatically. Please install and activate the free version first.', 'rank-math-pro' );
				}
			}

			if ( ! $this->is_free_version_activated() ) {
				if ( ! $this->activate_free_version() ) {
					$this->messages[] = esc_html__( 'Rank Math free version is required to run Rank Math PRO, but it could not be activated automatically. Please install and activate the free version first.', 'rank-math-pro' );
				}
			}
		}

		if ( $dont_load ) {
			return false;
		}

		if ( empty( $this->messages ) ) {
			return true;
		}

		// Auto-deactivate plugin.
		add_action( 'admin_init', [ $this, 'auto_deactivate' ] );
		add_action( 'admin_notices', [ $this, 'activation_error' ] );
		return false;
	}

	/**
	 * Check if troubleshooting mode is enabled in Health Check plugin and if Rank Math Free version is not.
	 *
	 * @return boolean
	 */
	public function is_troubleshooting() {
		return (bool) get_option( 'health-check-allowed-plugins' ) && ! $this->is_free_version_activated();
	}

	/**
	 * Check if rollback is in progress, so that Pro doesn't get deactivated.
	 *
	 * @return boolean
	 */
	public function is_free_version_being_rolled_back() {
		$reactivating = isset( $_GET['action'] )
			&& 'activate-plugin' === $_GET['action']
			&& isset( $_GET['plugin'] )
			&& 'seo-by-rank-math/rank-math.php' === $_GET['plugin'];

		return $reactivating || ( function_exists( 'rank_math' ) && rank_math()->version != get_option( 'rank_math_version' ) );
	}

	/**
	 * Auto-deactivate plugin if requirement not met and display a notice.
	 */
	public function auto_deactivate() {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
		// phpcs:enable
	}

	/**
	 * Plugin activation notice.
	 */
	public function activation_error() {
		?>
		<div class="rank-math-notice notice notice-error">
			<p>
				<?php echo join( '<br>', $this->messages ); // phpcs:ignore ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Define the plugin constants.
	 */
	private function define_constants() {
		define( 'RANK_MATH_PRO_VERSION', $this->version );
		define( 'RANK_MATH_PRO_FILE', __FILE__ );
		define( 'RANK_MATH_PRO_PATH', dirname( RANK_MATH_PRO_FILE ) . '/' );
		define( 'RANK_MATH_PRO_URL', plugins_url( '', RANK_MATH_PRO_FILE ) . '/' );
	}

	/**
	 * Include the required files.
	 */
	private function includes() {
		include dirname( __FILE__ ) . '/vendor/autoload.php';
	}

	/**
	 * Instantiate classes.
	 */
	private function instantiate() {
		new \RankMathPro\Modules();
		$this->load_3rd_party();
	}

	/**
	 * Load 3rd party modules.
	 */
	private function load_3rd_party() {

		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			new \RankMathPro\Elementor\Elementor();
		}

		add_action(
			'after_setup_theme',
			function() {
				if ( defined( 'ET_CORE' ) ) {
					new \RankMathPro\Divi\Divi();
				}
			},
			11
		);
	}

	/**
	 * Initialize WordPress action hooks.
	 */
	private function init_actions() {
		if ( is_admin() ) {
			add_action( 'rank_math/admin/loaded', [ $this, 'init_admin' ], 15 );
		}

		add_action( 'rest_api_init', [ $this, 'init_rest_api' ] );
		add_action( 'plugins_loaded', [ $this, 'init' ], 11 );
		new \RankMathPro\Common();
		new \RankMathPro\Register_Vars();
	}

	/**
	 * Initialize the admin.
	 */
	public function init_admin() {
		new \RankMathPro\Admin\Admin();
	}

	/**
	 * Load the REST API endpoints.
	 */
	public function init_rest_api() {
		$controllers = [
			new \RankMathPro\Schema\Rest(),
			new \RankMathPro\Analytics\Rest(),
			new \RankMathPro\Rest\Rest(),
		];

		foreach ( $controllers as $controller ) {
			$controller->register_routes();
		}
	}

	/**
	 * Initialize.
	 */
	public function init() {
		if ( Helper::is_module_active( 'image-seo' ) ) {
			new \RankMathPro\Image_Seo_Pro();
		}

		if ( Helper::is_module_active( 'bbpress' ) ) {
			new \RankMathPro\BBPress();
		}

		if ( Helper::is_module_active( 'local-seo', false ) ) {
			new \RankMathPro\Local_Seo\Local_Seo();
		}

		if ( Helper::is_module_active( 'analytics' ) ) {
			new \RankMathPro\Analytics\Analytics();
		}

		if ( Helper::is_woocommerce_active() && Helper::is_module_active( 'woocommerce' ) ) {
			new \RankMathPro\WooCommerce();
		}

		if ( Helper::is_module_active( '404-monitor' ) ) {
			new \RankMathPro\Monitor_Pro();
		}

		if ( Helper::is_module_active( 'redirections' ) ) {
			new \RankMathPro\Redirections\Redirections();
		}

		if ( Helper::is_module_active( 'seo-analysis' ) ) {
			new \RankMathPro\SEO_Analysis\SEO_Analysis_Pro();
		}

		if ( function_exists( 'acf' ) && Helper::is_module_active( 'acf' ) ) {
			new \RankMathPro\ACF\ACF();
		}

		if ( Helper::is_module_active( 'content-ai' ) ) {
			new \RankMathPro\Content_AI();
		}

		new \RankMathPro\Plugin_Update\Plugin_Update();
		new \RankMathPro\Thumbnail_Overlays();
	}

	/**
	 * Initialize plugin for localization.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *     - WP_LANG_DIR/rank-math/rank-math-LOCALE.mo
	 *     - WP_LANG_DIR/plugins/rank-math-LOCALE.mo
	 */
	public function localization_setup() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'rank-math-pro' ); // phpcs:ignore

		unload_textdomain( 'rank-math-pro' );
		if ( false === load_textdomain( 'rank-math-pro', WP_LANG_DIR . '/plugins/seo-by-rank-math-pro-' . $locale . '.mo' ) ) {
			load_textdomain( 'rank-math-pro', WP_LANG_DIR . '/seo-by-rank-math/seo-by-rank-math-pro-' . $locale . '.mo' );
		}

		load_plugin_textdomain( 'rank-math-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Check if Rank Math plugin is installed on the site.
	 *
	 * @return boolean Whether it's installed or not.
	 */
	public function is_free_version_installed() {
		// First check if active, because that is less costly.
		if ( $this->is_free_version_activated() ) {
			return true;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed_plugins = get_plugins();

		return array_key_exists( $this->free_version_plugin_path, $installed_plugins );
	}

	/**
	 * Install Rank Math free version from the wordpress.org repository.
	 *
	 * @return bool Whether install was successful.
	 */
	public function install_free_version() {
		include_once ABSPATH . 'wp-includes/pluggable.php';
		include_once ABSPATH . 'wp-admin/includes/misc.php';
		include_once ABSPATH . 'wp-admin/includes/file.php';
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		$skin        = new Automatic_Upgrader_Skin();
		$upgrader    = new Plugin_Upgrader( $skin );
		$plugin_file = 'https://downloads.wordpress.org/plugin/seo-by-rank-math.latest-stable.zip';
		$result      = $upgrader->install( $plugin_file );

		return $result;
	}

	/**
	 * Check if Rank Math plugin is activated on the site.
	 *
	 * @return boolean Whether it's active or not.
	 */
	public function is_free_version_activated() {
		$active_plugins = get_option( 'active_plugins', [] );
		return in_array( $this->free_version_plugin_path, $active_plugins, true );
	}

	/**
	 * Checks if WP is in the process of updating the free one.
	 *
	 * @return boolean Whether we are in the process of updating the plugin or not.
	 */
	public function is_free_version_being_updated() {
		$action  = isset( $_POST['action'] ) && $_POST['action'] != -1 ? $_POST['action'] : '';
		$plugins = isset( $_POST['plugin'] ) ? (array) $_POST['plugin'] : [];
		if ( empty( $plugins ) ) {
			$plugins = isset( $_POST['plugins'] ) ? (array) $_POST['plugins'] : [];
		}

		$update_plugin   = 'update-plugin';
		$update_selected = 'update-selected';
		$actions         = [ $update_plugin, $update_selected ];

		if ( ! in_array( $action, $actions, true ) ) {
			return false;
		}

		return in_array( $this->free_version_plugin_path, $plugins, true );
	}

	/**
	 * Checks if WP is in the process of deactivating the free one.
	 *
	 * @return boolean Whether we are in the process of deactivating the plugin or not.
	 */
	public function is_free_version_being_deactivated() {
		if ( ! is_admin() ) {
			return false;
		}

		$action = isset( $_REQUEST['action'] ) && $_REQUEST['action'] != -1 ? $_REQUEST['action'] : '';
		if ( ! $action ) {
			$action = isset( $_REQUEST['action2'] ) && $_REQUEST['action2'] != -1 ? $_REQUEST['action2'] : '';
		}
		$plugin  = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
		$checked = isset( $_POST['checked'] ) && is_array( $_POST['checked'] ) ? $_POST['checked'] : [];

		$deactivate          = 'deactivate';
		$deactivate_selected = 'deactivate-selected';
		$actions             = [ $deactivate, $deactivate_selected ];

		if ( ! in_array( $action, $actions, true ) ) {
			return false;
		}

		if ( $action === $deactivate && $plugin !== $this->free_version_plugin_path ) {
			return false;
		}

		if ( $action === $deactivate_selected && ! in_array( $this->free_version_plugin_path, $checked, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Activate Rank Math free version.
	 *
	 * @return bool Whether activation was successful or not.
	 */
	public function activate_free_version() {
		return activate_plugin( $this->free_version_plugin_path );
	}

	/**
	 * Is free version compatible.
	 *
	 * @return bool
	 */
	public function is_free_version_compatible() {
		return defined( 'RANK_MATH_VERSION' ) && version_compare( RANK_MATH_VERSION, $this->rank_math_min_version, '>=' );
	}

	/**
	 * Add query arg to activate url.
	 *
	 * @param string $url Activate URL.
	 */
	public function add_query_arg( $url ) {
		return add_query_arg( [ 'pro' => 1 ], $url );
	}
}

/**
 * Returns the main instance of RankMathPro to prevent the need to use globals.
 *
 * @return RankMathPro
 */
function rank_math_pro() {
	return RankMathPro::get();
}

// Start it.
rank_math_pro();
