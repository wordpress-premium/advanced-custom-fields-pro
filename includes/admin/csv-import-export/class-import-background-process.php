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

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

/**
 * CSV Import Export class.
 *
 * @codeCoverageIgnore
 */
class Import_Background_Process extends \WP_Background_Process {

	/**
	 * Prefix.
	 *
	 * (default value: 'wp')
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'rank_math';

	/**
	 * Action.
	 *
	 * @var string
	 */
	protected $action = 'csv_import';

	/**
	 * Importer instance.
	 *
	 * @var Importer
	 */
	private $importer;

	/**
	 * Main instance.
	 *
	 * Ensure only one instance is loaded or can be loaded.
	 *
	 * @return Import_Background_Process
	 */
	public static function get() {
		static $instance;

		if ( is_null( $instance ) || ! ( $instance instanceof Import_Background_Process ) ) {
			$instance = new Import_Background_Process();
		}

		return $instance;
	}

	/**
	 * Start creating batches.
	 *
	 * @param [type] $posts [description].
	 */
	public function start( $lines_number ) {
		$chunks = array_chunk( range( 0, $lines_number ), apply_filters( 'rank_math/admin/csv_import_chunk_size', 100 ) );
		foreach ( $chunks as $chunk ) {
			$this->push_to_queue( $chunk );
		}

		Helper::add_notification(
			sprintf(
				// Translators: placeholders are opening and closing tags for link.
				__( 'CSV import in progress. You can see its progress and cancel it in the %1$sImport & Export panel%2$s.', 'rank-math-pro' ),
				'<a href="' . esc_url( Helper::get_admin_url( 'status', [ 'view' => 'import_export' ] ) ) . '">',
				'</a>'
			),
			[
				'type'    => 'success',
				'classes' => 'is-dismissible',
			]
		);

		$this->save()->dispatch();
	}

	/**
	 * Task.
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		try {
			$this->importer = new Importer();
			foreach ( $item as $row ) {
				$this->importer->import_line( $row );
			}
			$this->importer->batch_done( $item );
			return false;
		} catch ( \Exception $error ) {
			return true;
		}
	}

	/**
	 * Import complete. Clear options & add notification.
	 *
	 * @return void
	 */
	protected function complete() {
		unlink( get_option( 'rank_math_csv_import' ) );
		delete_option( 'rank_math_csv_import' );
		delete_option( 'rank_math_csv_import_total' );
		delete_option( 'rank_math_csv_import_settings' );

		$status = (array) get_option( 'rank_math_csv_import_status', [] );

		$notification_args = [
			'type'    => 'success',
			'classes' => 'is-dismissible',
		];

		if ( ! empty( $status['errors'] ) ) {
			$notification_args = [
				'type'    => 'error',
				'classes' => 'is-dismissible',
			];
		}

		Helper::add_notification(
			CSV_Import_Export::get_import_complete_message(),
			$notification_args
		);

		parent::complete();
	}

	/**
	 * Count remaining items in batch.
	 *
	 * @return int
	 */
	public function count_remaining_items() {
		if ( $this->is_queue_empty() ) {
			// This fixes an issue where get_batch() runs too early and results in a PHP notice.
			return get_option( 'rank_math_csv_import_total' );
		}
		$batch = $this->get_batch();
		$count = 0;
		if ( ! empty( $batch->data ) && is_array( $batch->data ) ) {
			foreach ( $batch->data as $items ) {
				$count += count( $items );
			}
		}

		return $count;
	}
}