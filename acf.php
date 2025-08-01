<?php
/**
 * Advanced Custom Fields PRO
 *
 * @package ACF
 * @author  WP Engine
 *
 * @wordpress-plugin
 * Plugin Name:       Advanced Custom Fields PRO
 * Plugin URI:        https://www.advancedcustomfields.com
 * Description:       Customize WordPress with powerful, professional and intuitive fields.
 * Version:           6.4.3
 * Author:            WP Engine
 * Author URI:        https://wpengine.com/?utm_source=wordpress.org&utm_medium=referral&utm_campaign=plugin_directory&utm_content=advanced_custom_fields
 * Update URI:        https://www.advancedcustomfields.com/pro
 * Text Domain:       acf
 * Domain Path:       /lang
 * Requires PHP:      7.4
 * Requires at least: 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter('pre_http_request', function($preempt, $parsed_args, $url) {
    // Intercept ACF activation request
    if (strpos($url, 'https://connect.advancedcustomfields.com/v2/plugins/activate?p=pro') !== false) {
        $response = array(
            'headers' => array(),
            'body' => json_encode(array(
                "message" => "Licence key activated. Updates are now enabled",
                "license" => "WEADOWN000000005603B1EBE59708542",
                "license_status" => array(
                    "status" => "active",
                    "lifetime" => true,
                    "name" => "Agency",
                    "view_licenses_url" => "https://www.advancedcustomfields.com/my-account/view-licenses/"
                ),
                "status" => 1
            )),
            'response' => array(
                'code' => 200,
                'message' => 'OK'
            )
        );
        return $response;
    }

    // Intercept ACF validation request
    if (strpos($url, 'https://connect.advancedcustomfields.com/v2/plugins/validate?p=pro') !== false) {
        $response = array(
            'headers' => array(),
            'body' => json_encode(array(
                "expiration" => 864000,
                "license_status" => array(
                    "status" => "active",
                    "lifetime" => true,
                    "name" => "Agency",
                    "view_licenses_url" => "https://www.advancedcustomfields.com/my-account/view-licenses/"
                ),
                "status" => 1
            )),
            'response' => array(
                'code' => 200,
                'message' => 'OK'
            )
        );
        return $response;
    }

    // Intercept ACF get-info request
    if (strpos($url, 'https://connect.advancedcustomfields.com/v2/plugins/get-info?p=pro') !== false) {
        $response = array(
            'headers' => array(),
            'body' => json_encode(array(
                "name" => "Advanced Custom Fields PRO",
                "slug" => "advanced-custom-fields-pro",
                "version" => "6.x.x"
            )),
            'response' => array(
                'code' => 200,
                'message' => 'OK'
            )
        );
        return $response;
    }

    // Proceed with the original request if the URL doesn't match
    return $preempt;
}, 10, 3);


if ( ! class_exists( 'ACF' ) ) {

	/**
	 * The main ACF class
	 */
	#[AllowDynamicProperties]
	class ACF {

		/**
		 * The plugin version number.
		 *
		 * @var string
		 */
		public $version = '6.4.3';

		/**
		 * The plugin settings array.
		 *
		 * @var array
		 */
		public $settings = array();

		/**
		 * The plugin data array.
		 *
		 * @var array
		 */
		public $data = array();

		/**
		 * Storage for class instances.
		 *
		 * @var array
		 */
		public $instances = array();

		/**
		 * A dummy constructor to ensure ACF is only setup once.
		 *
		 * @date    23/06/12
		 * @since   5.0.0
		 */
		public function __construct() {
			// Do nothing.
		}

		/**
		 * Sets up the ACF plugin.
		 *
		 * @date    28/09/13
		 * @since   5.0.0
		 */
		public function initialize() {

			// Define constants.
			$this->define( 'ACF', true );
			$this->define( 'ACF_PATH', plugin_dir_path( __FILE__ ) );
			$this->define( 'ACF_BASENAME', plugin_basename( __FILE__ ) );
			$this->define( 'ACF_VERSION', $this->version );
			$this->define( 'ACF_MAJOR_VERSION', 6 );
			$this->define( 'ACF_FIELD_API_VERSION', 5 );
			$this->define( 'ACF_UPGRADE_VERSION', '5.5.0' ); // Highest version with an upgrade routine. See upgrades.php.

			// Register activation hook.
			register_activation_hook( __FILE__, array( $this, 'acf_plugin_activated' ) );

			// Define settings.
			$this->settings = array(
				'name'                    => 'Advanced Custom Fields',
				'slug'                    => dirname( ACF_BASENAME ),
				'version'                 => ACF_VERSION,
				'basename'                => ACF_BASENAME,
				'path'                    => ACF_PATH,
				'file'                    => __FILE__,
				'url'                     => plugin_dir_url( __FILE__ ),
				'show_admin'              => true,
				'show_updates'            => true,
				'enable_post_types'       => true,
				'enable_options_pages_ui' => true,
				'stripslashes'            => false,
				'local'                   => true,
				'json'                    => true,
				'save_json'               => '',
				'load_json'               => array(),
				'default_language'        => '',
				'current_language'        => '',
				'capability'              => 'manage_options',
				'uploader'                => 'wp',
				'autoload'                => false,
				'l10n'                    => true,
				'l10n_textdomain'         => '',
				'google_api_key'          => '',
				'google_api_client'       => '',
				'enqueue_google_maps'     => true,
				'enqueue_select2'         => true,
				'enqueue_datepicker'      => true,
				'enqueue_datetimepicker'  => true,
				'select2_version'         => 4,
				'row_index_offset'        => 1,
				'remove_wp_meta_box'      => true,
				'rest_api_enabled'        => true,
				'rest_api_format'         => 'light',
				'rest_api_embed_links'    => true,
				'preload_blocks'          => true,
				'enable_shortcode'        => true,
				'enable_bidirection'      => true,
				'enable_block_bindings'   => true,
				'enable_meta_box_cb_edit' => true,
			);

			// Include autoloader.
			include_once __DIR__ . '/vendor/autoload.php';

			// Include utility functions.
			include_once ACF_PATH . 'includes/acf-utility-functions.php';

			// Include previous API functions.
			acf_include( 'includes/api/api-helpers.php' );
			acf_include( 'includes/api/api-template.php' );
			acf_include( 'includes/api/api-term.php' );

			// Include classes.
			acf_include( 'includes/class-acf-data.php' );
			acf_include( 'includes/class-acf-internal-post-type.php' );
			acf_include( 'includes/fields/class-acf-field.php' );
			acf_include( 'includes/locations/abstract-acf-legacy-location.php' );
			acf_include( 'includes/locations/abstract-acf-location.php' );

			// Initialise autoloaded classes.
			new ACF\Site_Health\Site_Health();

			// Include functions.
			acf_include( 'includes/acf-helper-functions.php' );

			acf_new_instance( 'ACF\Meta\Comment' );
			acf_new_instance( 'ACF\Meta\Post' );
			acf_new_instance( 'ACF\Meta\Term' );
			acf_new_instance( 'ACF\Meta\User' );
			acf_new_instance( 'ACF\Meta\Option' );

			acf_include( 'includes/acf-hook-functions.php' );
			acf_include( 'includes/acf-field-functions.php' );
			acf_include( 'includes/acf-bidirectional-functions.php' );
			acf_include( 'includes/acf-internal-post-type-functions.php' );
			acf_include( 'includes/acf-post-type-functions.php' );
			acf_include( 'includes/acf-taxonomy-functions.php' );
			acf_include( 'includes/acf-field-group-functions.php' );
			acf_include( 'includes/acf-form-functions.php' );
			acf_include( 'includes/acf-meta-functions.php' );
			acf_include( 'includes/acf-post-functions.php' );
			acf_include( 'includes/acf-user-functions.php' );
			acf_include( 'includes/acf-value-functions.php' );
			acf_include( 'includes/acf-input-functions.php' );
			acf_include( 'includes/acf-wp-functions.php' );

			// Override the shortcode default value based on the version when installed.
			$first_activated_version = acf_get_version_when_first_activated();

			// Only enable shortcode by default for versions prior to 6.3
			if ( $first_activated_version && version_compare( $first_activated_version, '6.3', '>=' ) ) {
				$this->settings['enable_shortcode'] = false;
			}

			// Include core.
			acf_include( 'includes/fields.php' );
			acf_include( 'includes/locations.php' );
			acf_include( 'includes/assets.php' );
			acf_include( 'includes/compatibility.php' );
			acf_include( 'includes/deprecated.php' );
			acf_include( 'includes/l10n.php' );
			acf_include( 'includes/local-fields.php' );
			acf_include( 'includes/local-meta.php' );
			acf_include( 'includes/local-json.php' );
			acf_include( 'includes/loop.php' );
			acf_include( 'includes/media.php' );
			acf_include( 'includes/revisions.php' );
			acf_include( 'includes/upgrades.php' );
			acf_include( 'includes/validation.php' );
			acf_include( 'includes/rest-api.php' );

			// Include field group class.
			acf_include( 'includes/post-types/class-acf-field-group.php' );

			// Include ajax.
			acf_include( 'includes/ajax/class-acf-ajax.php' );
			acf_include( 'includes/ajax/class-acf-ajax-check-screen.php' );
			acf_include( 'includes/ajax/class-acf-ajax-user-setting.php' );
			acf_include( 'includes/ajax/class-acf-ajax-upgrade.php' );
			acf_include( 'includes/ajax/class-acf-ajax-query.php' );
			acf_include( 'includes/ajax/class-acf-ajax-query-users.php' );
			acf_include( 'includes/ajax/class-acf-ajax-local-json-diff.php' );

			// Include forms.
			acf_include( 'includes/forms/form-attachment.php' );
			acf_include( 'includes/forms/form-comment.php' );
			acf_include( 'includes/forms/form-customizer.php' );
			acf_include( 'includes/forms/form-front.php' );
			acf_include( 'includes/forms/form-nav-menu.php' );
			acf_include( 'includes/forms/form-post.php' );
			acf_include( 'includes/forms/form-gutenberg.php' );
			acf_include( 'includes/forms/form-taxonomy.php' );
			acf_include( 'includes/forms/form-user.php' );
			acf_include( 'includes/forms/form-widget.php' );

			// Include admin.
			if ( is_admin() ) {
				acf_include( 'includes/admin/admin.php' );
				acf_include( 'includes/admin/admin-internal-post-type-list.php' );
				acf_include( 'includes/admin/admin-internal-post-type.php' );
				acf_include( 'includes/admin/admin-notices.php' );
				acf_include( 'includes/admin/admin-tools.php' );
				acf_include( 'includes/admin/admin-upgrade.php' );
			}

			// Include legacy.
			acf_include( 'includes/legacy/legacy-locations.php' );

			// Include updater if included with this build.
			acf_include( 'includes/Updater/init.php' );

			// Include PRO if included with this build.
			if ( ! defined( 'ACF_PREVENT_PRO_LOAD' ) || ( defined( 'ACF_PREVENT_PRO_LOAD' ) && ! ACF_PREVENT_PRO_LOAD ) ) {
				acf_include( 'pro/acf-pro.php' );
			}

			if ( is_admin() && function_exists( 'acf_is_pro' ) && ! acf_is_pro() ) {
				acf_include( 'includes/admin/admin-options-pages-preview.php' );
			}

			// Add actions.
			add_action( 'init', array( $this, 'register_post_status' ), 4 );
			add_action( 'init', array( $this, 'init' ), 5 );
			add_action( 'init', array( $this, 'register_post_types' ), 5 );
			add_action( 'activated_plugin', array( $this, 'deactivate_other_instances' ) );
			add_action( 'pre_current_active_plugins', array( $this, 'plugin_deactivated_notice' ) );

			// Add filters.
			add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );
		}

		/**
		 * Completes the setup process on "init" of earlier.
		 *
		 * @date    28/09/13
		 * @since   5.0.0
		 */
		public function init() {

			// Bail early if called directly from functions.php or plugin file.
			if ( ! did_action( 'plugins_loaded' ) ) {
				return;
			}

			// This function may be called directly from template functions. Bail early if already did this.
			if ( acf_did( 'init' ) ) {
				return;
			}

			// Update url setting. Allows other plugins to modify the URL (force SSL).
			acf_update_setting( 'url', plugin_dir_url( __FILE__ ) );

			// Load textdomain file.
			acf_load_textdomain();

			// Make plugin name translatable.
			acf_update_setting( 'name', __( 'Advanced Custom Fields', 'acf' ) );

			// Include 3rd party compatiblity.
			acf_include( 'includes/third-party.php' );

			// Include wpml support.
			if ( defined( 'ICL_SITEPRESS_VERSION' ) ) {
				acf_include( 'includes/wpml.php' );
			}

			// Add post types and taxonomies.
			if ( acf_get_setting( 'enable_post_types' ) ) {
				acf_include( 'includes/post-types/class-acf-taxonomy.php' );
				acf_include( 'includes/post-types/class-acf-post-type.php' );
			}

			// Add other ACF internal post types.
			do_action( 'acf/init_internal_post_types' );

			// Include fields.
			acf_include( 'includes/fields/class-acf-field-text.php' );
			acf_include( 'includes/fields/class-acf-field-textarea.php' );
			acf_include( 'includes/fields/class-acf-field-number.php' );
			acf_include( 'includes/fields/class-acf-field-range.php' );
			acf_include( 'includes/fields/class-acf-field-email.php' );
			acf_include( 'includes/fields/class-acf-field-url.php' );
			acf_include( 'includes/fields/class-acf-field-password.php' );
			acf_include( 'includes/fields/class-acf-field-image.php' );
			acf_include( 'includes/fields/class-acf-field-file.php' );
			acf_include( 'includes/fields/class-acf-field-wysiwyg.php' );
			acf_include( 'includes/fields/class-acf-field-oembed.php' );
			acf_include( 'includes/fields/class-acf-field-select.php' );
			acf_include( 'includes/fields/class-acf-field-checkbox.php' );
			acf_include( 'includes/fields/class-acf-field-radio.php' );
			acf_include( 'includes/fields/class-acf-field-button-group.php' );
			acf_include( 'includes/fields/class-acf-field-true_false.php' );
			acf_include( 'includes/fields/class-acf-field-link.php' );
			acf_include( 'includes/fields/class-acf-field-post_object.php' );
			acf_include( 'includes/fields/class-acf-field-page_link.php' );
			acf_include( 'includes/fields/class-acf-field-relationship.php' );
			acf_include( 'includes/fields/class-acf-field-taxonomy.php' );
			acf_include( 'includes/fields/class-acf-field-user.php' );
			acf_include( 'includes/fields/class-acf-field-google-map.php' );
			acf_include( 'includes/fields/class-acf-field-date_picker.php' );
			acf_include( 'includes/fields/class-acf-field-date_time_picker.php' );
			acf_include( 'includes/fields/class-acf-field-time_picker.php' );
			acf_include( 'includes/fields/class-acf-field-color_picker.php' );
			acf_include( 'includes/fields/class-acf-field-icon_picker.php' );
			acf_include( 'includes/fields/class-acf-field-message.php' );
			acf_include( 'includes/fields/class-acf-field-accordion.php' );
			acf_include( 'includes/fields/class-acf-field-tab.php' );
			acf_include( 'includes/fields/class-acf-field-group.php' );

			/**
			 * Fires after field types have been included.
			 *
			 * @date    28/09/13
			 * @since   5.0.0
			 *
			 * @param   int ACF_FIELD_API_VERSION The field API version.
			 */
			do_action( 'acf/include_field_types', ACF_FIELD_API_VERSION );

			// Include locations.
			acf_include( 'includes/locations/class-acf-location-post-type.php' );
			acf_include( 'includes/locations/class-acf-location-post-template.php' );
			acf_include( 'includes/locations/class-acf-location-post-status.php' );
			acf_include( 'includes/locations/class-acf-location-post-format.php' );
			acf_include( 'includes/locations/class-acf-location-post-category.php' );
			acf_include( 'includes/locations/class-acf-location-post-taxonomy.php' );
			acf_include( 'includes/locations/class-acf-location-post.php' );
			acf_include( 'includes/locations/class-acf-location-page-template.php' );
			acf_include( 'includes/locations/class-acf-location-page-type.php' );
			acf_include( 'includes/locations/class-acf-location-page-parent.php' );
			acf_include( 'includes/locations/class-acf-location-page.php' );
			acf_include( 'includes/locations/class-acf-location-current-user.php' );
			acf_include( 'includes/locations/class-acf-location-current-user-role.php' );
			acf_include( 'includes/locations/class-acf-location-user-form.php' );
			acf_include( 'includes/locations/class-acf-location-user-role.php' );
			acf_include( 'includes/locations/class-acf-location-taxonomy.php' );
			acf_include( 'includes/locations/class-acf-location-attachment.php' );
			acf_include( 'includes/locations/class-acf-location-comment.php' );
			acf_include( 'includes/locations/class-acf-location-widget.php' );
			acf_include( 'includes/locations/class-acf-location-nav-menu.php' );
			acf_include( 'includes/locations/class-acf-location-nav-menu-item.php' );

			/**
			 * Fires after location types have been included.
			 *
			 * @date    28/09/13
			 * @since   5.0.0
			 *
			 * @param   int ACF_FIELD_API_VERSION The field API version.
			 */
			do_action( 'acf/include_location_rules', ACF_FIELD_API_VERSION );

			/**
			 * Fires during initialization. Used to add local fields.
			 *
			 * @date    28/09/13
			 * @since   5.0.0
			 *
			 * @param   int ACF_FIELD_API_VERSION The field API version.
			 */
			do_action( 'acf/include_fields', ACF_FIELD_API_VERSION );

			/**
			 * Fires during initialization. Used to add local post types.
			 *
			 * @since 6.1
			 *
			 * @param int ACF_MAJOR_VERSION The major version of ACF.
			 */
			do_action( 'acf/include_post_types', ACF_MAJOR_VERSION );

			/**
			 * Fires during initialization. Used to add local taxonomies.
			 *
			 * @since 6.1
			 *
			 * @param int ACF_MAJOR_VERSION The major version of ACF.
			 */
			do_action( 'acf/include_taxonomies', ACF_MAJOR_VERSION );

			// If we're on 6.5 or newer, load block bindings.
			if ( version_compare( get_bloginfo( 'version' ), '6.5', '>=' ) ) {
				new ACF\Blocks\Bindings();
			}

			/**
			 * Fires after ACF is completely "initialized".
			 *
			 * @date    28/09/13
			 * @since   5.0.0
			 *
			 * @param   int ACF_MAJOR_VERSION The major version of ACF.
			 */
			do_action( 'acf/init', ACF_MAJOR_VERSION );
		}

		/**
		 * Registers the ACF post types.
		 *
		 * @date    22/10/2015
		 * @since   5.3.2
		 */
		public function register_post_types() {
			$cap = acf_get_setting( 'capability' );

			// Register the Field Group post type.
			register_post_type(
				'acf-field-group',
				array(
					'labels'          => array(
						'name'               => __( 'Field Groups', 'acf' ),
						'singular_name'      => __( 'Field Group', 'acf' ),
						'add_new'            => __( 'Add New', 'acf' ),
						'add_new_item'       => __( 'Add New Field Group', 'acf' ),
						'edit_item'          => __( 'Edit Field Group', 'acf' ),
						'new_item'           => __( 'New Field Group', 'acf' ),
						'view_item'          => __( 'View Field Group', 'acf' ),
						'search_items'       => __( 'Search Field Groups', 'acf' ),
						'not_found'          => __( 'No Field Groups found', 'acf' ),
						'not_found_in_trash' => __( 'No Field Groups found in Trash', 'acf' ),
					),
					'public'          => false,
					'hierarchical'    => true,
					'show_ui'         => true,
					'show_in_menu'    => false,
					'_builtin'        => false,
					'capability_type' => 'post',
					'capabilities'    => array(
						'edit_post'    => $cap,
						'delete_post'  => $cap,
						'edit_posts'   => $cap,
						'delete_posts' => $cap,
					),
					'supports'        => false,
					'rewrite'         => false,
					'query_var'       => false,
				)
			);

			// Register the Field post type.
			register_post_type(
				'acf-field',
				array(
					'labels'          => array(
						'name'               => __( 'Fields', 'acf' ),
						'singular_name'      => __( 'Field', 'acf' ),
						'add_new'            => __( 'Add New', 'acf' ),
						'add_new_item'       => __( 'Add New Field', 'acf' ),
						'edit_item'          => __( 'Edit Field', 'acf' ),
						'new_item'           => __( 'New Field', 'acf' ),
						'view_item'          => __( 'View Field', 'acf' ),
						'search_items'       => __( 'Search Fields', 'acf' ),
						'not_found'          => __( 'No Fields found', 'acf' ),
						'not_found_in_trash' => __( 'No Fields found in Trash', 'acf' ),
					),
					'public'          => false,
					'hierarchical'    => true,
					'show_ui'         => false,
					'show_in_menu'    => false,
					'_builtin'        => false,
					'capability_type' => 'post',
					'capabilities'    => array(
						'edit_post'    => $cap,
						'delete_post'  => $cap,
						'edit_posts'   => $cap,
						'delete_posts' => $cap,
					),
					'supports'        => array( 'title' ),
					'rewrite'         => false,
					'query_var'       => false,
				)
			);
		}

		/**
		 * Registers the ACF post statuses.
		 *
		 * @date    22/10/2015
		 * @since   5.3.2
		 */
		public function register_post_status() {

			// Register the Inactive post status.
			register_post_status(
				'acf-disabled',
				array(
					'label'                     => _x( 'Inactive', 'post status', 'acf' ),
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					/* translators: counts for inactive field groups */
					'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'acf' ),
				)
			);
		}

		/**
		 * Checks if another version of ACF/ACF PRO is active and deactivates it.
		 * Hooked on `activated_plugin` so other plugin is deactivated when current plugin is activated.
		 *
		 * @param string $plugin The plugin being activated.
		 */
		public function deactivate_other_instances( $plugin ) {
			if ( ! in_array( $plugin, array( 'advanced-custom-fields/acf.php', 'advanced-custom-fields-pro/acf.php' ), true ) ) {
				return;
			}

			$plugin_to_deactivate  = 'advanced-custom-fields/acf.php';
			$deactivated_notice_id = '1';

			// If we just activated the free version, deactivate the pro version.
			if ( $plugin === $plugin_to_deactivate ) {
				$plugin_to_deactivate  = 'advanced-custom-fields-pro/acf.php';
				$deactivated_notice_id = '2';
			}

			if ( is_multisite() && is_network_admin() ) {
				$active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
				$active_plugins = array_keys( $active_plugins );
			} else {
				$active_plugins = (array) get_option( 'active_plugins', array() );
			}

			foreach ( $active_plugins as $plugin_basename ) {
				if ( $plugin_to_deactivate === $plugin_basename ) {
					set_transient( 'acf_deactivated_notice_id', $deactivated_notice_id, 1 * HOUR_IN_SECONDS );
					deactivate_plugins( $plugin_basename );
					return;
				}
			}
		}

		/**
		 * Displays a notice when either ACF or ACF PRO is automatically deactivated.
		 */
		public function plugin_deactivated_notice() {
			$deactivated_notice_id = (int) get_transient( 'acf_deactivated_notice_id' );
			if ( ! in_array( $deactivated_notice_id, array( 1, 2 ), true ) ) {
				return;
			}

			$message = __( "Advanced Custom Fields and Advanced Custom Fields PRO should not be active at the same time. We've automatically deactivated Advanced Custom Fields.", 'acf' );
			if ( 2 === $deactivated_notice_id ) {
				$message = __( "Advanced Custom Fields and Advanced Custom Fields PRO should not be active at the same time. We've automatically deactivated Advanced Custom Fields PRO.", 'acf' );
			}

			?>
			<div class="updated" style="border-left: 4px solid #ffba00;">
				<p><?php echo esc_html( $message ); ?></p>
			</div>
			<?php

			delete_transient( 'acf_deactivated_notice_id' );
		}

		/**
		 * Filters the $where clause allowing for custom WP_Query args.
		 *
		 * @date    31/8/19
		 * @since   5.8.1
		 *
		 * @param  string   $where    The WHERE clause.
		 * @param  WP_Query $wp_query The query object.
		 * @return string
		 */
		public function posts_where( $where, $wp_query ) {
			global $wpdb;

			$field_key     = $wp_query->get( 'acf_field_key' );
			$field_name    = $wp_query->get( 'acf_field_name' );
			$group_key     = $wp_query->get( 'acf_group_key' );
			$post_type_key = $wp_query->get( 'acf_post_type_key' );
			$taxonomy_key  = $wp_query->get( 'acf_taxonomy_key' );

			// Add custom "acf_field_key" arg.
			if ( $field_key ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_name = %s", $field_key );
			}

			// Add custom "acf_field_name" arg.
			if ( $field_name ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_excerpt = %s", $field_name );
			}

			// Add custom "acf_group_key" arg.
			if ( $group_key ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_name = %s", $group_key );
			}

			// Add custom "acf_post_type_key" arg.
			if ( $post_type_key ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_name = %s", $post_type_key );
			}

			// Add custom "acf_taxonomy_key" arg.
			if ( $taxonomy_key ) {
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_name = %s", $taxonomy_key );
			}

			return $where;
		}

		/**
		 * Defines a constant if doesnt already exist.
		 *
		 * @date    3/5/17
		 * @since   5.5.13
		 *
		 * @param   string $name  The constant name.
		 * @param   mixed  $value The constant value.
		 * @return  void
		 */
		public function define( $name, $value = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Returns true if a setting exists for this name.
		 *
		 * @date    2/2/18
		 * @since   5.6.5
		 *
		 * @param   string $name The setting name.
		 * @return  boolean
		 */
		public function has_setting( $name ) {
			return isset( $this->settings[ $name ] );
		}

		/**
		 * Returns a setting or null if doesn't exist.
		 *
		 * @date    28/09/13
		 * @since   5.0.0
		 *
		 * @param   string $name The setting name.
		 * @return  mixed
		 */
		public function get_setting( $name ) {
			return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : null;
		}

		/**
		 * Updates a setting for the given name and value.
		 *
		 * @date    28/09/13
		 * @since   5.0.0
		 *
		 * @param   string $name  The setting name.
		 * @param   mixed  $value The setting value.
		 * @return  true
		 */
		public function update_setting( $name, $value ) {
			$this->settings[ $name ] = $value;
			return true;
		}

		/**
		 * Returns data or null if doesn't exist.
		 *
		 * @date    28/09/13
		 * @since   5.0.0
		 *
		 * @param   string $name The data name.
		 * @return  mixed
		 */
		public function get_data( $name ) {
			return isset( $this->data[ $name ] ) ? $this->data[ $name ] : null;
		}

		/**
		 * Sets data for the given name and value.
		 *
		 * @date    28/09/13
		 * @since   5.0.0
		 *
		 * @param   string $name  The data name.
		 * @param   mixed  $value The data value.
		 * @return  void
		 */
		public function set_data( $name, $value ) {
			$this->data[ $name ] = $value;
		}

		/**
		 * Returns an instance or null if doesn't exist.
		 *
		 * @date    13/2/18
		 * @since   5.6.9
		 *
		 * @param   string $class The instance class name.
		 * @return  object
		 */
		public function get_instance( $class ) {
			$name = strtolower( $class );
			return isset( $this->instances[ $name ] ) ? $this->instances[ $name ] : null;
		}

		/**
		 * Creates and stores an instance of the given class.
		 *
		 * @date    13/2/18
		 * @since   5.6.9
		 *
		 * @param   string $class The instance class name.
		 * @return  object
		 */
		public function new_instance( $class ) {
			$instance                 = new $class();
			$name                     = strtolower( $class );
			$this->instances[ $name ] = $instance;
			return $instance;
		}

		/**
		 * Magic __isset method for backwards compatibility.
		 *
		 * @date    24/4/20
		 * @since   5.9.0
		 *
		 * @param   string $key Key name.
		 * @return  boolean
		 */
		public function __isset( $key ) {
			return in_array( $key, array( 'locations', 'json' ), true );
		}

		/**
		 * Magic __get method for backwards compatibility.
		 *
		 * @date    24/4/20
		 * @since   5.9.0
		 *
		 * @param   string $key Key name.
		 * @return  mixed
		 */
		public function __get( $key ) {
			switch ( $key ) {
				case 'locations':
					return acf_get_instance( 'ACF_Legacy_Locations' );
				case 'json':
					return acf_get_instance( 'ACF_Local_JSON' );
			}
			return null;
		}

		/**
		 * Plugin Activation Hook
		 *
		 * @since 6.2.6
		 */
		public function acf_plugin_activated() {
			// Set the first activated version of ACF.
			if ( null === get_option( 'acf_first_activated_version', null ) ) {
				// If acf_version is set, this isn't the first activated version, so leave it unset so it's legacy.
				if ( null === get_option( 'acf_version', null ) ) {
					update_option( 'acf_first_activated_version', ACF_VERSION, true );

					do_action( 'acf/first_activated' );
				}
			}

			if ( acf_is_pro() ) {
				do_action( 'acf/activated_pro' );
			}
		}
	}

	/**
	 * An ACF specific getter to replace `home_url` in our license checks to ensure we can avoid third party filters.
	 *
	 * @since 6.0.1
	 * @since 6.2.8 - Renamed to acf_pro_get_home_url to match pro exclusive function naming.
	 * @since 6.3.10 - Renamed to acf_get_home_url now updater logic applies to free.
	 *
	 * @return string $home_url The output from home_url, sans known third party filters which cause license activation issues.
	 */
	function acf_get_home_url() {
		if ( acf_is_pro() ) {
			// Disable WPML and TranslatePress's home url overrides for our license check.
			add_filter( 'wpml_get_home_url', 'acf_pro_license_ml_intercept', 99, 2 );
			add_filter( 'trp_home_url', 'acf_pro_license_ml_intercept', 99, 2 );

			if ( acf_pro_is_legacy_multisite() && acf_is_multisite_sub_site() ) {
				$home_url = get_home_url( get_main_site_id() );
			} else {
				$home_url = home_url();
			}

			// Re-enable WPML and TranslatePress's home url overrides.
			remove_filter( 'wpml_get_home_url', 'acf_pro_license_ml_intercept', 99 );
			remove_filter( 'trp_home_url', 'acf_pro_license_ml_intercept', 99 );
		} else {
			$home_url = home_url();
		}

		return $home_url;
	}

	/**
	 * The main function responsible for returning the one true acf Instance to functions everywhere.
	 * Use this function like you would a global variable, except without needing to declare the global.
	 *
	 * Example: <?php $acf = acf(); ?>
	 *
	 * @date    4/09/13
	 * @since   4.3.0
	 *
	 * @return  ACF
	 */
	function acf() {
		global $acf;

		// Instantiate only once.
		if ( ! isset( $acf ) ) {
			$acf = new ACF();
			$acf->initialize();
		}
		return $acf;
	}

	// Instantiate.
	acf();
} // class_exists check
