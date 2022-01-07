<?php
/**
 * Shortcode - Job Posting
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

defined( 'ABSPATH' ) || exit;

$shortcode->get_title();
$shortcode->get_image();
?>
<div class="rank-math-review-data">

	<?php $shortcode->get_description(); ?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Salary', 'rank-math-pro' ),
		'baseSalary.value.value'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Salary Currency', 'rank-math-pro' ),
		'baseSalary.currency'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Payroll', 'rank-math-pro' ),
		'baseSalary.value.unitText'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Date Posted', 'rank-math-pro' ),
		'datePosted'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Posting Expiry Date', 'rank-math-pro' ),
		'validThrough'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Unpublish when expired', 'rank-math-pro' ),
		'unpublish'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Employment Type ', 'rank-math-pro' ),
		'employmentType'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Hiring Organization ', 'rank-math-pro' ),
		'hiringOrganization.name'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Organization URL', 'rank-math-pro' ),
		'hiringOrganization.sameAs'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Organization Logo', 'rank-math-pro' ),
		'hiringOrganization.logo'
	);
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Job Type', 'rank-math-pro' ),
		'jobLocationType'
	);
	?>

	<?php
	$locations = $shortcode->get_field_value( 'applicantLocationRequirements' );
	if ( ! empty( $locations ) ) {
		$locations = array_map(
			function( $location ) {
				return ! empty( $location['name'] ) ? $location['name'] : '';
			},
			$locations
		);

		$shortcode->output_field(
			esc_html__( 'Job Location', 'rank-math-pro' ),
			'<ul><li>' . join( '</li><li>', $locations ) . '</li></ul>'
		);
	}
	?>

	<?php
	$shortcode->get_field(
		esc_html__( 'Location', 'rank-math-pro' ),
		'jobLocation.address'
	);
	?>

	<?php
	$education = $shortcode->get_field_value( 'educationRequirements' );
	if ( is_array( $education ) && ! empty( $education ) ) {
		$education = array_map(
			function( $credential ) {
				return ! empty( $credential['credentialCategory'] ) ? ucwords( $credential['credentialCategory'] ) : '';
			},
			$education
		);

		$shortcode->output_field(
			esc_html__( 'Education Required', 'rank-math-pro' ),
			'<ul><li>' . join( '</li><li>', $education ) . '</li></ul>'
		);
	}
	?>

	<?php
	$experience = $shortcode->get_field_value( 'experienceRequirements' );
	if ( is_array( $experience ) && ! empty( $experience['monthsOfExperience'] ) ) {
		$shortcode->output_field(
			esc_html__( 'Experience Required', 'rank-math-pro' ),
			$experience['monthsOfExperience'] . ' ' . esc_html__( 'Months', 'rank-math-pro' )
		);
	}
	?>

</div>
