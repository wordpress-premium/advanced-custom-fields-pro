<?php
/**
 * Post filters for the manage posts screen.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Param;

defined( 'ABSPATH' ) || exit;

/**
 * Post filters class.
 *
 * @codeCoverageIgnore
 */
class Post_Filters {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->filter( 'rank_math/manage_posts/seo_filter_options', 'add_seo_filters', 10, 1 );
		$this->filter( 'restrict_manage_posts', 'add_schema_filters', 20, 0 );
		$this->filter( 'manage_posts_extra_tablenav', 'add_filter_clear_button', 20, 1 );
		$this->filter( 'pre_get_posts', 'posts_by_seo_filters', 20 );
	}

	/**
	 * Add our custom options to the SEO filter dropdown.
	 *
	 * @param array $options Original options.
	 *
	 * @return array New options.
	 */
	public function add_seo_filters( $options ) {
		$new_options = [
			'custom_canonical'   => __( 'Custom Canonical URL', 'rank-math-pro' ),
			'custom_title'       => __( 'Custom Meta Title', 'rank-math-pro' ),
			'custom_description' => __( 'Custom Meta Description', 'rank-math-pro' ),
			'redirected'         => __( 'Redirected Posts', 'rank-math-pro' ),
			'orphan'             => __( 'Orphan Posts', 'rank-math-pro' ),
		];

		if ( Helper::is_module_active( 'rich-snippet' ) ) {
			$new_options['schema_type'] = __( 'Filter by Schema Type', 'rank-math-pro' );
		}

		return $options + $new_options;
	}

	/**
	 * Output dropdown to filter by Schema type.
	 */
	public function add_schema_filters() {
		global $post_type;

		if ( 'attachment' === $post_type || ! in_array( $post_type, Helper::get_allowed_post_types(), true ) ) {
			return;
		}

		$options = [
			'none'                => esc_html__( 'Turned Off', 'rank-math-pro' ),
			'Article'             => esc_html__( 'Article', 'rank-math-pro' ),
			'BlogPosting'         => esc_html__( 'Blog Post', 'rank-math-pro' ),
			'NewsArticle'         => esc_html__( 'News Article', 'rank-math-pro' ),
			'Book'                => esc_html__( 'Book', 'rank-math-pro' ),
			'Course'              => esc_html__( 'Course', 'rank-math-pro' ),
			'Event'               => esc_html__( 'Event', 'rank-math-pro' ),
			'JobPosting'          => esc_html__( 'Job Posting', 'rank-math-pro' ),
			'MusicGroup'          => esc_html__( 'Music', 'rank-math-pro' ),
			'Movie'               => esc_html__( 'Movie', 'rank-math-pro' ),
			'Person'              => esc_html__( 'Person', 'rank-math-pro' ),
			'Product'             => esc_html__( 'Product', 'rank-math-pro' ),
			'Recipe'              => esc_html__( 'Recipe', 'rank-math-pro' ),
			'Restaurant'          => esc_html__( 'Restaurant', 'rank-math-pro' ),
			'Service'             => esc_html__( 'Service', 'rank-math-pro' ),
			'SoftwareApplication' => esc_html__( 'Software', 'rank-math-pro' ),
			'VideoObject'         => esc_html__( 'Video', 'rank-math-pro' ),
			'Dataset'             => esc_html__( 'Dataset', 'rank-math-pro' ),
			'FAQPage'             => esc_html__( 'FAQ', 'rank-math-pro' ),
			'ClaimReview'         => esc_html__( 'FactCheck', 'rank-math-pro' ),
			'HowTo'               => esc_html__( 'How To', 'rank-math-pro' ),
		];

		$options  = $this->do_filter( 'manage_posts/schema_filter_options', $options, $post_type );
		$selected = Param::get( 'schema-filter' );
		?>
		<select name="schema-filter" id="rank-math-schema-filter" class="hidden">
			<?php foreach ( $options as $val => $option ) : ?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $selected, $val, true ); ?>><?php echo esc_html( $option ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Filter for pre WP Query on the post list page.
	 *
	 * @param  object $query Query object passed by reference.
	 *
	 * @return void
	 */
	public function posts_by_seo_filters( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		if ( ! $this->can_seo_filters() ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );
		$filter     = Param::get( 'seo-filter' );
		$this->set_seo_filters( $meta_query, $filter );
		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}

		if ( 'redirected' === $filter ) {
			$query->set( 'is_redirected', 1 );
			$this->filter( 'posts_where', 'posts_where_redirected', 10, 2 );
		} elseif ( 'orphan' === $filter ) {
			$query->set( 'is_orphan', 1 );
			$this->filter( 'posts_where', 'posts_where_orphan', 10, 2 );
		}
	}

	/**
	 * Add SEO filters for the meta_query.
	 *
	 * @param array  $query  The meta_query array passed by reference.
	 * @param string $filter Input filter.
	 */
	public function set_seo_filters( &$query, $filter ) {
		if ( false === $filter ) {
			return;
		} elseif ( 'schema_type' === $filter ) {
			$schema_filter = Param::get( 'schema-filter' );
			$this->set_schema_filters( $query, $schema_filter );
			return;
		}

		$hash = [
			'custom_canonical'   => [
				[
					'key'     => 'rank_math_canonical_url',
					'compare' => 'EXISTS',
				],
				[
					'key'     => 'rank_math_canonical_url',
					'compare' => '!=',
					'value'   => '',
				],
			],
			'custom_title'       => [
				[
					'key'     => 'rank_math_title',
					'compare' => 'EXISTS',
				],
				[
					'key'     => 'rank_math_title',
					'compare' => '!=',
					'value'   => '',
				],
			],
			'custom_description' => [
				[
					'key'     => 'rank_math_description',
					'compare' => 'EXISTS',
				],
				[
					'key'     => 'rank_math_description',
					'compare' => '!=',
					'value'   => '',
				],
			],
		];

		if ( isset( $hash[ $filter ] ) ) {
			foreach ( $hash[ $filter ] as $query_parts ) {
				$query[] = $query_parts;
			}
			$query[] = $hash[ $filter ];
		}
	}

	/**
	 * Add Schema type filters for the meta_query.
	 *
	 * @param array  $query  The meta_query array passed by reference.
	 * @param string $filter Input filter.
	 */
	public function set_schema_filters( &$query, $filter ) {
		$post_type         = Param::get( 'post_type' );
		$post_type_default = Helper::get_settings( 'titles.pt_' . $post_type . '_default_rich_snippet' );

		if ( false === $filter ) {
			return;
		}

		if ( 'none' === $filter ) {
			$query[] = [
				'key'   => 'rank_math_rich_snippet',
				'value' => 'off',
			];
			$this->filter( 'posts_where', 'posts_where_no_schema', 20, 2 );
			return;
		}

		switch ( $filter ) {
			case 'Event':
				$query['relation'] = 'OR';
				foreach ( [ 'Event', 'BusinessEvent', 'ChildrensEvent', 'ComedyEvent', 'DanceEvent', 'DeliveryEvent', 'EducationEvent', 'ExhibitionEvent', 'Festival', 'FoodEvent', 'LiteraryEvent', 'MusicEvent', 'PublicationEvent', 'SaleEvent', 'ScreeningEvent', 'SocialEvent', 'SportsEvent', 'TheaterEvent', 'VisualArtsEvent' ] as $type ) {
					$query[] = [
						'key'     => 'rank_math_schema_' . $type,
						'compare' => 'LIKE',
					];
				}
				break;
			case 'Music':
				$query['relation'] = 'OR';
				foreach ( [ 'MusicAlbum', 'MusicGroup' ] as $type ) {
					$query[] = [
						'key'     => 'rank_math_schema_' . $type,
						'compare' => 'LIKE',
					];
				}
				break;
			default:
				$query[] = [
					'key'     => 'rank_math_schema_' . $filter,
					'compare' => 'LIKE',
				];
				break;
		}

		if ( strtolower( $filter ) === $post_type_default ) {
			// Also get not set because we filter for the default.
			$query['relation'] = 'OR';
			$query[]           = [
				'key'     => 'rank_math_rich_snippet',
				'compare' => 'NOT EXISTS',
			];
			$this->filter( 'posts_where', 'posts_where_no_schema', 20, 2 );
		}
	}

	/**
	 * Add extra WHERE clause to find posts with no Schema.
	 *
	 * @param string    $where    Original WHERE clause string.
	 * @param \WP_Query $wp_query WP_Query object.
	 * @return string
	 */
	public function posts_where_no_schema( $where, \WP_Query $wp_query ) {
		global $wpdb;
		$where .= " AND NOT EXISTS ( SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = wp_posts.ID AND meta_key LIKE 'rank_math_schema_%' )";
		// Remove this filter for subsequent queries.
		$this->remove_filter( 'posts_where', 'posts_where_no_schema', 20, 2 );

		return $where;
	}

	/**
	 * Can apply SEO filters.
	 *
	 * @return bool
	 */
	private function can_seo_filters() {
		global $pagenow;
		if ( 'edit.php' !== $pagenow || ! in_array( Param::get( 'post_type' ), Helper::get_allowed_post_types(), true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add our where clause to the query.
	 *
	 * @param  string $where  Original where clause.
	 * @param  object $query The query object.
	 *
	 * @return string        New where clause.
	 */
	public function posts_where_redirected( $where, $query ) {
		if ( ! $query->get( 'is_redirected' ) ) {
			return $where;
		}
		global $wpdb;
		$redirections_table = $wpdb->prefix . 'rank_math_redirections_cache';

		$where .= " AND ID IN ( SELECT object_id FROM {$redirections_table} WHERE is_redirected = 1 AND object_type = 'post' )";
		return $where;
	}

	/**
	 * Add our where clause to the query.
	 *
	 * @param  string $where  Original where clause.
	 * @param  object $query The query object.
	 *
	 * @return string        New where clause.
	 */
	public function posts_where_orphan( $where, $query ) {
		if ( ! $query->get( 'is_orphan' ) ) {
			return $where;
		}
		global $wpdb;
		$linkmeta_table = $wpdb->prefix . 'rank_math_internal_meta';

		$where .= " AND ID IN ( SELECT object_id FROM {$linkmeta_table} WHERE incoming_link_count = 0 )";
		return $where;
	}

	/**
	 * Add filter reset button
	 *
	 * @param string $which Where to place.
	 */
	public function add_filter_clear_button( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		$post_type     = get_post_type();
		$clear_label   = __( 'Clear Filter', 'rank-math-pro' );
		$clear_url     = add_query_arg( 'post_type', $post_type, admin_url( 'edit.php' ) );
		$clear_classes = 'clear-tablenav-filter';
		$filter_params = [ 'm', 'cat', 'seo-filter' ];
		$filtered      = false;

		foreach ( $filter_params as $filter ) {
			$val = Param::get( $filter );
			if ( ! empty( $val ) ) {
				$filtered = true;
				break;
			}
		}
		$clear_classes .= $filtered ? '' : ' hidden';

		echo '<a href="' . esc_url( $clear_url ) . '" class="' . esc_attr( $clear_classes ) . '" title="' . esc_attr( $clear_label ) . '"><span class="dashicons dashicons-dismiss"></span> ' . esc_html( $clear_label ) . '</a>';
	}
}
