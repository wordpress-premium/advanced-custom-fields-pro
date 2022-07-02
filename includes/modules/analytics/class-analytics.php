<?php
/**
 * Analytics module.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Analytics\Stats;
use MyThemeShop\Helpers\Param;
use MyThemeShop\Helpers\DB as DB_Helper;


// Analytics.
use RankMathPro\Google\Adsense;
use RankMath\Google\Permissions;
use RankMath\Google\Authentication;
use RankMath\Admin\Admin_Helper;
use RankMathPro\Analytics\Workflow\Jobs;
use RankMathPro\Analytics\Workflow\Workflow;
use RankMathPro\Admin\Admin_Helper as ProAdminHelper;
use RankMathPro\Analytics\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics class.
 */
class Analytics {

	use Hooker;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->action( 'rank_math/admin/enqueue_scripts', 'enqueue_analytics' );
		$this->action( 'rank_math/analytics/options/console', 'add_country_dropdown3' );
		$this->action( 'rank_math/analytics/options/analytics', 'add_country_dropdown2' );
		$this->action( 'update_option_rank_math_analytics_last_updated', 'send_summary' );
		$this->action( 'rank_math/admin/settings/analytics', 'add_new_settings' );
		$this->filter( 'rank_math/analytics/schedule_gap', 'schedule_gap' );
		$this->filter( 'rank_math/analytics/fetch_gap', 'fetch_gap' );
		$this->filter( 'rank_math/analytics/max_days_allowed', 'data_retention_period' );
		$this->filter( 'rank_math/analytics/options/cache_control/description', 'change_description' );
		$this->filter( 'rank_math/analytics/check_all_services', 'check_all_services' );
		$this->filter( 'rank_math/analytics/user_preference', 'change_user_preference' );
		$this->action( 'template_redirect', 'local_js_endpoint' );
		$this->filter( 'rank_math/analytics/gtag_config', 'gtag_config' );
		$this->filter( 'rank_math/status/rank_math_info', 'google_permission_info' );
		$this->filter( 'rank_math/analytics/gtag', 'gtag' );
		$this->filter( 'rank_math/analytics/pre_filter_data', 'filter_winning_losing_posts', 10, 3 );
		$this->filter( 'rank_math/analytics/pre_filter_data', 'filter_winning_keywords', 10, 3 );
		$this->action( 'cmb2_save_options-page_fields_rank-math-options-general_options', 'sync_global_settings', 25, 2 );
		$this->filter( 'rank_math/metabox/post/values', 'add_metadata', 10, 2 );
		$this->filter( 'rank_math/analytics/date_exists_tables', 'date_exists_tables', 10 );

		if ( Helper::has_cap( 'analytics' ) ) {
			$this->action( 'rank_math/admin_bar/items', 'admin_bar_items', 11 );
			$this->action( 'rank_math_seo_details', 'post_column_search_traffic' );
		}

		if ( Helper::can_add_frontend_stats() ) {
			$this->action( 'wp_enqueue_scripts', 'enqueue' );
		}

		Posts::get();
		Keywords::get();
		Jobs::get();
		Workflow::get();
		new Pageviews();
		new Summary();
		new Ajax();
		new Email_Reports();
		new Url_Inspection();
	}

	/**
	 * Enqueue Frontend stats script.
	 */
	public function enqueue() {
		if ( ! is_singular() || is_admin() || is_preview() || Helper::is_divi_frontend_editor() ) {
			return;
		}

		$uri = untrailingslashit( plugin_dir_url( __FILE__ ) );
		wp_enqueue_style( 'rank-math-analytics-pro-stats', $uri . '/assets/css/admin-bar.css', [ 'rank-math-analytics-stats' ], rank_math_pro()->version );
		wp_enqueue_script( 'rank-math-analytics-pro-stats', $uri . '/assets/js/admin-bar.js', [ 'rank-math-analytics-stats' ], rank_math_pro()->version, true );

		Helper::add_json( 'dateFormat', get_option( 'date_format' ) );
	}

	/**
	 * Add localized data to use in the Post Editor.
	 *
	 * @param array $values Aray of localized data.
	 *
	 * @return array
	 */
	public function add_metadata( $values ) {
		$values['isAnalyticsConnected'] = \RankMath\Google\Analytics::is_analytics_connected();

		return $values;
	}

	/**
	 * Change user perference.
	 *
	 * @param  array $preference Array of preference.
	 * @return array
	 */
	public function change_user_preference( $preference ) {
		Helper::add_json( 'isAdsenseConnected', ! empty( Adsense::get_adsense_id() ) );
		Helper::add_json( 'isLinkModuleActive', Helper::is_module_active( 'link-counter' ) );
		Helper::add_json( 'isSchemaModuleActive', Helper::is_module_active( 'rich-snippet' ) );
		Helper::add_json( 'isAnalyticsConnected', \RankMath\Google\Analytics::is_analytics_connected() );
		Helper::add_json( 'dateFormat', get_option( 'date_format' ) );

		$preference['topKeywords']['ctr']    = false;
		$preference['topKeywords']['ctr']    = false;
		$preference['performance']['clicks'] = false;

		return $preference;
	}

	/**
	 * Data rentention days.
	 *
	 * @return int
	 */
	public function data_retention_period() {
		return 'pro' === Admin_Helper::get_user_plan() ? 180 : 1000;
	}

	/**
	 * Data retrival job gap in seconds.
	 *
	 * @return int
	 */
	public function schedule_gap() {
		return 10;
	}

	/**
	 * Data retrival fetch gap in days.
	 *
	 * @return int
	 */
	public function fetch_gap() {
		return 3;
	}

	/**
	 * Fetch adsense account.
	 *
	 * @param  array $result Result array.
	 * @return array
	 */
	public function check_all_services( $result ) {
		$result['adsenseAccounts'] = Adsense::get_adsense_accounts();

		return $result;
	}

	/**
	 * Add admin bar item.
	 *
	 * @param Admin_Bar_Menu $menu Menu class instance.
	 */
	public function admin_bar_items( $menu ) {
		$post_types = Helper::get_accessible_post_types();
		unset( $post_types['attachment'] );

		if ( is_singular( $post_types ) && Helper::is_post_indexable( get_the_ID() ) ) {
			$menu->add_sub_menu(
				'post_analytics',
				[
					'title'    => esc_html__( 'Post Analytics', 'rank-math-pro' ),
					'href'     => Helper::get_admin_url( 'analytics#/single/' . get_the_ID() ),
					'meta'     => [ 'title' => esc_html__( 'Analytics Report', 'rank-math-pro' ) ],
					'priority' => 20,
				]
			);
		}
	}

	/**
	 * Enqueue scripts for the metabox.
	 */
	public function enqueue_analytics() {
		$screen = get_current_screen();
		if ( 'rank-math_page_rank-math-analytics' !== $screen->id ) {
			return;
		}

		$url = RANK_MATH_PRO_URL . 'includes/modules/analytics/assets/';
		wp_enqueue_style(
			'rank-math-pro-analytics',
			$url . 'css/stats.css',
			null,
			rank_math_pro()->version
		);

		wp_enqueue_script(
			'rank-math-pro-analytics',
			$url . 'js/stats.js',
			[
				'wp-components',
				'wp-element',
				'wp-i18n',
				'wp-date',
				'wp-html-entities',
				'wp-api-fetch',
				'rank-math-analytics',
			],
			rank_math_pro()->version,
			true
		);
	}

	/**
	 * Add country dropdown.
	 */
	public function add_country_dropdown3() {
		$profile = wp_parse_args(
			get_option( 'rank_math_google_analytic_profile' ),
			[
				'profile' => '',
				'country' => 'all',
			]
		);
		?>
		<div class="cmb-row-col">
			<label for="site-console-country"><?php esc_html_e( 'Country', 'rank-math-pro' ); ?></label>
			<select class="cmb2_select site-console-country notrack" name="site-console-country" id="site-console-country" disabled="disabled">
				<?php foreach ( Helper::choices_countries_3() as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"<?php selected( $profile['country'], $code ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Add country dropdown.
	 */
	public function add_country_dropdown2() {
		$analytics = $this->get_settings();
		?>
		<div class="cmb-row-col country-option">
			<label for="site-analytics-country"><?php esc_html_e( 'Country', 'rank-math-pro' ); ?></label>
			<select class="cmb2_select site-analytics-country notrack" name="site-analytics-country" id="site-analytics-country" disabled="disabled">
				<?php foreach ( Helper::choices_countries() as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>"<?php selected( $analytics['country'], $code ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * Get Analytics settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return wp_parse_args(
			get_option( 'rank_math_google_analytic_options' ),
			[
				'adsense_id'       => '',
				'account_id'       => '',
				'property_id'      => '',
				'view_id'          => '',
				'country'          => 'all',
				'install_code'     => false,
				'anonymize_ip'     => false,
				'local_ga_js'      => false,
				'exclude_loggedin' => false,
			]
		);
	}

	/**
	 * Send analytics summary to RankMath.com.
	 */
	public function send_summary() {
		if ( ! Helper::get_settings( 'general.sync_global_setting' ) ) {
			return;
		}

		$registered = Admin_Helper::get_registration_data();
		if ( $registered && isset( $registered['username'] ) && isset( $registered['api_key'] ) ) {
			Stats::get()->set_date_range( '-30 days' );
			$stats = Stats::get()->get_analytics_summary();
			\RankMathPro\Admin\Api::get()->send_summary(
				[
					'username'    => $registered['username'],
					'api_key'     => $registered['api_key'],
					'site_url'    => esc_url( home_url() ),
					'impressions' => array_values( $stats['impressions'] ),
					'clicks'      => array_values( $stats['clicks'] ),
					'keywords'    => array_values( $stats['keywords'] ),
					'pageviews'   => isset( $stats['pageviews'] ) && is_array( $stats['pageviews'] ) ? array_values( $stats['pageviews'] ) : [],
					'adsense'     => isset( $stats['adsense'] ) && is_array( $stats['adsense'] ) ? array_values( $stats['adsense'] ) : [],
				]
			);
		}
	}

	/**
	 * Change option description.
	 */
	public function change_description() {
		return __( 'Enter the number of days to keep Analytics data in your database. The maximum allowed days are 180. Though, 2x data will be stored in the DB for calculating the difference properly.', 'rank-math-pro' );
	}

	/**
	 * Add new settings.
	 *
	 * @param object $cmb CMB2 instance.
	 */
	public function add_new_settings( $cmb ) {
		if ( ! Authentication::is_authorized() ) {
			return;
		}

		$type            = ! ProAdminHelper::is_business_plan() ? 'hidden' : 'toggle';
		$field_ids       = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$fields_position = array_search( 'console_caching_control', array_keys( $field_ids ), true ) + 1;

		$cmb->add_field(
			[
				'id'      => 'sync_global_setting',
				'type'    => $type,
				'name'    => esc_html__( 'Monitor SEO Performance', 'rank-math-pro' ),
				'desc'    => sprintf(
					/* translators: Link to kb article */
					wp_kses_post( __( 'This option allows you to monitor the SEO performance of all of your sites in one centralized dashboard on RankMath.com, so you can check up on sites at a glance. <a href="%1$s" target="_blank">Learn more</a>.', 'rank-math-pro' ) ),
					'https://rankmath.com/kb/analytics/'
				),
				'default' => 'off',
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'      => 'google_updates',
				'type'    => $type,
				'name'    => esc_html__( 'Google Core Updates in the Graphs', 'rank-math-pro' ),
				'desc'    => sprintf(
					/* translators: Link to kb article */
					__( 'This option allows you to show %s in the Analytics graphs.', 'rank-math-pro' ),
					'<a href="https://rankmath.com/google-updates/" target="_blank">' . __( 'Google Core Updates', 'rank-math-pro' ) . '</a>'
				),
				'default' => 'on',
			],
			++$fields_position
		);
	}

	/**
	 * Check if certain fields got updated.
	 *
	 * @param int   $object_id The ID of the current object.
	 * @param array $updated   Array of field ids that were updated.
	 *                         Will only include field ids that had values change.
	 */
	public function sync_global_settings( $object_id, $updated ) {
		if ( in_array( 'sync_global_setting', $updated, true ) ) {
			\RankMathPro\Admin\Api::get()->sync_setting(
				cmb2_get_option( $object_id, 'sync_global_setting' )
			);

			$this->send_summary();
		}
	}

	/**
	 * Get local Analytics JS URL if the option is turned on.
	 *
	 * @return mixed
	 */
	public function get_local_gtag_js_url() {
		$settings      = $this->get_settings();
		$validator_key = 'rank_math_local_ga_js_validator_' . md5( $settings['property_id'] );
		$validator     = get_transient( $validator_key );
		if ( ! is_string( $validator ) || empty( $validator ) ) {
			$validator = '1';
		}
		return add_query_arg( 'local_ga_js', $validator, trailingslashit( home_url() ) );
	}

	/**
	 * Serve Analytics JS from local cache if the option is turned on.
	 *
	 * @return void
	 */
	public function local_js_endpoint() {
		if ( Param::get( 'local_ga_js' ) && $this->get_settings()['local_ga_js'] && $this->get_local_ga_js_contents() ) {
			header( 'Content-Type: application/javascript' );
			header( 'Cache-Control: max-age=604800, public' );
			echo $this->get_local_ga_js_contents(); // phpcs:ignore
			exit;
		}
	}

	/**
	 * Get local cache of GA JS file contents or fetch new data.
	 *
	 * @param boolean $force_update Force update transient now.
	 * @return string
	 */
	public function get_local_ga_js_contents( $force_update = false ) {
		$settings      = $this->get_settings();
		$cache_key     = 'rank_math_local_ga_js_' . md5( $settings['property_id'] );
		$validator_key = 'rank_math_local_ga_js_validator_' . md5( $settings['property_id'] );
		$validator     = md5( $cache_key . time() );
		$stored        = get_transient( $cache_key );
		if ( false !== $stored && ! $force_update ) {
			return $stored;
		}

		$response = wp_remote_get( 'https://www.googletagmanager.com/gtag/js?id=' . $settings['property_id'] );

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $cache_key, '', 12 * HOUR_IN_SECONDS );
			return '';
		}

		$contents = wp_remote_retrieve_body( $response );
		set_transient( $cache_key, $contents, 12 * HOUR_IN_SECONDS );
		set_transient( $validator_key, $validator, 12 * HOUR_IN_SECONDS );

		return $contents;
	}

	/**
	 * Filter gtag.js config array.
	 *
	 * @param array $config Config parameters.
	 * @return array
	 */
	public function gtag_config( $config ) {
		$settings = $this->get_settings();

		if ( ! empty( $settings['anonymize_ip'] ) ) {
			$config[] = "'anonymize_ip': true";
		}

		return $config;
	}

	/**
	 * Filter function to add Google permissions used in Pro.
	 *
	 * @param array $data Array of System status data.
	 */
	public function google_permission_info( $data ) {
		$data['fields']['permissions']['value'] = array_merge(
			$data['fields']['permissions']['value'],
			[
				esc_html__( 'AdSense', 'rank-math-pro' )   => Permissions::get_status_text( Permissions::has_adsense() ),
				esc_html__( 'Analytics', 'rank-math-pro' ) => Permissions::get_status_text( Permissions::has_analytics() ),
			]
		);

		ksort( $data['fields']['permissions']['value'] );
		return $data;
	}

	/**
	 * Filter inline JS & URL for gtag.js.
	 *
	 * @param array $gtag_data Array containing URL & inline code for the gtag script.
	 * @return array
	 */
	public function gtag( $gtag_data ) {
		if ( is_admin() ) {
			return $gtag_data;
		}

		$settings = $this->get_settings();
		if ( empty( $settings['install_code'] ) ) {
			return $gtag_data;
		}

		if ( ! empty( $settings['local_ga_js'] ) ) {
			$gtag_data['url'] = $this->get_local_gtag_js_url();
		}

		return $gtag_data;
	}

	/**
	 * Filter winning and losing posts if needed.
	 *
	 * @param null  $null Null.
	 * @param array $data Analytics data array.
	 * @param array $args Query arguments.
	 *
	 * @return mixed
	 */
	public function filter_winning_losing_posts( $null, $data, $args ) {
		$order_by_field = $args['orderBy'];
		$type           = $args['type'];
		$objects        = $args['objects'];

		if ( ! in_array( $type, [ 'win', 'lose' ], true ) ) {
			return $null;
		}

		// Filter array by $type value.
		$order_by_position = in_array( $order_by_field, [ 'diffPosition', 'position' ], true ) ? true : false;
		if ( ( 'win' === $type && $order_by_position ) || ( 'lose' === $type && ! $order_by_position ) ) {
			$data = array_filter(
				$data,
				function( $row ) use ( $order_by_field, $objects ) {
					if ( $objects ) {
						// Show Winning posts if difference is 80 or less.
						return $row[ $order_by_field ] < 0 && $row[ $order_by_field ] > -80;
					}

					return $row[ $order_by_field ] < 0;
				}
			);
		} elseif ( ( 'lose' === $type && $order_by_position ) || ( 'win' === $type && ! $order_by_position ) ) {
			$data = array_filter(
				$data,
				function( $row ) use ( $order_by_field ) {
					return $row[ $order_by_field ] > 0;
				}
			);
		}

		$data = $this->finalize_filtered_data( $data, $args );

		return $data;
	}

	/**
	 * Filter winning keywords if needed.
	 *
	 * @param null  $null Null.
	 * @param array $data Analytics data array.
	 * @param array $args Query arguments.
	 *
	 * @return mixed
	 */
	public function filter_winning_keywords( $null, $data, $args ) {
		$order_by_field = $args['orderBy'];
		$dimension      = $args['dimension'];

		if ( 'query' !== $dimension || 'diffPosition' !== $order_by_field || 'ASC' !== $args['order'] ) {
			return $null;
		}

		// Filter array by $type value.
		$data = array_filter(
			$data,
			function( $row ) use ( $order_by_field ) {
				return $row[ $order_by_field ] < 0 && $row[ $order_by_field ] > -80;
			}
		);

		$data = $this->finalize_filtered_data( $data, $args );

		return $data;
	}

	/**
	 * Sort & limit keywords according to the args.
	 *
	 * @param array $data Data rows.
	 * @param array $args Query args.
	 *
	 * @return array
	 */
	private function finalize_filtered_data( $data, $args ) {
		if ( ! empty( $args['order'] ) ) {
			$sort_base_arr = array_column( $data, $args['orderBy'], $args['dimension'] );
			array_multisort( $sort_base_arr, 'ASC' === $args['order'] ? SORT_ASC : SORT_DESC, $data );
		}

		$data = array_slice( $data, $args['offset'], $args['perpage'], true );

		return $data;
	}

	/**
	 * Add search traffic value in seo detail of post lists
	 *
	 * @param int $post_id object Id.
	 */
	public function post_column_search_traffic( $post_id ) {
		if ( ! Authentication::is_authorized() ) {
			return;
		}

		$analytics           = get_option( 'rank_math_google_analytic_options' );
		$analytics_connected = ! empty( $analytics ) && ! empty( $analytics['view_id'] );

		static $traffic_data;
		if ( null === $traffic_data ) {
			$post_ids = $this->get_queried_post_ids();
			if ( empty( $post_ids ) ) {
				$traffic_data = [];
				return;
			}

			$traffic_data = $analytics_connected ? Pageviews::get_traffic_by_object_ids( $post_ids ) : Pageviews::get_impressions_by_object_ids( $post_ids );
		}

		if ( ! isset( $traffic_data[ $post_id ] ) ) {
			return;
		}
		?>
		<span class="rank-math-column-display rank-math-search-traffic">
			<strong>
				<?php
					$analytics_connected
						? esc_html_e( 'Search Traffic:', 'rank-math-pro' )
						: esc_html_e( 'Search Impression:', 'rank-math-pro' );
				?>
			</strong>
			<?php echo esc_html( number_format( $traffic_data[ $post_id ] ) ); ?>
		</span>
		<?php
	}

	/**
	 * Extend the date_exists() function to include the additional tables.
	 *
	 * @param  string $tables Tables.
	 * @return string
	 */
	public function date_exists_tables( $tables ) {
		$tables['analytics'] = DB_Helper::check_table_exists( 'rank_math_analytics_ga' ) ? 'rank_math_analytics_ga' : '';
		$tables['adsense']   = DB_Helper::check_table_exists( 'rank_math_analytics_adsense' ) ? 'rank_math_analytics_adsense' : '';

		return $tables;
	}

	/**
	 * Get queried post ids.
	 */
	private function get_queried_post_ids() {
		global $wp_query;
		if ( empty( $wp_query->posts ) ) {
			return false;
		}

		$post_ids = array_filter(
			array_map(
				function( $post ) {
					return isset( $post->ID ) ? $post->ID : '';
				},
				$wp_query->posts
			)
		);

		return $post_ids;
	}
}
