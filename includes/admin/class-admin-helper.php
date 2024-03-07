<?php
/**
 * Admin helper Functions.
 *
 * This file contains functions needed on the admin screens.
 *
 * @since      2.0.0
 * @package    RankMath
 * @subpackage RankMath\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper as Free_Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Admin_Helper class.
 */
class Admin_Helper {

	/**
	 * Get primary term ID.
	 *
	 * @param  int $post_id Post ID.
	 *
	 * @return int
	 */
	public static function get_primary_term_id( $post_id = null ) {
		$taxonomy = self::get_primary_taxonomy( $post_id );
		if ( ! $taxonomy ) {
			return 0;
		}

		$id = get_post_meta( $post_id ? $post_id : get_the_ID(), 'rank_math_primary_' . $taxonomy['name'], true );

		return $id ? absint( $id ) : 0;
	}

	/**
	 * Get current post type.
	 *
	 * @param  int $post_id Post ID.
	 *
	 * @return string
	 */
	public static function get_current_post_type( $post_id = null ) {
		if ( ! $post_id && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			return isset( $screen->post_type ) ? $screen->post_type : '';
		}

		return get_post_type( $post_id );
	}

	/**
	 * Get primary taxonomy.
	 *
	 * @param  int $post_id Post ID.
	 *
	 * @return bool|array
	 */
	public static function get_primary_taxonomy( $post_id = null ) {
		$taxonomy  = false;
		$post_type = self::get_current_post_type( $post_id );

		/**
		 * Allow disabling the primary term feature.
		 *
		 * @param bool $return True to disable.
		 */
		if ( false === apply_filters( 'rank_math/admin/disable_primary_term', false ) ) {
			$taxonomy = Helper::get_settings( 'titles.pt_' . $post_type . '_primary_taxonomy', false );
		}

		if ( ! $taxonomy ) {
			return false;
		}

		$taxonomy = get_taxonomy( $taxonomy );
		if ( ! $taxonomy ) {
			return false;
		}

		$primary_taxonomy = [
			'title'         => $taxonomy->labels->singular_name,
			'name'          => $taxonomy->name,
			'singularLabel' => $taxonomy->labels->singular_name,
			'restBase'      => ( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name,
		];

		return $primary_taxonomy;
	}

	/**
	 * Check if current plan is business.
	 *
	 * @return boolean
	 */
	public static function is_business_plan() {
		return in_array( self::get_plan(), [ 'business', 'agency' ], true );
	}

	/**
	 * Get current plan.
	 *
	 * @return string
	 */
	public static function get_plan() {
		$registered = Free_Admin_Helper::get_registration_data();

		return isset( $registered['plan'] ) ? $registered['plan'] : 'pro';
	}

	/**
	 * Country.
	 *
	 * @return array
	 */
	public static function choices_countries() {
		return [
			'all' => __( 'Worldwide', 'rank-math-pro' ),
			'AF'  => __( 'Afghanistan', 'rank-math-pro' ),
			'AL'  => __( 'Albania', 'rank-math-pro' ),
			'DZ'  => __( 'Algeria', 'rank-math-pro' ),
			'AS'  => __( 'American Samoa', 'rank-math-pro' ),
			'AD'  => __( 'Andorra', 'rank-math-pro' ),
			'AO'  => __( 'Angola', 'rank-math-pro' ),
			'AI'  => __( 'Anguilla', 'rank-math-pro' ),
			'AQ'  => __( 'Antarctica', 'rank-math-pro' ),
			'AG'  => __( 'Antigua and Barbuda', 'rank-math-pro' ),
			'AR'  => __( 'Argentina', 'rank-math-pro' ),
			'AM'  => __( 'Armenia', 'rank-math-pro' ),
			'AW'  => __( 'Aruba', 'rank-math-pro' ),
			'AU'  => __( 'Australia', 'rank-math-pro' ),
			'AT'  => __( 'Austria', 'rank-math-pro' ),
			'AZ'  => __( 'Azerbaijan', 'rank-math-pro' ),
			'BS'  => __( 'Bahamas', 'rank-math-pro' ),
			'BH'  => __( 'Bahrain', 'rank-math-pro' ),
			'BD'  => __( 'Bangladesh', 'rank-math-pro' ),
			'BB'  => __( 'Barbados', 'rank-math-pro' ),
			'BY'  => __( 'Belarus', 'rank-math-pro' ),
			'BE'  => __( 'Belgium', 'rank-math-pro' ),
			'BZ'  => __( 'Belize', 'rank-math-pro' ),
			'BJ'  => __( 'Benin', 'rank-math-pro' ),
			'BM'  => __( 'Bermuda', 'rank-math-pro' ),
			'BT'  => __( 'Bhutan', 'rank-math-pro' ),
			'BO'  => __( 'Bolivia', 'rank-math-pro' ),
			'BA'  => __( 'Bosnia and Herzegovina', 'rank-math-pro' ),
			'BW'  => __( 'Botswana', 'rank-math-pro' ),
			'BV'  => __( 'Bouvet Island', 'rank-math-pro' ),
			'BR'  => __( 'Brazil', 'rank-math-pro' ),
			'IO'  => __( 'British Indian Ocean Territory', 'rank-math-pro' ),
			'BN'  => __( 'Brunei Darussalam', 'rank-math-pro' ),
			'BG'  => __( 'Bulgaria', 'rank-math-pro' ),
			'BF'  => __( 'Burkina Faso', 'rank-math-pro' ),
			'BI'  => __( 'Burundi', 'rank-math-pro' ),
			'KH'  => __( 'Cambodia', 'rank-math-pro' ),
			'CM'  => __( 'Cameroon', 'rank-math-pro' ),
			'CA'  => __( 'Canada', 'rank-math-pro' ),
			'CV'  => __( 'Cape Verde', 'rank-math-pro' ),
			'KY'  => __( 'Cayman Islands', 'rank-math-pro' ),
			'CF'  => __( 'Central African Republic', 'rank-math-pro' ),
			'TD'  => __( 'Chad', 'rank-math-pro' ),
			'CL'  => __( 'Chile', 'rank-math-pro' ),
			'CN'  => __( 'China', 'rank-math-pro' ),
			'CX'  => __( 'Christmas Island', 'rank-math-pro' ),
			'CC'  => __( 'Cocos (Keeling) Islands', 'rank-math-pro' ),
			'CO'  => __( 'Colombia', 'rank-math-pro' ),
			'KM'  => __( 'Comoros', 'rank-math-pro' ),
			'CG'  => __( 'Congo', 'rank-math-pro' ),
			'CD'  => __( 'Congo, the Democratic Republic of the', 'rank-math-pro' ),
			'CK'  => __( 'Cook Islands', 'rank-math-pro' ),
			'CR'  => __( 'Costa Rica', 'rank-math-pro' ),
			'CI'  => __( "Cote D'ivoire", 'rank-math-pro' ),
			'HR'  => __( 'Croatia', 'rank-math-pro' ),
			'CU'  => __( 'Cuba', 'rank-math-pro' ),
			'CY'  => __( 'Cyprus', 'rank-math-pro' ),
			'CZ'  => __( 'Czech Republic', 'rank-math-pro' ),
			'DK'  => __( 'Denmark', 'rank-math-pro' ),
			'DJ'  => __( 'Djibouti', 'rank-math-pro' ),
			'DM'  => __( 'Dominica', 'rank-math-pro' ),
			'DO'  => __( 'Dominican Republic', 'rank-math-pro' ),
			'EC'  => __( 'Ecuador', 'rank-math-pro' ),
			'EG'  => __( 'Egypt', 'rank-math-pro' ),
			'SV'  => __( 'El Salvador', 'rank-math-pro' ),
			'GQ'  => __( 'Equatorial Guinea', 'rank-math-pro' ),
			'ER'  => __( 'Eritrea', 'rank-math-pro' ),
			'EE'  => __( 'Estonia', 'rank-math-pro' ),
			'ET'  => __( 'Ethiopia', 'rank-math-pro' ),
			'FK'  => __( 'Falkland Islands (Malvinas)', 'rank-math-pro' ),
			'FO'  => __( 'Faroe Islands', 'rank-math-pro' ),
			'FJ'  => __( 'Fiji', 'rank-math-pro' ),
			'FI'  => __( 'Finland', 'rank-math-pro' ),
			'FR'  => __( 'France', 'rank-math-pro' ),
			'GF'  => __( 'French Guiana', 'rank-math-pro' ),
			'PF'  => __( 'French Polynesia', 'rank-math-pro' ),
			'TF'  => __( 'French Southern Territories', 'rank-math-pro' ),
			'GA'  => __( 'Gabon', 'rank-math-pro' ),
			'GM'  => __( 'Gambia', 'rank-math-pro' ),
			'GE'  => __( 'Georgia', 'rank-math-pro' ),
			'DE'  => __( 'Germany', 'rank-math-pro' ),
			'GH'  => __( 'Ghana', 'rank-math-pro' ),
			'GI'  => __( 'Gibraltar', 'rank-math-pro' ),
			'GR'  => __( 'Greece', 'rank-math-pro' ),
			'GL'  => __( 'Greenland', 'rank-math-pro' ),
			'GD'  => __( 'Grenada', 'rank-math-pro' ),
			'GP'  => __( 'Guadeloupe', 'rank-math-pro' ),
			'GU'  => __( 'Guam', 'rank-math-pro' ),
			'GT'  => __( 'Guatemala', 'rank-math-pro' ),
			'GN'  => __( 'Guinea', 'rank-math-pro' ),
			'GW'  => __( 'Guinea-Bissau', 'rank-math-pro' ),
			'GY'  => __( 'Guyana', 'rank-math-pro' ),
			'HT'  => __( 'Haiti', 'rank-math-pro' ),
			'HM'  => __( 'Heard Island and Mcdonald Islands', 'rank-math-pro' ),
			'VA'  => __( 'Holy See (Vatican City State)', 'rank-math-pro' ),
			'HN'  => __( 'Honduras', 'rank-math-pro' ),
			'HK'  => __( 'Hong Kong', 'rank-math-pro' ),
			'HU'  => __( 'Hungary', 'rank-math-pro' ),
			'IS'  => __( 'Iceland', 'rank-math-pro' ),
			'IN'  => __( 'India', 'rank-math-pro' ),
			'ID'  => __( 'Indonesia', 'rank-math-pro' ),
			'IR'  => __( 'Iran, Islamic Republic of', 'rank-math-pro' ),
			'IQ'  => __( 'Iraq', 'rank-math-pro' ),
			'IE'  => __( 'Ireland', 'rank-math-pro' ),
			'IL'  => __( 'Israel', 'rank-math-pro' ),
			'IT'  => __( 'Italy', 'rank-math-pro' ),
			'JM'  => __( 'Jamaica', 'rank-math-pro' ),
			'JP'  => __( 'Japan', 'rank-math-pro' ),
			'JO'  => __( 'Jordan', 'rank-math-pro' ),
			'KZ'  => __( 'Kazakhstan', 'rank-math-pro' ),
			'KE'  => __( 'Kenya', 'rank-math-pro' ),
			'KI'  => __( 'Kiribati', 'rank-math-pro' ),
			'KP'  => __( "Korea, Democratic People's Republic of", 'rank-math-pro' ),
			'KR'  => __( 'Korea, Republic of', 'rank-math-pro' ),
			'KW'  => __( 'Kuwait', 'rank-math-pro' ),
			'KG'  => __( 'Kyrgyzstan', 'rank-math-pro' ),
			'LA'  => __( "Lao People's Democratic Republic", 'rank-math-pro' ),
			'LV'  => __( 'Latvia', 'rank-math-pro' ),
			'LB'  => __( 'Lebanon', 'rank-math-pro' ),
			'LS'  => __( 'Lesotho', 'rank-math-pro' ),
			'LR'  => __( 'Liberia', 'rank-math-pro' ),
			'LY'  => __( 'Libyan Arab Jamahiriya', 'rank-math-pro' ),
			'LI'  => __( 'Liechtenstein', 'rank-math-pro' ),
			'LT'  => __( 'Lithuania', 'rank-math-pro' ),
			'LU'  => __( 'Luxembourg', 'rank-math-pro' ),
			'MO'  => __( 'Macao', 'rank-math-pro' ),
			'MK'  => __( 'Macedonia, the Former Yugosalv Republic of', 'rank-math-pro' ),
			'MG'  => __( 'Madagascar', 'rank-math-pro' ),
			'MW'  => __( 'Malawi', 'rank-math-pro' ),
			'MY'  => __( 'Malaysia', 'rank-math-pro' ),
			'MV'  => __( 'Maldives', 'rank-math-pro' ),
			'ML'  => __( 'Mali', 'rank-math-pro' ),
			'MT'  => __( 'Malta', 'rank-math-pro' ),
			'MH'  => __( 'Marshall Islands', 'rank-math-pro' ),
			'MQ'  => __( 'Martinique', 'rank-math-pro' ),
			'MR'  => __( 'Mauritania', 'rank-math-pro' ),
			'MU'  => __( 'Mauritius', 'rank-math-pro' ),
			'YT'  => __( 'Mayotte', 'rank-math-pro' ),
			'MX'  => __( 'Mexico', 'rank-math-pro' ),
			'FM'  => __( 'Micronesia, Federated States of', 'rank-math-pro' ),
			'MC'  => __( 'Moldova, Republic of', 'rank-math-pro' ),
			'MP'  => __( 'Northern Mariana Islands', 'rank-math-pro' ),
			'MC'  => __( 'Monaco', 'rank-math-pro' ),
			'MN'  => __( 'Mongolia', 'rank-math-pro' ),
			'MS'  => __( 'Montserrat', 'rank-math-pro' ),
			'MA'  => __( 'Morocco', 'rank-math-pro' ),
			'MZ'  => __( 'Mozambique', 'rank-math-pro' ),
			'MM'  => __( 'Myanmar', 'rank-math-pro' ),
			'NA'  => __( 'Namibia', 'rank-math-pro' ),
			'NR'  => __( 'Nauru', 'rank-math-pro' ),
			'NP'  => __( 'Nepal', 'rank-math-pro' ),
			'NL'  => __( 'Netherlands', 'rank-math-pro' ),
			'AN'  => __( 'Netherlands Antilles', 'rank-math-pro' ),
			'NC'  => __( 'New Caledonia', 'rank-math-pro' ),
			'NZ'  => __( 'New Zealand', 'rank-math-pro' ),
			'NI'  => __( 'Nicaragua', 'rank-math-pro' ),
			'NE'  => __( 'Niger', 'rank-math-pro' ),
			'NG'  => __( 'Nigeria', 'rank-math-pro' ),
			'NU'  => __( 'Niue', 'rank-math-pro' ),
			'NF'  => __( 'Norfolk Island', 'rank-math-pro' ),
			'MP'  => __( 'Northern Mariana Islands', 'rank-math-pro' ),
			'NO'  => __( 'Norway', 'rank-math-pro' ),
			'OM'  => __( 'Oman', 'rank-math-pro' ),
			'PK'  => __( 'Pakistan', 'rank-math-pro' ),
			'PW'  => __( 'Palau', 'rank-math-pro' ),
			'PS'  => __( 'Palestinian Territory, Occupied', 'rank-math-pro' ),
			'PA'  => __( 'Panama', 'rank-math-pro' ),
			'PG'  => __( 'Papua New Guinea', 'rank-math-pro' ),
			'PY'  => __( 'Paraguay', 'rank-math-pro' ),
			'PE'  => __( 'Peru', 'rank-math-pro' ),
			'PH'  => __( 'Philippines', 'rank-math-pro' ),
			'PN'  => __( 'Pitcairn', 'rank-math-pro' ),
			'PL'  => __( 'Poland', 'rank-math-pro' ),
			'PT'  => __( 'Portugal', 'rank-math-pro' ),
			'PR'  => __( 'Puerto Rico', 'rank-math-pro' ),
			'QA'  => __( 'Qatar', 'rank-math-pro' ),
			'RE'  => __( 'Reunion', 'rank-math-pro' ),
			'RO'  => __( 'Romania', 'rank-math-pro' ),
			'RU'  => __( 'Russian Federation', 'rank-math-pro' ),
			'RW'  => __( 'Rwanda', 'rank-math-pro' ),
			'SH'  => __( 'Saint Helena', 'rank-math-pro' ),
			'KN'  => __( 'Saint Kitts and Nevis', 'rank-math-pro' ),
			'LC'  => __( 'Saint Lucia', 'rank-math-pro' ),
			'PM'  => __( 'Saint Pierre and Miquelon', 'rank-math-pro' ),
			'VC'  => __( 'Saint Vincent and the Grenadines', 'rank-math-pro' ),
			'WS'  => __( 'Samoa', 'rank-math-pro' ),
			'SM'  => __( 'San Marino', 'rank-math-pro' ),
			'ST'  => __( 'Sao Tome and Principe', 'rank-math-pro' ),
			'SA'  => __( 'Saudi Arabia', 'rank-math-pro' ),
			'SN'  => __( 'Senegal', 'rank-math-pro' ),
			'CS'  => __( 'Serbia and Montenegro', 'rank-math-pro' ),
			'SC'  => __( 'Seychelles', 'rank-math-pro' ),
			'SL'  => __( 'Sierra Leone', 'rank-math-pro' ),
			'SG'  => __( 'Singapore', 'rank-math-pro' ),
			'SK'  => __( 'Slovakia', 'rank-math-pro' ),
			'SI'  => __( 'Slovenia', 'rank-math-pro' ),
			'SB'  => __( 'Solomon Islands', 'rank-math-pro' ),
			'SO'  => __( 'Somalia', 'rank-math-pro' ),
			'ZA'  => __( 'South Africa', 'rank-math-pro' ),
			'GS'  => __( 'South Georgia and the South Sandwich Islands', 'rank-math-pro' ),
			'ES'  => __( 'Spain', 'rank-math-pro' ),
			'LK'  => __( 'Sri Lanka', 'rank-math-pro' ),
			'SD'  => __( 'Sudan', 'rank-math-pro' ),
			'SR'  => __( 'Suriname', 'rank-math-pro' ),
			'SJ'  => __( 'Svalbard and Jan Mayen', 'rank-math-pro' ),
			'SZ'  => __( 'Swaziland', 'rank-math-pro' ),
			'SE'  => __( 'Sweden', 'rank-math-pro' ),
			'CH'  => __( 'Switzerland', 'rank-math-pro' ),
			'SY'  => __( 'Syrian Arab Republic', 'rank-math-pro' ),
			'TW'  => __( 'Taiwan, Province of China', 'rank-math-pro' ),
			'TJ'  => __( 'Tajikistan', 'rank-math-pro' ),
			'TZ'  => __( 'Tanzania, United Republic of', 'rank-math-pro' ),
			'TH'  => __( 'Thailand', 'rank-math-pro' ),
			'TL'  => __( 'Timor-Leste', 'rank-math-pro' ),
			'TG'  => __( 'Togo', 'rank-math-pro' ),
			'TK'  => __( 'Tokelau', 'rank-math-pro' ),
			'TO'  => __( 'Tonga', 'rank-math-pro' ),
			'TT'  => __( 'Trinidad and Tobago', 'rank-math-pro' ),
			'TN'  => __( 'Tunisia', 'rank-math-pro' ),
			'TR'  => __( 'Turkey', 'rank-math-pro' ),
			'TM'  => __( 'Turkmenistan', 'rank-math-pro' ),
			'TC'  => __( 'Turks and Caicos Islands', 'rank-math-pro' ),
			'TV'  => __( 'Tuvalu', 'rank-math-pro' ),
			'UG'  => __( 'Uganda', 'rank-math-pro' ),
			'UA'  => __( 'Ukraine', 'rank-math-pro' ),
			'AE'  => __( 'United Arab Emirates', 'rank-math-pro' ),
			'GB'  => __( 'United Kingdom', 'rank-math-pro' ),
			'US'  => __( 'United States', 'rank-math-pro' ),
			'UM'  => __( 'United States Minor Outlying Islands', 'rank-math-pro' ),
			'UY'  => __( 'Uruguay', 'rank-math-pro' ),
			'UZ'  => __( 'Uzbekistan', 'rank-math-pro' ),
			'VU'  => __( 'Vanuatu', 'rank-math-pro' ),
			'VE'  => __( 'Venezuela', 'rank-math-pro' ),
			'VN'  => __( 'Viet Nam', 'rank-math-pro' ),
			'VG'  => __( 'Virgin Islands, British', 'rank-math-pro' ),
			'VI'  => __( 'Virgin Islands, U.S.', 'rank-math-pro' ),
			'WF'  => __( 'Wallis and Futuna', 'rank-math-pro' ),
			'EH'  => __( 'Western Sahara', 'rank-math-pro' ),
			'YE'  => __( 'Yemen', 'rank-math-pro' ),
			'ZM'  => __( 'Zambia', 'rank-math-pro' ),
			'ZW'  => __( 'Zimbabwe', 'rank-math-pro' ),
		];
	}

	/**
	 * Country.
	 *
	 * @return array
	 */
	public static function choices_countries_3() {
		return [
			'all' => __( 'Worldwide', 'rank-math-pro' ),
			'AFG' => __( 'Afghanistan', 'rank-math-pro' ),
			'ALA' => __( 'Aland Islands', 'rank-math-pro' ),
			'ALB' => __( 'Albania', 'rank-math-pro' ),
			'DZA' => __( 'Algeria', 'rank-math-pro' ),
			'ASM' => __( 'American Samoa', 'rank-math-pro' ),
			'AND' => __( 'Andorra', 'rank-math-pro' ),
			'AGO' => __( 'Angola', 'rank-math-pro' ),
			'AIA' => __( 'Anguilla', 'rank-math-pro' ),
			'ATA' => __( 'Antarctica', 'rank-math-pro' ),
			'ATG' => __( 'Antigua & Barbuda', 'rank-math-pro' ),
			'ARG' => __( 'Argentina', 'rank-math-pro' ),
			'ARM' => __( 'Armenia', 'rank-math-pro' ),
			'ABW' => __( 'Aruba', 'rank-math-pro' ),
			'AUS' => __( 'Australia', 'rank-math-pro' ),
			'AUT' => __( 'Austria', 'rank-math-pro' ),
			'AZE' => __( 'Azerbaijan', 'rank-math-pro' ),
			'BHS' => __( 'Bahamas', 'rank-math-pro' ),
			'BHR' => __( 'Bahrain', 'rank-math-pro' ),
			'BGD' => __( 'Bangladesh', 'rank-math-pro' ),
			'BRB' => __( 'Barbados', 'rank-math-pro' ),
			'BLR' => __( 'Belarus', 'rank-math-pro' ),
			'BEL' => __( 'Belgium', 'rank-math-pro' ),
			'BLZ' => __( 'Belize', 'rank-math-pro' ),
			'BEN' => __( 'Benin', 'rank-math-pro' ),
			'BMU' => __( 'Bermuda', 'rank-math-pro' ),
			'BTN' => __( 'Bhutan', 'rank-math-pro' ),
			'BOL' => __( 'Bolivia', 'rank-math-pro' ),
			'BIH' => __( 'Bosnia & Herzegovina', 'rank-math-pro' ),
			'BWA' => __( 'Botswana', 'rank-math-pro' ),
			'BRA' => __( 'Brazil', 'rank-math-pro' ),
			'IOT' => __( 'British Indian Ocean Territory', 'rank-math-pro' ),
			'VGB' => __( 'British Virgin Islands', 'rank-math-pro' ),
			'BRN' => __( 'Brunei', 'rank-math-pro' ),
			'BGR' => __( 'Bulgaria', 'rank-math-pro' ),
			'BFA' => __( 'Burkina Faso', 'rank-math-pro' ),
			'BDI' => __( 'Burundi', 'rank-math-pro' ),
			'KHM' => __( 'Cambodia', 'rank-math-pro' ),
			'CMR' => __( 'Cameroon', 'rank-math-pro' ),
			'CAN' => __( 'Canada', 'rank-math-pro' ),
			'CPV' => __( 'Cape Verde', 'rank-math-pro' ),
			'BES' => __( 'Caribbean Netherlands', 'rank-math-pro' ),
			'CYM' => __( 'Cayman Islands', 'rank-math-pro' ),
			'CAF' => __( 'Central African Republic', 'rank-math-pro' ),
			'TCD' => __( 'Chad', 'rank-math-pro' ),
			'CHL' => __( 'Chile', 'rank-math-pro' ),
			'CHN' => __( 'China', 'rank-math-pro' ),
			'CXR' => __( 'Christmas Island', 'rank-math-pro' ),
			'COL' => __( 'Colombia', 'rank-math-pro' ),
			'COM' => __( 'Comoros', 'rank-math-pro' ),
			'COG' => __( 'Congo - Brazzaville', 'rank-math-pro' ),
			'COD' => __( 'Congo - Kinshasa', 'rank-math-pro' ),
			'COK' => __( 'Cook Islands', 'rank-math-pro' ),
			'CRI' => __( 'Costa Rica', 'rank-math-pro' ),
			'HRV' => __( 'Croatia', 'rank-math-pro' ),
			'CUB' => __( 'Cuba', 'rank-math-pro' ),
			'CUW' => __( 'Curaçao', 'rank-math-pro' ),
			'CYP' => __( 'Cyprus', 'rank-math-pro' ),
			'CZE' => __( 'Czechia', 'rank-math-pro' ),
			'DJI' => __( "Côte d'Ivoire", 'rank-math-pro' ),
			'DNK' => __( 'Denmark', 'rank-math-pro' ),
			'DJI' => __( 'Djibouti', 'rank-math-pro' ),
			'DMA' => __( 'Dominica', 'rank-math-pro' ),
			'DOM' => __( 'Dominican Republic', 'rank-math-pro' ),
			'ECU' => __( 'Ecuador', 'rank-math-pro' ),
			'EGY' => __( 'Egypt', 'rank-math-pro' ),
			'SLV' => __( 'El Salvador', 'rank-math-pro' ),
			'GNQ' => __( 'Equatorial Guinea', 'rank-math-pro' ),
			'ERI' => __( 'Eritrea', 'rank-math-pro' ),
			'EST' => __( 'Estonia', 'rank-math-pro' ),
			'ETH' => __( 'Ethiopia', 'rank-math-pro' ),
			'FLK' => __( 'Falkland Islands (Islas Malvinas)', 'rank-math-pro' ),
			'FRO' => __( 'Faroe Islands', 'rank-math-pro' ),
			'FJI' => __( 'Fiji', 'rank-math-pro' ),
			'FIN' => __( 'Finland', 'rank-math-pro' ),
			'FRA' => __( 'France', 'rank-math-pro' ),
			'GUF' => __( 'French Guiana', 'rank-math-pro' ),
			'PYF' => __( 'French Polynesia', 'rank-math-pro' ),
			'GAB' => __( 'Gabon', 'rank-math-pro' ),
			'GMB' => __( 'Gambia', 'rank-math-pro' ),
			'GEO' => __( 'Georgia', 'rank-math-pro' ),
			'DEU' => __( 'Germany', 'rank-math-pro' ),
			'GHA' => __( 'Ghana', 'rank-math-pro' ),
			'GIB' => __( 'Gibraltar', 'rank-math-pro' ),
			'GRC' => __( 'Greece', 'rank-math-pro' ),
			'GRL' => __( 'Greenland', 'rank-math-pro' ),
			'GRD' => __( 'Grenada', 'rank-math-pro' ),
			'GLP' => __( 'Guadeloupe', 'rank-math-pro' ),
			'GUM' => __( 'Guam', 'rank-math-pro' ),
			'GTM' => __( 'Guatemala', 'rank-math-pro' ),
			'GGY' => __( 'Guernsey', 'rank-math-pro' ),
			'GIN' => __( 'Guinea', 'rank-math-pro' ),
			'GNB' => __( 'Guinea-Bissau', 'rank-math-pro' ),
			'GUY' => __( 'Guyana', 'rank-math-pro' ),
			'HTI' => __( 'Haiti', 'rank-math-pro' ),
			'HND' => __( 'Honduras', 'rank-math-pro' ),
			'HKG' => __( 'Hong Kong', 'rank-math-pro' ),
			'HUN' => __( 'Hungary', 'rank-math-pro' ),
			'ISL' => __( 'Iceland', 'rank-math-pro' ),
			'IND' => __( 'India', 'rank-math-pro' ),
			'IDN' => __( 'Indonesia', 'rank-math-pro' ),
			'IRN' => __( 'Iran', 'rank-math-pro' ),
			'IRQ' => __( 'Iraq', 'rank-math-pro' ),
			'IRL' => __( 'Ireland', 'rank-math-pro' ),
			'IMN' => __( 'Isle of Man', 'rank-math-pro' ),
			'ISR' => __( 'Israel', 'rank-math-pro' ),
			'ITA' => __( 'Italy', 'rank-math-pro' ),
			'JAM' => __( 'Jamaica', 'rank-math-pro' ),
			'JPN' => __( 'Japan', 'rank-math-pro' ),
			'JEY' => __( 'Jersey', 'rank-math-pro' ),
			'JOR' => __( 'Jordan', 'rank-math-pro' ),
			'KAZ' => __( 'Kazakhstan', 'rank-math-pro' ),
			'KEN' => __( 'Kenya', 'rank-math-pro' ),
			'KIR' => __( 'Kiribati', 'rank-math-pro' ),
			'XKK' => __( 'Kosovo', 'rank-math-pro' ),
			'KWT' => __( 'Kuwait', 'rank-math-pro' ),
			'KGZ' => __( 'Kyrgyzstan', 'rank-math-pro' ),
			'LAO' => __( 'Laos', 'rank-math-pro' ),
			'LBN' => __( 'Lebanon', 'rank-math-pro' ),
			'LSO' => __( 'Lesotho', 'rank-math-pro' ),
			'LBR' => __( 'Liberia', 'rank-math-pro' ),
			'LBY' => __( 'Libya', 'rank-math-pro' ),
			'LIE' => __( 'Liechtenstein', 'rank-math-pro' ),
			'LTU' => __( 'Lithuania', 'rank-math-pro' ),
			'LUX' => __( 'Luxembourg', 'rank-math-pro' ),
			'MAC' => __( 'Macau', 'rank-math-pro' ),
			'MKD' => __( 'Macedonia', 'rank-math-pro' ),
			'MDG' => __( 'Madagascar', 'rank-math-pro' ),
			'MWI' => __( 'Malawi', 'rank-math-pro' ),
			'MYS' => __( 'Malaysia', 'rank-math-pro' ),
			'MDV' => __( 'Maldives', 'rank-math-pro' ),
			'MLI' => __( 'Mali', 'rank-math-pro' ),
			'MLT' => __( 'Malta', 'rank-math-pro' ),
			'MHL' => __( 'Marshall Islands', 'rank-math-pro' ),
			'MTQ' => __( 'Martinique', 'rank-math-pro' ),
			'MRT' => __( 'Mauritania', 'rank-math-pro' ),
			'MUS' => __( 'Mauritius', 'rank-math-pro' ),
			'MYT' => __( 'Mayotte', 'rank-math-pro' ),
			'MEX' => __( 'Mexico', 'rank-math-pro' ),
			'FSM' => __( 'Micronesia', 'rank-math-pro' ),
			'MDA' => __( 'Moldova', 'rank-math-pro' ),
			'MCO' => __( 'Monaco', 'rank-math-pro' ),
			'MNG' => __( 'Mongolia', 'rank-math-pro' ),
			'MNE' => __( 'Montenegro', 'rank-math-pro' ),
			'MSR' => __( 'Montserrat', 'rank-math-pro' ),
			'MAR' => __( 'Morocco', 'rank-math-pro' ),
			'MOZ' => __( 'Mozambique', 'rank-math-pro' ),
			'MMR' => __( 'Myanmar (Burma)', 'rank-math-pro' ),
			'NAM' => __( 'Namibia', 'rank-math-pro' ),
			'NRU' => __( 'Nauru', 'rank-math-pro' ),
			'NPL' => __( 'Nepal', 'rank-math-pro' ),
			'NLD' => __( 'Netherlands', 'rank-math-pro' ),
			'NCL' => __( 'New Caledonia', 'rank-math-pro' ),
			'NZL' => __( 'New Zealand', 'rank-math-pro' ),
			'NIC' => __( 'Nicaragua', 'rank-math-pro' ),
			'NER' => __( 'Niger', 'rank-math-pro' ),
			'NGA' => __( 'Nigeria', 'rank-math-pro' ),
			'NIU' => __( 'Niue', 'rank-math-pro' ),
			'NFK' => __( 'Norfolk Island', 'rank-math-pro' ),
			'PRK' => __( 'North Korea', 'rank-math-pro' ),
			'MNP' => __( 'Northern Mariana Islands', 'rank-math-pro' ),
			'NOR' => __( 'Norway', 'rank-math-pro' ),
			'OMN' => __( 'Oman', 'rank-math-pro' ),
			'PAK' => __( 'Pakistan', 'rank-math-pro' ),
			'PLW' => __( 'Palau', 'rank-math-pro' ),
			'PSE' => __( 'Palestine', 'rank-math-pro' ),
			'PAN' => __( 'Panama', 'rank-math-pro' ),
			'PNG' => __( 'Papua New Guinea', 'rank-math-pro' ),
			'PRY' => __( 'Paraguay', 'rank-math-pro' ),
			'PER' => __( 'Peru', 'rank-math-pro' ),
			'PHL' => __( 'Philippines', 'rank-math-pro' ),
			'POL' => __( 'Poland', 'rank-math-pro' ),
			'PRT' => __( 'Portugal', 'rank-math-pro' ),
			'PRI' => __( 'Puerto Rico', 'rank-math-pro' ),
			'QAT' => __( 'Qatar', 'rank-math-pro' ),
			'ROU' => __( 'Romania', 'rank-math-pro' ),
			'RUS' => __( 'Russia', 'rank-math-pro' ),
			'RWA' => __( 'Rwanda', 'rank-math-pro' ),
			'REU' => __( 'Réunion', 'rank-math-pro' ),
			'WSM' => __( 'Samoa', 'rank-math-pro' ),
			'SMR' => __( 'San Marino', 'rank-math-pro' ),
			'SAU' => __( 'Saudi Arabia', 'rank-math-pro' ),
			'SEN' => __( 'Senegal', 'rank-math-pro' ),
			'SRB' => __( 'Serbia', 'rank-math-pro' ),
			'SYC' => __( 'Seychelles', 'rank-math-pro' ),
			'SLE' => __( 'Sierra Leone', 'rank-math-pro' ),
			'SGP' => __( 'Singapore', 'rank-math-pro' ),
			'SXM' => __( 'Sint Maarten', 'rank-math-pro' ),
			'SVK' => __( 'Slovakia', 'rank-math-pro' ),
			'SVN' => __( 'Slovenia', 'rank-math-pro' ),
			'SLB' => __( 'Solomon Islands', 'rank-math-pro' ),
			'SOM' => __( 'Somalia', 'rank-math-pro' ),
			'ZAF' => __( 'South Africa', 'rank-math-pro' ),
			'KOR' => __( 'South Korea', 'rank-math-pro' ),
			'SSD' => __( 'South Sudan', 'rank-math-pro' ),
			'ESP' => __( 'Spain', 'rank-math-pro' ),
			'LKA' => __( 'Sri Lanka', 'rank-math-pro' ),
			'SHN' => __( 'St. Helena', 'rank-math-pro' ),
			'KNA' => __( 'St. Kitts & Nevis', 'rank-math-pro' ),
			'LCA' => __( 'St. Lucia', 'rank-math-pro' ),
			'MAF' => __( 'St. Martin', 'rank-math-pro' ),
			'SPM' => __( 'St. Pierre & Miquelon', 'rank-math-pro' ),
			'VCT' => __( 'St. Vincent & Grenadines', 'rank-math-pro' ),
			'SDN' => __( 'Sudan', 'rank-math-pro' ),
			'SUR' => __( 'Suriname', 'rank-math-pro' ),
			'SJM' => __( 'Svalbard & Jan Mayen', 'rank-math-pro' ),
			'SWZ' => __( 'Swaziland', 'rank-math-pro' ),
			'SWE' => __( 'Sweden', 'rank-math-pro' ),
			'CHE' => __( 'Switzerland', 'rank-math-pro' ),
			'SYR' => __( 'Syria', 'rank-math-pro' ),
			'STP' => __( 'São Tomé & Príncipe', 'rank-math-pro' ),
			'TWN' => __( 'Taiwan', 'rank-math-pro' ),
			'TJK' => __( 'Tajikistan', 'rank-math-pro' ),
			'TZA' => __( 'Tanzania', 'rank-math-pro' ),
			'THA' => __( 'Thailand', 'rank-math-pro' ),
			'TLS' => __( 'Timor-Leste', 'rank-math-pro' ),
			'TGO' => __( 'Togo', 'rank-math-pro' ),
			'TON' => __( 'Tonga', 'rank-math-pro' ),
			'TTO' => __( 'Trinidad & Tobago', 'rank-math-pro' ),
			'TUN' => __( 'Tunisia', 'rank-math-pro' ),
			'TUR' => __( 'Turkey', 'rank-math-pro' ),
			'TKM' => __( 'Turkmenistan', 'rank-math-pro' ),
			'TCA' => __( 'Turks & Caicos Islands', 'rank-math-pro' ),
			'TUV' => __( 'Tuvalu', 'rank-math-pro' ),
			'VIR' => __( 'U.S. Virgin Islands', 'rank-math-pro' ),
			'UGA' => __( 'Uganda', 'rank-math-pro' ),
			'UKR' => __( 'Ukraine', 'rank-math-pro' ),
			'ARE' => __( 'United Arab Emirates', 'rank-math-pro' ),
			'GBR' => __( 'United Kingdom', 'rank-math-pro' ),
			'USA' => __( 'United States', 'rank-math-pro' ),
			'URY' => __( 'Uruguay', 'rank-math-pro' ),
			'UZB' => __( 'Uzbekistan', 'rank-math-pro' ),
			'VUT' => __( 'Vanuatu', 'rank-math-pro' ),
			'VEN' => __( 'Venezuela', 'rank-math-pro' ),
			'VNM' => __( 'Vietnam', 'rank-math-pro' ),
			'WLF' => __( 'Wallis & Futuna', 'rank-math-pro' ),
			'ESH' => __( 'Western Sahara', 'rank-math-pro' ),
			'YEM' => __( 'Yemen', 'rank-math-pro' ),
			'ZMB' => __( 'Zambia', 'rank-math-pro' ),
			'ZWE' => __( 'Zimbabwe', 'rank-math-pro' ),
			'ZZZ' => __( 'Unknown Region', 'rank-math-pro' ),
		];
	}
}
