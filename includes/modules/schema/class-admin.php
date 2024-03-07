<?php
/**
 * The Schema Module
 *
 * @since      2.0.0
 * @package    RankMath
 * @subpackage RankMath\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Admin\Admin_Helper;
use RankMath\Schema\DB;
use RankMath\Traits\Hooker;
use RankMath\Rest\Sanitize;
use RankMath\Helpers\Str;
use RankMath\Helpers\Param;
use WP_Screen;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 */
class Admin {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'admin_enqueue_scripts', 'overwrite_wplink', 100 );
		$this->action( 'rank_math/admin/before_editor_scripts', 'admin_scripts' );
		$this->action( 'rank_math/admin/editor_scripts', 'deregister_scripts', 99 );
		$this->action( 'save_post', 'save', 10, 2 );
		$this->action( 'edit_form_after_title', 'render_div' );
		$this->filter( 'rank_math/filter_metadata', 'filter_metadata', 10, 2 );
		$this->filter( 'rank_math/settings/snippet/types', 'add_pro_schema_types' );
		$this->filter( 'rank_math/schema/filter_data', 'update_schema_data' );

		new Taxonomy();
	}

	/**
	 * Update schema data.
	 *
	 * @param array $schemas Schema data.
	 */
	public function update_schema_data( $schemas ) {
		if ( empty( $schemas ) ) {
			return $schemas;
		}

		foreach ( $schemas as $schema_key => $schema ) {
			if ( empty( $schema['review'] ) ) {
				continue;
			}

			if (
				! empty( $schema['review']['positiveNotes'] ) &&
				! empty( $schema['review']['positiveNotes']['itemListElement'] ) &&
				is_string( $schema['review']['positiveNotes']['itemListElement'] )
			) {

				$notes = explode( PHP_EOL, $schema['review']['positiveNotes']['itemListElement'] );
				$schemas[ $schema_key ]['review']['positiveNotes']['itemListElement'] = [];
				foreach ( $notes as $key => $note ) {
					$schemas[ $schema_key ]['review']['positiveNotes']['itemListElement'][] = [
						'@type'    => 'ListItem',
						'position' => $key + 1,
						'name'     => $note,
					];
				}
			}

			if (
				! empty( $schema['review']['negativeNotes'] ) &&
				! empty( $schema['review']['negativeNotes']['itemListElement'] ) &&
				is_string( $schema['review']['negativeNotes']['itemListElement'] )
			) {

				$notes = explode( PHP_EOL, $schema['review']['negativeNotes']['itemListElement'] );
				$schemas[ $schema_key ]['review']['negativeNotes']['itemListElement'] = [];
				foreach ( $notes as $key => $note ) {
					$schemas[ $schema_key ]['review']['negativeNotes']['itemListElement'][] = [
						'@type'    => 'ListItem',
						'position' => $key + 1,
						'name'     => $note,
					];
				}
			}
		}

		return $schemas;
	}

	/**
	 * Add Pro schema types in Schema settings choices array.
	 *
	 * @param array $types Schema types.
	 */
	public function add_pro_schema_types( $types ) {
		$types = array_merge(
			$types,
			[
				'dataset'   => esc_html__( 'DataSet', 'rank-math-pro' ),
				'FactCheck' => esc_html__( 'Fact Check', 'rank-math-pro' ),
				'movie'     => esc_html__( 'Movie', 'rank-math-pro' ),
			]
		);

		unset( $types['off'] );
		ksort( $types, SORT_NATURAL | SORT_FLAG_CASE );
		return [ 'off' => esc_html__( 'None (Click here to set one)', 'rank-math-pro' ) ] + $types;
	}

	/**
	 * Overwrite wplink script file.
	 * Rank Math adds new options in the link popup when editing a post.
	 */
	public function overwrite_wplink() {
		if ( ! Admin_Helper::is_post_edit() || Admin_Helper::is_posts_page() ) {
			return;
		}

		wp_deregister_script( 'rank-math-formats' );
		wp_register_script(
			'rank-math-formats',
			RANK_MATH_PRO_URL . 'assets/admin/js/gutenberg-formats.js',
			[],
			rank_math_pro()->version,
			true
		);

		wp_deregister_script( 'wplink' );
		wp_register_script( 'wplink', RANK_MATH_PRO_URL . 'assets/admin/js/wplink.js', [ 'jquery', 'wp-a11y' ], rank_math_pro()->version, true );

		wp_localize_script(
			'wplink',
			'wpLinkL10n',
			[
				'title'             => esc_html__( 'Insert/edit link', 'rank-math-pro' ),
				'update'            => esc_html__( 'Update', 'rank-math-pro' ),
				'save'              => esc_html__( 'Add Link', 'rank-math-pro' ),
				'noTitle'           => esc_html__( '(no title)', 'rank-math-pro' ),
				'noMatchesFound'    => esc_html__( 'No matches found.', 'rank-math-pro' ),
				'linkSelected'      => esc_html__( 'Link selected.', 'rank-math-pro' ),
				'linkInserted'      => esc_html__( 'Link inserted.', 'rank-math-pro' ),
				'relCheckbox'       => '<code>rel="nofollow"</code>',
				'sponsoredCheckbox' => '<code>rel="sponsored"</code>',
				'aboutCheckbox'     => '<code>about</code>',
				'mentionsCheckbox'  => '<code>mentions</code>',
				'schemaMarkupLabel' => esc_html__( 'Use in Schema Markup', 'rank-math-pro' ),
				'linkTitle'         => esc_html__( 'Link Title', 'rank-math-pro' ),
			]
		);
	}

	/**
	 * Enqueue Styles and Scripts required for the schema functionality on Gutenberg & Classic editor.
	 *
	 * @return void
	 */
	public function admin_scripts() {
		if ( ! $this->can_enqueue_scripts() ) {
			return;
		}

		$this->localize_data();
		wp_enqueue_style( 'rank-math-schema-pro', RANK_MATH_PRO_URL . 'includes/modules/schema/assets/css/schema.css', [ 'rank-math-schema' ], rank_math_pro()->version );

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

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( $screen instanceof WP_Screen && 'rank_math_schema' === $screen->post_type ) {
			Helper::add_json( 'isTemplateScreen', true );
			wp_enqueue_script(
				'rank-math-pro-schema',
				rank_math()->plugin_url() . 'includes/modules/schema/assets/js/schema-template.js',
				[
					'clipboard',
					'wp-autop',
					'wp-components',
					'wp-editor',
					'wp-edit-post',
					'wp-element',
					'wp-i18n',
					'wp-plugins',
					'rank-math-analyzer',
				],
				rank_math_pro()->version,
				true
			);

			return;
		}

		wp_enqueue_script( 'rank-math-schema-pro', RANK_MATH_PRO_URL . 'includes/modules/schema/assets/js/schema.js', [ 'rank-math-schema' ], rank_math_pro()->version, true );
	}

	/**
	 * Deregister some scripts.
	 */
	public function deregister_scripts() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( ! $screen instanceof WP_Screen || 'post' !== $screen->base || Helper::is_elementor_editor() || 'rank_math_schema' !== $screen->post_type ) {
			return;
		}

		wp_deregister_script( 'rank-math-editor' );
		wp_deregister_script( 'rank-math-schema' );

		if ( wp_script_is( 'rank-math-pro-editor', 'registered' ) ) {
			wp_deregister_script( 'rank-math-pro-editor' );
		}
	}



	/**
	 * Render app div
	 */
	public function render_div() {
		$screen = get_current_screen();
		if ( 'rank_math_schema' !== $screen->post_type ) {
			return;
		}

		Helper::add_json( 'postType', 'rank_math_schema' );

		wp_nonce_field( 'rank_math_schema_template', 'security' );
		?>
		<div id="rank-math-schema-template"></div>
		<textarea name="rank_math_schema" rows="8" cols="80" class="rank-math-schema"></textarea>
		<input type="text" name="rank_math_schema_meta_id" class="rank-math-schema-meta-id" value="">
		<?php
	}

	/**
	 * Save post data.
	 *
	 * @param  int    $post_id Post id.
	 * @param  object $post    Post object.
	 */
	public function save( $post_id, $post ) {
		if (
			! isset( $_POST['security'] ) ||
			! wp_verify_nonce( $_POST['security'], 'rank_math_schema_template' ) ||
			! isset( $_POST['rank_math_schema'] )
		) {
			return $post_id;
		}

		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return $post_id;
		}

		$sanitizer = Sanitize::get();
		$schema    = stripslashes_deep( $_POST['rank_math_schema'] );
		$schema    = json_decode( $schema, true );
		$schema    = $sanitizer->sanitize( 'rank_math_schema', $schema );

		$meta_key = 'rank_math_schema_' . $schema['@type'];
		update_post_meta( $post_id, $meta_key, $schema );

		// Publish Schema Template post.
		if ( 'rank_math_schema' === $post->post_type && 'publish' !== $post->post_status ) {
			wp_update_post(
				[
					'ID'          => $post_id,
					'post_status' => 'publish',
				]
			);
		}
	}

	/**
	 * Add excluded template conditions in the Schema template and remove it from the metadata.
	 *
	 * @param array           $meta    Meta data to update.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array Processed metadata.
	 */
	public function filter_metadata( $meta, $request ) {
		foreach ( $meta as $meta_key => $meta_value ) {
			if ( ! Str::starts_with( 'rank_math_exclude_template_', $meta_key ) ) {
				continue;
			}

			$template_id = absint( \str_replace( 'rank_math_exclude_template_', '', $meta_key ) );
			$schema_data = DB::get_schemas( $template_id );
			$meta_id     = key( $schema_data );
			$meta_key    = 'rank_math_schema_' . $schema_data[ $meta_id ]['@type'];

			$schema_data[ $meta_id ]['metadata']['displayConditions'][] = [
				'condition' => 'exclude',
				'category'  => 'singular',
				'type'      => $request->get_param( 'objectType' ),
				'value'     => $request->get_param( 'objectID' ),
			];

			$db_id = absint( str_replace( 'schema-', '', $meta_id ) );
			update_metadata_by_mid( 'post', $db_id, $schema_data[ $meta_id ], $meta_key );

			unset( $meta[ "rank_math_exclude_template_{$template_id}" ] );
		}

		return $meta;
	}

	/**
	 * [get_schema_templates description]
	 *
	 * @return array
	 */
	protected function get_schema_templates() {
		$posts = get_posts(
			[
				'post_type'      => 'rank_math_schema',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			]
		);

		if ( empty( $posts ) ) {
			return [];
		}

		$templates = [];
		foreach ( $posts as $post ) {
			$data          = DB::get_template_type( $post->ID );
			$data['title'] = $post->post_title;
			$data['id']    = $post->ID;

			$templates[] = $data;
		}

		return $templates;
	}

	/**
	 * Whether to enqueue schema scripts on the page.
	 *
	 * @return bool
	 */
	private function can_enqueue_scripts() {
		if ( ! Helper::has_cap( 'onpage_snippet' ) ) {
			return false;
		}

		if ( ! Helper::is_divi_frontend_editor() && ! is_admin() ) {
			return false;
		}

		global $pagenow;
		if ( 'edit-tags.php' === $pagenow ) {
			return false;
		}

		if ( Admin_Helper::is_term_edit() ) {
			$taxonomy = Param::request( 'taxonomy' );
			return true !== apply_filters(
				'rank_math/snippet/remove_taxonomy_data',
				Helper::get_settings( 'titles.remove_' . $taxonomy . '_snippet_data' ),
				$taxonomy
			);
		}

		return ( Admin_Helper::is_post_edit() || Helper::is_divi_frontend_editor() ) && ! Admin_Helper::is_posts_page();
	}

	/**
	 * Add active templates to the schemas json
	 *
	 * @return array
	 */
	private function get_active_templates() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;
		if ( $screen instanceof WP_Screen && 'rank_math_schema' === $screen->post_type ) {
			return [];
		}

		$templates = Display_Conditions::get_schema_templates();
		if ( empty( $templates ) ) {
			return [];
		}

		$schemas = [];
		foreach ( $templates as  $template ) {
			$template['schema']['isTemplate'] = true;
			$schemas[ $template['id'] ]       = $template['schema'];
		}

		return $schemas;
	}

	/**
	 * Localized data.
	 */
	private function localize_data() {
		$post = get_post();
		Helper::add_json( 'postStatus', get_post_field( 'post_status', $post ) );
		Helper::add_json( 'postLink', get_permalink( $post ) );
		Helper::add_json( 'schemaTemplates', $this->get_schema_templates() );
		Helper::add_json( 'activeTemplates', $this->get_active_templates() );
		Helper::add_json( 'accessiblePostTypes', Helper::get_accessible_post_types() );
		Helper::add_json( 'accessibleTaxonomies', Helper::get_accessible_taxonomies() );
		Helper::add_json( 'postTaxonomies', $this->get_post_taxonomies() );
	}

	/**
	 * Get Post taxonomies.
	 */
	private function get_post_taxonomies() {
		$post_types = Helper::get_accessible_post_types();
		$data       = [];
		foreach ( $post_types as $post_type ) {
			$taxonomies = Helper::get_object_taxonomies( $post_type );
			if ( empty( $taxonomies ) ) {
				continue;
			}
			unset( $taxonomies['off'] );
			$data[ $post_type ] = [ 'all' => esc_html__( 'All Taxonomies', 'rank-math-pro' ) ] + $taxonomies;
		}

		return $data;
	}
}
