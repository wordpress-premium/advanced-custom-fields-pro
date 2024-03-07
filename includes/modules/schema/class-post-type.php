<?php
/**
 * The Schema Template Post Type
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     RankMath <support@rankmath.com>
 */

namespace RankMathPro\Schema;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * Post_Type class.
 */
class Post_Type {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->action( 'init', 'register' );
		$this->action( 'admin_menu', 'add_menu', 11 );
		$this->action( 'parent_file', 'parent_file' );
		$this->action( 'submenu_file', 'submenu_file' );
	}

	/**
	 * Register template post type.
	 */
	public function register() {
		$labels = [
			'name'               => _x( 'Schemas', 'Post Type General Name', 'rank-math-pro' ),
			'singular_name'      => _x( 'Schema', 'Post Type Singular Name', 'rank-math-pro' ),
			'menu_name'          => __( 'Schemas', 'rank-math-pro' ),
			'name_admin_bar'     => __( 'Schema', 'rank-math-pro' ),
			'all_items'          => __( 'All Schemas', 'rank-math-pro' ),
			'add_new'            => __( 'Add New Schema', 'rank-math-pro' ),
			'add_new_item'       => __( 'Add New Schema', 'rank-math-pro' ),
			'new_item'           => __( 'New Schema', 'rank-math-pro' ),
			'edit_item'          => __( 'Edit Schema', 'rank-math-pro' ),
			'update_item'        => __( 'Update Schema', 'rank-math-pro' ),
			'view_item'          => __( 'View Schema', 'rank-math-pro' ),
			'view_items'         => __( 'View Schemas', 'rank-math-pro' ),
			'search_items'       => __( 'Search schemas', 'rank-math-pro' ),
			'not_found'          => __( 'No schema found.', 'rank-math-pro' ),
			'not_found_in_trash' => __( 'No schema found in Trash.', 'rank-math-pro' ),
		];

		$capability = 'rank_math_onpage_snippet';
		$args       = [
			'label'               => __( 'Schema', 'rank-math-pro' ),
			'description'         => __( 'Rank Math Schema Templates', 'rank-math-pro' ),
			'labels'              => $labels,
			'supports'            => [ 'title' ],
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'menu_position'       => 5,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'page',
			'capabilities'        => [
				'edit_post'          => $capability,
				'read_post'          => $capability,
				'delete_post'        => $capability,
				'edit_posts'         => $capability,
				'edit_others_posts'  => $capability,
				'publish_posts'      => $capability,
				'read_private_posts' => $capability,
				'create_posts'       => $capability,
			],
			'show_in_rest'        => true,
		];

		register_post_type( 'rank_math_schema', $args );
	}

	/**
	 * Add post type as submenu.
	 */
	public function add_menu() {
		if ( ! Helper::has_cap( 'onpage_snippet' ) ) {
			return;
		}

		add_submenu_page(
			'rank-math',
			esc_html__( 'Schema Templates', 'rank-math-pro' ),
			esc_html__( 'Schema Templates', 'rank-math-pro' ),
			'edit_posts',
			'edit.php?post_type=rank_math_schema'
		);
	}

	/**
	 * Fix parent active menu
	 *
	 * @param  string $file Filename.
	 * @return string
	 */
	public function parent_file( $file ) {
		$screen = get_current_screen();

		if ( in_array( $screen->base, [ 'post', 'edit' ], true ) && 'rank_math_schema' === $screen->post_type ) {
			$file = 'rank-math';
		}

		return $file;
	}

	/**
	 * Fix submenu active menu
	 *
	 * @param  string $file Filename.
	 * @return string
	 */
	public function submenu_file( $file ) {
		$screen = get_current_screen();

		if ( in_array( $screen->base, [ 'post', 'edit' ], true ) && 'rank_math_schema' === $screen->post_type ) {
			$file = 'edit.php?post_type=rank_math_schema';
		}

		return $file;
	}
}
