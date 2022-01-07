<?php
/**
 * The Schema Module
 *
 * @since      2.1.0
 * @package    RankMath
 * @subpackage RankMath\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper;
use RankMath\Schema\DB;
use RankMath\Rest\Sanitize;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Arr;
use MyThemeShop\Helpers\Str;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Taxonomy class.
 */
class Taxonomy extends Admin {
	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'init', 'init', 9999 );
		$this->action( 'rank_math/json_ld', 'add_schema', 11, 2 );

		if (
			! Helper::has_cap( 'onpage_snippet' ) ||
			! Admin_Helper::is_term_edit() ||
			! $this->can_add( Param::request( 'taxonomy' ) )
		) {
			return;
		}

		$this->action( 'rank_math/admin/editor_scripts', 'enqueue' );
	}

	/**
	 * Add schema-preview rewrite rule for taxonomies.
	 */
	public function init() {
		$taxonomies = Helper::get_accessible_taxonomies();
		foreach ( $taxonomies as $slug => $taxonomy ) {
			if ( empty( $taxonomy->rewrite['slug'] ) || ! $this->can_add( $slug ) ) {
				continue;
			}

			add_rewrite_rule(
				$taxonomy->rewrite['slug'] . '/(.+?)/schema-preview/?$',
				'index.php?' . $slug . '=$matches[1]&schema-preview=$matches[3]',
				'top'
			);
		}
	}

	/**
	 * Enqueue Styles and Scripts required for metabox.
	 */
	public function enqueue() {
		$cmb = cmb2_get_metabox( 'rank_math_metabox' );
		if ( false === $cmb ) {
			return;
		}

		$schemas = $this->get_schema_data( $cmb->object_id() );
		Helper::add_json( 'schemas', $schemas );
		Helper::add_json( 'customSchemaImage', esc_url( rank_math()->plugin_url() . 'includes/modules/schema/assets/img/custom-schema-builder.jpg' ) );
		Helper::add_json( 'postLink', get_term_link( (int) $cmb->object_id() ) );
		Helper::add_json( 'activeTemplates', $this->get_active_templates() );
		wp_enqueue_style( 'rank-math-schema', rank_math()->plugin_url() . 'includes/modules/schema/assets/css/schema.css', [ 'wp-components', 'rank-math-editor' ], rank_math()->version );
		wp_enqueue_script( 'rank-math-schema', rank_math()->plugin_url() . 'includes/modules/schema/assets/js/schema-gutenberg.js', [ 'rank-math-editor', 'clipboard' ], rank_math()->version, true );

		wp_enqueue_style( 'rank-math-schema-pro', RANK_MATH_PRO_URL . 'includes/modules/schema/assets/css/schema.css', null, rank_math_pro()->version );
		wp_enqueue_script(
			'rank-math-pro-schema-filters',
			RANK_MATH_PRO_URL . 'includes/modules/schema/assets/js/schemaFilters.js',
			[
				'wp-plugins',
				'wp-components',
				'wp-hooks',
				'wp-api-fetch',
				'lodash',
			],
			rank_math_pro()->version,
			true
		);

		wp_enqueue_script( 'rank-math-schema-pro', RANK_MATH_PRO_URL . 'includes/modules/schema/assets/js/schema.js', [ 'rank-math-editor' ], rank_math_pro()->version, true );
	}

	/**
	 * Get Default Schema Data.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld Instance of jsonld.
	 *
	 * @return array
	 */
	public function add_schema( $data, $jsonld ) {
		if ( ! is_category() && ! is_tag() && ! is_tax() ) {
			return $data;
		}

		$queried_object = get_queried_object();
		if (
			empty( $queried_object ) ||
			is_wp_error( $queried_object ) ||
			! $this->can_add( $queried_object->taxonomy )
		) {
			return $data;
		}

		$schemas = DB::get_schemas( $queried_object->term_id, 'termmeta' );
		if ( empty( $schemas ) ) {
			return $data;
		}

		if ( isset( $data['ItemList'] ) ) {
			unset( $data['ItemList'] );
		}

		if ( isset( $data['ProductsPage'] ) ) {
			unset( $data['ProductsPage'] );
		}

		if ( isset( $data['WebPage'] ) ) {
			$data['WebPage']['@type'] = [
				'WebPage',
				'CollectionPage',
			];
		}

		$schemas = $jsonld->replace_variables( $schemas );
		$schemas = $jsonld->filter( $schemas, $jsonld, $data );

		return array_merge( $data, $schemas );
	}

	/**
	 * Get Schema Data.
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return array $schemas Schema Data.
	 */
	private function get_schema_data( $term_id ) {
		$schemas = DB::get_schemas( $term_id, 'termmeta' );
		if ( ! empty( $schemas ) || metadata_exists( 'term', $term_id, 'rank_math_rich_snippet' ) ) {
			return $schemas;
		}

		return [];
	}

	/**
	 * Add active templates to the schemas json
	 *
	 * @return array
	 */
	public function get_active_templates() {
		$templates = $this->get_schema_templates();
		if ( empty( $templates ) ) {
			return [];
		}

		$screen  = get_current_screen();
		$schemas = [];
		foreach ( $templates as $template ) {
			if ( empty( $template['schema']['metadata']['displayConditions'] ) ) {
				continue;
			}

			$conditions = $template['schema']['metadata']['displayConditions'];
			$can_add    = false;
			$data       = [];

			foreach ( $conditions as $condition ) {
				$category = $condition['category'];
				if ( 'archive' !== $category ) {
					continue;
				}

				$operator = $condition['condition'];
				if ( ! empty( $data[ $category ] ) && 'exclude' !== $operator ) {
					continue;
				}

				$type  = $condition['type'];
				$value = $condition['value'];

				if ( 'general' === $category ) {
					$data[ $category ] = 'include' === $operator;
					continue;
				}

				if ( $value && absint( Param::get( 'tag_ID' ) ) === $value ) {
					$data[ $category ] = 'include' === $operator;
					break;
				}

				if ( 'all' === $type ) {
					$data[ $category ] = 'include' === $operator;
				} elseif ( $type !== $screen->taxonomy ) {
					$data[ $category ] = false;
				} elseif ( ! $value ) {
					$data[ $category ] = 'include' === $operator;
				} elseif ( Param::get( 'tag_ID' ) !== $value ) {
					$data[ $category ] = isset( $data[ $category ] ) ? $data[ $category ] : false;
				} else {
					$data[ $category ] = 'include' === $operator;
				}
			}

			if ( isset( $data['archive'] ) ) {
				$can_add = $data['archive'];
			} else {
				$can_add = ! empty( $data['general'] );
			}

			if ( $can_add ) {
				$template['schema']['isTemplate'] = true;
				$schemas[ $template['id'] ]       = $template['schema'];
			}
		}

		return $schemas;
	}

	/**
	 * Can add Schema data on current taxonomy
	 *
	 * @param string $taxonomy Taxonomy name.
	 *
	 * @return bool
	 */
	private function can_add( $taxonomy ) {
		return Helper::get_settings( 'titles.tax_' . $taxonomy . '_add_meta_box' ) &&
			true !== apply_filters(
				'rank_math/snippet/remove_taxonomy_data',
				Helper::get_settings( 'titles.remove_' . $taxonomy . '_snippet_data' ),
				$taxonomy
			);
	}
}
