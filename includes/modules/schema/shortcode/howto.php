<?php
/**
 * Shortcode - HowTo
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

use RankMath\Helper;
use RankMath\Schema\Block_HowTo;

defined( 'ABSPATH' ) || exit;

$has_duration = false;
$days         = 0;
$hours        = 0;
$minutes      = 0;
if ( isset( $schema['totalTime'] ) && Helper::get_formatted_duration( $schema['totalTime'] ) ) {
	$has_duration = true;
	$duration     = new \DateInterval( $schema['totalTime'] );
	$days         = $duration->d;
	$hours        = $duration->h;
	$minutes      = $duration->i;
}

$attributes = [
	'days'                  => $days,
	'hours'                 => $hours,
	'minutes'               => $minutes,
	'hasDuration'           => $has_duration,
	'material'              => isset( $schema['material'] ) ? $schema['material'] : '',
	'imageUrl'              => isset( $schema['image']['url'] ) ? $schema['image']['url'] : '',
	'description'           => isset( $schema['description'] ) ? $schema['description'] : '',
	'estimatedCost'         => isset( $schema['estimatedCost']['value'] ) ? $schema['estimatedCost']['value'] : '',
	'estimatedCostCurrency' => isset( $schema['estimatedCost']['currency'] ) ? $schema['estimatedCost']['currency'] : '',
	'steps'                 => [],
	'supply'                => '',
	'tools'                 => '',
];

if ( ! empty( $schema['step'] ) ) {
	foreach ( $schema['step'] as $step ) {
		$attributes['steps'][] = [
			'visible'  => 1,
			'title'    => $step['name'],
			'imageUrl' => $step['image']['url'],
			'content'  => $step['itemListElement']['text'],
		];
	}
}

if ( ! empty( $schema['supply'] ) ) {
	foreach ( $schema['supply'] as $supply ) {
		$attributes['supply'] .= $supply['name'] . PHP_EOL;
	}
}

if ( ! empty( $schema['tool'] ) ) {
	foreach ( $schema['tool'] as $tool ) {
		$attributes['tools'] .= $tool['name'] . PHP_EOL;
	}
}

// No steps.
if ( empty( $attributes['steps'] ) ) {
	return;
}

echo Block_HowTo::markup( $attributes ); // phpcs:ignore
