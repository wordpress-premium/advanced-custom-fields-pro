<?php
/**
 * Redirection categories.
 *
 * @since      3.0.11
 * @package    RankMathPro
 * @subpackage RankMathPro\Redirections
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Redirections;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Categories class.
 */
class Categories {

	use Hooker;

	/**
	 * Save categories with a delay so that we know the new redirection ID.
	 *
	 * @var array
	 */
	private $save_categories = [];

	/**
	 * Import categories from the Redirection plugin.
	 *
	 * @var array
	 */
	private $import_categories = [];

	/**
	 * Whether categories have been imported from the Redirection plugin.
	 *
	 * @var boolean
	 */
	private $categories_imported = false;

	/**
	 * Register hooks.
	 */
	public function __construct() {

		// Redirection categories.
		$this->action( 'init', 'register_categories', 20 );
		$this->action( 'cmb2_admin_init', 'cmb_init', 99 );
		$this->action( 'admin_post_rank_math_save_redirections', 'save_category', 8 );
		$this->action( 'wp_loaded', 'filter_category', 5 );
		$this->action( 'rank_math/redirection/extra_tablenav', 'category_filter', 20, 1 );
		$this->action( 'rank_math/redirection/get_redirections_query', 'get_redirections_query', 20, 2 );
		$this->action( 'rank_math/redirection/after_import', 'import_redirection_categories', 20, 2 );
		$this->action( 'rank_math_redirection_category_add_form', 'back_to_redirections_link', 20 );

		$this->filter( 'rank_math/redirection/bulk_actions', 'bulk_actions', 20, 1 );
		$this->filter( 'wp_loaded', 'handle_bulk_actions', 8, 3 );
		$this->filter( 'rank_math/redirection/admin_columns', 'add_category_column', 20, 1 );
		$this->filter( 'rank_math/redirection/admin_column_category', 'category_column_content', 20, 2 );
		$this->filter( 'parent_file', 'fix_categories_parent_menu', 20, 1 );
		$this->filter( 'submenu_file', 'fix_categories_sub_menu', 20, 2 );

		$this->filter( 'rank_math/redirections/page_title_actions', 'page_title_actions', 20, 1 );
	}

	/**
	 * Register redirection categories taxonomy.
	 *
	 * @return void
	 */
	public function register_categories() {
		new CSV_Import_Export_Redirections\CSV_Import_Export_Redirections();
		$tax_labels = [
			'name'              => _x( 'Redirection Categories', 'taxonomy general name', 'rank-math-pro' ),
			'singular_name'     => _x( 'Redirection Category', 'taxonomy singular name', 'rank-math-pro' ),
			'search_items'      => __( 'Search Redirection Categories', 'rank-math-pro' ),
			'all_items'         => __( 'All Redirection Categories', 'rank-math-pro' ),
			'parent_item'       => __( 'Parent Category', 'rank-math-pro' ),
			'parent_item_colon' => __( 'Parent Category:', 'rank-math-pro' ),
			'edit_item'         => __( 'Edit Category', 'rank-math-pro' ),
			'update_item'       => __( 'Update Category', 'rank-math-pro' ),
			'add_new_item'      => __( 'Add New Category', 'rank-math-pro' ),
			'new_item_name'     => __( 'New Category Name', 'rank-math-pro' ),
			'menu_name'         => __( 'Redirection Categories', 'rank-math-pro' ),
		];

		$tax_args = [
			'labels'            => $tax_labels,
			'public'            => false,
			'rewrite'           => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'query_var'         => false,
			'hierarchical'      => true,
			'capabilities'      => [
				'manage_terms' => 'rank_math_redirections',
				'edit_terms'   => 'rank_math_redirections',
				'delete_terms' => 'rank_math_redirections',
				'assign_terms' => 'rank_math_redirections',
			],
		];

		register_taxonomy( 'rank_math_redirection_category', 'rank_math_redirection', $tax_args );
	}

	/**
	 * Add bulk actions for Redirections screen.
	 *
	 * @param array $actions Original actions.
	 * @return array
	 */
	public function bulk_actions( $actions ) {
		if ( Param::get( 'status' ) === 'trashed' ) {
			return $actions;
		}

		$actions['bulk_add_redirection_category'] = __( 'Add to Category', 'rank-math-pro' );
		return $actions;
	}

	/**
	 * Handle new bulk actions.
	 *
	 * @return void
	 */
	public function handle_bulk_actions() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || $_POST['action'] !== 'bulk_add_redirection_category' || ! Helper::has_cap( 'redirections' ) ) {
			return;
		}

		check_admin_referer( 'bulk-redirections' );

		$category_filter = ! empty( $_POST['redirection_category_filter_top'] ) ? $_POST['redirection_category_filter_top'] : $_POST['redirection_category_filter_bottom'];
		if ( empty( $category_filter ) ) {
			return;
		}

		$ids = (array) wp_parse_id_list( $_REQUEST['redirection'] );
		if ( empty( $ids ) ) {
			Helper::add_notification( __( 'No valid ID provided.', 'rank-math-pro' ) );
			return;
		}

		foreach ( $ids as $id ) {
			wp_set_object_terms( $id, absint( $category_filter ), 'rank_math_redirection_category', apply_filters( 'rank_math_pro/redirection/bulk_append_categories', true ) );
		}

		// Translators: placeholder is the number of updated redirections.
		Helper::add_notification( sprintf( __( '%d redirections have been assigned to the category.', 'rank-math-pro' ), count( $ids ) ) );
	}

	/**
	 * Hook CMB2 init process.
	 */
	public function cmb_init() {
		$this->action( 'cmb2_init_hookup_rank-math-redirections', 'add_category_cmb_field', 110 );
	}

	/**
	 * Add new fields to CMB form.
	 *
	 * @param object $cmb CMB object.
	 * @return void
	 */
	public function add_category_cmb_field( $cmb ) {
		$field_ids      = wp_list_pluck( $cmb->prop( 'fields' ), 'id' );
		$field_position = array_search( 'header_code', array_keys( $field_ids ), true ) + 1;

		$add_button  = '<span class="button button-secondary add-redirection-category-button">' . esc_html__( 'Add New', 'rank-math-pro' ) . '</span>';
		$add_input   = '<input type="text" class="add-redirection-category-input exclude" placeholder="' . esc_attr__( 'New Category', 'rank-math-pro' ) . '" value="">';
		$manage_link = '<a href="' . admin_url( 'edit-tags.php?taxonomy=rank_math_redirection_category' ) . '" class="manage-redirection-categories-link">' . esc_html__( 'Manage Categories', 'rank-math-pro' ) . '</a>';

		$terms = get_terms(
			[
				'taxonomy' => 'rank_math_redirection_category',
				'hide_empty' => false,
			]
		);

		$default_options = [];
		if ( $this->is_editing() ) {
			$default_options = $this->get_redirection_categories( Param::get( 'redirection' ), [ 'fields' => 'ids' ] );
		}

		$ids   = wp_list_pluck( $terms, 'term_id' );
		$names = wp_list_pluck( $terms, 'name' );

		$multicheck_options = array_combine( $ids, $names );

		$cmb->add_field(
			[
				'id'                => 'redirection_category',
				'type'              => 'multicheck_inline',
				'name'              => esc_html__( 'Redirection Category', 'rank-math-pro' ),
				'desc'              => esc_html__( 'Organize your redirections in categories.', 'rank-math-pro' ),
				'options'           => $multicheck_options,
				'select_all_button' => false,
				'after_field'       => $add_input . $add_button . $manage_link,
				'default'           => $default_options,
			],
			++$field_position
		);

		$cmb->add_field(
			[
				'id'      => 'set_redirection_category',
				'type'    => 'hidden',
				'default' => '1',
			],
			++$field_position
		);

	}

	/**
	 * Select correct parent item when we are editing the Redirection Categories.
	 *
	 * @param string $parent_file Original parent file.
	 * @return string
	 */
	public function fix_categories_parent_menu( $parent_file ) {
		global $pagenow;

		if ( in_array( $pagenow, [ 'edit-tags.php', 'term.php' ], true ) && Param::get( 'taxonomy' ) === 'rank_math_redirection_category' ) {
			$parent_file = 'rank-math';
		}

		return $parent_file;
	}

	/**
	 * Select correct submenu item when we are editing the Redirection Categories.
	 *
	 * @param string $submenu_file Original submenu file.
	 * @param string $parent_file Selected parent file.
	 * @return string
	 */
	public function fix_categories_sub_menu( $submenu_file, $parent_file ) {
		global $pagenow;

		if ( in_array( $pagenow, [ 'edit-tags.php', 'term.php' ], true ) && Param::get( 'taxonomy' ) === 'rank_math_redirection_category' ) {
			$submenu_file = 'rank-math-redirections';
		}

		return $submenu_file;
	}

	/**
	 * Add "Category" column for Redirections screen.
	 *
	 * @param array $columns Original columns.
	 * @return array
	 */
	public function add_category_column( $columns ) {
		$columns['category'] = __( 'Category', 'rank-math-pro' );
		return $columns;
	}

	/**
	 * Add content in the new "Category" column fields.
	 *
	 * @param bool  $false False.
	 * @param array $item  Item data.
	 * @return string
	 */
	public function category_column_content( $false, $item ) {
		$format = '<span class="%1$s">%2$s</span>';
		$categories = $this->get_redirection_categories( $item['id'] );
		$classes = '';

		$cats = '';
		$count = 0;
		foreach ( $categories as $category ) {
			$count++;
			if ( $count > 10 ) {
				$cats .= '...';
				break;
			}
			$url = Helper::get_admin_url( 'redirections', [ 'redirection_category' => $category->term_id ] );
			$cats .= '<a href="' . $url . '">' . $category->name . '</a>, ';
		}
		$cats = rtrim( $cats, ', ' );

		if ( empty( $cats ) ) {
			$cats = __( 'Uncategorized', 'rank-math-pro' );
			$classes .= ' uncategorized';
		}
		return sprintf( $format, $classes, $cats );
	}

	/**
	 * Get categories for a redirection.
	 *
	 * @param int   $redirection_id Redirection ID.
	 * @param array $args           Array of query string of term query parameters.
	 * @return array
	 */
	public function get_redirection_categories( $redirection_id, $args = [] ) {
		return wp_get_object_terms( $redirection_id, 'rank_math_redirection_category', $args );
	}

	/**
	 * Redirect to filtered URL when the category filter dropdown is used.
	 *
	 * @return void
	 */
	public function filter_category() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $_POST['rank_math_filter_redirections_top'] ) && ! isset( $_POST['rank_math_filter_redirections_bottom'] ) ) {
			return;
		}

		if ( ! Helper::has_cap( 'redirections' ) ) {
			return;
		}

		$category_filter = isset( $_POST['rank_math_filter_redirections_top'] ) ? $_POST['redirection_category_filter_top'] : $_POST['redirection_category_filter_bottom'];
		if ( ! $category_filter || 'none' === $category_filter ) {
			wp_safe_redirect( Helper::get_admin_url( 'redirections' ) );
			exit;
		}

		wp_safe_redirect( Helper::get_admin_url( 'redirections', [ 'redirection_category' => $category_filter ] ) );
		exit;
	}

	/**
	 * Save category when creating or editing a redirection.
	 *
	 * @return boolean
	 */
	public function save_category() {
		// If no form submission, bail!
		if ( empty( $_POST ) ) {
			return false;
		}

		if ( ! isset( $_POST['set_redirection_category'] ) ) {
			return false;
		}

		check_admin_referer( 'rank-math-save-redirections', 'security' );
		if ( ! Helper::has_cap( 'redirections' ) ) {
			return false;
		}

		$cmb    = cmb2_get_metabox( 'rank-math-redirections' );
		$values = $cmb->get_sanitized_values( $_POST );

		$values['redirection_category'] = isset( $values['redirection_category'] ) && is_array( $values['redirection_category'] ) ? $values['redirection_category'] : [];
		unset( $_POST['redirection_category'], $_POST['set_redirection_category'] );

		if ( empty( $values['id'] ) ) {
			$this->save_categories = $values['redirection_category'];
			$this->action( 'rank_math/redirection/saved', 'save_category_after_add' );
			return true;
		}

		wp_set_object_terms( $values['id'], array_map( 'absint', $values['redirection_category'] ), 'rank_math_redirection_category' );
		return true;
	}

	/**
	 * Save categories with a delay so that we know the new redirection ID.
	 *
	 * @param object $redirection Redirection object passed to the hook.
	 * @return bool
	 */
	public function save_category_after_add( $redirection ) {
		wp_set_object_terms( $redirection->get_id(), array_map( 'absint', $this->save_categories ), 'rank_math_redirection_category' );

		return true;
	}

	/**
	 * Output category filter dropdown and the submit button for it in the tablenav area.
	 *
	 * @param string $which "top" or "bottom".
	 * @return void
	 */
	public function category_filter( $which ) {
		if ( $this->is_trashed_page() ) {
			return;
		}

		$dropdown_args = [
			'taxonomy'          => 'rank_math_redirection_category',
			'show_option_all'   => false,
			'show_option_none'  => __( 'Select Category', 'rank-math-pro' ),
			'option_none_value' => 'none',
			'echo'              => false,
			'hierarchical'      => true,
			'name'              => 'redirection_category_filter_' . $which,
			'id'                => 'redirection-category-filter-' . $which,
			'selected'          => Param::get( 'redirection_category', '' ),
			'class'             => 'redirection-category-filter',
			'hide_empty'        => false,
		];

		$submit_args = [
			__( 'Filter', 'rank-math-pro' ), // text.
			'secondary category-filter-submit', // type.
			'rank_math_filter_redirections_' . $which, // name.
			false,  // wrap.
		];

		$clear_label    = __( 'Clear Filter', 'rank-math-pro' );
		$clear_url      = Helper::get_admin_url( 'redirections' );
		$clear_classes  = 'clear-redirection-category-filter';
		$clear_classes .= $dropdown_args['selected'] ? '' : ' hidden';
		$clear_button   = '<a href="' . $clear_url . '" class="' . esc_attr( $clear_classes ) . '" title="' . esc_attr( $clear_label ) . '"><span class="dashicons dashicons-dismiss"></span> ' . $clear_label . '</a>';

		$categories    = rtrim( wp_dropdown_categories( $dropdown_args ) );
		$submit_button = call_user_func_array( 'get_submit_button', $submit_args );

		echo sprintf( '%1$s%2$s%3$s', $categories, $submit_button, $clear_button );
	}

	/**
	 * Extend get_redirections query to filter by category.
	 *
	 * @param object $table Table object.
	 * @param array  $args Get redirections function args.
	 * @return void
	 */
	public function get_redirections_query( $table, $args ) {
		$categories = Param::get( 'redirection_category' );
		if ( ! $categories ) {
			return;
		}

		$categories = array_map( 'absint', (array) $categories );

		global $wpdb;
		$table->leftJoin( $wpdb->term_relationships, $wpdb->prefix . 'rank_math_redirections.id', $wpdb->term_relationships . '.object_id' );
		$table->leftJoin( $wpdb->term_taxonomy, $wpdb->term_taxonomy . '.term_taxonomy_id', $wpdb->term_relationships . '.term_taxonomy_id' );
		$table->whereIn( $wpdb->term_taxonomy . '.term_id', $categories );
	}

	/**
	 * Import category from the Redirection plugin after importing the redirection itself.
	 *
	 * @param int   $redirection_id  Redirection ID of the imported redirection.
	 * @param array $source_data_row Data related to the redirection in the original table.
	 * @return void
	 */
	public function import_redirection_categories( $redirection_id, $source_data_row ) {
		if ( ! isset( $this->categories_imported ) ) {
			$this->import_categories_from_redirection_plugin();
		}

		if ( ! $source_data_row->group_id || ! isset( $this->import_categories[ $source_data_row->group_id ] ) ) {
			return;
		}

		wp_set_object_terms( $redirection_id, $this->import_categories[ $source_data_row->group_id ], 'rank_math_redirection_category' );
	}

	/**
	 * Import "groups" from Redirection plugin as redirection categories.
	 *
	 * @return bool
	 */
	public function import_categories_from_redirection_plugin() {
		global $wpdb;

		$count = 0;
		$rows  = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}redirection_groups" );

		$this->import_categories = [];

		if ( empty( $rows ) ) {
			$this->categories_imported = true;
			return false;
		}

		foreach ( (array) $rows as $row ) {
			$insert = wp_insert_term( $row->name, 'rank_math_redirection_category' );
			if ( ! is_array( $insert ) || ! isset( $insert['term_id'] ) ) {
				continue;
			}
			$this->import_categories[ $row->id ] = $insert['term_id'];
		}

		$this->categories_imported = true;
		return true;
	}

	/**
	 * Show link to go back to the Redirections from the Redirections Categories screen.
	 *
	 * @param string $taxonomy Current taxonomy.
	 *
	 * @return void
	 */
	public function back_to_redirections_link( $taxonomy ) {
		$link = Helper::get_admin_url( 'redirections' );
		echo '<p><a href="' . esc_url( $link ) . '">' . esc_html__( '&larr; Go Back to the Redirections', 'rank-math-pro' ) . '</a></p>';
	}

	/**
	 * Is editing a record.
	 *
	 * @return int|boolean
	 */
	public function is_editing() {

		if ( 'edit' !== Param::get( 'action' ) ) {
			return false;
		}

		return Param::get( 'redirection', false, FILTER_VALIDATE_INT );
	}

	/**
	 * Checks if page status is set to trashed.
	 *
	 * @return bool
	 */
	protected function is_trashed_page() {
		return 'trashed' === Param::get( 'status' );
	}

	/**
	 * Add page title action for categories.
	 *
	 * @param array $actions Original actions.
	 * @return array
	 */
	public function page_title_actions( $actions ) {
		// Move Settings button to the end.
		$tmp_settings = false;
		if ( isset( $actions['settings'] ) ) {
			$tmp_settings = $actions['settings'];
			unset( $actions['settings'] );
		}

		$actions['manage_categories'] = [
			'class' => 'page-title-action',
			'href'  => admin_url( 'edit-tags.php?taxonomy=rank_math_redirection_category' ),
			'label' => __( 'Manage Categories', 'rank-math-pro' ),
		];

		if ( $tmp_settings ) {
			$actions['settings'] = $tmp_settings;
		}

		return $actions;
	}
}
