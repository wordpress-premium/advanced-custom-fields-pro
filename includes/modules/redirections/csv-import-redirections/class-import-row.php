<?php
/**
 * The CSV Import class.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Redirections\CSV_Import_Export_Redirections;

use RankMath\Redirections\DB;
use RankMath\Redirections\Redirection;
use RankMath\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Importer class.
 *
 * @codeCoverageIgnore
 */
class Import_Row {

	/**
	 * Column defaults for optional columns.
	 *
	 * @var array
	 */
	private $column_defaults = [
		'matching' => 'exact',
		'type'     => '301',
		'category' => '',
		'status'   => 'active',
	];

	/**
	 * Stores whether import was successful or not.
	 *
	 * @var boolean
	 */
	public $success = false;

	/**
	 * Stores what kind of import action has been done - create, update, or delete.
	 *
	 * @var boolean
	 */
	public $action = '';

	/**
	 * Stores import error.
	 *
	 * @var string
	 */
	public $error = '';

	/**
	 * Stores row data.
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Stores import settings.
	 *
	 * @var array
	 */
	private $settings = [];

	/**
	 * Stores columns.
	 *
	 * @var array
	 */
	private $columns = [];

	/**
	 * Constructor.
	 *
	 * @param array $data     Row data.
	 * @param array $settings Import settings.
	 * @return void
	 */
	public function __construct( $data, $settings ) {
		$this->data     = $data;
		$this->settings = $settings;

		$this->import_redirection( $this->data );

		/**
		 * Do custom action after importing a row.
		 */
		do_action( 'rank_math/admin/csv_import_redirection_row', $data, $settings, $this );
	}

	/**
	 * Get column default value.
	 *
	 * @param string $column Column name.
	 * @return string
	 */
	public function get_column_default( $column ) {
		if ( isset( $this->column_defaults[ $column ] ) ) {
			return $this->column_defaults[ $column ];
		}
		return '';
	}

	/**
	 * Magic getter.
	 *
	 * Return column value if is set and column name is in allowed columns list.
	 *
	 * @param string $property Property we want to get.
	 * @return string
	 */
	public function __get( $property ) {
		if ( in_array( $property, $this->get_columns(), true ) && isset( $this->data[ $property ] ) ) {
			return $this->data[ $property ];
		}

		return $this->get_column_default( $property );
	}

	/**
	 * Get CSV columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		if ( ! empty( $this->columns ) ) {
			return $this->columns;
		}
		$this->columns   = CSV_Import_Export_Redirections::get_columns();
		$this->columns[] = 'ignore';

		return $this->columns;
	}

	/**
	 * Create or update redirection.
	 *
	 * @param array $data Redirection data.
	 * @return mixed
	 */
	public function import_redirection( $data = [] ) {

		$exist = DB::get_redirection( $data );

		/**
		 * Filter to modify the redirection data before updating a redirection.
		 * Pass a false value to skip the update and create a new redirection instead.
		 *
		 * @param array|false $data Redirection data.
		 */
		$exist = apply_filters( 'rank_math/admin/csv_import_redirection_update', $exist, $data, $this );
		if ( $exist ) {
			return $this->update_redirection( $exist, $data );
		}

		return $this->create_redirection();
	}

	/**
	 * Insert redirection.
	 *
	 * @return mixed
	 */
	public function create_redirection() {
		$sources = $this->get_sources();
		if ( ! $sources || ( in_array( $this->type, [ '301', '302', '307' ], true ) && ! $this->destination ) ) {
			return;
		}

		$redirection = Redirection::from(
			[
				'id'          => '',
				'url_to'      => $this->destination,
				'sources'     => $sources,
				'header_code' => $this->type,
				'status'      => $this->status,
			]
		);
		$redirection->set_nocache( true );
		if ( false === $redirection->save() ) {
			return;
		}

		if ( $this->category ) {
			// Create category if it doesn't exist.
			wp_set_object_terms( $redirection->id, Arr::from_string( $this->category ), 'rank_math_redirection_category' );
		}

		$this->success = true;
		$this->action  = 'created';
		return $redirection->id;
	}

	/**
	 * Edit an existing redirection.
	 *
	 * @param array $data Redirection exist.
	 * @return mixed
	 */
	public function update_redirection( $data, $input = [] ) {

		if ( 'DELETE' === $this->destination ) {
			$this->success = true;
			$this->action  = 'deleted';
			return $this->delete_redirection( $data['id'] );
		}

		$sources = $this->get_sources();
		if ( ! is_array( $data['sources'] ) ) {
			return;
		}
		$sources = array_unique( array_merge( $sources, $data['sources'] ), SORT_REGULAR );

		$url_to = ! empty( $input['destination'] ) ? $input['destination'] : $data['url_to'];
		$header_code = ! empty( $input['type'] ) ? $input['type'] : $data['header_code'];

		$redirection = Redirection::from(
			[
				'id'          => $data['id'],
				'sources'     => $sources,
				'url_to'      => $url_to,
				'header_code' => $header_code,
				'status'      => $data['status'],
			]
		);

		if ( false === $redirection->save() ) {
			return;
		}

		$category = $this->category ? Arr::from_string( $this->category ) : [];
		wp_set_object_terms( $redirection->id, $category, 'rank_math_redirection_category', true );

		$this->success = true;
		$this->action  = ( empty( $input['id'] ) ) ? 'merged' : 'updated';
		return $redirection->id;
	}

	/**
	 * Get stored error message or default.
	 *
	 * @return string
	 */
	public function get_error() {
		if ( ! empty( $this->error ) ) {
			return $this->error;
		}
		return esc_html__( 'Could not import redirection.', 'rank-math-pro' );
	}

	/**
	 * Delete a redirection.
	 *
	 * @param int $id Redirection ID.
	 * @return mixed
	 */
	public function delete_redirection( $id ) {
		return DB::delete( $id );
	}

	/**
	 * Get correctly formatted sources array for saving.
	 *
	 * @return array
	 */
	public function get_sources() {
		$sources = [];
		if ( substr( $this->source, 0, 1 ) === '[' && substr( $this->source, -1 ) === ']' ) {
			$sources = json_decode( $this->source, true );
			if ( ! is_array( $sources ) || empty( $sources ) ) {
				return false;
			}

			return $sources;
		}

		$sources = [
			[
				'pattern'    => wp_specialchars_decode( $this->source ),
				'comparison' => $this->matching,
				'ignore'     => $this->ignore,
			],
		];

		return $sources;
	}

}
