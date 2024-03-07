<?php
/**
 *  Outputs specific schema code from Schema Template
 *
 * @since      2.0.7
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     RankMath <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Schema\DB;
use RankMath\Traits\Hooker;

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
	 * Insert Schema data.
	 *
	 * @var array
	 */
	private static $insert_schemas = [];

	/**
	 * Get Schema data from Schema Templates post type.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld Instance of jsonld.
	 *
	 * @return array
	 */
	public static function get_schema_templates( $data = [], $jsonld = [] ) {
		global $wpdb;
		$templates = $wpdb->get_col( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type='rank_math_schema' AND post_status='publish'" );

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

			$schema = DB::get_schemas( $template );

			self::prepare_inserted_schemas( current( $schema ) );

			if ( ! self::can_add( current( $schema ) ) ) {
				continue;
			}

			if ( is_admin() || Helper::is_divi_frontend_editor() ) {
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
			if ( 'insert' === $operator ) {
				// We handle the insert condition in the prepare_inserted_schemas() method.
				continue;
			}

			$category = $condition['category'];
			$taxonomy = ! empty( $condition['postTaxonomy'] ) ? $condition['postTaxonomy'] : '';
			$type     = $condition['type'];
			$value    = $condition['value'];

			$method = "can_add_{$category}";

			// Skip if already confirmed.
			if ( 'include' === $operator && self::$conditions[ $category ] ) {
				continue;
			}
			if ( 'exclude' === $operator && ! self::$conditions[ $category ] ) {
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
	 * Prepare inserted schemas: check if they can be added to current page, and if so, add them to the $insert_schemas static array.
	 *
	 * @param array $schema Schema Data.
	 */
	private static function prepare_inserted_schemas( $schema ) {
		if ( empty( $schema ) || empty( $schema['metadata']['displayConditions'] ) ) {
			return;
		}

		foreach ( $schema['metadata']['displayConditions'] as $condition ) {
			$operator = $condition['condition'];
			if ( 'insert' !== $operator ) {
				continue;
			}

			if ( empty( $schema['metadata']['title'] ) ) {
				continue;
			}

			$in_schema = $condition['category'];
			if ( 'custom' === $in_schema ) {
				if ( empty( $condition['value'] ) ) {
					continue;
				}
				$in_schema = $condition['value'];
			}

			if ( 'ProfilePage' === $in_schema && ! is_singular() ) {
				continue;
			}

			if ( 'ProfilePage' === $in_schema && ! empty( $condition['authorID'] ) ) {
				$author_ids = wp_parse_id_list( $condition['authorID'] );
				global $post;

				if ( ! in_array( (int) $post->post_author, $author_ids, true ) ) {
					continue;
				}
			}

			$with_key = $schema['metadata']['title'];

			self::$insert_schemas[ $in_schema ][] = [
				'key'    => $with_key,
				'schema' => $schema,
			];
		}
	}

	/**
	 * Get inserted schemas.
	 *
	 * @return array
	 */
	public static function get_insertable_schemas() {
		return self::$insert_schemas;
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

		if ( $taxonomy && 'exclude' === $operator ) {
			return ! has_term( $value, $taxonomy );
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
