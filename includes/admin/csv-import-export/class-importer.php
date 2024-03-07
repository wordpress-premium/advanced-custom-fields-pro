<?php
/**
 * The CSV Import class.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin\CSV_Import_Export;

use RankMath\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Importer class.
 *
 * @codeCoverageIgnore
 */
class Importer {

	/**
	 * Term slug => ID cache.
	 *
	 * @var array
	 */
	private static $term_ids = [];

	/**
	 * Settings array. Default values.
	 *
	 * @var array
	 */
	private $settings = [
		'not_applicable_value' => 'n/a',
		'clear_command'        => 'DELETE',
		'no_overwrite'         => true,
	];

	/**
	 * Lines in the CSV that could not be imported for any reason.
	 *
	 * @var array
	 */
	private $failed_rows = [];

	/**
	 * Lines in the CSV that could be imported successfully.
	 *
	 * @var array
	 */
	private $imported_rows = [];

	/**
	 * Error messages.
	 *
	 * @var array
	 */
	private $errors = [];

	/**
	 * SplFileObject instance.
	 *
	 * @var \SplFileObject
	 */
	private $spl;

	/**
	 * Column headers.
	 *
	 * @var array
	 */
	private $column_headers = [];

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->load_settings();
	}

	/**
	 * Load settings.
	 *
	 * @return void
	 */
	public function load_settings() {
		$this->settings = apply_filters( 'rank_math/admin/csv_import_settings', wp_parse_args( get_option( 'rank_math_csv_import_settings', [] ), $this->settings ) );
	}

	/**
	 * Start import from file.
	 *
	 * @param string $file     Path to temporary CSV file.
	 * @param string $settings Import settings.
	 * @return void
	 */
	public function start( $file, $settings = [] ) {
		update_option( 'rank_math_csv_import', $file );
		update_option( 'rank_math_csv_import_settings', $settings );
		delete_option( 'rank_math_csv_import_status' );
		$this->load_settings();
		$lines = $this->count_lines( $file );
		update_option( 'rank_math_csv_import_total', $lines );
		Import_Background_Process::get()->start( $lines );
	}

	/**
	 * Count all lines in CSV file.
	 *
	 * @param mixed $file Path to CSV.
	 * @return int
	 */
	public function count_lines( $file ) {
		$file = new \SplFileObject( $file );
		while ( $file->valid() ) {
			$file->fgets();
		}

		$count = $file->key();

		// Check if last line is empty.
		$file->seek( $count );
		$contents = $file->current();
		if ( empty( trim( $contents ) ) ) {
			$count--;
		}

		// Unlock file.
		$file = null;

		return $count;
	}

	/**
	 * Get specified line from CSV.
	 *
	 * @param string $file Path to file.
	 * @param int    $line Line number.
	 * @return string
	 */
	public function get_line( $file, $line ) {
		if ( empty( $this->spl ) ) {
			$this->spl = new \SplFileObject( $file );
		}

		if ( ! $this->spl->eof() ) {
			$this->spl->seek( $line );
			$contents = $this->spl->current();
		}

		return $contents;
	}

	/**
	 * Parse and return column headers (first line in CSV).
	 *
	 * @param string $file Path to file.
	 * @return array
	 */
	public function get_column_headers( $file ) {
		if ( ! empty( $this->column_headers ) ) {
			return $this->column_headers;
		}

		if ( empty( $this->spl ) ) {
			$this->spl = new \SplFileObject( $file );
		}

		if ( ! $this->spl->eof() ) {
			$this->spl->seek( 0 );
			$contents = $this->spl->current();
		}

		if ( empty( $contents ) ) {
			return [];
		}

		$this->column_headers = Arr::from_string( $contents, apply_filters( 'rank_math/csv_import/separator', ',' ) );
		return $this->column_headers;
	}

	/**
	 * Import specified line.
	 *
	 * @param int $line_number Selected line number.
	 * @return void
	 */
	public function import_line( $line_number ) {
		// Skip headers.
		if ( 0 === $line_number ) {
			return;
		}

		$file = get_option( 'rank_math_csv_import' );
		if ( ! $file ) {
			$this->add_error( esc_html__( 'Missing import file.', 'rank-math-pro' ), 'missing_file' );
			CSV_Import_Export::cancel_import( true );
			return;
		}

		$headers = $this->get_column_headers( $file );
		if ( empty( $headers ) ) {
			$this->add_error( esc_html__( 'Missing CSV headers.', 'rank-math-pro' ), 'missing_headers' );
			return;
		}

		$required_columns = [ 'id', 'object_type', 'slug' ];
		if ( count( array_intersect( $headers, $required_columns ) ) !== count( $required_columns ) ) {
			$this->add_error( esc_html__( 'Missing one or more required columns.', 'rank-math-pro' ), 'missing_required_columns' );
			return;
		}

		$raw_data = $this->get_line( $file, $line_number );
		if ( empty( $raw_data ) ) {
			$total_lines = (int) get_option( 'rank_math_csv_import_total' );

			// Last line can be empty, that is not an error.
			if ( $line_number !== $total_lines ) {
				$this->add_error( esc_html__( 'Empty column data.', 'rank-math-pro' ), 'missing_data' );
				$this->row_failed( $line_number );
			}

			return;
		}

		$csv_separator = apply_filters( 'rank_math/csv_import/separator', ',' );
		$decoded       = str_getcsv( $raw_data, $csv_separator );
		if ( count( $headers ) !== count( $decoded ) ) {
			$this->add_error( esc_html__( 'Columns number mismatch.', 'rank-math-pro' ), 'columns_number_mismatch' );
			$this->row_failed( $line_number );
			return;
		}

		$data = array_combine( $headers, $decoded );
		if ( ! in_array( $data['object_type'], array_keys( CSV_Import_Export::get_possible_object_types() ), true ) ) {
			$this->add_error( esc_html__( 'Unknown object type.', 'rank-math-pro' ), 'unknown_object_type' );
			$this->row_failed( $line_number );
			return;
		}

		new Import_Row( $data, $this->settings );
		$this->row_imported( $line_number );
	}

	/**
	 * Get term ID from slug.
	 *
	 * @param string $term_slug Term slug.
	 * @return int
	 */
	public static function get_term_id( $term_slug ) {
		global $wpdb;

		if ( ! empty( self::$term_ids[ $term_slug ] ) ) {
			return self::$term_ids[ $term_slug ];
		}

		self::$term_ids[ $term_slug ] = $wpdb->get_var(
			$wpdb->prepare( "SELECT term_id FROM {$wpdb->terms} WHERE slug = %s", $term_slug )
		);

		return self::$term_ids[ $term_slug ];
	}

	/**
	 * After each batch is finished.
	 *
	 * @param array $items Processed items.
	 */
	public function batch_done( $items ) { // phpcs:ignore
		unset( $this->spl );

		$status = (array) get_option( 'rank_math_csv_import_status', [] );
		if ( ! isset( $status['errors'] ) || ! is_array( $status['errors'] ) ) {
			$status['errors'] = [];
		}
		if ( ! isset( $status['failed_rows'] ) || ! is_array( $status['failed_rows'] ) ) {
			$status['failed_rows'] = [];
		}
		if ( ! isset( $status['imported_rows'] ) || ! is_array( $status['imported_rows'] ) ) {
			$status['imported_rows'] = [];
		}

		$status['imported_rows'] = array_merge( $status['imported_rows'], $this->get_imported_rows() );

		$errors = $this->get_errors();
		if ( $errors ) {
			$status['errors']      = array_merge( $status['errors'], $errors );
			$status['failed_rows'] = array_merge( $status['failed_rows'], $this->get_failed_rows() );
		}

		update_option( 'rank_math_csv_import_status', $status );
	}

	/**
	 * Set row import status.
	 *
	 * @param int $row Row index.
	 */
	private function row_failed( $row ) {
		$this->failed_rows[] = $row + 1;
	}

	/**
	 * Set row import status.
	 *
	 * @param int $row Row index.
	 */
	private function row_imported( $row ) {
		$this->imported_rows[] = $row + 1;
	}

	/**
	 * Get failed rows array.
	 *
	 * @return array
	 */
	private function get_failed_rows() {
		return $this->failed_rows;
	}

	/**
	 * Get failed rows array.
	 *
	 * @return array
	 */
	private function get_imported_rows() {
		return $this->imported_rows;
	}

	/**
	 * Get all import errors.
	 *
	 * @return mixed Array of errors or false if there is no error.
	 */
	public function get_errors() {
		return empty( $this->errors ) ? false : $this->errors;
	}

	/**
	 * Add import error.
	 *
	 * @param string $message Error message.
	 * @param int    $code    Error code.
	 */
	public function add_error( $message, $code = null ) {
		if ( is_null( $code ) ) {
			$this->errors[] = $message;
			return;
		}
		$this->errors[ $code ] = $message;
	}
}
