<?php
/**
 * Shortcode - FAQPage
 *
 * @package    RankMath
 * @subpackage RankMath\Schema
 */

use RankMath\Schema\Block_FAQ;

defined( 'ABSPATH' ) || exit;

if ( empty( $schema['mainEntity'] ) ) {
	return;
}

$attributes = [
	'questions'         => [],
	'listStyle'         => '',
	'titleWrapper'      => 'h3',
	'sizeSlug'          => 'thumbnail',
	'listCssClasses'    => '',
	'titleCssClasses'   => '',
	'contentCssClasses' => '',
	'textAlign'         => 'left',
];

foreach ( $schema['mainEntity'] as $index => $main_entity ) {
	$attributes['questions'][] = [
		'id'       => 'faq-' . ( $index + 1 ),
		'title'    => $main_entity['name'],
		'content'  => $main_entity['acceptedAnswer']['text'],
		'visible'  => 1,
		'imageID'  => 0,
		'imageUrl' => isset( $main_entity['image'] ) ? $main_entity['image'] : '',
	];
}

echo Block_FAQ::markup( $attributes ); // phpcs:ignore
