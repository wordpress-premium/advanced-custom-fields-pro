<?php
/**
 * The CSV Export class.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Redirections\CSV_Import_Export_Redirections;

use RankMath\Helper;
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

	/**
	 * Redirection cache.
	 *
	 * @var array
	 */
	private $redirection = [];

	/**
	 * Constructor.
	 *
	 * @param array $options Export options.
	 * @return void
	 */
	public function __construct( $options ) {
		$defaults       = [
			'include_deactivated' => true,
		];
		$this->settings = wp_parse_args( $options, $defaults );
		$this->columns  = CSV_Import_Export_Redirections::get_columns();
	}

	/**
	 * Do export.
	 *
	 * @return void
	 */
	public function process_export() {
		$this->export(
			[
				'filename' => 'rank-math-redirections',
				'columns'  => $this->columns,
				'items'    => $this->get_items(),
			]
		);

		exit;
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
		$val = '';

		switch ( $column ) {
			case 'id':
				$val = $object->id;
				break;

			case 'source':
				$val = $object->source_processed;
				break;

			case 'matching':
				$val = $object->matching_processed;
				break;

			case 'destination':
				$val = $object->url_to;
				break;

			case 'type':
				$val = $object->header_code;
				break;

			case 'category':
				$val = $object->categories_processed;
				break;

			case 'status':
				$val = $object->status;
				break;
		}
		return $this->escape_csv( apply_filters( "rank_math/admin/csv_export_redirections_column_{$column}", $val, $object ) ); //phpcs:ignore
	}

	/**
	 * Get all redirection IDs.
	 *
	 * @return array
	 */
	public function get_ids() {
		global $wpdb;
		$table    = $wpdb->prefix . 'rank_math_redirections';
		$statuses = [ 'active' ];
		if ( $this->settings['include_deactivated'] ) {
			$statuses[] = 'inactive';
		}
		$where    = 'status IN (\'' . join( '\',\'', $statuses ) . '\')';
		$post_ids = $wpdb->get_col( "SELECT ID FROM {$table} WHERE $where" ); // phpcs:ignore

		return $post_ids;
	}

	/**
	 * Export all redirections.
	 *
	 * @return array
	 */
	public function get_items() {
		global $wpdb;
		$items = [];
		$ids   = $this->get_ids();
		if ( ! $ids ) {
			return $items;
		}

		$primary_column = 'id';
		$table          = $wpdb->prefix . 'rank_math_redirections';
		$cols           = $this->columns;

		// Fetch 50 at a time rather than loading the entire table into memory.
		while ( $next_batch = array_splice( $ids, 0, 50 ) ) {
			$where          = 'WHERE ' . $primary_column . ' IN (' . join( ',', $next_batch ) . ')';
			$objects        = $wpdb->get_results( "SELECT * FROM {$table} $where" );
			$current_object = 0;
			// Begin Loop.
			foreach ( $objects as $object ) {
				$current_object++;
				$columns = [];
				foreach ( $cols as $column ) {
					$this->process_sources( $object );
					$columns[] = $this->get_column_value( $column, $object ); // phpcs:ignore
				}

				$items[] = $columns;
			}
		}

		return $items;
	}

	/**
	 * Process sources & categories data for export.
	 *
	 * @param object $object Redirection row.
	 * @return void
	 */
	public function process_sources( &$object ) {
		$sources                      = maybe_unserialize( $object->sources );
		$object->source_processed     = json_encode( $sources );
		$object->matching_processed   = '';
		$object->categories_processed = '';

		if ( count( $sources ) === 1 ) {
			$object->source_processed   = $sources[0]['pattern'];
			$object->matching_processed = $sources[0]['comparison'];
		}

		$terms = wp_get_object_terms( $object->id, 'rank_math_redirection_category' );
		if ( is_a( $terms, 'WP_Error' ) || ! is_array( $terms ) || empty( $terms ) ) {
			return;
		}
		$object->categories_processed = join( ', ', wp_list_pluck( $terms, 'slug' ) );
	}
}
