<?php
/**
 * The local seo settings.
 *
 * @package    RankMath
 * @subpackage RankMathPro
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

$company = [ [ 'knowledgegraph_type', 'company' ] ];
$person  = [ [ 'knowledgegraph_type', 'person' ] ];

$use_multiple_locations   = [ 'relation' => 'and' ] + $company;
$use_multiple_locations[] = [ 'use_multiple_locations', 'on' ];

$hide_on_multiple_locations   = [ 'relation' => 'and' ] + $company;
$hide_on_multiple_locations[] = [ 'use_multiple_locations', 'on', '!=' ];

$countries = [
	''   => __( 'Choose a country', 'rank-math-pro' ),
	'AX' => __( 'Åland Islands', 'rank-math-pro' ),
	'AF' => __( 'Afghanistan', 'rank-math-pro' ),
	'AL' => __( 'Albania', 'rank-math-pro' ),
	'DZ' => __( 'Algeria', 'rank-math-pro' ),
	'AD' => __( 'Andorra', 'rank-math-pro' ),
	'AO' => __( 'Angola', 'rank-math-pro' ),
	'AI' => __( 'Anguilla', 'rank-math-pro' ),
	'AQ' => __( 'Antarctica', 'rank-math-pro' ),
	'AG' => __( 'Antigua and Barbuda', 'rank-math-pro' ),
	'AR' => __( 'Argentina', 'rank-math-pro' ),
	'AM' => __( 'Armenia', 'rank-math-pro' ),
	'AW' => __( 'Aruba', 'rank-math-pro' ),
	'AU' => __( 'Australia', 'rank-math-pro' ),
	'AT' => __( 'Austria', 'rank-math-pro' ),
	'AZ' => __( 'Azerbaijan', 'rank-math-pro' ),
	'BS' => __( 'Bahamas', 'rank-math-pro' ),
	'BH' => __( 'Bahrain', 'rank-math-pro' ),
	'BD' => __( 'Bangladesh', 'rank-math-pro' ),
	'BB' => __( 'Barbados', 'rank-math-pro' ),
	'BY' => __( 'Belarus', 'rank-math-pro' ),
	'PW' => __( 'Belau', 'rank-math-pro' ),
	'BE' => __( 'Belgium', 'rank-math-pro' ),
	'BZ' => __( 'Belize', 'rank-math-pro' ),
	'BJ' => __( 'Benin', 'rank-math-pro' ),
	'BM' => __( 'Bermuda', 'rank-math-pro' ),
	'BT' => __( 'Bhutan', 'rank-math-pro' ),
	'BO' => __( 'Bolivia', 'rank-math-pro' ),
	'BQ' => __( 'Bonaire, Sint Eustatius and Saba', 'rank-math-pro' ),
	'BA' => __( 'Bosnia and Herzegovina', 'rank-math-pro' ),
	'BW' => __( 'Botswana', 'rank-math-pro' ),
	'BV' => __( 'Bouvet Island', 'rank-math-pro' ),
	'BR' => __( 'Brazil', 'rank-math-pro' ),
	'IO' => __( 'British Indian Ocean Territory', 'rank-math-pro' ),
	'VG' => __( 'British Virgin Islands', 'rank-math-pro' ),
	'BN' => __( 'Brunei', 'rank-math-pro' ),
	'BG' => __( 'Bulgaria', 'rank-math-pro' ),
	'BF' => __( 'Burkina Faso', 'rank-math-pro' ),
	'BI' => __( 'Burundi', 'rank-math-pro' ),
	'KH' => __( 'Cambodia', 'rank-math-pro' ),
	'CM' => __( 'Cameroon', 'rank-math-pro' ),
	'CA' => __( 'Canada', 'rank-math-pro' ),
	'CV' => __( 'Cape Verde', 'rank-math-pro' ),
	'KY' => __( 'Cayman Islands', 'rank-math-pro' ),
	'CF' => __( 'Central African Republic', 'rank-math-pro' ),
	'TD' => __( 'Chad', 'rank-math-pro' ),
	'CL' => __( 'Chile', 'rank-math-pro' ),
	'CN' => __( 'China', 'rank-math-pro' ),
	'CX' => __( 'Christmas Island', 'rank-math-pro' ),
	'CC' => __( 'Cocos (Keeling) Islands', 'rank-math-pro' ),
	'CO' => __( 'Colombia', 'rank-math-pro' ),
	'KM' => __( 'Comoros', 'rank-math-pro' ),
	'CG' => __( 'Congo (Brazzaville)', 'rank-math-pro' ),
	'CD' => __( 'Congo (Kinshasa)', 'rank-math-pro' ),
	'CK' => __( 'Cook Islands', 'rank-math-pro' ),
	'CR' => __( 'Costa Rica', 'rank-math-pro' ),
	'HR' => __( 'Croatia', 'rank-math-pro' ),
	'CU' => __( 'Cuba', 'rank-math-pro' ),
	'CW' => __( 'Curaçao', 'rank-math-pro' ),
	'CY' => __( 'Cyprus', 'rank-math-pro' ),
	'CZ' => __( 'Czech Republic', 'rank-math-pro' ),
	'DK' => __( 'Denmark', 'rank-math-pro' ),
	'DJ' => __( 'Djibouti', 'rank-math-pro' ),
	'DM' => __( 'Dominica', 'rank-math-pro' ),
	'DO' => __( 'Dominican Republic', 'rank-math-pro' ),
	'EC' => __( 'Ecuador', 'rank-math-pro' ),
	'EG' => __( 'Egypt', 'rank-math-pro' ),
	'SV' => __( 'El Salvador', 'rank-math-pro' ),
	'GQ' => __( 'Equatorial Guinea', 'rank-math-pro' ),
	'ER' => __( 'Eritrea', 'rank-math-pro' ),
	'EE' => __( 'Estonia', 'rank-math-pro' ),
	'ET' => __( 'Ethiopia', 'rank-math-pro' ),
	'FK' => __( 'Falkland Islands', 'rank-math-pro' ),
	'FO' => __( 'Faroe Islands', 'rank-math-pro' ),
	'FJ' => __( 'Fiji', 'rank-math-pro' ),
	'FI' => __( 'Finland', 'rank-math-pro' ),
	'FR' => __( 'France', 'rank-math-pro' ),
	'GF' => __( 'French Guiana', 'rank-math-pro' ),
	'PF' => __( 'French Polynesia', 'rank-math-pro' ),
	'TF' => __( 'French Southern Territories', 'rank-math-pro' ),
	'GA' => __( 'Gabon', 'rank-math-pro' ),
	'GM' => __( 'Gambia', 'rank-math-pro' ),
	'GE' => __( 'Georgia', 'rank-math-pro' ),
	'DE' => __( 'Germany', 'rank-math-pro' ),
	'GH' => __( 'Ghana', 'rank-math-pro' ),
	'GI' => __( 'Gibraltar', 'rank-math-pro' ),
	'GR' => __( 'Greece', 'rank-math-pro' ),
	'GL' => __( 'Greenland', 'rank-math-pro' ),
	'GD' => __( 'Grenada', 'rank-math-pro' ),
	'GP' => __( 'Guadeloupe', 'rank-math-pro' ),
	'GT' => __( 'Guatemala', 'rank-math-pro' ),
	'GG' => __( 'Guernsey', 'rank-math-pro' ),
	'GN' => __( 'Guinea', 'rank-math-pro' ),
	'GW' => __( 'Guinea-Bissau', 'rank-math-pro' ),
	'GY' => __( 'Guyana', 'rank-math-pro' ),
	'HT' => __( 'Haiti', 'rank-math-pro' ),
	'HM' => __( 'Heard Island and McDonald Islands', 'rank-math-pro' ),
	'HN' => __( 'Honduras', 'rank-math-pro' ),
	'HK' => __( 'Hong Kong', 'rank-math-pro' ),
	'HU' => __( 'Hungary', 'rank-math-pro' ),
	'IS' => __( 'Iceland', 'rank-math-pro' ),
	'IN' => __( 'India', 'rank-math-pro' ),
	'ID' => __( 'Indonesia', 'rank-math-pro' ),
	'IR' => __( 'Iran', 'rank-math-pro' ),
	'IQ' => __( 'Iraq', 'rank-math-pro' ),
	'IM' => __( 'Isle of Man', 'rank-math-pro' ),
	'IL' => __( 'Israel', 'rank-math-pro' ),
	'IT' => __( 'Italy', 'rank-math-pro' ),
	'CI' => __( 'Ivory Coast', 'rank-math-pro' ),
	'JM' => __( 'Jamaica', 'rank-math-pro' ),
	'JP' => __( 'Japan', 'rank-math-pro' ),
	'JE' => __( 'Jersey', 'rank-math-pro' ),
	'JO' => __( 'Jordan', 'rank-math-pro' ),
	'KZ' => __( 'Kazakhstan', 'rank-math-pro' ),
	'KE' => __( 'Kenya', 'rank-math-pro' ),
	'KI' => __( 'Kiribati', 'rank-math-pro' ),
	'KW' => __( 'Kuwait', 'rank-math-pro' ),
	'KG' => __( 'Kyrgyzstan', 'rank-math-pro' ),
	'LA' => __( 'Laos', 'rank-math-pro' ),
	'LV' => __( 'Latvia', 'rank-math-pro' ),
	'LB' => __( 'Lebanon', 'rank-math-pro' ),
	'LS' => __( 'Lesotho', 'rank-math-pro' ),
	'LR' => __( 'Liberia', 'rank-math-pro' ),
	'LY' => __( 'Libya', 'rank-math-pro' ),
	'LI' => __( 'Liechtenstein', 'rank-math-pro' ),
	'LT' => __( 'Lithuania', 'rank-math-pro' ),
	'LU' => __( 'Luxembourg', 'rank-math-pro' ),
	'MO' => __( 'Macao S.A.R., China', 'rank-math-pro' ),
	'MK' => __( 'Macedonia', 'rank-math-pro' ),
	'MG' => __( 'Madagascar', 'rank-math-pro' ),
	'MW' => __( 'Malawi', 'rank-math-pro' ),
	'MY' => __( 'Malaysia', 'rank-math-pro' ),
	'MV' => __( 'Maldives', 'rank-math-pro' ),
	'ML' => __( 'Mali', 'rank-math-pro' ),
	'MT' => __( 'Malta', 'rank-math-pro' ),
	'MH' => __( 'Marshall Islands', 'rank-math-pro' ),
	'MQ' => __( 'Martinique', 'rank-math-pro' ),
	'MR' => __( 'Mauritania', 'rank-math-pro' ),
	'MU' => __( 'Mauritius', 'rank-math-pro' ),
	'YT' => __( 'Mayotte', 'rank-math-pro' ),
	'MX' => __( 'Mexico', 'rank-math-pro' ),
	'FM' => __( 'Micronesia', 'rank-math-pro' ),
	'MD' => __( 'Moldova', 'rank-math-pro' ),
	'MC' => __( 'Monaco', 'rank-math-pro' ),
	'MN' => __( 'Mongolia', 'rank-math-pro' ),
	'ME' => __( 'Montenegro', 'rank-math-pro' ),
	'MS' => __( 'Montserrat', 'rank-math-pro' ),
	'MA' => __( 'Morocco', 'rank-math-pro' ),
	'MZ' => __( 'Mozambique', 'rank-math-pro' ),
	'MM' => __( 'Myanmar', 'rank-math-pro' ),
	'NA' => __( 'Namibia', 'rank-math-pro' ),
	'NR' => __( 'Nauru', 'rank-math-pro' ),
	'NP' => __( 'Nepal', 'rank-math-pro' ),
	'NL' => __( 'Netherlands', 'rank-math-pro' ),
	'AN' => __( 'Netherlands Antilles', 'rank-math-pro' ),
	'NC' => __( 'New Caledonia', 'rank-math-pro' ),
	'NZ' => __( 'New Zealand', 'rank-math-pro' ),
	'NI' => __( 'Nicaragua', 'rank-math-pro' ),
	'NE' => __( 'Niger', 'rank-math-pro' ),
	'NG' => __( 'Nigeria', 'rank-math-pro' ),
	'NU' => __( 'Niue', 'rank-math-pro' ),
	'NF' => __( 'Norfolk Island', 'rank-math-pro' ),
	'KP' => __( 'North Korea', 'rank-math-pro' ),
	'NO' => __( 'Norway', 'rank-math-pro' ),
	'OM' => __( 'Oman', 'rank-math-pro' ),
	'PK' => __( 'Pakistan', 'rank-math-pro' ),
	'PS' => __( 'Palestinian Territory', 'rank-math-pro' ),
	'PA' => __( 'Panama', 'rank-math-pro' ),
	'PG' => __( 'Papua New Guinea', 'rank-math-pro' ),
	'PY' => __( 'Paraguay', 'rank-math-pro' ),
	'PE' => __( 'Peru', 'rank-math-pro' ),
	'PH' => __( 'Philippines', 'rank-math-pro' ),
	'PN' => __( 'Pitcairn', 'rank-math-pro' ),
	'PL' => __( 'Poland', 'rank-math-pro' ),
	'PT' => __( 'Portugal', 'rank-math-pro' ),
	'QA' => __( 'Qatar', 'rank-math-pro' ),
	'IE' => __( 'Republic of Ireland', 'rank-math-pro' ),
	'RE' => __( 'Reunion', 'rank-math-pro' ),
	'RO' => __( 'Romania', 'rank-math-pro' ),
	'RU' => __( 'Russia', 'rank-math-pro' ),
	'RW' => __( 'Rwanda', 'rank-math-pro' ),
	'ST' => __( 'São Tomé and Príncipe', 'rank-math-pro' ),
	'BL' => __( 'Saint Barthélemy', 'rank-math-pro' ),
	'SH' => __( 'Saint Helena', 'rank-math-pro' ),
	'KN' => __( 'Saint Kitts and Nevis', 'rank-math-pro' ),
	'LC' => __( 'Saint Lucia', 'rank-math-pro' ),
	'SX' => __( 'Saint Martin (Dutch part)', 'rank-math-pro' ),
	'MF' => __( 'Saint Martin (French part)', 'rank-math-pro' ),
	'PM' => __( 'Saint Pierre and Miquelon', 'rank-math-pro' ),
	'VC' => __( 'Saint Vincent and the Grenadines', 'rank-math-pro' ),
	'SM' => __( 'San Marino', 'rank-math-pro' ),
	'SA' => __( 'Saudi Arabia', 'rank-math-pro' ),
	'SN' => __( 'Senegal', 'rank-math-pro' ),
	'RS' => __( 'Serbia', 'rank-math-pro' ),
	'SC' => __( 'Seychelles', 'rank-math-pro' ),
	'SL' => __( 'Sierra Leone', 'rank-math-pro' ),
	'SG' => __( 'Singapore', 'rank-math-pro' ),
	'SK' => __( 'Slovakia', 'rank-math-pro' ),
	'SI' => __( 'Slovenia', 'rank-math-pro' ),
	'SB' => __( 'Solomon Islands', 'rank-math-pro' ),
	'SO' => __( 'Somalia', 'rank-math-pro' ),
	'ZA' => __( 'South Africa', 'rank-math-pro' ),
	'GS' => __( 'South Georgia/Sandwich Islands', 'rank-math-pro' ),
	'KR' => __( 'South Korea', 'rank-math-pro' ),
	'SS' => __( 'South Sudan', 'rank-math-pro' ),
	'ES' => __( 'Spain', 'rank-math-pro' ),
	'LK' => __( 'Sri Lanka', 'rank-math-pro' ),
	'SD' => __( 'Sudan', 'rank-math-pro' ),
	'SR' => __( 'Suriname', 'rank-math-pro' ),
	'SJ' => __( 'Svalbard and Jan Mayen', 'rank-math-pro' ),
	'SZ' => __( 'Swaziland', 'rank-math-pro' ),
	'SE' => __( 'Sweden', 'rank-math-pro' ),
	'CH' => __( 'Switzerland', 'rank-math-pro' ),
	'SY' => __( 'Syria', 'rank-math-pro' ),
	'TW' => __( 'Taiwan', 'rank-math-pro' ),
	'TJ' => __( 'Tajikistan', 'rank-math-pro' ),
	'TZ' => __( 'Tanzania', 'rank-math-pro' ),
	'TH' => __( 'Thailand', 'rank-math-pro' ),
	'TL' => __( 'Timor-Leste', 'rank-math-pro' ),
	'TG' => __( 'Togo', 'rank-math-pro' ),
	'TK' => __( 'Tokelau', 'rank-math-pro' ),
	'TO' => __( 'Tonga', 'rank-math-pro' ),
	'TT' => __( 'Trinidad and Tobago', 'rank-math-pro' ),
	'TN' => __( 'Tunisia', 'rank-math-pro' ),
	'TR' => __( 'Turkey', 'rank-math-pro' ),
	'TM' => __( 'Turkmenistan', 'rank-math-pro' ),
	'TC' => __( 'Turks and Caicos Islands', 'rank-math-pro' ),
	'TV' => __( 'Tuvalu', 'rank-math-pro' ),
	'UG' => __( 'Uganda', 'rank-math-pro' ),
	'UA' => __( 'Ukraine', 'rank-math-pro' ),
	'AE' => __( 'United Arab Emirates', 'rank-math-pro' ),
	'GB' => __( 'United Kingdom (UK)', 'rank-math-pro' ),
	'US' => __( 'United States (US)', 'rank-math-pro' ),
	'UY' => __( 'Uruguay', 'rank-math-pro' ),
	'UZ' => __( 'Uzbekistan', 'rank-math-pro' ),
	'VU' => __( 'Vanuatu', 'rank-math-pro' ),
	'VA' => __( 'Vatican', 'rank-math-pro' ),
	'VE' => __( 'Venezuela', 'rank-math-pro' ),
	'VN' => __( 'Vietnam', 'rank-math-pro' ),
	'WF' => __( 'Wallis and Futuna', 'rank-math-pro' ),
	'EH' => __( 'Western Sahara', 'rank-math-pro' ),
	'WS' => __( 'Western Samoa', 'rank-math-pro' ),
	'YE' => __( 'Yemen', 'rank-math-pro' ),
	'ZM' => __( 'Zambia', 'rank-math-pro' ),
	'ZW' => __( 'Zimbabwe', 'rank-math-pro' ),
];

$cmb->add_field(
	[
		'id'      => 'knowledgegraph_type',
		'type'    => 'radio_inline',
		'name'    => esc_html__( 'Person or Company', 'rank-math-pro' ),
		'options' => [
			'person'  => esc_html__( 'Person', 'rank-math-pro' ),
			'company' => esc_html__( 'Organization', 'rank-math-pro' ),
		],
		'desc'    => esc_html__( 'Choose whether the site represents a person or an organization.', 'rank-math-pro' ),
		'default' => 'person',
	]
);

$cmb->add_field(
	[
		'id'      => 'knowledgegraph_name',
		'type'    => 'text',
		'name'    => esc_html__( 'Name', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Your name or company name', 'rank-math-pro' ),
		'default' => get_bloginfo( 'name' ),
	]
);

$cmb->add_field(
	[
		'id'      => 'knowledgegraph_logo',
		'type'    => 'file',
		'name'    => esc_html__( 'Logo', 'rank-math-pro' ),
		'desc'    => __( '<strong>Min Size: 160Χ90px, Max Size: 1920X1080px</strong>.<br /> A squared image is preferred by the search engines.', 'rank-math-pro' ),
		'options' => [ 'url' => false ],
	]
);

$cmb->add_field(
	[
		'id'      => 'url',
		'type'    => 'text_url',
		'name'    => esc_html__( 'URL', 'rank-math-pro' ),
		'desc'    => esc_html__( 'URL of the item.', 'rank-math-pro' ),
		'default' => home_url(),
	]
);

$cmb->add_field(
	[
		'id'      => 'use_multiple_locations',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Use Multiple Locations', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Once you save the changes, we will create a new custom post type called "Locations" where you can add multiple locations of your business/organization.', 'rank-math-pro' ),
		'options' => [
			'off' => esc_html__( 'Default', 'rank-math-pro' ),
			'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
		],
		'default' => 'off',
		'dep'     => $company,
	]
);

$cmb->add_field(
	[
		'id'   => 'email',
		'type' => 'text',
		'name' => esc_html__( 'Email', 'rank-math-pro' ),
		'desc' => esc_html__( 'Search engines display your email address.', 'rank-math-pro' ),
	]
);

$cmb->add_field(
	[
		'id'   => 'phone',
		'type' => 'text',
		'name' => esc_html__( 'Phone', 'rank-math-pro' ),
		'desc' => esc_html__( 'Search engines may prominently display your contact phone number for mobile users.', 'rank-math-pro' ),
		'dep'  => $person,
	]
);

$cmb->add_field(
	[
		'id'   => 'local_address',
		'type' => 'address',
		'name' => esc_html__( 'Address', 'rank-math-pro' ),
		'dep'  => [ [ 'use_multiple_locations', 'on', '!=' ] ],
	]
);

$cmb->add_field(
	[
		'id'         => 'local_address_format',
		'type'       => 'textarea_small',
		'name'       => esc_html__( 'Address Format', 'rank-math-pro' ),
		'desc'       => wp_kses_post( __( 'Format used when the address is displayed using the <code>[rank_math_contact_info]</code> shortcode.<br><strong>Available Tags: {address}, {locality}, {region}, {postalcode}, {country}, {gps}</strong>', 'rank-math-pro' ) ),
		'default'    => '{address} {locality}, {region} {postalcode}',
		'classes'    => 'rank-math-address-format',
		'attributes' => [
			'rows'        => 2,
			'placeholder' => '{address} {locality}, {region} {country}. {postalcode}.',
		],
		'dep'        => $company,
	]
);

$cmb->add_field(
	[
		'id'         => 'local_business_type',
		'type'       => 'select',
		'name'       => esc_html__( 'Business Type', 'rank-math-pro' ),
		'options'    => Helper::choices_business_types( true ),
		'attributes' => ( 'data-s2' ),
		'dep'        => $hide_on_multiple_locations,
	]
);

$opening_hours = $cmb->add_field(
	[
		'id'      => 'opening_hours',
		'type'    => 'group',
		'name'    => esc_html__( 'Opening Hours', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Select opening hours. You can add multiple sets if you have different opening or closing hours on some days or if you have a mid-day break. Times are specified using 24:00 time.', 'rank-math-pro' ),
		'options' => [
			'add_button'    => esc_html__( 'Add time', 'rank-math-pro' ),
			'remove_button' => esc_html__( 'Remove', 'rank-math-pro' ),
		],
		'dep'     => $hide_on_multiple_locations,
		'classes' => 'cmb-group-text-only',
	]
);

$cmb->add_group_field(
	$opening_hours,
	[
		'id'      => 'day',
		'type'    => 'select',
		'options' => [
			'Monday'    => esc_html__( 'Monday', 'rank-math-pro' ),
			'Tuesday'   => esc_html__( 'Tuesday', 'rank-math-pro' ),
			'Wednesday' => esc_html__( 'Wednesday', 'rank-math-pro' ),
			'Thursday'  => esc_html__( 'Thursday', 'rank-math-pro' ),
			'Friday'    => esc_html__( 'Friday', 'rank-math-pro' ),
			'Saturday'  => esc_html__( 'Saturday', 'rank-math-pro' ),
			'Sunday'    => esc_html__( 'Sunday', 'rank-math-pro' ),
		],
	]
);

$cmb->add_group_field(
	$opening_hours,
	[
		'id'         => 'time',
		'type'       => 'text',
		'attributes' => [ 'placeholder' => esc_html__( 'e.g. 09:00-17:00', 'rank-math-pro' ) ],
	]
);

$cmb->add_field(
	[
		'id'      => 'opening_hours_format',
		'type'    => 'switch',
		'name'    => esc_html__( 'Opening Hours Format', 'rank-math-pro' ),
		'options' => [
			'off' => '24:00',
			'on'  => '12:00',
		],
		'desc'    => esc_html__( 'Time format used in the contact shortcode.', 'rank-math-pro' ),
		'default' => 'off',
		'dep'     => $company,
	]
);

$phones = $cmb->add_field(
	[
		'id'      => 'phone_numbers',
		'type'    => 'group',
		'name'    => esc_html__( 'Phone Number', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Search engines may prominently display your contact phone number for mobile users.', 'rank-math-pro' ),
		'options' => [
			'add_button'    => esc_html__( 'Add number', 'rank-math-pro' ),
			'remove_button' => esc_html__( 'Remove', 'rank-math-pro' ),
		],
		'dep'     => $hide_on_multiple_locations,
		'classes' => 'cmb-group-text-only',
	]
);

$cmb->add_group_field(
	$phones,
	[
		'id'      => 'type',
		'type'    => 'select',
		'options' => Helper::choices_phone_types(),
		'default' => 'customer_support',
	]
);
$cmb->add_group_field(
	$phones,
	[
		'id'         => 'number',
		'type'       => 'text',
		'attributes' => [ 'placeholder' => esc_html__( 'Format: +1-401-555-1212', 'rank-math-pro' ) ],
	]
);

$cmb->add_field(
	[
		'id'   => 'price_range',
		'type' => 'text',
		'name' => esc_html__( 'Price Range', 'rank-math-pro' ),
		'desc' => esc_html__( 'The price range of the business, for example $$$.', 'rank-math-pro' ),
		'dep'  => $hide_on_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'hide_opening_hours',
		'type'    => 'switch',
		'name'    => esc_html__( 'Hide Opening Hours', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Don\'t add opening hours data in Schema', 'rank-math-pro' ),
		'options' => [
			'off' => esc_html__( 'No', 'rank-math-pro' ),
			'on'  => esc_html__( 'Yes', 'rank-math-pro' ),
		],
		'default' => 'off',
		'dep'     => $use_multiple_locations,
	]
);
$hide_opening_hours   = [ 'relation' => 'and' ] + $use_multiple_locations;
$hide_opening_hours[] = [ 'hide_opening_hours', 'on', '!=' ];
$cmb->add_field(
	[
		'id'      => 'closed_label',
		'type'    => 'text',
		'name'    => esc_html__( 'Closed label', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Text to show in Opening hours when business is closed.', 'rank-math-pro' ),
		'default' => 'Closed',
		'dep'     => $hide_opening_hours,
	]
);

$cmb->add_field(
	[
		'id'      => 'open_24_7',
		'type'    => 'text',
		'name'    => esc_html__( 'Open 24/7 label', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Select the text to display alongside your opening hours when your store is open 24/7.', 'rank-math-pro' ),
		'default' => 'Open 24/7',
		'dep'     => $hide_opening_hours,
	]
);

$cmb->add_field(
	[
		'id'      => 'open_24h',
		'type'    => 'text',
		'name'    => esc_html__( 'Open 24h label', 'rank-math-pro' ),
		'default' => 'Open 24h',
		'dep'     => $hide_opening_hours,
	]
);

$cmb->add_field(
	[
		'id'      => 'map_unit',
		'type'    => 'select',
		'name'    => esc_html__( 'Measurement system', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Select your preferred measurement system (miles or kilometers).', 'rank-math-pro' ),
		'options' => [
			'kilometers' => esc_html__( 'Kilometers', 'rank-math-pro' ),
			'miles'      => esc_html__( 'Miles', 'rank-math-pro' ),
		],
		'default' => 'kilometers',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'map_style',
		'type'    => 'select',
		'name'    => esc_html__( 'Map Style', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Select the map style you wish to use on the frontend of your website.', 'rank-math-pro' ),
		'options' => [
			'hybrid'    => esc_html__( 'Hybrid', 'rank-math-pro' ),
			'satellite' => esc_html__( 'Satellite', 'rank-math-pro' ),
			'roadmap'   => esc_html__( 'Roadmap', 'rank-math-pro' ),
			'terrain'   => esc_html__( 'Terrain', 'rank-math-pro' ),
		],
		'default' => 'hybrid',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'              => 'limit_results',
		'type'            => 'text',
		'name'            => esc_html__( 'Maximum number of locations to show', 'rank-math-pro' ),
		'desc'            => esc_html__( 'Limit the number of locations shown on your website to those nearest your user.', 'rank-math-pro' ),
		'default'         => 10,
		'dep'             => $use_multiple_locations,
		'attributes'      => [
			'type'    => 'number',
			'pattern' => '\d*',
			'class'   => 'small-text',
		],
		'sanitization_cb' => 'absint',
		'escape_cb'       => 'absint',
	]
);

$cmb->add_field(
	[
		'id'         => 'primary_country',
		'type'       => 'select',
		'options'    => $countries,
		'name'       => esc_html__( 'Primary Country', 'rank-math-pro' ),
		'desc'       => esc_html__( 'Select your organization’s primary country of operation. This helps improve the accuracy of the store locator.', 'rank-math-pro' ),
		'attributes' => ( 'data-s2' ),
		'dep'        => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'route_label',
		'type'    => 'text',
		'name'    => esc_html__( 'Show Route label', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Customize the label of the option users can use can click to get directions to your business location on the frontend.', 'rank-math-pro' ),
		'default' => 'My Route',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'enable_location_detection',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Location Detection', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Automatically detect the user\'s location as the starting point.', 'rank-math-pro' ),
		'options' => [
			'off' => esc_html__( 'Default', 'rank-math-pro' ),
			'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
		],
		'default' => 'off',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'same_organization_locations',
		'type'    => 'toggle',
		'name'    => esc_html__( 'All Locations are part of the same Organization', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Enable if all of the locations where you serve customers are a part of the same legal entity.', 'rank-math-pro' ),
		'options' => [
			'off' => esc_html__( 'Default', 'rank-math-pro' ),
			'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
		],
		'default' => 'off',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'locations_enhanced_search',
		'type'    => 'toggle',
		'name'    => esc_html__( 'Enhanced Search', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Include business locations in site-wide search results.', 'rank-math-pro' ),
		'options' => [
			'off' => esc_html__( 'Default', 'rank-math-pro' ),
			'on'  => esc_html__( 'Custom', 'rank-math-pro' ),
		],
		'default' => 'off',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'         => 'maps_api_key',
		'type'       => 'text',
		'name'       => esc_html__( 'Google Maps API Key', 'rank-math-pro' ),
		/* translators: %s expands to "Google Maps Embed API" https://developers.google.com/maps/documentation/embed/ */
		'desc'       => sprintf( esc_html__( 'An API Key is required to display embedded Google Maps on your site. Get it here: %s', 'rank-math-pro' ), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">' . __( 'Google Maps Embed API', 'rank-math-pro' ) . '</a>' ),
		'dep'        => $company,
		'attributes' => [ 'type' => 'password' ],
	]
);

$cmb->add_field(
	[
		'id'   => 'geo',
		'type' => 'text',
		'name' => esc_html__( 'Geo Coordinates', 'rank-math-pro' ),
		'desc' => esc_html__( 'Latitude and longitude values separated by comma.', 'rank-math-pro' ),
		'dep'  => $company,
	]
);

$cmb->add_field(
	[
		'id'      => 'locations_post_type_base',
		'type'    => 'text',
		'name'    => esc_html__( 'Locations Post Type Base', 'rank-math-pro' ),
		'default' => 'locations',
		'desc'    => '<code>' . home_url( 'locations/africa/' ) . '</code>',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'locations_category_base',
		'type'    => 'text',
		'name'    => esc_html__( 'Locations Category Base', 'rank-math-pro' ),
		'default' => 'locations-category',
		'desc'    => '<code>' . home_url( 'locations-category/regional-offices/' ) . '</code>',
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'locations_post_type_label',
		'type'    => 'text',
		'name'    => esc_html__( 'Location Post Type Name', 'rank-math-pro' ),
		'default' => 'RM Locations',
		'desc'    => esc_html__( 'The label that appears in the sidebar for the custom post type where you can add & manage locations.', 'rank-math-pro' ),
		'dep'     => $use_multiple_locations,
	]
);

$cmb->add_field(
	[
		'id'      => 'locations_post_type_plural_label',
		'type'    => 'text',
		'name'    => esc_html__( 'Locations Post Type Name (Plural)', 'rank-math-pro' ),
		'default' => 'RM Locations',
		'desc'    => esc_html__( 'As above, but the label that would be applicable for more than one location (default: RM Locations).', 'rank-math-pro' ),
		'dep'     => $use_multiple_locations,
	]
);

$about_page    = Helper::get_settings( 'titles.local_seo_about_page' );
$about_options = [ '' => __( 'Select Page', 'rank-math-pro' ) ];
if ( $about_page ) {
	$about_options[ $about_page ] = get_the_title( $about_page );
}
$cmb->add_field(
	[
		'id'         => 'local_seo_about_page',
		'type'       => 'select',
		'options'    => $about_options,
		'name'       => esc_html__( 'About Page', 'rank-math-pro' ),
		'desc'       => esc_html__( 'Select a page on your site where you want to show the LocalBusiness meta data.', 'rank-math-pro' ),
		'attributes' => ( 'data-s2-pages' ),
	]
);

$contact_page    = Helper::get_settings( 'titles.local_seo_contact_page' );
$contact_options = [ '' => __( 'Select Page', 'rank-math-pro' ) ];
if ( $contact_page ) {
	$contact_options[ $contact_page ] = get_the_title( $contact_page );
}
$cmb->add_field(
	[
		'id'         => 'local_seo_contact_page',
		'type'       => 'select',
		'options'    => $contact_options,
		'name'       => esc_html__( 'Contact Page', 'rank-math-pro' ),
		'desc'       => esc_html__( 'Select a page on your site where you want to show the LocalBusiness meta data.', 'rank-math-pro' ),
		'attributes' => ( 'data-s2-pages' ),
	]
);
