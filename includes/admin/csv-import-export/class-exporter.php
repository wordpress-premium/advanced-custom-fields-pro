<?php
/**
 * The CSV Export class.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin\CSV_Import_Export;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Redirections\DB;
use RankMath\Redirections\Cache;
use RankMathPro\Admin\CSV;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Export.
 *
 * @codeCoverageIgnore
 */
class Exporter extends CSV {

	use Hooker;

	/**
	 * Data
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Term ID => slug cache.
	 *
	 * @var array
	 */
	private $term_slugs = [];

	/**
	 * Not applicable placeholder.
	 *
	 * @var string
	 */
	private $not_applicable_value = 'n/a';

	/**
	 * Object types we want to export.
	 *
	 * @var array
	 */
	private $object_types = [];

	/**
	 * Use advanced options for export.
	 *
	 * @var bool
	 */
	private $use_advanced_options = false;

	/**
	 * Advanced options.
	 *
	 * @var array
	 */
	private $advanced_options = [];

	/**
	 * Redirection cache.
	 *
	 * @var array
	 */
	private $redirection = [];

	/**
	 * Constructor.
	 *
	 * @param mixed $object_types     Object types to export.
	 * @param mixed $advanced_options Options.
	 * @return void
	 */
	public function __construct( $object_types, $advanced_options ) {
		$this->object_types         = array_intersect( array_keys( CSV_Import_Export::get_possible_object_types() ), $object_types );
		$this->use_advanced_options = ! empty( $advanced_options );
		$this->advanced_options     = $advanced_options;

		if ( empty( $this->object_types ) ) {
			wp_die( esc_html__( 'Please select at least one object type to export.', 'rank-math-pro' ) );
		}

		$this->not_applicable_value = apply_filters( 'rank_math/admin/csv_export_not_applicable', $this->not_applicable_value );

		$this->needs_link_count = false;
		if ( $this->use_advanced_options && ! empty( $this->advanced_options['readonly_columns'] ) ) {
			if ( Helper::is_module_active( 'link-counter' ) ) {
				$this->needs_link_count = true;
			}

			$this->filter( 'rank_math/admin/csv_export_columns', 'add_readonly_columns' );
		}

		$this->columns = CSV_Import_Export::get_columns();
	}

	/**
	 * Do export.
	 *
	 * @return void
	 */
	public function process_export() {
		$this->export(
			[
				'filename' => 'rank-math',
				'columns'  => $this->columns,
				'items'    => $this->get_items(),
			]
		);

		exit;
	}

	/**
	 * Output column contents.
	 *
	 * @return array
	 */
	public function get_items() {
		foreach ( $this->object_types as  $object_type ) {
			$this->get_objects( $object_type );
		}

		return $this->data;
	}

	/**
	 * Get value for given column.
	 *
	 * @param string $column Column name.
	 * @param object $object WP_Post, WP_Term or WP_User.
	 *
	 * @return string
	 */
	public function get_column_value( $column, $object ) {
		global $wpdb;

		$value       = '';
		$object_type = 'post';
		if ( ! empty( $object->term_id ) ) {
			$object_type = 'term';
		} elseif ( ! empty( $object->user_login ) ) {
			$object_type = 'user';
		}

		$table          = "{$object_type}meta";
		$primary_column = "{$object_type}_id";
		$object_id      = isset( $object->ID ) ? $object->ID : $object->$primary_column;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta_rows = $wpdb->get_results(
			$wpdb->prepare(
				/* translators: %d: object id, %s: table name */
				"SELECT * FROM {$wpdb->$table} WHERE {$primary_column} = %d AND meta_key LIKE %s",
				$object_id,
				$wpdb->esc_like( 'rank_math_' ) . '%'
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$meta = $this->process_meta_rows( $meta_rows );

		$internal_meta = (object) [];
		if ( 'post' === $object_type && $this->needs_link_count ) {
			$internal_meta = $this->get_link_counts( $object_id );
		}

		if ( 'user' !== $object_type && in_array( $column, [ 'redirect_to', 'redirect_type' ], true ) ) {
			$redirection = $this->get_redirection( $object_type, $object_id );
		}

		switch ( $column ) {
			case 'id':
				$value = $object_id;
				break;

			case 'object_type':
				$value = $object_type;
				break;

			case 'slug':
				$slug = '';
				if ( 'user' === $object_type ) {
					$slug = $object->user_nicename;
				} elseif ( 'post' === $object_type ) {
					$slug = $object->post_name;
				} elseif ( 'term' === $object_type ) {
					$slug = $object->slug;
				}
				$value = urldecode( $slug );
				break;

			case 'seo_title':
				if ( isset( $meta['rank_math_title'] ) ) {
					$value = $meta['rank_math_title'];
				}
				break;

			case 'seo_description':
				if ( isset( $meta['rank_math_description'] ) ) {
					$value = $meta['rank_math_description'];
				}
				break;

			case 'is_pillar_content':
				$value = 'no';
				if ( in_array( $object_type, [ 'term', 'user' ], true ) ) {
					$value = $this->not_applicable_value;
					break;
				}
				if ( ! empty( $meta['rank_math_pillar_content'] ) ) {
					$value = 'yes';
				}
				break;

			case 'focus_keyword':
				if ( isset( $meta['rank_math_focus_keyword'] ) ) {
					$value = $meta['rank_math_focus_keyword'];
				}
				break;

			case 'seo_score':
				if ( isset( $meta['rank_math_seo_score'] ) ) {
					$value = $meta['rank_math_seo_score'];
				}
				break;

			case 'robots':
				if ( isset( $meta['rank_math_robots'] ) ) {
					$value = $this->process_robots( $meta['rank_math_robots'] );
				}
				break;

			case 'advanced_robots':
				if ( isset( $meta['rank_math_advanced_robots'] ) ) {
					$value = $this->process_advanced_robots( $meta['rank_math_advanced_robots'] );
				}
				break;

			case 'canonical_url':
				if ( isset( $meta['rank_math_canonical_url'] ) ) {
					$value = $meta['rank_math_canonical_url'];
				}
				break;

			case 'primary_term':
				if ( in_array( $object_type, [ 'term', 'user' ], true ) ) {
					$value = $this->not_applicable_value;
					break;
				}
				$value = $this->get_primary_term( $meta );
				break;

			case 'schema_data':
				if ( in_array( $object_type, [ 'term', 'user' ], true ) ) {
					$value = $this->not_applicable_value;
					break;
				}
				$value = $this->process_schema_data( $meta );
				break;

			case 'social_facebook_thumbnail':
				if ( isset( $meta['rank_math_facebook_image'] ) ) {
					$value = $meta['rank_math_facebook_image'];
				}
				break;

			case 'social_facebook_title':
				if ( isset( $meta['rank_math_facebook_title'] ) ) {
					$value = $meta['rank_math_facebook_title'];
				}
				break;

			case 'social_facebook_description':
				if ( isset( $meta['rank_math_facebook_description'] ) ) {
					$value = $meta['rank_math_facebook_description'];
				}
				break;

			case 'social_twitter_thumbnail':
				if ( empty( $meta['rank_math_twitter_use_facebook'] ) || 'on' !== $meta['rank_math_twitter_use_facebook'] ) {
					break;
				}
				if ( isset( $meta['rank_math_twitter_image'] ) ) {
					$value = $meta['rank_math_twitter_image'];
				}
				break;

			case 'social_twitter_title':
				if ( ! isset( $meta['rank_math_twitter_use_facebook'] ) || 'on' !== $meta['rank_math_twitter_use_facebook'] ) {
					break;
				}
				if ( isset( $meta['rank_math_twitter_title'] ) ) {
					$value = $meta['rank_math_twitter_title'];
				}
				break;

			case 'social_twitter_description':
				if ( ! isset( $meta['rank_math_twitter_use_facebook'] ) || 'on' !== $meta['rank_math_twitter_use_facebook'] ) {
					break;
				}
				if ( isset( $meta['rank_math_twitter_description'] ) ) {
					$value = $meta['rank_math_twitter_description'];
				}
				break;

			case 'redirect_to':
				if ( 'user' === $object_type ) {
					$value = $this->not_applicable_value;
					break;
				}
				if ( empty( $redirection['id'] ) ) {
					break;
				}
				$value = $redirection['url_to'];
				break;

			case 'redirect_type':
				if ( 'user' === $object_type ) {
					$value = $this->not_applicable_value;
					break;
				}
				if ( empty( $redirection['id'] ) ) {
					break;
				}
				$value = $redirection['header_code'];
				break;

			case 'internal_link_count':
			case 'external_link_count':
			case 'incoming_link_count':
				$value = $this->not_applicable_value;
				if ( isset( $internal_meta->$column ) ) {
					$value = $internal_meta->$column;
				}
				break;
		}

		return $this->escape_csv( apply_filters( "rank_math/admin/csv_export_column_{$column}", $value, $object ) ); //phpcs:ignore
	}

	/**
	 * Get redirection for object.
	 *
	 * @param string $object_type Object type (post/term).
	 * @param int    $object_id   Object ID.
	 * @return array
	 */
	public function get_redirection( $object_type, $object_id ) {
		if ( isset( $this->redirection[ $object_id ] ) ) {
			return $this->redirection[ $object_id ];
		}
		$url = 'term' === $object_type ? get_term_link( (int) $object_id ) : get_permalink( $object_id );
		$url = wp_parse_url( $url, PHP_URL_PATH );
		$url = trim( $url, '/' );

		$redirection = Cache::get_by_object_id( $object_id, $object_type );
		$redirection = $redirection ? DB::get_redirection_by_id( $redirection->redirection_id, 'active' ) : [
			'id'          => '',
			'url_to'      => '',
			'header_code' => Helper::get_settings( 'general.redirections_header_code' ),
		];

		$this->redirection = [ $object_id => $redirection ];

		return $redirection;
	}

	/**
	 * From DB format to key => value.
	 *
	 * @param array $rows Meta data rows from DB.
	 * @return array
	 */
	public function process_meta_rows( $rows ) {
		$out = [];
		foreach ( $rows as $meta ) {
			$out[ $meta->meta_key ] = $meta->meta_value;
		}
		return $out;
	}

	/**
	 * From DB format to CSV compatible.
	 *
	 * @param array $meta Robots meta value from DB.
	 * @return string
	 */
	public function process_robots( $meta ) {
		$meta = maybe_unserialize( $meta );

		return join( ',', $meta );
	}

	/**
	 * From DB format to CSV compatible.
	 *
	 * @param array $meta Robots meta value from DB.
	 * @return string
	 */
	public function process_advanced_robots( $meta ) {
		$meta = maybe_unserialize( $meta );

		return http_build_query( $meta, '', ', ' );
	}

	/**
	 * From DB format to JSON-encoded.
	 *
	 * @param array $metadata Schema data meta value from DB.
	 * @return string
	 */
	public function process_schema_data( $metadata ) {
		$output      = [];
		$schema_data = $this->filter_schema_meta( $metadata );

		if ( empty( $schema_data ) ) {
			return '';
		}

		foreach ( $schema_data as $meta_key => $meta_value ) {
			$name       = substr( $meta_key, 17 );
			$meta_value = maybe_unserialize( $meta_value );

			if ( $name ) {
				$output[ $name ] = $meta_value;
			}
		}

		return wp_json_encode( $output, JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Get all the rank_math_schema_* post meta values from all the values.
	 *
	 * @param array $metadata Schema data meta value from DB.
	 * @return array
	 */
	private function filter_schema_meta( $metadata ) {
		$found = [];
		foreach ( $metadata as $meta_key => $meta_value ) {
			if ( substr( $meta_key, 0, 17 ) === 'rank_math_schema_' ) {
				$found[ $meta_key ] = $meta_value;
			}
		}
		return $found;
	}

	/**
	 * Get primary term for given object.
	 *
	 * @param mixed $meta   Processed meta data.
	 * @return string
	 */
	public function get_primary_term( $meta ) {
		if ( empty( $meta['rank_math_primary_category'] ) ) {
			return '';
		}

		return $this->get_term_slug( $meta['rank_math_primary_category'] );
	}

	/**
	 * Get all post IDs.
	 *
	 * @return array
	 */
	public function get_post_ids() {
		global $wpdb;

		$where = $this->get_posts_where();

		$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE $where" ); // phpcs:ignore
		return $post_ids;
	}

	/**
	 * Get all term IDs.
	 *
	 * @return array
	 */
	public function get_term_ids() {
		global $wpdb;
		$taxonomies = Helper::get_allowed_taxonomies();

		if ( $this->use_advanced_options ) {
			if ( empty( $this->advanced_options['taxonomies'] ) ) {
				return [];
			}
			$taxonomies = $this->advanced_options['taxonomies'];
		}

		$term_ids = get_terms(
			[
				'taxonomy'   => $taxonomies,
				'fields'     => 'ids',
				'hide_empty' => false,
			]
		);

		return $term_ids;
	}

	/**
	 * Get all user IDs.
	 *
	 * @return array
	 */
	public function get_user_ids() {
		$args = [ 'fields' => [ 'ID' ] ];
		if ( $this->use_advanced_options ) {
			if ( empty( $this->advanced_options['roles'] ) ) {
				return [];
			}
			$args['role__in'] = $this->advanced_options['roles'];
		}

		$user_ids = get_users( $args );
		return wp_list_pluck( $user_ids, 'ID' );
	}

	/**
	 * Export all items of specified object type. Output column values.
	 *
	 * @param string $object_type Object type to export.
	 * @return array
	 */
	public function get_objects( $object_type ) {
		global $wpdb;
		$object_type_plural = $object_type . 's';
		// get_post_ids, get_term_ids, get_user_ids.
		$method = "get_{$object_type}_ids";
		$ids    = $this->$method();
		if ( ! $ids ) {
			return [];
		}

		$primary_column = 'ID';
		if ( 'term' === $object_type ) {
			$primary_column = "{$object_type}_id";
		}

		$cols = $this->columns;

		// Fetch 50 at a time rather than loading the entire table into memory.
		while ( $next_batch = array_splice( $ids, 0, 50 ) ) { // phpcs:ignore
			$where = 'WHERE ' . $primary_column . ' IN (' . join( ',', $next_batch ) . ')';

			$objects        = $wpdb->get_results( "SELECT * FROM {$wpdb->$object_type_plural} $where" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_object = 0;

			// Begin Loop.
			foreach ( $objects as $object ) {
				$current_object++;
				$current_col = 0;
				$columns     = [];
				foreach ( $cols as $column ) {
					$current_col++;
					$columns[] = $this->get_column_value( $column, $object ); // phpcs:ignore
				}
				$this->data[] = $columns;
			}
		}

		return $this->data;
	}

	/**
	 * Get WHERE for post types.
	 *
	 * @return string
	 */
	private function get_posts_where() {
		global $wpdb;

		$post_types = Helper::get_allowed_post_types();
		if ( $this->use_advanced_options ) {
			if ( empty( $this->advanced_options['post_types'] ) ) {
				return [];
			}

			$post_types = $this->advanced_options['post_types'];
		}

		$esses = array_fill( 0, count( $post_types ), '%s' );

		$where = $wpdb->prepare( "{$wpdb->posts}.post_type IN (" . implode( ',', $esses ) . ')', $post_types ); // phpcs:ignore

		$where .= " AND {$wpdb->posts}.post_status != 'auto-draft'";

		return $where;
	}

	/**
	 * Get slug from term ID.
	 *
	 * @param int $term_id Term ID.
	 * @return string
	 */
	public function get_term_slug( $term_id ) {
		if ( isset( $this->term_slugs[ $term_id ] ) ) {
			return $this->term_slugs[ $term_id ];
		}
		global $wpdb;
		$where                        = 'term_id = ' . absint( $term_id ) . '';
		$this->term_slugs[ $term_id ] = $wpdb->get_var( "SELECT slug FROM {$wpdb->terms} WHERE $where" ); // phpcs:ignore

		return $this->term_slugs[ $term_id ];
	}

	/**
	 * Add read-only columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function add_readonly_columns( $columns ) {
		$columns[] = 'seo_score';
		if ( $this->needs_link_count ) {
			$columns[] = 'internal_link_count';
			$columns[] = 'external_link_count';
			$columns[] = 'incoming_link_count';
		}

		return $columns;
	}

	/**
	 * Get post link counts.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return object
	 */
	public function get_link_counts( $post_id ) {
		global $wpdb;

		$counts = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}rank_math_internal_meta WHERE object_id = {$post_id}" ); // phpcs:ignore
		$counts = ! empty( $counts ) ? $counts : (object) [
			'internal_link_count' => '',
			'external_link_count' => '',
			'incoming_link_count' => '',
		];

		return $counts;
	}
}
