<?php
/**
 * The Local_Seo Module
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Local_Seo;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Sitemap\Cache_Watcher;

defined( 'ABSPATH' ) || exit;

/**
 * Local_Seo class.
 */
class Local_Seo {

	use Hooker;

	/**
	 * Post Type.
	 *
	 * @var string
	 */
	private $post_type = 'rank_math_locations';

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'init', 'init' );
		$this->action( 'rank_math/schema/update', 'update_post_schema_info', 10, 2 );
		$this->action( 'save_post', 'invalidate_cache' );

		$this->filter( 'classic_editor_enabled_editors_for_post_type', 'force_block_editor', 20, 2 );
		$this->filter( 'rank_math/sitemap/locations', 'add_kml_file' );

		$this->filter( "manage_{$this->post_type}_posts_columns", 'posts_columns' );
		$this->filter( "manage_{$this->post_type}_posts_custom_column", 'posts_custom_column', 10, 2 );
		$this->filter( "bulk_actions-edit-{$this->post_type}", 'post_bulk_actions', 20 );

		$this->includes();
	}

	/**
	 * Update post info for analytics.
	 *
	 * @param int    $object_id    Object ID.
	 * @param array  $schemas      Schema data.
	 * @param string $object_type  Object type.
	 */
	public function update_post_schema_info( $object_id, $schemas, $object_type = 'post' ) {
		if ( 'post' !== $object_type || 'rank_math_locations' !== get_post_type( $object_id ) ) {
			return;
		}

		$schema = current( $schemas );
		if ( ! isset( $schema['geo'], $schema['geo']['latitude'], $schema['geo']['longitude'] ) ) {
			return;
		}

		update_post_meta( $object_id, 'rank_math_local_business_latitide', $schema['geo']['latitude'] );
		update_post_meta( $object_id, 'rank_math_local_business_longitude', $schema['geo']['longitude'] );
	}

	/**
	 * Intialize.
	 */
	public function init() {
		if ( ! Helper::get_settings( 'titles.use_multiple_locations', false ) ) {
			return;
		}

		$this->post_singular_name = Helper::get_settings( 'titles.locations_post_type_label', 'Locations' );

		$this->register_location_post_type();
		$this->register_location_taxonomy();
	}

	/**
	 * Filters the editors that are enabled for the post type.
	 *
	 * @param array  $editors   Associative array of the editors and whether they are enabled for the post type.
	 * @param string $post_type The post type.
	 */
	public function force_block_editor( $editors, $post_type ) {
		if ( 'rank_math_locations' !== $post_type || ! $this->do_filter( 'schema/cpt_force_gutenberg', true ) ) {
			return $editors;
		}

		$editors['classic_editor'] = false;
		return $editors;
	}

	/**
	 * Add Locations KML file in the sitemap
	 */
	public function add_kml_file() {
		return Helper::get_settings( 'sitemap.local_sitemap', true );
	}

	/**
	 * Include required files.
	 */
	private function includes() {
		if ( is_admin() ) {
			new Admin();
			new RM_Pointers();
			return;
		}

		if ( Helper::get_settings( 'titles.use_multiple_locations', false ) ) {
			new Frontend();
			new Location_Shortcode();
			new KML_File();
		}
	}

	/**
	 * Register Locations post type.
	 */
	private function register_location_post_type() {
		$plural_label   = Helper::get_settings( 'titles.locations_post_type_plural_label', 'RM Locations' );
		$post_type_slug = Helper::get_settings( 'titles.locations_post_type_base', 'locations' );
		$labels         = [
			'name'                     => $this->post_singular_name,
			'singular_name'            => $this->post_singular_name,
			'menu_name'                => $plural_label,
			/* translators: Post Type Plural Name */
			'all_items'                => sprintf( esc_html__( 'All %s', 'rank-math-pro' ), $plural_label ),
			/* translators: Post Type Singular Name */
			'add_new_item'             => sprintf( esc_html__( 'Add New %s', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'new_item'                 => sprintf( esc_html__( 'New %s', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'edit_item'                => sprintf( esc_html__( 'Edit %s', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'update_item'              => sprintf( esc_html__( 'Update %s', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'view_item'                => sprintf( esc_html__( 'View %s', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Plural Name */
			'view_items'               => sprintf( esc_html__( 'View %s', 'rank-math-pro' ), $plural_label ),
			/* translators: Post Type Singular Name */
			'search_items'             => sprintf( esc_html__( 'Search %s', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'not_found'                => sprintf( esc_html__( 'No %s found.', 'rank-math-pro' ), $plural_label ),
			/* translators: Post Type Singular Name */
			'not_found_in_trash'       => sprintf( esc_html__( 'No %s found in Trash.', 'rank-math-pro' ), $plural_label ),
			/* translators: Post Type Singular Name */
			'item_published'           => sprintf( esc_html__( '%s published.', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'item_published_privately' => sprintf( esc_html__( '%s published privately.', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'item_reverted_to_draft'   => sprintf( esc_html__( '%s reverted to draft.', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'item_scheduled'           => sprintf( esc_html__( '%s scheduled.', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'item_updated'             => sprintf( esc_html__( '%s updated.', 'rank-math-pro' ), $this->post_singular_name ),
		];

		$capability = 'rank_math_general';
		$args       = [
			'label'              => $this->post_singular_name,
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'hierarchical'       => false,
			'has_archive'        => $post_type_slug,
			'menu_icon'          => 'dashicons-location',
			'query_var'          => true,
			'show_in_rest'       => true,
			'rest_base'          => 'rank-math-locations',
			'supports'           => [ 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'revisions', 'custom-fields', 'page-attributes', 'publicize' ],
			'rewrite'            => [
				'slug'       => $post_type_slug,
				'with_front' => $this->filter( 'locations/front', true ),
			],
			'capabilities'       => [
				'edit_post'          => $capability,
				'read_post'          => $capability,
				'delete_post'        => $capability,
				'edit_posts'         => $capability,
				'edit_others_posts'  => $capability,
				'publish_posts'      => $capability,
				'read_private_posts' => $capability,
				'create_posts'       => $capability,
			],
		];

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Register Locations Category taxonomy.
	 */
	private function register_location_taxonomy() {
		$category_slug = esc_html( Helper::get_settings( 'titles.locations_category_base', 'locations-category' ) );
		$labels        = [
			/* translators: Post Type Singular Name */
			'name'              => sprintf( esc_html__( '%s categories', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'singular_name'     => sprintf( esc_html__( '%s category', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'all_items'         => sprintf( esc_html__( 'All %s categories', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'edit_item'         => sprintf( esc_html__( 'Edit %s category', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'update_item'       => sprintf( esc_html__( 'Update %s category', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'add_new_item'      => sprintf( esc_html__( 'Add New %s category', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'new_item_name'     => sprintf( esc_html__( 'New %s category', 'rank-math-pro' ), $this->post_singular_name ),
			/* translators: Post Type Singular Name */
			'menu_name'         => sprintf( esc_html__( '%s categories', 'rank-math-pro' ), $this->post_singular_name ),
			'search_items'      => esc_html__( 'Search categories', 'rank-math-pro' ),
			'parent_item'       => esc_html__( 'Parent Category', 'rank-math-pro' ),
			'parent_item_colon' => esc_html__( 'Parent Category:', 'rank-math-pro' ),
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true,
			'rewrite'           => [ 'slug' => $category_slug ],
		];

		register_taxonomy( 'rank_math_location_category', [ $this->post_type ], $args );
	}

	/**
	 * Check for relevant post type before invalidation.
	 *
	 * @param int $post_id Post ID to possibly invalidate for.
	 */
	public function invalidate_cache( $post_id ) {
		if ( get_post_type( $post_id ) !== $this->post_type ) {
			return false;
		}

		Cache_Watcher::clear( [ 'locations' ] );
	}

	/**
	 * Add custom columns for post type.
	 *
	 * @param array $columns Current columns.
	 *
	 * @return array
	 */
	public function posts_columns( $columns ) {
		$columns['address']   = __( 'Address', 'rank-math-pro' );
		$columns['telephone'] = __( 'Phone', 'rank-math-pro' );

		return $columns;
	}

	/**
	 * Add the content in the custom columns.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function posts_custom_column( $column, $post_id ) {
		$schemas = \RankMath\Schema\DB::get_schemas( $post_id );
		if ( empty( $schemas ) ) {
			return;
		}

		$schema = reset( $schemas );
		if ( empty( $schema[ $column ] ) ) {
			return;
		}

		switch ( $column ) {
			case 'address':
				unset( $schema['address']['@type'] );
				echo esc_html( join( ' ', $schema['address'] ) );
				break;

			case 'telephone':
				echo esc_html( $schema['telephone'] );
				break;
		}
	}

	/**
	 * Remove unneeded bulk actions.
	 *
	 * @param  array $actions Actions.
	 * @return array             New actions.
	 */
	public function post_bulk_actions( $actions ) {
		unset( $actions['rank_math_bulk_schema_none'], $actions['rank_math_bulk_schema_default'] );
		return $actions;
	}
}
