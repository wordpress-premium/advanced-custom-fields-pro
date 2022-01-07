<?php
/**
 * Pro SEO Reports in Email.
 *
 * @since      2.0.0
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Analytics;

use RankMath\KB;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Analytics\Stats;
use RankMath\Admin\Admin_Helper;
use RankMath\Google\Authentication;
use RankMath\Analytics\Email_Reports as Email_Reports_Base;

use RankMathPro\Admin\Admin_Helper as ProAdminHelper;
use RankMathPro\Analytics\Keywords;
use RankMathPro\Analytics\Posts;

use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * DB class.
 */
class Email_Reports {

	use Hooker;

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Add filter & action hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		$this->views_path = dirname( __FILE__ ) . '/views/email-reports/';
		$this->assets_url = plugin_dir_url( __FILE__ ) . 'assets/';

		// CMB hooks.
		$this->action( 'rank_math/admin/settings/analytics', 'add_options' );

		// WP hooks.
		$this->filter( 'admin_post_rank_math_save_wizard', 'save_wizard' );

		// Rank Math hooks.
		$this->filter( 'rank_math/analytics/email_report_template_paths', 'add_template_path' );
		$this->filter( 'rank_math/analytics/email_report_variables', 'add_variables' );
		$this->filter( 'rank_math/analytics/email_report_parameters', 'email_parameters' );
		$this->filter( 'rank_math/analytics/email_report_image_atts', 'replace_logo', 10, 2 );
		$this->filter( 'rank_math/analytics/email_report_periods', 'frequency_periods' );
		$this->action( 'rank_math/analytics/options/wizard_after_email_report', 'wizard_options' );
	}

	/**
	 * Output CSS for required for the Pro reports.
	 *
	 * @return void
	 */
	public function add_pro_css() {
		$this->template_part( 'pro-style' );
	}

	/**
	 * Replace logo image in template.
	 *
	 * @param array  $atts   All original attributes.
	 * @param string $url    Image URL or identifier.
	 *
	 * @return array
	 */
	public function replace_logo( $atts, $url ) {
		if ( 'report-logo.png' !== $url ) {
			return $atts;
		}

		$atts['src'] = '###LOGO_URL###';
		$atts['alt'] = '###LOGO_ALT###';

		return $atts;
	}

	/**
	 * Add Pro variables.
	 *
	 * @param array $variables Original variables.
	 * @return array
	 */
	public function add_variables( $variables ) {
		$variables['pro_assets_url'] = $this->assets_url;

		$variables['logo_url'] = Email_Reports_Base::get_setting( 'logo', $this->get_logo_url_default() );
		$variables['logo_alt'] = __( 'Logo', 'rank-math-pro' );

		$image_id = Email_Reports_Base::get_setting( 'logo_id', 0 );
		if ( $image_id ) {
			$alt = get_post_meta( $image_id, '_wp_attachment_image_alt', true );
			if ( $alt ) {
				$variables['logo_alt'] = $alt;
			}
		}

		$variables['header_background'] = Email_Reports_Base::get_setting( 'header_background', 'linear-gradient(90deg, rgba(112,83,181,1) 0%, rgba(73,153,210,1) 100%)' );
		$variables['top_html']          = wp_kses_post( wpautop( Email_Reports_Base::get_setting( 'top_text', '' ) ) );
		$variables['footer_html']       = wp_kses_post( Email_Reports_Base::get_setting( 'footer_text', $this->get_default_footer_text() ) );
		$variables['custom_css']        = Email_Reports_Base::get_setting( 'custom_css', '' );
		$variables['logo_link']         = Email_Reports_Base::get_setting( 'logo_link', KB::get( 'email-reports' ) );

		// Get Pro stats.
		$period = Email_Reports_Base::get_period_from_frequency();
		Stats::get()->set_date_range( "-{$period} days" );

		$keywords = Keywords::get();
		if ( Email_Reports_Base::get_setting( 'tracked_keywords', false ) ) {
			$variables['winning_keywords'] = $keywords->get_tracked_winning_keywords();
			$variables['losing_keywords']  = $keywords->get_tracked_losing_keywords();
		} else {
			$variables['winning_keywords'] = $keywords->get_winning_keywords();
			$variables['losing_keywords']  = $keywords->get_losing_keywords();
		}

		$posts                      = Posts::get();
		$variables['winning_posts'] = $posts->get_winning_posts();
		$variables['losing_posts']  = $posts->get_losing_posts();

		return $variables;
	}

	/**
	 * Add Email Report options.
	 *
	 * @param object $cmb CMB object.
	 */
	public function add_options( $cmb ) {
		if ( ! Authentication::is_authorized() || Email_Reports_Base::are_fields_hidden() ) {
			return;
		}

		$is_business = ProAdminHelper::is_business_plan();

		// Add Frequency options.
		$frequency_field = $cmb->get_field( 'console_email_frequency' );

		// Early bail if the console_email_frequency field does not exist.
		if ( empty( $frequency_field ) ) {
			return;
		}

		$frequency_field->args['options']['every_15_days'] = esc_html__( 'Every 15 Days', 'rank-math-pro' );

		$field_ids       = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$fields_position = array_search( 'console_email_frequency', array_keys( $field_ids ), true ) + 1;

		if ( $is_business ) {
			$frequency_field->args['options']['weekly'] = esc_html__( 'Every 7 Days', 'rank-math-pro' );
		} else {
			/**
			 * This field is repeated further down, to insert it in the correct
			 * position when the account type is Business.
			 */
			$cmb->add_field(
				[
					'id'          => 'console_email_tracked_keywords',
					'type'        => 'toggle',
					'name'        => __( 'Include Only Tracked Keywords', 'rank-math-pro' ),
					'description' => __( 'When enabled, the Winning Keywords section will only show Tracked Keywords.', 'rank-math-pro' ),
					'default'     => 'off',
					'dep'         => [ [ 'console_email_reports', 'on' ] ],
				],
				++$fields_position
			);

			return;
		}

		// Business options from here on.
		$cmb->add_field(
			[
				'id'          => 'console_email_send_to',
				'type'        => 'text',
				'name'        => __( 'Report Email Address', 'rank-math-pro' ),
				'description' => __( 'Address where the reports will be sent. You can add multiple recipients separated with commas.', 'rank-math-pro' ),
				'default'     => Admin_Helper::get_registration_data()['email'],
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_subject',
				'type'        => 'text',
				'name'        => __( 'Report Email Subject', 'rank-math-pro' ),
				'description' => __( 'Subject of the report emails.', 'rank-math-pro' ),
				'default'     => $this->get_subject_default(),
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_logo',
				'type'        => 'file',
				'name'        => __( 'Report Logo', 'rank-math-pro' ),
				'description' => __( 'Logo appearing in the header part of the report.', 'rank-math-pro' ),
				'default'     => $this->get_logo_url_default(),
				'options'     => [ 'url' => false ],
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_logo_link',
				'type'        => 'text',
				'name'        => __( 'Logo Link', 'rank-math-pro' ),
				'description' => __( 'URL where the logo link should point to.', 'rank-math-pro' ),
				'default'     => KB::get( 'email-reports-logo' ),
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_header_background',
				'type'        => 'text',
				'name'        => __( 'Report Header Background', 'rank-math-pro' ),
				'description' => __( 'Color hex code or any other valid value for the <code>background:</code> CSS property.', 'rank-math-pro' ),
				'default'     => $this->get_header_bg_default(),
				'dep'         => [ [ 'console_email_reports', 'on' ] ],

				// Instant preview.
				'after_field' => $this->get_bg_preview(),
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_link_full_report',
				'type'        => 'toggle',
				'name'        => __( 'Link to Full Report', 'rank-math-pro' ),
				'description' => __( 'Select whether to include a link to the Full Report admin page in the email or not.', 'rank-math-pro' ),
				'default'     => 'on',
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_top_text',
				'type'        => 'textarea_small',
				'name'        => __( 'Report Top Text', 'rank-math-pro' ),
				'description' => __( 'Text or basic HTML to insert below the title.', 'rank-math-pro' ),
				'default'     => '',
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'                => 'console_email_sections',
				'type'              => 'multicheck',
				'name'              => esc_html__( 'Include Sections', 'rank-math-pro' ),
				'desc'              => esc_html__( 'Select which tables to show in the report.', 'rank-math-pro' ),
				'options'           => [
					'summary'          => __( 'Basic Summary', 'rank-math-pro' ),
					'positions'        => __( 'Positions Summary', 'rank-math-pro' ),
					'winning_posts'    => __( 'Top Winning Posts', 'rank-math-pro' ),
					'losing_posts'     => __( 'Top Losing Posts', 'rank-math-pro' ),
					'winning_keywords' => __( 'Top Winning Keywords', 'rank-math-pro' ),
					'losing_keywords'  => __( 'Top Losing Keywords', 'rank-math-pro' ),
				],
				'default'           => [ 'summary', 'positions', 'winning_posts', 'winning_keywords', 'losing_keywords' ],
				'select_all_button' => true,
				'dep'               => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		/**
		 * This field is also added for non-business accounts at the beginning
		 * of this function.
		 */
		$cmb->add_field(
			[
				'id'          => 'console_email_tracked_keywords',
				'type'        => 'toggle',
				'name'        => __( 'Include Only Tracked Keywords', 'rank-math-pro' ),
				'description' => __( 'When enabled, the Winning Keywords and Losing Keywords sections will only show Tracked Keywords.', 'rank-math-pro' ),
				'default'     => 'off',
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_footer_text',
				'type'        => 'textarea_small',
				'name'        => __( 'Report Footer Text', 'rank-math-pro' ),
				'description' => __( 'Text or basic HTML to insert in the footer area.', 'rank-math-pro' ),
				'default'     => $this->get_default_footer_text(),
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);

		$cmb->add_field(
			[
				'id'          => 'console_email_custom_css',
				'type'        => 'textarea_small',
				'name'        => __( 'Additional CSS code', 'rank-math-pro' ),
				'description' => __( 'Additional CSS code to customize the appearance of the reports. Insert the CSS code directly, without the wrapping style tag. Please note that the CSS support is limited in email clients and the appearance may vary greatly.', 'rank-math-pro' ),
				'default'     => '',
				'dep'         => [ [ 'console_email_reports', 'on' ] ],
			],
			++$fields_position
		);
	}

	/**
	 * Get default value for footer text option.
	 *
	 * @return string
	 */
	public function get_default_footer_text() {
		return join(
			' ',
			[
				// Translators: placeholder is a link to the homepage.
				sprintf( esc_html__( 'This email was sent to you as a registered member of %s.', 'rank-math-pro' ), '<a href="###SITE_URL###">###SITE_URL_SIMPLE###</a>' ),

				// Translators: placeholder is a link to the settings, with "click here" as the anchor text.
				sprintf( esc_html__( 'To update your email preferences, %s. ###ADDRESS###', 'rank-math-pro' ), '<a href="###SETTINGS_URL###">' . esc_html__( 'click here', 'rank-math-pro' ) . '</a>' ),
			]
		);
	}

	/**
	 * Change email parameters if needed.
	 *
	 * @param  array $email Parameters array.
	 * @return array
	 */
	public function email_parameters( $email ) {
		$email['to']      = Email_Reports_Base::get_setting( 'send_to', Admin_Helper::get_registration_data()['email'] );
		$email['subject'] = Email_Reports_Base::get_setting( 'subject', $this->get_subject_default() );

		return $email;
	}

	/**
	 * Get 'value' & 'diff' for the stat template part.
	 *
	 * @param mixed  $data Stats data.
	 * @param string $item Item we want to extract.
	 * @return array
	 */
	public static function get_stats_val( $data, $item ) {
		$value = isset( $data[ $item ]['total'] ) ? $data[ $item ]['total'] : 0;
		$diff  = isset( $data[ $item ]['difference'] ) ? $data[ $item ]['difference'] : 0;

		return compact( 'value', 'diff' );
	}

	/**
	 * Output additional options in the Setup Wizard.
	 *
	 * @return void
	 */
	public function wizard_options() {
		if ( ! ProAdminHelper::is_business_plan() ) {
			return;
		}

		?>
		<div class="cmb-row cmb-type-toggle cmb2-id-console-email-send-to" data-fieldtype="toggle">
			<div class="cmb-th">
				<label for="console_email_send"><?php esc_html_e( 'Report Email Address', 'rank-math-pro' ); ?></label>
			</div>
			<div class="cmb-td">
				<input type="text" class="regular-text" name="console_email_send_to" id="console_email_send_to" value="<?php echo esc_attr( Helper::get_settings( 'general.console_email_send_to' ) ); ?>" data-hash="42cpi4bihms0">
				<p class="cmb2-metabox-description"><?php esc_html_e( 'Address where the reports will be sent. You can add multiple recipients separated with commas.', 'rank-math-pro' ); ?></p>
				<div class="rank-math-cmb-dependency hidden" data-relation="or"><span class="hidden" data-field="console_email_reports" data-comparison="=" data-value="on"></span></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save additional wizard options.
	 *
	 * @return bool
	 */
	public function save_wizard() {
		$referer = Param::post( '_wp_http_referer' );
		if ( empty( $_POST ) ) {
			return wp_safe_redirect( $referer );
		}

		check_admin_referer( 'rank-math-wizard', 'security' );
		if ( ! Helper::has_cap( 'general' ) ) {
			return false;
		}

		$send_to = Param::post( 'console_email_send_to' );
		if ( ! $send_to ) {
			return true;
		}

		$settings = rank_math()->settings->all_raw();

		$settings['general']['console_email_send_to'] = $send_to;
		Helper::update_all_settings( $settings['general'], null, null );

		return true;
	}

	/**
	 * Add element and script for background preview.
	 *
	 * @return string
	 */
	public function get_bg_preview() {
		$script = '
			<script>
				jQuery( function() {
					jQuery( "#console_email_header_background" ).on( "change", function() {
						jQuery( ".rank-math-preview-bg" ).css( "background", jQuery( this ).val() );
					} );
				} );
			</script>
		';

		return '<div class="rank-math-preview-bg" data-title="' . esc_attr( __( 'Preview', 'rank-math-pro' ) ) . '" style="background: ' . esc_attr( Helper::get_settings( 'general.console_email_header_background', $this->get_header_bg_default() ) ) . '"></div>' . $script;
	}

	/**
	 * Get default value for the Header Background option.
	 *
	 * @return string
	 */
	public function get_header_bg_default() {
		return 'linear-gradient(90deg, #724BB7 0%, #4098D7 100%)';
	}

	/**
	 * Get default value for the Logo URL option.
	 *
	 * @return string
	 */
	public function get_logo_url_default() {
		$url = \rank_math()->plugin_url() . 'includes/modules/analytics/assets/img/';
		return $url . 'report-logo.png';
	}

	/**
	 * Get default value for the Subject option.
	 *
	 * @return string
	 */
	public function get_subject_default() {
		return sprintf(
			// Translators: placeholder is the site URL.
			__( 'Rank Math [SEO Report] - %s', 'rank-math-pro' ),
			explode( '://', get_home_url() )[1]
		);
	}

	/**
	 * Shorten a URL, like http://example-url...long-page/
	 *
	 * @param string  $url URL to shorten.
	 * @param integer $max Max length in characters.
	 * @return string
	 */
	public static function shorten_url( $url, $max = 16 ) {
		$length = strlen( $url );

		if ( $length <= $max + 3 ) {
			return $url;
		}

		return substr_replace( $url, '...', $max / 2, $length - $max );
	}

	/**
	 * Add pro template path to paths.
	 *
	 * @param string[] $paths Original paths.
	 * @return string[]
	 */
	public function add_template_path( $paths ) {
		$paths[] = $this->views_path;
		return $paths;
	}

	/**
	 * Add day numbers for new frequencies.
	 *
	 * @param array $periods Original periods.
	 * @return array
	 */
	public function frequency_periods( $periods ) {
		$periods['every_15_days'] = 15;
		$periods['weekly']        = 7;

		return $periods;
	}
}
