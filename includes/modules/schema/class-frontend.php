<?php
/**
 *  Outputs specific schema code from Schema Template
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     RankMath <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Schema\DB;
use RankMath\Helpers\Str;
use RankMath\Helpers\HTML;

defined( 'ABSPATH' ) || exit;

/**
 * Schema Frontend class.
 */
class Frontend {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/json_ld', 'add_about_mention_attributes', 11 );
		$this->filter( 'rank_math/json_ld', 'add_template_schema', 8, 2 );
		$this->filter( 'rank_math/json_ld', 'add_schema_from_shortcode', 8, 2 );
		$this->filter( 'rank_math/json_ld', 'convert_schema_to_item_list', 99, 2 );
		$this->filter( 'rank_math/json_ld', 'validate_schema_data', 999 );
		$this->filter( 'rank_math/json_ld', 'add_subjectof_property', 99 );
		$this->filter( 'rank_math/json_ld', 'insert_template_schema', 20, 2 );
		$this->action( 'rank_math/schema/preview/validate', 'validate_preview_data' );
		$this->filter( 'rank_math/snippet/rich_snippet_itemlist_entity', 'filter_item_list_schema' );
		$this->filter( 'rank_math/schema/valid_types', 'valid_types' );
		$this->filter( 'rank_math/snippet/rich_snippet_product_entity', 'add_manufacturer_property' );
		$this->filter( 'rank_math/snippet/rich_snippet_product_entity', 'remove_empty_offers' );
		$this->filter( 'rank_math/snippet/rich_snippet_videoobject_entity', 'convert_familyfriendly_property' );
		$this->filter( 'rank_math/snippet/rich_snippet_podcastepisode_entity', 'convert_familyfriendly_property' );
		$this->filter( 'rank_math/snippet/rich_snippet_entity', 'schema_entity' );

		new Display_Conditions();
		new Snippet_Pro_Shortcode();

		if ( $this->do_filter( 'link/remove_schema_attribute', false ) ) {
			$this->filter( 'the_content', 'remove_schema_attribute', 11 );
		}

		// Schema Preview.
		$this->filter( 'query_vars', 'add_query_vars' );
		$this->filter( 'init', 'add_endpoint' );
		$this->action( 'template_redirect', 'schema_preview_template' );
	}

	/**
	 * Add the 'photos' query variable so WordPress won't mangle it.
	 *
	 * @param array $vars Array of vars.
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'schema-preview';
		return $vars;
	}

	/**
	 * Add endpoint
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( 'schema-preview', EP_PERMALINK | EP_PAGES | EP_ROOT );
	}

	/**
	 * Schema preview template
	 */
	public function schema_preview_template() {
		global $wp_query;

		// if this is not a request for schema preview or a singular or home object then bail.
		if (
			! isset( $wp_query->query_vars['schema-preview'] ) ||
			( ! is_singular() && ! is_home() && ! is_category() && ! is_tag() && ! is_tax() )
		) {
			return;
		}

		header( 'Content-Type: application/json' );

		do_action( 'rank_math/json_ld/preview' );

		exit;
	}

	/**
	 * Add nofollow and target attributes to link.
	 *
	 * @param  string $content Post content.
	 * @return string
	 */
	public function remove_schema_attribute( $content ) {
		preg_match_all( '/<(a\s[^>]+)>/', $content, $matches );
		if ( empty( $matches ) || empty( $matches[0] ) ) {
			return $content;
		}

		foreach ( $matches[0] as $link ) {
			$attrs = HTML::extract_attributes( $link );

			if ( ! isset( $attrs['data-schema-attribute'] ) ) {
				continue;
			}

			unset( $attrs['data-schema-attribute'] );
			$content = str_replace( $link, '<a' . HTML::attributes_to_string( $attrs ) . '>', $content );
		}

		return $content;
	}

	/**
	 * Filter functiont to extend valid schema types to use in Rank Math generated schema object.
	 *
	 * @param array $types Valid Schema types.
	 *
	 * @return array
	 */
	public function valid_types( $types ) {
		return array_merge( $types, [ 'movie', 'dataset', 'claimreview' ] );
	}

	/**
	 * Validate Code Validation Schema data before displaying it in Preview window.
	 *
	 * @param  array $schemas Array of json-ld data.
	 * @return array
	 *
	 * @since 2.6.1
	 */
	public function validate_preview_data( $schemas ) {
		foreach ( $schemas as $schema_key => $schema ) {
			if ( empty( $schema['subjectOf'] ) ) {
				continue;
			}

			foreach ( $schema['subjectOf'] as $key => $property ) {
				if ( empty( $schemas[ $key ] ) ) {
					continue;
				}

				$schema['subjectOf'][ $key ] = $schemas[ $key ];
				unset( $schemas[ $key ] );
			}

			$schema['subjectOf']    = array_values( $schema['subjectOf'] );
			$schemas[ $schema_key ] = $schema;
		}

		return $schemas;
	}

	/**
	 * Add FAQ/HowTo schema in subjectOf property of primary schema.
	 *
	 * @param  array $schemas Array of json-ld data.
	 * @return array
	 *
	 * @since 1.0.62
	 */
	public function add_subjectof_property( $schemas ) {
		if ( empty( $schemas ) ) {
			return $schemas;
		}

		foreach ( $schemas as $id => $schema ) {
			if ( ! Str::starts_with( 'schema-', $id ) && 'richSnippet' !== $id ) {
				continue;
			}

			$this->add_prop_subjectof( $schema, $schemas );
			if ( ! empty( $schema['subjectOf'] ) ) {
				$schemas[ $id ] = $schema;
				break;
			}
		}

		return $schemas;
	}

	/**
	 * Add subjectOf property in current schema entity.
	 *
	 * @param array $entity  Schema Entity.
	 * @param array $schemas Array of json-ld data.
	 *
	 * @since 1.0.62
	 */
	private function add_prop_subjectof( &$entity, &$schemas ) {
		if (
			! isset( $entity['@type'] ) ||
			empty( $entity['isPrimary'] ) ||
			! empty( $entity['isCustom'] ) ||
			in_array( $entity['@type'], [ 'FAQPage', 'HowTo' ], true )
		) {
			return;
		}

		global $wp_query;
		$subject_of = [];
		foreach ( $schemas as $key => $schema ) {
			if ( ! isset( $schema['@type'] ) || ! in_array( $schema['@type'], [ 'FAQPage', 'HowTo' ], true ) ) {
				continue;
			}

			if ( isset( $schema['isPrimary'] ) ) {
				unset( $schema['isPrimary'] );
			}

			if ( isset( $schema['isCustom'] ) ) {
				unset( $schema['isCustom'] );
			}

			if ( isset( $wp_query->query_vars['schema-preview'] ) ) {
				$subject_of[ $key ] = $schema;
				continue;
			}

			$subject_of[] = $schema;
			unset( $schemas[ $key ] );
		}

		$entity['subjectOf'] = $subject_of;
	}

	/**
	 * Get Default Schema Data.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld Instance of jsonld.
	 *
	 * @return array
	 */
	public function convert_schema_to_item_list( $data, $jsonld ) {
		$schemas = array_filter(
			$data,
			function( $schema ) {
				if ( isset( $schema['@type'] ) && in_array( $schema['@type'], [ 'Course', 'Movie', 'Recipe', 'Restaurant' ], true ) ) {
					return true;
				}

				return false;
			}
		);

		if ( 2 > count( $schemas ) ) {
			return $data;
		}

		$data['itemList'] = [
			'@type'           => 'ItemList',
			'itemListElement' => [],
		];

		$count = 1;
		foreach ( $schemas as $id => $schema ) {
			unset( $data[ $id ] );
			$schema['url'] = $jsonld->parts['url'] . '#' . $id;

			if ( isset( $schema['isPrimary'] ) ) {
				unset( $schema['isPrimary'] );
			}

			$data['itemList']['itemListElement'][] = [
				'@type'    => 'ListItem',
				'position' => $count,
				'item'     => $schema,
			];

			$count++;
		}

		return $data;
	}

	/**
	 * Add Schema data from Schema Templates.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld Instance of jsonld.
	 *
	 * @return array
	 */
	public function add_template_schema( $data, $jsonld ) {
		$schemas = Display_Conditions::get_schema_templates( $data, $jsonld );
		if ( empty( $schemas ) ) {
			return $data;
		}

		foreach ( $schemas as $schema ) {
			$data = array_merge( $data, $schema );
		}

		return $data;
	}

	/**
	 * Insert the appropriate Schema data from Schema Templates.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld Instance of jsonld.
	 *
	 * @return array
	 */
	public function insert_template_schema( $data, $jsonld ) {
		$schema_array = Display_Conditions::get_insertable_schemas();
		if ( empty( $schema_array ) ) {
			return $data;
		}

		foreach ( $schema_array as $insert_in => $schemas ) {

			// If the $insert_in is not a @type present in the data, then skip it.
			$insert_key = false;
			foreach ( $data as $key => $schema ) {
				if ( $key === $insert_in ) {
					$insert_key = $key;
					break;
				}

				if ( ! isset( $schema['@type'] ) ) {
					continue;
				}

				if ( $schema['@type'] === $insert_in ) {
					$insert_key = $key;
					break;
				}
			}
			if ( ! $insert_key ) {
				continue;
			}

			// Now insert the schema(s).
			foreach ( $schemas as $schema ) {
				$schema_key  = $schema['key'];
				$schema_data = $schema['schema'];

				unset( $schema_data['isPrimary'], $schema_data['isCustom'], $schema_data['isTemplate'], $schema_data['metadata'] );

				$schema_data = $jsonld->replace_variables( $schema_data );

				foreach ( $schema_data as $key => $value ) {
					if ( ! isset( $data[ $insert_key ][ $key ] ) ) {
						$data[ $insert_key ][ $key ] = $value;	
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Add About & Mention attributes to Webpage schema.
	 *
	 * @param  array $data Array of json-ld data.
	 * @return array
	 */
	public function add_about_mention_attributes( $data ) {
		if ( ! is_singular() || empty( $data['WebPage'] ) ) {
			return $data;
		}

		global $post;
		if ( ! $post->post_content ) {
			return $data;
		}

		preg_match_all( '|<a[^>]+>([^<]+)</a>|', $post->post_content, $matches );
		if ( empty( $matches ) || empty( $matches[0] ) ) {
			return $data;
		}

		foreach ( $matches[0] as $link ) {
			$attrs = HTML::extract_attributes( $link );
			if ( empty( $attrs['data-schema-attribute'] ) ) {
				continue;
			}

			$attributes = explode( ' ', $attrs['data-schema-attribute'] );
			if ( in_array( 'about', $attributes, true ) ) {
				$data['WebPage']['about'][] = [
					'@type'  => 'Thing',
					'name'   => wp_strip_all_tags( $link ),
					'sameAs' => $attrs['href'],
				];
			}

			if ( in_array( 'mentions', $attributes, true ) ) {
				$data['WebPage']['mentions'][] = [
					'@type'  => 'Thing',
					'name'   => wp_strip_all_tags( $link ),
					'sameAs' => $attrs['href'],
				];
			}
		}

		return $data;
	}

	/**
	 * Filter to change the itemList schema data.
	 *
	 * @param array $schema Snippet Data.
	 * @return array
	 */
	public function filter_item_list_schema( $schema ) {
		if ( ! is_archive() ) {
			return $schema;
		}

		$elements = [];
		$count    = 1;
		while ( have_posts() ) {
			the_post();
			$elements[] = [
				'@type'    => 'ListItem',
				'position' => $count,
				'url'      => get_the_permalink(),
			];

			$count++;
		}

		wp_reset_postdata();

		$schema['itemListElement'] = $elements;

		return $schema;
	}

	/**
	 * Validate Schema Data.
	 *
	 * @param array $schemas Array of json-ld data.
	 *
	 * @return array
	 */
	public function validate_schema_data( $schemas ) {
		if ( empty( $schemas ) ) {
			return $schemas;
		}

		$validate_types = [ 'Dataset', 'LocalBusiness' ];
		foreach ( $schemas as $id => $schema ) {
			$type = isset( $schema['@type'] ) ? $schema['@type'] : '';
			if ( ! Str::starts_with( 'schema-', $id ) || ! in_array( $type, $validate_types, true ) ) {
				continue;
			}

			$hash = [
				'isPartOf'   => true,
				'publisher'  => 'LocalBusiness' === $type,
				'inLanguage' => 'LocalBusiness' === $type,
			];

			foreach ( $hash as $property => $value ) {
				if ( ! $value || ! isset( $schema[ $property ] ) ) {
					continue;
				}

				if ( 'Dataset' === $type && 'isPartOf' === $property && ! empty( $schema[ $property ]['@type'] ) ) {
					continue;
				}

				unset( $schemas[ $id ][ $property ] );
			}

			if ( 'Dataset' === $type && ! empty( $schema['publisher'] ) ) {
				$schemas[ $id ]['creator'] = $schema['publisher'];
				unset( $schemas[ $id ]['publisher'] );
			}
		}

		return $schemas;
	}

	/**
	 * Get Schema data from Schema Templates post type.
	 *
	 * @param array  $data   Array of json-ld data.
	 * @param JsonLD $jsonld Instance of jsonld.
	 *
	 * @return array
	 */
	public function add_schema_from_shortcode( $data, $jsonld ) {
		if ( ! is_singular() || ! $this->do_filter( 'rank_math/schema/add_shortcode_schema', true ) ) {
			return $data;
		}

		global $post;
		$blocks = parse_blocks( $post->post_content );
		if ( ! empty( $blocks ) ) {
			foreach ( $blocks as $block ) {
				if ( 'rank-math/rich-snippet' !== $block['blockName'] ) {
					continue;
				}

				$id      = isset( $block['attrs']['id'] ) ? $block['attrs']['id'] : '';
				$post_id = isset( $block['attrs']['post_id'] ) ? $block['attrs']['post_id'] : '';

				if ( ! $id && ! $post_id ) {
					continue;
				}

				$data = array_merge( $data, $this->get_schema_data_by_id( $id, $post_id, $jsonld, $data ) );
			}
		}

		$regex = '/\[rank_math_rich_snippet (.*)\]/m';
		preg_match_all( $regex, $post->post_content, $matches, PREG_SET_ORDER, 0 );
		if ( ! empty( $matches ) ) {
			foreach ( $matches as $key => $match ) {
				parse_str( str_replace( ' ', '&', $match[1] ), $output );

				$post_id = isset( $output['post_id'] ) ? str_replace( [ '"', "'" ], '', $output['post_id'] ) : '';
				$id      = isset( $output['id'] ) ? str_replace( [ '"', "'" ], '', $output['id'] ) : '';
				$data    = array_merge( $data, $this->get_schema_data_by_id( $id, $post_id, $jsonld, $data ) );
			}
		}

		return $data;
	}

	/**
	 * Add Manufacturer property to Product schema.
	 *
	 * @param array $schema Product schema data.
	 * @return array
	 */
	public function add_manufacturer_property( $schema ) {
		if ( empty( $schema['manufacturer'] ) ) {
			return $schema;
		}

		$type = Helper::get_settings( 'titles.knowledgegraph_type' );
		$type = 'company' === $type ? 'organization' : 'person';

		$schema['manufacturer'] = [ '@id' => home_url( "/#{$type}" ) ];
		return $schema;
	}

	/**
	 * Remove empty offers data from the Product schema.
	 *
	 * @param array $schema Product schema data.
	 * @return array
	 */
	public function remove_empty_offers( $schema ) {
		if (
			empty( $schema['offers'] ) ||
			empty( $schema['review'] ) ||
			(
				empty( $schema['review']['positiveNotes'] ) &&
				empty( $schema['review']['negativeNotes'] )
			)
		) {
			return $schema;
		}

		if ( ! empty( $schema['offers']['price'] ) ) {
			return $schema;
		}

		unset( $schema['offers'] );

		return $schema;
	}

	/**
	 * Backward compatibility code to move the positiveNotes & negativeNotes properties in review.
	 *
	 * @param array $schema Schema data.
	 * @return array
	 *
	 * @since 3.0.19
	 */
	public function schema_entity( $schema ) {
		if ( empty( $schema['review'] ) ) {
			return $schema;
		}

		if ( ! empty( $schema['positiveNotes'] ) ) {
			$schema['review']['positiveNotes'] = $schema['positiveNotes'];
			unset( $schema['positiveNotes'] );
		}

		if ( ! empty( $schema['negativeNotes'] ) ) {
			$schema['review']['negativeNotes'] = $schema['negativeNotes'];
			unset( $schema['negativeNotes'] );
		}

		return $schema;
	}

	/**
	 * Convert isFamilyFriendly property used in Video schema to boolean.
	 *
	 * @param array $schema Video schema data.
	 * @return array
	 *
	 * @since 2.13.0
	 */
	public function convert_familyfriendly_property( $schema ) {
		if ( empty( $schema['isFamilyFriendly'] ) ) {
			return $schema;
		}

		$schema['isFamilyFriendly'] = 'True';
		return $schema;
	}

	/**
	 * Get Schema data by ID.
	 *
	 * @param string $id   Schema shortcode ID.
	 * @param int    $post_id Post ID.
	 * @param JsonLD $jsonld Instance of jsonld.
	 * @param array  $data   Array of json-ld data.
	 *
	 * @return array
	 */
	private function get_schema_data_by_id( $id, $post_id, $jsonld, $data ) {
		$schemas         = $id ? DB::get_schema_by_shortcode_id( trim( $id ) ) : DB::get_schemas( trim( $post_id ) );
		$current_post_id = get_the_ID();
		if (
			empty( $schemas ) ||
			(
				isset( $schemas['post_id'] ) && $current_post_id === (int) $schemas['post_id']
			) ||
			$post_id === $current_post_id
		) {
			return [];
		}

		$post_id = isset( $schemas['post_id'] ) ? $schemas['post_id'] : $post_id;
		$schemas = isset( $schemas['schema'] ) ? [ $schemas['schema'] ] : $schemas;
		$schemas = $jsonld->replace_variables( $schemas, get_post( $post_id ) );
		$schemas = $jsonld->filter( $schemas, $jsonld, $data );

		if ( isset( $schemas[0]['isPrimary'] ) ) {
			unset( $schemas[0]['isPrimary'] );
		}

		return $schemas;
	}
}
