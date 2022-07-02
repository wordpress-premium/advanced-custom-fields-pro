<?php
/**
 *  Outputs specific schema code from Schema Template
 *
 * @since      2.0.7
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Schema\DB;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Display Conditions class.
 */
class Display_Conditions {

	use Hooker;

	/**
	 * Display conditions data.
	 *
	 * @var array
	 */
	private static $conditions = [];

	/**
	 * Get Schema data from Schema Templates post type.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld Instance of jsonld.
	 *
	 * @return array
	 */
	public static function get_schema_templates( $data = [], $jsonld = [] ) {
		$templates = get_posts(
			[
				'post_type'   => 'rank_math_schema',
				'numberposts' => -1,
				'fields'      => 'ids',
			]
		);

		if ( empty( $templates ) ) {
			return;
		}

		$newdata = [];
		foreach ( $templates as $template ) {
			self::$conditions = [
				'general'  => '',
				'singular' => '',
				'archive'  => '',
			];
			$schema           = DB::get_schemas( $template );
			if ( ! self::can_add( current( $schema ) ) ) {
				continue;
			}

			if ( is_admin() || Helper::is_divi_frontend_editor()  ) {
				$newdata[] = [
					'id'     => $template,
					'schema' => current( $schema ),
				];

				continue;
			}

			DB::unpublish_jobposting_post( $jsonld, $schema );

			$schema = $jsonld->replace_variables( $schema );
			$schema = $jsonld->filter( $schema, $jsonld, $data );

			$newdata[] = $schema;
		}

		return $newdata;
	}

	/**
	 * Whether schema can be added to current page
	 *
	 * @param array $schema Schema Data.
	 *
	 * @return boolean
	 */
	private static function can_add( $schema ) {
		if ( empty( $schema ) || empty( $schema['metadata']['displayConditions'] ) ) {
			return false;
		}

		foreach ( $schema['metadata']['displayConditions'] as $condition ) {
			$operator = $condition['condition'];
			$category = $condition['category'];
			$taxonomy = ! empty( $condition['postTaxonomy'] ) ? $condition['postTaxonomy'] : '';
			$type     = $condition['type'];
			$value    = $condition['value'];

			$method = "can_add_{$category}";

			// Skip if already confirmed.
			if ( 'include' === $operator && self::$conditions[ $category ] ) {
				continue;
			}

			self::$conditions[ $category ] = self::$method( $operator, $type, $value, $taxonomy );
		}

		// Add Schema if the only condition is "Include / Entire Site".
		if ( ! empty( self::$conditions['general'] ) && 1 === count( $schema['metadata']['displayConditions'] ) ) {
			return true;
		}

		if ( ( is_singular() || is_admin() ) && isset( self::$conditions['singular'] ) ) {
			return self::$conditions['singular'];
		}

		if ( ( is_archive() || is_search() ) && isset( self::$conditions['archive'] ) ) {
			return self::$conditions['archive'];
		}

		return ! empty( self::$conditions['general'] );
	}

	/**
	 * Whether schema can be added to current page
	 *
	 * @param string $operator Comparision Operator.
	 *
	 * @return boolean
	 */
	private static function can_add_general( $operator ) {
		return 'include' === $operator;
	}

	/**
	 * Whether schema can be added on archive page
	 *
	 * @param string $operator Comparision Operator.
	 * @param string $type     Post/Taxonoy type.
	 * @param string $value    Post/Term ID.
	 *
	 * @return boolean
	 */
	private static function can_add_archive( $operator, $type, $value ) {
		if ( 'search' === $type ) {
			return 'include' === $operator && is_search();
		}

		if ( ! is_archive() ) {
			return false;
		}

		if ( 'all' === $type ) {
			return 'include' === $operator;
		}

		if ( 'author' === $type ) {
			return is_author() && 'include' === $operator && is_author( $value );
		}

		if ( 'category' === $type ) {
			return ! is_category() ? self::$conditions['archive'] : 'include' === $operator && is_category( $value );
		}

		if ( 'post_tag' === $type ) {
			return ! is_tag() ? self::$conditions['archive'] : 'include' === $operator && is_tag( $value );
		}

		return 'include' === $operator && is_tax( $type, $value );
	}

	/**
	 * Whether schema can be added on single page
	 *
	 * @param string $operator Comparision Operator.
	 * @param string $type     Post/Taxonoy type.
	 * @param string $value    Post/Term ID.
	 * @param string $taxonomy Post Taxonomy.
	 *
	 * @return boolean
	 */
	private static function can_add_singular( $operator, $type, $value, $taxonomy ) {
		$post = is_admin() || is_singular() ? get_post( get_the_ID() ) : [];
		if ( empty( $post ) ) {
			return false;
		}

		if ( 'all' === $type ) {
			return 'include' === $operator;
		}

		if ( $type !== $post->post_type ) {
			return false;
		}

		if ( ! $value ) {
			return 'include' === $operator;
		}

		if ( $taxonomy ) {
			return 'include' === $operator && has_term( $value, $taxonomy );
		}

		if ( absint( $post->ID ) === absint( $value ) ) {
			return 'include' === $operator;
		}

		return 'exclude' === $operator;
	}
}
